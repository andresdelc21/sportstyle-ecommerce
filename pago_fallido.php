<?php

session_start();

include("config/conexion.php");
include("config/config.php");
include("includes/mercadopago.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

$pedidoId = (int) ($_GET['pedido'] ?? 0);

if($pedidoId <= 0){
    header("Location: productos.php");
    exit;
}

if(!empty($MP_ACCESS_TOKEN)){
    mpActualizarPedidoDesdeRetorno($conn, $MP_ACCESS_TOKEN, $pedidoId, $_SESSION['usuario_id']);
}

$sqlEstado = "UPDATE pedidos
              SET estado = 'Cancelado'
              WHERE id = ?
              AND usuario_id = ?
              AND estado <> 'Pagado'";

$stmtEstado = mysqli_prepare($conn, $sqlEstado);

mysqli_stmt_bind_param(
    $stmtEstado,
    "ii",
    $pedidoId,
    $_SESSION['usuario_id']
);

mysqli_stmt_execute($stmtEstado);

$_SESSION['ultimo_pedido_id'] = $pedidoId;

?>

<?php include("includes/header.php"); ?>

<section class="checkout-resultado">

    <span class="checkout-icono">!</span>

    <h2>Pago no aprobado</h2>

    <p class="checkout-numero">
        Pedido #<?= (int) $pedidoId ?>
    </p>

    <p>
        Mercado Pago no aprobó el pago. Podés volver a la tienda y realizar una nueva compra cuando quieras.
    </p>

    <div class="checkout-acciones">

        <a href="index.php"
           class="btn-secundario-checkout">
            Volver al inicio
        </a>

        <a href="pedido_detalle.php?id=<?= (int) $pedidoId ?>"
           class="btn-secundario-checkout">
            Ver pedido
        </a>

        <a href="productos.php"
           class="btn-pagar">
            Seguir comprando
        </a>

    </div>

</section>

<?php include("includes/footer.php"); ?>
