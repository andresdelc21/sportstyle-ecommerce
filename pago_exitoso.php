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

$resultadoMp = [
    'estado' => 'Pendiente',
    'mp_status' => $_GET['status'] ?? null,
    'payment_id' => $_GET['payment_id'] ?? null
];

if(!empty($MP_ACCESS_TOKEN)){
    $resultadoMp = mpActualizarPedidoDesdeRetorno($conn, $MP_ACCESS_TOKEN, $pedidoId, $_SESSION['usuario_id']);
}

$estadoFinal = $resultadoMp['estado'];

$sqlEstado = "UPDATE pedidos
              SET estado = ?
              WHERE id = ?
              AND usuario_id = ?";

$stmtEstado = mysqli_prepare($conn, $sqlEstado);

mysqli_stmt_bind_param(
    $stmtEstado,
    "sii",
    $estadoFinal,
    $pedidoId,
    $_SESSION['usuario_id']
);

mysqli_stmt_execute($stmtEstado);

$_SESSION['ultimo_pedido_id'] = $pedidoId;

?>

<?php include("includes/header.php"); ?>

<section class="checkout-resultado">

    <span class="checkout-icono">✓</span>

    <h2>
        <?= $estadoFinal === 'Pagado' ? 'Pago recibido' : 'Pago pendiente de confirmacion' ?>
    </h2>

    <p class="checkout-numero">
        Pedido #<?= (int) $pedidoId ?>
    </p>

    <p>
        <?php if($estadoFinal === 'Pagado'): ?>
            Registramos el pago de Mercado Pago. Desde acá podés seguir el estado de tu pedido.
        <?php else: ?>
            Mercado Pago nos devolvió la compra, pero todavía no pudimos confirmar el pago como aprobado. Podés seguir el estado desde acá.
        <?php endif; ?>
    </p>

    <div class="checkout-acciones">

        <a href="index.php"
           class="btn-secundario-checkout">
            Volver al inicio
        </a>

        <a href="pedido_detalle.php?id=<?= (int) $pedidoId ?>"
           class="btn-secundario-checkout">
            Seguir mi pedido
        </a>

        <a href="productos.php"
           class="btn-pagar">
            Seguir comprando
        </a>

    </div>

</section>

<?php include("includes/footer.php"); ?>
