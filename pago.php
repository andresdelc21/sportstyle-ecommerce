<?php

session_start();

include("config/conexion.php");
include("config/config.php");
include("includes/mercadopago.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

$pedidoId = (int) ($_GET['pedido'] ?? $_SESSION['mp_pedido_id'] ?? 0);

if($pedidoId <= 0){
    header("Location: carrito.php");
    exit;
}

$sqlPedido = "SELECT
                pedidos.*,
                usuarios.email AS email_cliente
              FROM pedidos
              LEFT JOIN usuarios
              ON pedidos.usuario_id = usuarios.id
              WHERE pedidos.id = ?
              AND pedidos.usuario_id = ?";

$stmtPedido = mysqli_prepare($conn, $sqlPedido);

mysqli_stmt_bind_param(
    $stmtPedido,
    "ii",
    $pedidoId,
    $_SESSION['usuario_id']
);

mysqli_stmt_execute($stmtPedido);

$pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPedido));

if(!$pedido || $pedido['metodo_pago'] !== 'mp'){
    header("Location: carrito.php");
    exit;
}

$errorPago = "";

if(empty($MP_ACCESS_TOKEN)){
    $errorPago = "Mercado Pago todavía no tiene configurado el Access Token desde el admin.";
}

$sqlProductos = "SELECT
                    detalle_pedidos.cantidad,
                    detalle_pedidos.precio,
                    detalle_pedidos.talle_label,
                    productos.nombre
                 FROM detalle_pedidos
                 LEFT JOIN productos
                 ON detalle_pedidos.producto_id = productos.id
                 WHERE detalle_pedidos.pedido_id = ?";

$stmtProductos = mysqli_prepare($conn, $sqlProductos);

mysqli_stmt_bind_param($stmtProductos, "i", $pedidoId);

mysqli_stmt_execute($stmtProductos);

$resultadoProductos = mysqli_stmt_get_result($stmtProductos);

$items = [];

while($producto = mysqli_fetch_assoc($resultadoProductos)){

    $tituloItem = $producto['nombre'] ?: "Producto SportStyle";

    if(!empty($producto['talle_label'])){
        $tituloItem .= " - " . $producto['talle_label'];
    }

    $items[] = [
        "title" => $tituloItem,
        "quantity" => (int) $producto['cantidad'],
        "unit_price" => (float) $producto['precio'],
        "currency_id" => "ARS"
    ];

}

if((float) $pedido['costo_envio'] > 0){

    $items[] = [
        "title" => "Envio - " . ($pedido['zona_envio'] ?: "SportStyle"),
        "quantity" => 1,
        "unit_price" => (float) $pedido['costo_envio'],
        "currency_id" => "ARS"
    ];

}

if(empty($items)){
    $errorPago = "El pedido no tiene productos cargados.";
}

if($errorPago === ""){

    $baseUrl = mpBaseUrl();
    $notificationUrl = $baseUrl . "/mp_webhook.php";

    if(!empty($MP_WEBHOOK_TOKEN)){
        $notificationUrl .= "?token=" . urlencode($MP_WEBHOOK_TOKEN);
    }

    $payer = [
        "name" => $pedido['nombre_cliente']
    ];

    if(filter_var($pedido['email_cliente'], FILTER_VALIDATE_EMAIL)){
        $payer["email"] = $pedido['email_cliente'];
    }

    $preferencia = [
        "items" => $items,
        "payer" => $payer,
        "back_urls" => [
            "success" => $baseUrl . "/pago_exitoso.php?pedido=" . $pedidoId,
            "failure" => $baseUrl . "/pago_fallido.php?pedido=" . $pedidoId,
            "pending" => $baseUrl . "/pago_pendiente.php?pedido=" . $pedidoId
        ],
        "notification_url" => $notificationUrl,
        "auto_return" => "approved",
        "external_reference" => "pedido_" . $pedidoId,
        "metadata" => [
            "pedido_id" => $pedidoId
        ],
        "statement_descriptor" => "SPORTSTYLE"
    ];

    $respuesta = mpApiRequest(
        'POST',
        '/checkout/preferences',
        $MP_ACCESS_TOKEN,
        $preferencia
    );

    if($respuesta['ok'] && !empty($respuesta['data']['init_point'])){

        $preferenceId = $respuesta['data']['id'] ?? null;

        if($preferenceId){

            $sqlPreference = "UPDATE pedidos
                              SET mp_preference_id = ?
                              WHERE id = ?";

            $stmtPreference = mysqli_prepare($conn, $sqlPreference);

            mysqli_stmt_bind_param(
                $stmtPreference,
                "si",
                $preferenceId,
                $pedidoId
            );

            mysqli_stmt_execute($stmtPreference);

        }

        unset($_SESSION['carrito'], $_SESSION['mp_pedido_id']);

        header("Location: " . $respuesta['data']['init_point']);
        exit;

    }

    $mensajeApi = $respuesta['data']['message'] ?? $respuesta['error'] ?? 'No se pudo crear la preferencia de pago.';
    $errorPago = "Mercado Pago respondió con un problema: " . $mensajeApi;

}

?>

<?php include("includes/header.php"); ?>

<section class="checkout-resultado">

    <span class="checkout-icono">$</span>

    <h2>Mercado Pago no está listo</h2>

    <p>
        <?= htmlspecialchars($errorPago) ?>
    </p>

    <p class="checkout-numero">
        Pedido #<?= (int) $pedidoId ?>
    </p>

    <div class="checkout-acciones">

        <a href="checkout.php"
           class="btn-secundario-checkout">
            Volver al checkout
        </a>

        <a href="productos.php"
           class="btn-pagar">
            Seguir comprando
        </a>

    </div>

</section>

<?php include("includes/footer.php"); ?>
