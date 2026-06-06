<?php

session_start();

include("config/conexion.php");
include("data/carrito_helpers.php");
include("includes/csrf.php");

function limpiarCompraActual(): void {
    unset($_SESSION['carrito']);
    $_SESSION['descuento'] = 0;
    $_SESSION['cupon'] = '';
    $_SESSION['cupon_tipo'] = '';
    $_SESSION['cupon_valor'] = 0;
    $_SESSION['cupon_error'] = '';
    $_SESSION['envio'] = 0;
    $_SESSION['cp'] = '';
    $_SESSION['zona_envio'] = '';
    $_SESSION['envio_gratis_desde'] = 0;
    $_SESSION['carrito_msg'] = '';
}

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

$tallesPorId = [];
$resultadoTallesCarrito = mysqli_query($conn, "SELECT * FROM producto_talles");

if($resultadoTallesCarrito){
    while($talleCarrito = mysqli_fetch_assoc($resultadoTallesCarrito)){
        $tallesPorId[(int) $talleCarrito['id']] = $talleCarrito;
    }
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

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_compra'])){
    if(validarCsrf()){
        limpiarCompraActual();
    }

    header("Location: index.php");
    exit;
}

/* ===== LIMPIAR CARRITO CONTRA STOCK ACTUAL ===== */
foreach($_SESSION['carrito'] as $key => $cantidad){

    $id = carritoProductoId($key);
    $talleId = carritoTalleId($key);
    $cantidad = (int) $cantidad;

    if(!isset($productosPorId[$id]) || $cantidad <= 0){
        unset($_SESSION['carrito'][$id]);
        continue;
    }

    $stock = $talleId && isset($tallesPorId[$talleId])
        ? (int) $tallesPorId[$talleId]['stock']
        : (int) $productosPorId[$id]['stock'];

    if($stock <= 0){
        unset($_SESSION['carrito'][$key]);
        $_SESSION['carrito_msg'] = 'Quitamos productos sin stock de tu carrito.';
        continue;
    }

    if($cantidad > $stock){
        $_SESSION['carrito'][$key] = $stock;
        $_SESSION['carrito_msg'] = 'Ajustamos algunas cantidades al stock disponible.';
    }

}

