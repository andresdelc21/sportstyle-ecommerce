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

if(!isset($_SESSION['envio'])){
    $_SESSION['envio'] = 0;
}

if(!isset($_SESSION['cp'])){
    $_SESSION['cp'] = '';
}

if(!isset($_SESSION['zona_envio'])){
    $_SESSION['zona_envio'] = '';
}

/* ===== AGREGAR PRODUCTO ===== */
if(isset($_GET['agregar'])){

    $id = $_GET['agregar'];

    if(isset($_SESSION['carrito'][$id])){
        $_SESSION['carrito'][$id]++;
    } else {
        $_SESSION['carrito'][$id] = 1;
    }

    header("Location: carrito.php");
    exit();

}

/* ===== ELIMINAR PRODUCTO ===== */
if(isset($_GET['eliminar'])){

    $id = $_GET['eliminar'];

    unset($_SESSION['carrito'][$id]);

    header("Location: carrito.php");
    exit();

}

/* ===== RESTAR CANTIDAD ===== */
if(isset($_GET['restar'])){

    $id = $_GET['restar'];

    if(isset($_SESSION['carrito'][$id])){

        $_SESSION['carrito'][$id]--;

        if($_SESSION['carrito'][$id] <= 0){
            unset($_SESSION['carrito'][$id]);
        }

    }

    header("Location: carrito.php");
    exit();

}

/* ===== APLICAR CUPÓN ===== */
if(isset($_POST['cupon'])){

    $codigo = $_POST['cupon'];

    $descuento = validarCupon($codigo);

    if($descuento !== false){

        $_SESSION['descuento'] = $descuento;
        $_SESSION['cupon'] = strtoupper(trim($codigo));
        $_SESSION['cupon_error'] = '';

    } else {

        $_SESSION['descuento'] = 0;
        $_SESSION['cupon'] = '';
        $_SESSION['cupon_error'] = 'Cupón inválido';

    }

    header("Location: carrito.php");
    exit();

}

/* ===== QUITAR CUPÓN ===== */
if(isset($_GET['quitar_cupon'])){

    $_SESSION['descuento'] = 0;
    $_SESSION['cupon'] = '';
    $_SESSION['cupon_error'] = '';

    header("Location: carrito.php");
    exit();

}

$carrito = $_SESSION['carrito'];
$descuento = $_SESSION['descuento'];

$totalSinDescuento = calcularTotal($carrito, $productos, 0);
$total = calcularTotal($carrito, $productos, $descuento);

/* ===== CALCULAR ENVÍO ===== */
if(isset($_POST['calcular_envio'])){

    $cp = (int) trim($_POST['codigo_postal']);

    $_SESSION['cp'] = $cp;

    if($total >= 100000){

        $_SESSION['envio'] = 0;
        $_SESSION['zona_envio'] = 'Envío gratis';

    } else {

        $sqlEnvio = "SELECT *
                     FROM envios
                     WHERE ? BETWEEN cp_desde AND cp_hasta
                     AND activo = 1
                     LIMIT 1";

        $stmtEnvio = mysqli_prepare($conn, $sqlEnvio);

        mysqli_stmt_bind_param(
            $stmtEnvio,
            "i",
            $cp
        );

        mysqli_stmt_execute($stmtEnvio);

        $resultadoEnvio = mysqli_stmt_get_result($stmtEnvio);

        if(mysqli_num_rows($resultadoEnvio) > 0){

            $envio = mysqli_fetch_assoc($resultadoEnvio);

            $_SESSION['envio'] = $envio['costo'];
            $_SESSION['zona_envio'] = $envio['zona'];

        } else {

            $_SESSION['envio'] = 0;
            $_SESSION['zona_envio'] = 'No disponible';

        }

    }

    header("Location: carrito.php");
    exit();

}

$costoEnvio = $_SESSION['envio'];
$totalFinal = $total + $costoEnvio;

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

                                <a href="carrito.php?agregar=<?= $p['id'] ?>"
                                   class="btn-cantidad">

                                   +

                                </a>

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

            <p class="carrito-vacio">

                Tu carrito está vacío.

                <a href="productos.php">
                    Ver productos
                </a>

            </p>

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
                        aplicado (<?= $descuento ?>% OFF)

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
            <?php if($descuento > 0): ?>

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

           <?php if($total >= 100000): ?>

    <p class="envio-gratis">
        🚚 Envío gratis aplicado
    </p>

<?php else: ?>

    <p class="faltante-envio">

        Sumá

        <strong>
            $<?= number_format(100000 - $total, 0, ',', '.') ?>
        </strong>

        más y obtené envío gratis

    </p>

<?php endif; ?>

            <a href="checkout.php"
               class="btn-pagar">

                Finalizar compra

            </a>

            <a href="pago.php"
               class="btn btn-pagar">

               💳 Pagar con MercadoPago

            </a>

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