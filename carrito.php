<?php

session_start();

include("config/conexion.php");
include("data/carrito_helpers.php");

/* ===== TRAER PRODUCTOS MYSQL ===== */
$sqlProductos = "SELECT * FROM productos";
$resultadoProductos = mysqli_query($conn, $sqlProductos);

$productos = [];

while($fila = mysqli_fetch_assoc($resultadoProductos)){
    $productos[] = $fila;
}

$productosPorId = [];

foreach($productos as $producto){
    $productosPorId[(int) $producto['id']] = $producto;
}

/* ===== CREAR CARRITO ===== */
if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

if(!isset($_SESSION['descuento'])){
    $_SESSION['descuento'] = 0;
}

if(!isset($_SESSION['cupon'])){
    $_SESSION['cupon'] = '';
}

if(!isset($_SESSION['cupon_tipo'])){
    $_SESSION['cupon_tipo'] = '';
}

if(!isset($_SESSION['cupon_valor'])){
    $_SESSION['cupon_valor'] = 0;
}

if(!isset($_SESSION['envio'])){
    $_SESSION['envio'] = 0;
}

if(!isset($_SESSION['cp'])){
    $_SESSION['cp'] = '';
}

if(!isset($_SESSION['zona_envio'])){
    $_SESSION['zona_envio'] = '';
}

if(!isset($_SESSION['envio_gratis_desde'])){
    $_SESSION['envio_gratis_desde'] = 0;
}

if(!isset($_SESSION['carrito_msg'])){
    $_SESSION['carrito_msg'] = '';
}

/* ===== LIMPIAR CARRITO CONTRA STOCK ACTUAL ===== */
foreach($_SESSION['carrito'] as $id => $cantidad){

    $id = (int) $id;
    $cantidad = (int) $cantidad;

    if(!isset($productosPorId[$id]) || $cantidad <= 0){
        unset($_SESSION['carrito'][$id]);
        continue;
    }

    $stock = (int) $productosPorId[$id]['stock'];

    if($stock <= 0){
        unset($_SESSION['carrito'][$id]);
        $_SESSION['carrito_msg'] = 'Quitamos productos sin stock de tu carrito.';
        continue;
    }

    if($cantidad > $stock){
        $_SESSION['carrito'][$id] = $stock;
        $_SESSION['carrito_msg'] = 'Ajustamos algunas cantidades al stock disponible.';
    }

}

/* ===== AGREGAR PRODUCTO ===== */
if(isset($_GET['agregar'])){

    $id = (int) $_GET['agregar'];

    if(!isset($productosPorId[$id])){

        $_SESSION['carrito_msg'] = 'El producto no existe o ya no está disponible.';

    } else {

        $stock = (int) $productosPorId[$id]['stock'];
        $cantidadActual = isset($_SESSION['carrito'][$id])
            ? (int) $_SESSION['carrito'][$id]
            : 0;

        if($stock <= 0){

            $_SESSION['carrito_msg'] = 'Este producto está sin stock.';

        } elseif($cantidadActual >= $stock){

            $_SESSION['carrito_msg'] = 'Ya agregaste el máximo disponible de este producto.';

        } else {

            $_SESSION['carrito'][$id] = $cantidadActual + 1;
            $_SESSION['carrito_msg'] = '';

        }

    }

    $volver = $_SERVER['HTTP_REFERER'] ?? 'carrito.php';

    if(strpos($volver, 'sportstyle') === false){
        $volver = 'carrito.php';
    }

    if(strpos($volver, 'carrito.php') === false){
        header("Location: " . $volver);
    } else {
        header("Location: carrito.php");
    }

    exit();

}

/* ===== ELIMINAR PRODUCTO ===== */
if(isset($_GET['eliminar'])){

    $id = (int) $_GET['eliminar'];

    unset($_SESSION['carrito'][$id]);
    $_SESSION['carrito_msg'] = '';

    header("Location: carrito.php");
    exit();

}

/* ===== RESTAR CANTIDAD ===== */
if(isset($_GET['restar'])){

    $id = (int) $_GET['restar'];

    if(isset($_SESSION['carrito'][$id])){

        $_SESSION['carrito'][$id]--;

        if($_SESSION['carrito'][$id] <= 0){
            unset($_SESSION['carrito'][$id]);
        }

    }

    $_SESSION['carrito_msg'] = '';

    header("Location: carrito.php");
    exit();

}

