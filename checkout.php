<?php

session_start();

include("config/conexion.php");
include("config/config.php");
include("includes/funciones.php");
include("includes/pedidos.php");

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

/* PROCESAR CHECKOUT */
if(isset($_POST['confirmar'])){

    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $pago = trim($_POST['pago']);

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
    foreach($_SESSION['carrito'] as $id => $cantidad){

        $producto = obtenerProductoPorId($productos, $id);

        if(!$producto){
            die("Producto no encontrado.");
        }

        if($producto['stock'] < $cantidad){
            die("No hay stock suficiente para: " . $producto['nombre']);
        }

    }

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

    /* GUARDAR DETALLES */
    foreach($_SESSION['carrito'] as $id => $cantidad){

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
                $producto['precio']
            );

            descontarStock(
                $conn,
                $id,
                $cantidad
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

        unset($_SESSION['carrito']);

        header("Location: checkout.php?success=1");
        exit;

    }

}

$subtotalVista = 0;

?>

<?php include("includes/header.php"); ?>

<h1 class="titulo">
    Finalizar Compra
</h1>

<?php if(isset($_GET['success'])): ?>

    <p style="text-align:center; color:green; font-size:20px;">
        ✅ Compra realizada con éxito
    </p>

<?php elseif(isset($_GET['transferencia'])): ?>

    <p style="text-align:center;">
    Alias: <?= $ALIAS_TIENDA ?> <br>
    CBU/CVU: <?= $CBU_TIENDA ?> <br>
    Titular: <?= $TITULAR_TIENDA ?> <br>
    Enviá el comprobante por WhatsApp
</p>

<?php endif; ?>

<?php if(!isset($_GET['success']) && !isset($_GET['transferencia'])): ?>

<div class="carrito-container">

    <!-- FORMULARIO -->
    <form method="POST"
          action="checkout.php">

        <h2>
            Datos del cliente
        </h2>

        <input type="text"
               name="nombre"
               placeholder="Nombre completo"
               required
               class="input-cupon">

        <input type="text"
               name="telefono"
               placeholder="Teléfono"
               required
               class="input-cupon">

        <input type="text"
               name="direccion"
               placeholder="Dirección de envío"
               required
               class="input-cupon">

        <input type="text"
               value="<?= $codigoPostal ?>"
               placeholder="Código postal"
               readonly
               class="input-cupon">

        <input type="text"
               value="<?= $zonaEnvio ?>"
               placeholder="Zona de envío"
               readonly
               class="input-cupon">

        <h3>
            Método de pago
        </h3>

        <select name="pago"
                class="input-cupon"
                required>

            <option value="">
                Seleccionar método
            </option>

            <option value="mp">
                MercadoPago
            </option>

            <option value="transferencia">
                Transferencia
            </option>

            <option value="whatsapp">
                Coordinar por WhatsApp
            </option>

        </select>

        <button type="submit"
                name="confirmar"
                class="btn-pagar">

            Confirmar compra

        </button>

    </form>

    <!-- PRODUCTOS -->
    <div class="carrito-items">

        <?php foreach($_SESSION['carrito'] as $id => $cantidad): ?>

            <?php

            $producto = obtenerProductoPorId(
                $productos,
                $id
            );

            if($producto):

                $subtotal = $producto['precio'] * $cantidad;

                $subtotalVista += $subtotal;

            ?>

            <div class="carrito-card">

                <h3>
                    <?= $producto['nombre'] ?>
                </h3>

                <p>
                    Cantidad: <?= $cantidad ?>
                </p>

                <p>
                    Subtotal:
                    $<?= number_format($subtotal, 0, ',', '.') ?>
                </p>

            </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

    <!-- RESUMEN -->
    <div class="carrito-resumen">

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

</div>

<?php endif; ?>

<?php include("includes/footer.php"); ?>