<?php

session_start();

include("config/conexion.php");
include("config/config.php");
include("includes/funciones.php");
include("includes/pedidos.php");
include("includes/csrf.php");

function limpiarCompraCheckout(): void {
    unset($_SESSION['carrito']);
    unset($_SESSION['cliente']);
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

/* PROTEGER CHECKOUT */
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

/* TRAER PRODUCTOS */
$sqlProductos = "SELECT * FROM productos";
$resultadoProductos = mysqli_query($conn, $sqlProductos);

$productos = [];

while($fila = mysqli_fetch_assoc($resultadoProductos)){
    $productos[] = $fila;
}

$tallesPorId = [];
$resultadoTallesCheckout = mysqli_query($conn, "SELECT * FROM producto_talles");

if($resultadoTallesCheckout){
    while($talleCheckout = mysqli_fetch_assoc($resultadoTallesCheckout)){
        $tallesPorId[(int) $talleCheckout['id']] = $talleCheckout;
    }
}

/* EVITAR ACCESO VACÍO */
if(
    (!isset($_SESSION['carrito']) || count($_SESSION['carrito']) == 0)
    &&
    !isset($_GET['success'])
    &&
    !isset($_GET['transferencia'])
){
    header("Location: index.php");
    exit;
}

/* DATOS DE ENVÍO DESDE CARRITO */
$costoEnvio = $_SESSION['envio'] ?? 0;
$codigoPostal = $_SESSION['cp'] ?? '';
$zonaEnvio = $_SESSION['zona_envio'] ?? '';
$envioGratisDesde = (float) ($_SESSION['envio_gratis_desde'] ?? 0);

$subtotalCarritoActual = calcularTotalCarrito(
    $_SESSION['carrito'] ?? [],
    $productos
);

if($envioGratisDesde > 0 && $subtotalCarritoActual >= $envioGratisDesde){

    $costoEnvio = 0;
    $_SESSION['envio'] = 0;

}

if(
    !isset($_GET['success'])
    &&
    !isset($_GET['transferencia'])
    &&
    (
        empty($codigoPostal)
        ||
        empty($zonaEnvio)
        ||
        $zonaEnvio === 'No disponible'
        ||
        $zonaEnvio === 'No configurado'
    )
){

    $_SESSION['carrito_msg'] = 'Calculá el envío antes de finalizar la compra.';
    header("Location: carrito.php");
    exit;

}

$erroresCheckout = [];
$datosCheckout = [
    "nombre" => $_SESSION['usuario_nombre'] ?? '',
    "telefono" => '',
    "direccion" => '',
    "pago" => ''
];
$pedidoConfirmadoId = $_SESSION['ultimo_pedido_id'] ?? null;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_compra'])){
    if(validarCsrf()){
        limpiarCompraCheckout();
    }

    header("Location: index.php");
    exit;
}