/* ===== APLICAR CUPÓN ===== */
if(isset($_POST['cupon'])){

    $codigo = $_POST['cupon'];

    $cupon = validarCuponDb($conn, $codigo);

    if($cupon !== false){

        $_SESSION['cupon'] = $cupon['codigo'];
        $_SESSION['cupon_tipo'] = $cupon['tipo'];
        $_SESSION['cupon_valor'] = (float) $cupon['valor'];
        $_SESSION['descuento'] = $cupon['tipo'] === 'porcentaje'
            ? (float) $cupon['valor']
            : 0;
        $_SESSION['cupon_error'] = '';

    } else {

        $_SESSION['descuento'] = 0;
        $_SESSION['cupon'] = '';
        $_SESSION['cupon_tipo'] = '';
        $_SESSION['cupon_valor'] = 0;
        $_SESSION['cupon_error'] = 'Cupón inválido';

    }

    header("Location: carrito.php");
    exit();

}

/* ===== QUITAR CUPÓN ===== */
if(isset($_GET['quitar_cupon'])){

    $_SESSION['descuento'] = 0;
    $_SESSION['cupon'] = '';
    $_SESSION['cupon_tipo'] = '';
    $_SESSION['cupon_valor'] = 0;
    $_SESSION['cupon_error'] = '';

    header("Location: carrito.php");
    exit();

}

$carrito = $_SESSION['carrito'];
$descuento = $_SESSION['descuento'];
$cuponTipo = $_SESSION['cupon_tipo'] ?? '';
$cuponValor = (float) ($_SESSION['cupon_valor'] ?? 0);

$totalSinDescuento = calcularTotal($carrito, $productos, 0);
$total = calcularTotal($carrito, $productos, $descuento);

if($cuponTipo === 'fijo' && $cuponValor > 0){
    $total = max(0, $totalSinDescuento - $cuponValor);
}

/* ===== CALCULAR ENVÍO ===== */
if(isset($_POST['calcular_envio'])){

    $cp = (int) trim($_POST['codigo_postal']);

    $_SESSION['cp'] = $cp;

    if($cp <= 0){

        $_SESSION['envio'] = 0;
        $_SESSION['zona_envio'] = 'No disponible';
        $_SESSION['envio_gratis_desde'] = 0;

    } else {

        $sqlEnvio = "SELECT *
                     FROM zonas_envio
                     WHERE ? BETWEEN cp_desde AND cp_hasta
                     LIMIT 1";

        $stmtEnvio = mysqli_prepare($conn, $sqlEnvio);

        if($stmtEnvio){

            mysqli_stmt_bind_param(
                $stmtEnvio,
                "i",
                $cp
            );

            mysqli_stmt_execute($stmtEnvio);

            $resultadoEnvio = mysqli_stmt_get_result($stmtEnvio);

            if(mysqli_num_rows($resultadoEnvio) > 0){

                $envio = mysqli_fetch_assoc($resultadoEnvio);

                $envioGratisDesde = (float) ($envio['envio_gratis_desde'] ?? 0);

                $_SESSION['zona_envio'] = $envio['nombre'];
                $_SESSION['envio_gratis_desde'] = $envioGratisDesde;

                if($envioGratisDesde > 0 && $total >= $envioGratisDesde){

                    $_SESSION['envio'] = 0;

                } else {

                    $_SESSION['envio'] = $envio['costo'];

                }

                if($_SESSION['cupon_tipo'] === 'envio'){
                    $_SESSION['envio'] = 0;
                }

            } else {

                $_SESSION['envio'] = 0;
                $_SESSION['zona_envio'] = 'No disponible';
                $_SESSION['envio_gratis_desde'] = 0;

            }

        } else {

            $_SESSION['envio'] = 0;
            $_SESSION['zona_envio'] = 'No configurado';
            $_SESSION['envio_gratis_desde'] = 0;
            $_SESSION['carrito_msg'] = 'La tabla zonas_envio no está configurada.';

        }

    }

    header("Location: carrito.php");
    exit();

}