/* ===== AGREGAR PRODUCTO ===== */
if(isset($_GET['agregar'])){

    $id = (int) $_GET['agregar'];
    $talleId = isset($_GET['talle']) ? (int) $_GET['talle'] : 0;

    if(!isset($productosPorId[$id])){

        $_SESSION['carrito_msg'] = 'El producto no existe o ya no está disponible.';

    } else {

        if($talleId <= 0){
            $tallesProducto = array_filter($tallesPorId, fn($t) => (int) $t['producto_id'] === $id);
            if(count($tallesProducto) === 1){
                $talleTmp = reset($tallesProducto);
                $talleId = (int) $talleTmp['id'];
            }
        }

        $key = carritoKey($id, $talleId > 0 ? $talleId : null);
        $stock = $talleId > 0 && isset($tallesPorId[$talleId])
            ? (int) $tallesPorId[$talleId]['stock']
            : (int) $productosPorId[$id]['stock'];
        $cantidadActual = isset($_SESSION['carrito'][$key])
            ? (int) $_SESSION['carrito'][$key]
            : 0;

        if($stock <= 0){

            $_SESSION['carrito_msg'] = 'Este producto está sin stock.';

        } elseif($cantidadActual >= $stock){

            $_SESSION['carrito_msg'] = 'Ya agregaste el máximo disponible de este producto.';

        } else {

            $_SESSION['carrito'][$key] = $cantidadActual + 1;
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

    $key = $_GET['eliminar'];

    unset($_SESSION['carrito'][$key]);
    $_SESSION['carrito_msg'] = '';

    header("Location: carrito.php");
    exit();

}

/* ===== RESTAR CANTIDAD ===== */
if(isset($_GET['restar'])){

    $key = $_GET['restar'];

    if(isset($_SESSION['carrito'][$key])){

        $_SESSION['carrito'][$key]--;

        if($_SESSION['carrito'][$key] <= 0){
            unset($_SESSION['carrito'][$key]);
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

$cantidadItemsCarrito = array_sum(array_map('intval', $carrito));

?>

<?php include("includes/header.php"); ?>

<section class="productos-header carrito-header">

    <div class="carrito-header-inner">

        <div>

            <h1>
                Tu carrito
                <span class="carrito-count-header">
                    (<?= (int) $cantidadItemsCarrito ?> <?= $cantidadItemsCarrito === 1 ? 'producto' : 'productos' ?>)
                </span>
            </h1>

            <p>
                Revisá tus artículos antes de finalizar la compra.
            </p>

        </div>

            <a href="productos.php"
           class="seguir-comprando carrito-header-link">

           Seguir comprando →

        </a>

        <?php if(count($carrito) > 0): ?>

            <form method="POST"
                  action="carrito.php"
                  class="cancelar-compra-form">

                <?= csrfInput() ?>

                <button type="submit"
                        name="cancelar_compra"
                        class="cancelar-compra-btn"
                        onclick="return confirm('¿Cancelar la compra y vaciar el carrito?')">

                    Cancelar compra

                </button>

            </form>

        <?php endif; ?>

    </div>

</section>

<?php if(!empty($carritoMsg)): ?>

    <div class="carrito-alerta">
        <?= htmlspecialchars($carritoMsg) ?>
    </div>

<?php endif; ?>

<div class="carrito-container <?= count($carrito) === 0 ? 'carrito-container-vacio' : '' ?>">

    <!-- ===== PRODUCTOS ===== -->
    <div class="carrito-items">

        <?php if(count($carrito) > 0): ?>

            <?php foreach($carrito as $key => $cantidad): ?>

                <?php $id = carritoProductoId($key); $talleId = carritoTalleId($key); ?>

                <?php foreach($productos as $p): ?>

                    <?php if($p['id'] == $id): ?>

                        <div class="carrito-card premium-cart">

                            <a href="detalle.php?id=<?= $p['id'] ?>"
                               class="carrito-producto-img">

                                <img src="<?= $p['imagen'] ?>"
                                     alt="<?= $p['nombre'] ?>">

                            </a>

                            <div class="carrito-info">

                                <a href="detalle.php?id=<?= $p['id'] ?>"
                                   class="carrito-producto-nombre">

                                    <?= $p['nombre'] ?>

                                </a>

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

                                <?php if($talleId && isset($tallesPorId[$talleId])): ?>

                                    <p class="stock-cart">
                                        Talle: <?= htmlspecialchars(talleLabel($tallesPorId[$talleId])) ?>
                                    </p>

                                <?php endif; ?>

                                <p class="stock-cart">
                                    Stock disponible: <?= $talleId && isset($tallesPorId[$talleId]) ? (int) $tallesPorId[$talleId]['stock'] : (int) $p['stock'] ?>
                                </p>

                            </div>

                            <div class="carrito-item-control">

                                <span class="carrito-control-label">
                                    Cantidad
                                </span>

                                <div class="carrito-cantidad">

                                    <a href="carrito.php?restar=<?= urlencode($key) ?>"
                                       class="btn-cantidad">

                                       −

                                    </a>

                                    <span>
                                        <?= $cantidad ?>
                                    </span>

                                    <?php $stockItem = $talleId && isset($tallesPorId[$talleId]) ? (int) $tallesPorId[$talleId]['stock'] : (int) $p['stock']; ?>

                                    <?php if($cantidad < $stockItem): ?>

                                        <a href="carrito.php?agregar=<?= $p['id'] ?><?= $talleId ? '&talle=' . $talleId : '' ?>"
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

                            </div>

                            <div class="carrito-item-total">

                                <span>
                                    Subtotal
                                </span>

                                <strong>
                                    $<?= number_format($p['precio'] * $cantidad, 0, ',', '.') ?>
                                </strong>

                                <a href="carrito.php?eliminar=<?= urlencode($key) ?>"
                                   class="btn-eliminar">

                                   Quitar

                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="carrito-vacio carrito-vacio-pro">

                <h2>
                    El carrito está vacío
                </h2>

                <p>
                    Agregá productos desde el catálogo para comenzar tu pedido.
                </p>

                <div class="carrito-vacio-acciones">

                    <a href="productos.php"
                       class="btn-pagar">

                        Empezar compra →

                    </a>

                    <a href="index.php"
                       class="btn-secundario-checkout">

                        Volver al inicio →

                    </a>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <!-- ===== RESUMEN ===== -->
    <?php if(count($carrito) > 0): ?>

        <div class="carrito-resumen">

            <h2>
                Resumen de compra
            </h2>

            <!-- CUPÓN -->
            <div class="cupon-box">

                <?php if($_SESSION['cupon']): ?>

                    <p class="cupon-activo">

                        Cupón
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
                            Quitar
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
                            <?= $_SESSION['cupon_error'] ?>
                        </p>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <!-- ENVÍO -->
            <div class="envio-box">

                <h4>
                    Calcular envío
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
                        No encontramos envíos para ese código postal.
                    </p>

                <?php elseif($_SESSION['zona_envio'] === 'No configurado'): ?>

                    <p class="envio-msg">
                        El cálculo de envíos todavía no está configurado.
                    </p>

                <?php elseif(!empty($_SESSION['cp'])): ?>

                    <p class="envio-msg">

                        Zona:
                        <strong>
                            <?= $_SESSION['zona_envio'] ?>
                        </strong>

                    </p>

                    <p class="envio-msg">

                        Envío:
                        <strong>
                            $<?= number_format($costoEnvio, 0, ',', '.') ?>
                        </strong>

                    </p>

                <?php else: ?>

                    <p class="envio-msg">
                        Ingresá tu código postal para ver el costo.
                    </p>

                <?php endif; ?>

            </div>

            <!-- TOTALES -->
            <div class="resumen-lineas">

                <div class="resumen-linea">
                    <span>Subtotal</span>
                    <strong>$<?= number_format($totalSinDescuento, 0, ',', '.') ?></strong>
                </div>

            <?php if($totalSinDescuento > $total): ?>

                <div class="resumen-linea descuento-aplicado">
                    <span>Descuento</span>
                    <strong>-$<?= number_format($totalSinDescuento - $total, 0, ',', '.') ?></strong>
                </div>

            <?php endif; ?>

                <div class="resumen-linea">
                    <span>Envío</span>
                    <strong><?= $envioListo ? '$' . number_format($costoEnvio, 0, ',', '.') : 'A calcular' ?></strong>
                </div>

                <div class="resumen-linea resumen-total">
                    <span>Total</span>
                    <strong>$<?= number_format($totalFinal, 0, ',', '.') ?></strong>
                </div>

            </div>

           <?php if($envioListo && $envioGratisDesde > 0 && $total >= $envioGratisDesde): ?>

    <p class="envio-gratis">
        Envío gratis aplicado
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

            <form method="POST"
                  action="carrito.php"
                  class="cancelar-compra-resumen">

                <?= csrfInput() ?>

                <button type="submit"
                        name="cancelar_compra"
                        class="cancelar-compra-link"
                        onclick="return confirm('¿Cancelar la compra y vaciar el carrito?')">

                    Cancelar compra

                </button>

            </form>

            <div class="beneficios-cart">

                <div class="beneficio-item">
                    Compra segura y pedido registrado
                </div>

                <div class="beneficio-item">
                    Envíos según zona configurada
                </div>

                <div class="beneficio-item">
                    Opciones de pago al finalizar
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php include("includes/footer.php"); ?>
