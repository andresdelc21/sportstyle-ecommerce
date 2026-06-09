<?php

session_start();

include("config/conexion.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

$sqlPedidos = "SELECT *
               FROM pedidos
               WHERE usuario_id = ?
               ORDER BY fecha DESC, id DESC";

$stmtPedidos = mysqli_prepare($conn, $sqlPedidos);
mysqli_stmt_bind_param($stmtPedidos, "i", $usuarioId);
mysqli_stmt_execute($stmtPedidos);
$pedidos = mysqli_stmt_get_result($stmtPedidos);

function pedidoPasoActivo(string $estado, string $paso): bool {
    $orden = [
        'Pendiente' => 1,
        'Pagado' => 2,
        'Enviado' => 3,
        'Entregado' => 4
    ];

    return ($orden[$estado] ?? 0) >= ($orden[$paso] ?? 99);
}

?>

<?php include("includes/header.php"); ?>

<section class="pedidos-header">
    <h1>Mis pedidos</h1>
    <p>Seguí tus compras, revisá el estado y gestioná solicitudes.</p>
</section>

<main class="pedidos-page">

    <?php if(mysqli_num_rows($pedidos) > 0): ?>

        <div class="pedidos-lista">

            <?php while($pedido = mysqli_fetch_assoc($pedidos)): ?>

                <article class="pedido-item">

                    <div class="pedido-item-main">
                        <div class="pedido-identidad">
                            <span class="pedido-numero">Pedido #<?= (int) $pedido['id'] ?></span>
                            <p><?= date("d/m/Y", strtotime($pedido['fecha'])) ?></p>
                        </div>

                        <div class="pedido-total-mini">
                            <span>Total</span>
                            <strong>$<?= number_format((float) $pedido['total'], 0, ',', '.') ?></strong>
                        </div>

                        <span class="pedido-estado estado-<?= strtolower($pedido['estado']) ?>">
                            <?= htmlspecialchars($pedido['estado']) ?>
                        </span>
                    </div>

                    <div class="pedido-tracking-mini">
                        <span class="<?= pedidoPasoActivo($pedido['estado'], 'Pendiente') ? 'activo' : '' ?>">Registrado</span>
                        <span class="<?= pedidoPasoActivo($pedido['estado'], 'Pagado') ? 'activo' : '' ?>">Pago</span>
                        <span class="<?= pedidoPasoActivo($pedido['estado'], 'Enviado') ? 'activo' : '' ?>">Envío</span>
                        <span class="<?= pedidoPasoActivo($pedido['estado'], 'Entregado') ? 'activo' : '' ?>">Entregado</span>
                    </div>

                    <?php if(!empty($pedido['solicitud_tipo'])): ?>
                        <p class="pedido-solicitud">
                            Solicitud de <?= htmlspecialchars($pedido['solicitud_tipo']) ?>:
                            <strong><?= htmlspecialchars($pedido['solicitud_estado'] ?? 'Pendiente') ?></strong>
                        </p>
                    <?php endif; ?>

                    <?php if(!empty($pedido['empresa_envio']) || !empty($pedido['numero_seguimiento'])): ?>
                        <div class="pedido-seguimiento-mini">
                            <div>
                                <span>Seguimiento</span>
                                <strong>
                                    <?= htmlspecialchars($pedido['empresa_envio'] ?: 'Correo') ?>
                                    <?php if(!empty($pedido['numero_seguimiento'])): ?>
                                        · <?= htmlspecialchars($pedido['numero_seguimiento']) ?>
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <?php if(!empty($pedido['link_seguimiento'])): ?>
                                <a href="<?= htmlspecialchars($pedido['link_seguimiento']) ?>"
                                   target="_blank"
                                   rel="noopener"
                                   class="pedido-link pedido-link-secundario">
                                    Seguir envío
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pedido-item-footer">

                        <div>
                            <span>Entrega</span>
                            <strong><?= htmlspecialchars($pedido['zona_envio'] ?? 'A confirmar') ?></strong>
                        </div>

                        <a href="pedido_detalle.php?id=<?= (int) $pedido['id'] ?>" class="pedido-link">
                            Ver detalle
                        </a>

                    </div>

                </article>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <section class="pedidos-vacio">
            <h2>Todavía no tenés pedidos</h2>
            <p>Cuando finalices una compra, vas a poder seguirla desde acá.</p>
            <a href="productos.php" class="btn-pagar">Empezar compra →</a>
        </section>

    <?php endif; ?>

</main>

<?php include("includes/footer.php"); ?>