$costoEnvio = $_SESSION['envio'];
$totalFinal = $total + $costoEnvio;
$carritoMsg = $_SESSION['carrito_msg'] ?? '';
$_SESSION['carrito_msg'] = '';
$envioGratisDesde = (float) ($_SESSION['envio_gratis_desde'] ?? 0);
$envioListo = (
    !empty($_SESSION['cp'])
    &&
    !empty($_SESSION['zona_envio'])
    &&
    $_SESSION['zona_envio'] !== 'No disponible'
    &&
    $_SESSION['zona_envio'] !== 'No configurado'
);

?>

<?php include("includes/header.php"); ?>

<h1 class="titulo">
    Carrito
</h1>

<div class="carrito-topbar">

    <a href="productos.php"
       class="seguir-comprando">

       ← Seguir comprando

    </a>

</div>

<?php if(!empty($carritoMsg)): ?>

    <div class="carrito-alerta">
        <?= htmlspecialchars($carritoMsg) ?>
    </div>

<?php endif; ?>

<div class="carrito-container">

    <!-- ===== PRODUCTOS ===== -->
    <div class="carrito-items">

        <?php if(count($carrito) > 0): ?>

            <?php foreach($carrito as $id => $cantidad): ?>

                <?php foreach($productos as $p): ?>

                    <?php if($p['id'] == $id): ?>

                        <div class="carrito-card premium-cart">

                            <img src="<?= $p['imagen'] ?>"
                                 alt="<?= $p['nombre'] ?>">

                            <div class="carrito-info">

                                <h3>
                                    <?= $p['nombre'] ?>
                                </h3>

                                <div class="mini-tags-cart">

                                    <?php if(!empty($p['genero'])): ?>

                                        <span>
                                            <?= $p['genero'] ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <p class="precio-unit">
                                    $<?= number_format($p['precio'], 0, ',', '.') ?> c/u
                                </p>

                                <p class="stock-cart">
                                    Stock disponible: <?= (int) $p['stock'] ?>
                                </p>

                                <p class="precio-subtotal">
                                    Subtotal:
                                    $<?= number_format($p['precio'] * $cantidad, 0, ',', '.') ?>
                                </p>

                            </div>

                            <div class="carrito-cantidad">

                                <a href="carrito.php?restar=<?= $p['id'] ?>"
                                   class="btn-cantidad">

                                   −

                                </a>

                                <span>
                                    <?= $cantidad ?>
                                </span>

                                <?php if($cantidad < (int) $p['stock']): ?>

                                    <a href="carrito.php?agregar=<?= $p['id'] ?>"
                                       class="btn-cantidad">

                                       +

                                    </a>

                                <?php else: ?>

                                    <span class="btn-cantidad btn-cantidad-disabled"
                                          title="No hay más stock disponible">

                                        +

                                    </span>

                                <?php endif; ?>

                            </div>

                            <a href="carrito.php?eliminar=<?= $p['id'] ?>"
                               class="btn-eliminar">

                               ❌

                            </a>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="carrito-vacio carrito-vacio-pro">

                <span class="carrito-vacio-icono">
                    🛒
                </span>

                <h2>
                    Tu carrito está vacío
                </h2>

                <p>
                    Explorá la colección y agregá productos para continuar con tu compra.
                </p>

                <a href="productos.php"
                   class="btn-pagar">

                    Ver productos

                </a>

            </div>

        <?php endif; ?>

    </div>

    <!-- ===== RESUMEN ===== -->
    <?php if(count($carrito) > 0): ?>

        <div class="carrito-resumen">

            <h2>
                Resumen
            </h2>

            <!-- CUPÓN -->
            <div class="cupon-box">

                <?php if($_SESSION['cupon']): ?>

                    <p class="cupon-activo">

                        ✅ Cupón
                        <strong><?= $_SESSION['cupon'] ?></strong>
                        aplicado

                        <?php if($cuponTipo === 'porcentaje'): ?>
                            (<?= number_format($cuponValor, 0, ',', '.') ?>% OFF)
                        <?php elseif($cuponTipo === 'fijo'): ?>
                            (-$<?= number_format($cuponValor, 0, ',', '.') ?>)
                        <?php elseif($cuponTipo === 'envio'): ?>
                            (envío gratis)
                        <?php endif; ?>

                        <a href="carrito.php?quitar_cupon=1">
                            ✖ Quitar
                        </a>

                    </p>

                <?php else: ?>

                    <form method="POST"
                          action="carrito.php">

                        <input type="text"
                               name="cupon"
                               placeholder="Código de cupón"
                               class="input-cupon">

                        <button type="submit"
                                class="btn">

                            Aplicar

                        </button>

                    </form>

                    <?php if(!empty($_SESSION['cupon_error'])): ?>

                        <p class="cupon-error">
                            ❌ <?= $_SESSION['cupon_error'] ?>
                        </p>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <!-- ENVÍO -->
            <div class="envio-box">

                <h4>
                    🚚 Calcular envío
                </h4>

                <form method="POST"
                      action="carrito.php">

                    <input type="text"
                           name="codigo_postal"
                           placeholder="Código postal"
                           class="input-cupon"
                           value="<?= $_SESSION['cp'] ?>">

                    <button type="submit"
                            name="calcular_envio"
                            class="btn-calcular-envio">

                        Calcular

                    </button>

                </form>

                <?php if($_SESSION['zona_envio'] === 'No disponible'): ?>

                    <p class="envio-msg">
                        ❌ No encontramos envíos para ese código postal.
                    </p>

                <?php elseif($_SESSION['zona_envio'] === 'No configurado'): ?>

                    <p class="envio-msg">
                        ❌ El cálculo de envíos todavía no está configurado.
                    </p>

                <?php elseif(!empty($_SESSION['cp'])): ?>

                    <p class="envio-msg">

                        Zona:
                        <strong>
                            <?= $_SESSION['zona_envio'] ?>
                        </strong>

                    </p>

                    <p class="envio-msg">

                        🚚 Envío:
                        <strong>
                            $<?= number_format($costoEnvio, 0, ',', '.') ?>
                        </strong>

                    </p>

                <?php else: ?>

                    <p class="envio-msg">
                        Envíos a todo el país 🇦🇷
                    </p>

                <?php endif; ?>

            </div>

            <!-- TOTALES -->
            <?php if($totalSinDescuento > $total): ?>

                <p class="total-sin-descuento">
                    Subtotal:
                    $<?= number_format($totalSinDescuento, 0, ',', '.') ?>
                </p>

                <p class="descuento-aplicado">
                    Descuento:
                    -$<?= number_format($totalSinDescuento - $total, 0, ',', '.') ?>
                </p>

            <?php endif; ?>

            <h3 class="total-final">
                Total:
                $<?= number_format($totalFinal, 0, ',', '.') ?>
            </h3>

           <?php if($envioListo && $envioGratisDesde > 0 && $total >= $envioGratisDesde): ?>

    <p class="envio-gratis">
        🚚 Envío gratis aplicado
    </p>

<?php elseif($envioListo && $envioGratisDesde > 0): ?>

    <p class="faltante-envio">

        Sumá

        <strong>
            $<?= number_format(max(0, $envioGratisDesde - $total), 0, ',', '.') ?>
        </strong>

        más y obtené envío gratis

    </p>

<?php elseif(!$envioListo): ?>

    <p class="faltante-envio">
        Calculá el envío para ver costos y beneficios por tu zona.
    </p>

<?php endif; ?>

            <a href="checkout.php"
               class="btn-pagar <?= $envioListo ? '' : 'btn-pagar-disabled' ?>"
               <?= $envioListo ? '' : 'aria-disabled="true" onclick="return false;"' ?>>

                Finalizar compra

            </a>

            <?php if(!$envioListo): ?>

                <p class="checkout-bloqueado">
                    Calculá el envío para continuar con la compra.
                </p>

            <?php endif; ?>

            <div class="beneficios-cart">

                <div class="beneficio-item">
                    🔒 Compra 100% segura
                </div>

                <div class="beneficio-item">
                    🚚 Envíos a todo el país
                </div>

                <div class="beneficio-item">
                    💳 Hasta 6 cuotas sin interés
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php include("includes/footer.php"); ?>