/* PROCESAR CHECKOUT */
if(isset($_POST['confirmar'])){

    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $pago = trim($_POST['pago']);

    $datosCheckout = [
        "nombre" => $nombre,
        "telefono" => $telefono,
        "direccion" => $direccion,
        "pago" => $pago
    ];

    if(strlen($nombre) < 3){
        $erroresCheckout[] = 'Ingresá un nombre completo válido.';
    }

    if(strlen(preg_replace('/[^0-9]/', '', $telefono)) < 8){
        $erroresCheckout[] = 'Ingresá un teléfono válido.';
    }

    if(strlen($direccion) < 5){
        $erroresCheckout[] = 'Ingresá una dirección de envío válida.';
    }

    $metodosPermitidos = ['mp', 'transferencia', 'whatsapp'];

    if(!in_array($pago, $metodosPermitidos, true)){
        $erroresCheckout[] = 'Seleccioná un método de pago.';
    }

    if($pago === 'mp' && empty($MP_ACCESS_TOKEN)){
        $erroresCheckout[] = 'Mercado Pago todavía no está configurado. Elegí otro método de pago o cargá las credenciales desde el admin.';
    }

    $_SESSION['cliente'] = [
        "nombre" => $nombre,
        "telefono" => $telefono,
        "direccion" => $direccion,
        "pago" => $pago,
        "codigo_postal" => $codigoPostal,
        "zona_envio" => $zonaEnvio,
        "costo_envio" => $costoEnvio
    ];

    /* VALIDAR STOCK */
    foreach($_SESSION['carrito'] as $key => $cantidad){

        $id = carritoProductoId($key);
        $talleId = carritoTalleId($key);

        $producto = obtenerProductoPorId($productos, $id);

        if(!$producto){
            $erroresCheckout[] = 'Uno de los productos ya no está disponible.';
            continue;
        }

        $stockDisponible = $talleId && isset($tallesPorId[$talleId])
            ? (int) $tallesPorId[$talleId]['stock']
            : (int) $producto['stock'];

        if($stockDisponible < $cantidad){
            $erroresCheckout[] = 'No hay stock suficiente para: ' . $producto['nombre'];
        }

    }

    if(empty($erroresCheckout)){

    /* CALCULAR TOTAL PRODUCTOS */
    $subtotalProductos = calcularTotalCarrito(
        $_SESSION['carrito'],
        $productos
    );

    /* TOTAL FINAL = PRODUCTOS + ENVÍO */
    $totalPedido = $subtotalProductos + $costoEnvio;

    /* CREAR PEDIDO */
    $usuario_id = $_SESSION['usuario_id'];

    $pedido_id = crearPedido(
        $conn,
        $usuario_id,
        $nombre,
        $telefono,
        $direccion,
        $subtotalProductos,
        $costoEnvio,
        $codigoPostal,
        $zonaEnvio,
        $pago,
        $totalPedido
    );

    $_SESSION['ultimo_pedido_id'] = $pedido_id;

    /* GUARDAR DETALLES */
    foreach($_SESSION['carrito'] as $key => $cantidad){

        $id = carritoProductoId($key);
        $talleId = carritoTalleId($key);

        $producto = obtenerProductoPorId(
            $productos,
            $id
        );

        if($producto){

            guardarDetallePedido(
                $conn,
                $pedido_id,
                $id,
                $cantidad,
                $producto['precio'],
                $talleId,
                $talleId && isset($tallesPorId[$talleId]) ? talleLabel($tallesPorId[$talleId]) : ''
            );

            descontarStock(
                $conn,
                $id,
                $cantidad,
                $talleId
            );

        }

    }

    /* WHATSAPP */
    if($pago == "whatsapp"){

        $mensaje = generarMensajeWhatsApp(
            $_SESSION['carrito'],
            $productos
        );

        $mensaje .= "%0A";
        $mensaje .= "Envío: $".number_format($costoEnvio, 0, ',', '.')."%0A";
        $mensaje .= "Total: $".number_format($totalPedido, 0, ',', '.')."%0A";

        unset($_SESSION['carrito']);

        header("Location: https://wa.me/".$WHATSAPP_TIENDA."?text=".$mensaje);
        exit;

    }

    /* TRANSFERENCIA */
    if($pago == "transferencia"){

        unset($_SESSION['carrito']);

        header("Location: checkout.php?transferencia=1");
        exit;

    }

    /* MERCADOPAGO */
    if($pago == "mp"){

        $_SESSION['mp_pedido_id'] = $pedido_id;

        header("Location: pago.php?pedido=" . $pedido_id);
        exit;

    }

    }

}

$subtotalVista = 0;

?>

<?php include("includes/header.php"); ?>

<section class="checkout-header">

    <span class="productos-badge">
        Checkout
    </span>

    <h1>
        Finalizar compra
    </h1>

    <p>
        Completá tus datos, revisá el pedido y elegí cómo querés pagar.
    </p>

</section>

<?php if(isset($_GET['success'])): ?>

    <section class="checkout-resultado">

        <span class="checkout-icono">
            ✓
        </span>

        <h2>
            Pedido confirmado
        </h2>

        <?php if($pedidoConfirmadoId): ?>

            <p class="checkout-numero">
                Pedido #<?= (int) $pedidoConfirmadoId ?>
            </p>

        <?php endif; ?>

        <p>
            Registramos tu compra correctamente. Desde acá podés seguir el estado de tu pedido.
        </p>

        <div class="checkout-acciones">

            <?php if($pedidoConfirmadoId): ?>

                <a href="pedido_detalle.php?id=<?= (int) $pedidoConfirmadoId ?>"
                   class="btn-pagar">

                    Seguir mi pedido

                </a>

            <?php endif; ?>

            <a href="productos.php"
               class="btn-secundario-checkout">

                Seguir comprando

            </a>

        </div>

    </section>

<?php elseif(isset($_GET['transferencia'])): ?>

    <section class="checkout-resultado">

        <span class="checkout-icono">
            $
        </span>

        <h2>
            Pedido registrado
        </h2>

        <?php if($pedidoConfirmadoId): ?>

            <p class="checkout-numero">
                Pedido #<?= (int) $pedidoConfirmadoId ?>
            </p>

        <?php endif; ?>

        <p>
            Realizá la transferencia y enviá el comprobante para confirmar el pago.
        </p>

        <div class="transferencia-box">

            <p><strong>Alias:</strong> <?= htmlspecialchars($ALIAS_TIENDA) ?></p>
            <p><strong>CBU/CVU:</strong> <?= htmlspecialchars($CBU_TIENDA) ?></p>
            <p><strong>Titular:</strong> <?= htmlspecialchars($TITULAR_TIENDA) ?></p>

        </div>

        <a href="https://wa.me/<?= $WHATSAPP_TIENDA ?>"
           target="_blank"
           class="btn-pagar">

            Enviar comprobante

        </a>

        <div class="checkout-acciones">

            <a href="index.php"
               class="btn-secundario-checkout">

                Volver al inicio

            </a>

            <?php if($pedidoConfirmadoId): ?>

                <a href="pedido_detalle.php?id=<?= (int) $pedidoConfirmadoId ?>"
                   class="btn-secundario-checkout">

                    Seguir mi pedido

                </a>

            <?php endif; ?>

            <a href="productos.php"
               class="btn-secundario-checkout">

                Seguir comprando

            </a>

        </div>

    </section>

<?php endif; ?>

<?php if(!isset($_GET['success']) && !isset($_GET['transferencia'])): ?>

<?php if(!empty($erroresCheckout)): ?>

    <div class="checkout-alerta">

        <?php foreach($erroresCheckout as $error): ?>

            <p>
                <?= htmlspecialchars($error) ?>
            </p>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<div class="carrito-container checkout-container">

    <!-- FORMULARIO -->
    <form method="POST"
          action="checkout.php"
          class="checkout-form">

        <div class="checkout-panel-title">

            <span>1</span>

            <div>

                <h2>
                    Datos del cliente
                </h2>

                <p>
                    Usamos estos datos para preparar y enviar tu pedido.
                </p>

            </div>

        </div>

        <label class="checkout-field">
            <span>Nombre completo</span>
            <input type="text"
                   name="nombre"
                   placeholder="Nombre y apellido"
                   value="<?= htmlspecialchars($datosCheckout['nombre']) ?>"
                   required
                   class="input-cupon">
        </label>

        <label class="checkout-field">
            <span>Teléfono</span>
            <input type="text"
                   name="telefono"
                   placeholder="Código de área + número"
                   value="<?= htmlspecialchars($datosCheckout['telefono']) ?>"
                   required
                   class="input-cupon">
        </label>

        <label class="checkout-field">
            <span>Dirección de envío</span>
            <input type="text"
                   name="direccion"
                   placeholder="Calle, número, piso/depto"
                   value="<?= htmlspecialchars($datosCheckout['direccion']) ?>"
                   required
                   class="input-cupon">
        </label>

        <div class="checkout-field-grid">
            <label class="checkout-field">
                <span>Código postal</span>
                <input type="text"
                       value="<?= $codigoPostal ?>"
                       readonly
                       class="input-cupon">
            </label>

            <label class="checkout-field">
                <span>Zona</span>
                <input type="text"
                       value="<?= $zonaEnvio ?>"
                       readonly
                       class="input-cupon">
            </label>
        </div>

        <div class="checkout-panel-title mini">

            <span>2</span>

            <div>

                <h3>
                    Método de pago
                </h3>

                <p>
                    Podés pagar online, por transferencia o coordinar por WhatsApp.
                </p>

            </div>

        </div>

        <label class="checkout-field">
            <span>Forma de pago</span>
            <select name="pago"
                    class="input-cupon"
                    required>

            <option value=""
                    <?= $datosCheckout['pago'] === '' ? 'selected' : '' ?>>
                Seleccionar método
            </option>

            <option value="mp"
                    <?= $datosCheckout['pago'] === 'mp' ? 'selected' : '' ?>>
                MercadoPago
            </option>

            <option value="transferencia"
                    <?= $datosCheckout['pago'] === 'transferencia' ? 'selected' : '' ?>>
                Transferencia
            </option>

            <option value="whatsapp"
                    <?= $datosCheckout['pago'] === 'whatsapp' ? 'selected' : '' ?>>
                Coordinar por WhatsApp
            </option>

            </select>
        </label>

        <button type="submit"
                name="confirmar"
                class="btn-pagar">

            Confirmar compra

        </button>

        <?= csrfInput() ?>

        <button type="submit"
                name="cancelar_compra"
                class="cancelar-compra-link"
                formnovalidate
                onclick="return confirm('¿Cancelar la compra y volver al inicio?')">

            Cancelar compra

        </button>

    </form>

    <aside class="checkout-side">

    <!-- PRODUCTOS -->
    <div class="carrito-items checkout-productos">

        <div class="checkout-panel-title">

            <span>3</span>

            <div>

                <h2>
                    Productos
                </h2>

                <p>
                    Confirmá cantidades y subtotal antes de pagar.
                </p>

            </div>

        </div>

        <?php foreach($_SESSION['carrito'] as $key => $cantidad): ?>

            <?php
            $id = carritoProductoId($key);
            $talleId = carritoTalleId($key);

            $producto = obtenerProductoPorId(
                $productos,
                $id
            );

            if($producto):

                $subtotal = $producto['precio'] * $cantidad;

                $subtotalVista += $subtotal;

            ?>

            <div class="carrito-card checkout-producto-card">

                <h3>
                    <?= $producto['nombre'] ?>
                </h3>

                <p>
                    Cantidad: <?= $cantidad ?>
                </p>

                <?php if($talleId && isset($tallesPorId[$talleId])): ?>
                    <p>
                        Talle: <?= htmlspecialchars(talleLabel($tallesPorId[$talleId])) ?>
                    </p>
                <?php endif; ?>

                <p>
                    Subtotal:
                    $<?= number_format($subtotal, 0, ',', '.') ?>
                </p>

            </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

    <!-- RESUMEN -->
    <div class="carrito-resumen checkout-summary">

        <h2>
            Resumen
        </h2>

        <p>
            Productos:
            <strong>
                $<?= number_format($subtotalVista, 0, ',', '.') ?>
            </strong>
        </p>

        <p>
            Envío:
            <strong>
                $<?= number_format($costoEnvio, 0, ',', '.') ?>
            </strong>
        </p>

        <h3>
            Total:
            $<?= number_format($subtotalVista + $costoEnvio, 0, ',', '.') ?>
        </h3>

    </div>

    </aside>

</div>

<?php endif; ?>

<?php include("includes/footer.php"); ?>
