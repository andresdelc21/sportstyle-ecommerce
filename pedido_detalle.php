<?php

session_start();

include("config/conexion.php");
include("includes/csrf.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$pedidoId = (int) ($_GET['id'] ?? 0);
$mensajePedido = '';

if($pedidoId <= 0){
    header("Location: mis_pedidos.php");
    exit;
}

function obtenerPedidoCliente(mysqli $conn, int $pedidoId, int $usuarioId): ?array {
    $sql = "SELECT *
            FROM pedidos
            WHERE id = ?
            AND usuario_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $pedidoId, $usuarioId);
    mysqli_stmt_execute($stmt);

    $pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $pedido ?: null;
}

$pedido = obtenerPedidoCliente($conn, $pedidoId, $usuarioId);

if(!$pedido){
    header("Location: mis_pedidos.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!validarCsrf()){
        $mensajePedido = 'La sesión expiró. Volvé a intentar.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $estadoActual = $pedido['estado'];

        if($accion === 'cancelar' && in_array($estadoActual, ['Pendiente', 'Pagado'], true)){
            if($estadoActual === 'Pendiente'){
                $sql = "UPDATE pedidos
                        SET estado = 'Cancelado',
                            solicitud_tipo = 'Cancelacion',
                            solicitud_estado = 'Aprobada',
                            solicitud_motivo = ?,
                            solicitud_fecha = NOW()
                        WHERE id = ?
                        AND usuario_id = ?";

                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sii", $motivo, $pedidoId, $usuarioId);
                mysqli_stmt_execute($stmt);
                $mensajePedido = 'Cancelamos el pedido pendiente.';
            } else {
                $sql = "UPDATE pedidos
                        SET solicitud_tipo = 'Cancelacion',
                            solicitud_estado = 'Pendiente',
                            solicitud_motivo = ?,
                            solicitud_fecha = NOW()
                        WHERE id = ?
                        AND usuario_id = ?";

                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sii", $motivo, $pedidoId, $usuarioId);
                mysqli_stmt_execute($stmt);
                $mensajePedido = 'Registramos tu solicitud de cancelación.';
            }
        }

        if($accion === 'devolucion' && $estadoActual === 'Entregado'){
            $sql = "UPDATE pedidos
                    SET solicitud_tipo = 'Devolucion',
                        solicitud_estado = 'Pendiente',
                        solicitud_motivo = ?,
                        solicitud_fecha = NOW()
                    WHERE id = ?
                    AND usuario_id = ?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sii", $motivo, $pedidoId, $usuarioId);
            mysqli_stmt_execute($stmt);
            $mensajePedido = 'Registramos tu solicitud de devolución.';
        }

        $pedido = obtenerPedidoCliente($conn, $pedidoId, $usuarioId);
    }
}

$sqlDetalle = "SELECT
                    detalle_pedidos.*,
                    productos.nombre,
                    productos.imagen
               FROM detalle_pedidos
               LEFT JOIN productos
               ON detalle_pedidos.producto_id = productos.id
               WHERE detalle_pedidos.pedido_id = ?";

$stmtDetalle = mysqli_prepare($conn, $sqlDetalle);
mysqli_stmt_bind_param($stmtDetalle, "i", $pedidoId);
mysqli_stmt_execute($stmtDetalle);
$productosPedido = mysqli_stmt_get_result($stmtDetalle);

$puedeCancelar = in_array($pedido['estado'], ['Pendiente', 'Pagado'], true) && empty($pedido['solicitud_tipo']);
$puedeDevolver = $pedido['estado'] === 'Entregado' && empty($pedido['solicitud_tipo']);

$pasos = [
    'Pendiente' => 'Pedido registrado',
    'Pagado' => 'Pago confirmado',
    'Enviado' => 'Pedido enviado',
    'Entregado' => 'Pedido entregado'
];

$ordenEstados = [
    'Pendiente' => 1,
    'Pagado' => 2,
    'Enviado' => 3,
    'Entregado' => 4
];

?>

<?php include("includes/header.php"); ?>

<section class="pedidos-header">
    <a href="mis_pedidos.php" class="pedido-volver">← Mis pedidos</a>
    <h1>Pedido #<?= (int) $pedido['id'] ?></h1>
    <p>Estado actual: <strong><?= htmlspecialchars($pedido['estado']) ?></strong></p>
</section>

<main class="pedido-detalle-page">

    <?php if($mensajePedido): ?>
        <div class="pedido-alerta"><?= htmlspecialchars($mensajePedido) ?></div>
    <?php endif; ?>

    <section class="pedido-detalle-grid">

        <div class="pedido-panel-cliente">
            <h2>Seguimiento</h2>

            <div class="pedido-timeline">
                <?php foreach($pasos as $estado => $label): ?>
                    <div class="timeline-step <?= ($ordenEstados[$pedido['estado']] ?? 0) >= $ordenEstados[$estado] ? 'activo' : '' ?>">
                        <span></span>
                        <p><?= htmlspecialchars($label) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if($pedido['estado'] === 'Cancelado'): ?>
                <p class="pedido-nota">Este pedido fue cancelado.</p>
            <?php elseif($pedido['estado'] === 'Pendiente'): ?>
                <p class="pedido-nota">Estamos esperando confirmación de pago o revisión del pedido.</p>
            <?php elseif($pedido['estado'] === 'Pagado'): ?>
                <p class="pedido-nota">El pago está confirmado. Estamos preparando el pedido.</p>
            <?php elseif($pedido['estado'] === 'Enviado'): ?>
                <p class="pedido-nota">Tu pedido ya salió hacia la zona indicada.</p>
            <?php elseif($pedido['estado'] === 'Entregado'): ?>
                <p class="pedido-nota">El pedido figura como entregado.</p>
            <?php endif; ?>
        </div>

        <aside class="pedido-panel-cliente pedido-resumen-cliente">
            <h2>Resumen</h2>
            <p><span>Fecha</span><strong><?= date("d/m/Y", strtotime($pedido['fecha'])) ?></strong></p>
            <p><span>Pago</span><strong><?= htmlspecialchars($pedido['metodo_pago'] ?? '-') ?></strong></p>
            <p><span>Envío</span><strong><?= htmlspecialchars($pedido['zona_envio'] ?? '-') ?></strong></p>
            <p><span>Total</span><strong>$<?= number_format((float) $pedido['total'], 0, ',', '.') ?></strong></p>
        </aside>

    </section>

    <section class="pedido-panel-cliente">
        <h2>Productos</h2>

        <div class="pedido-productos-lista">
            <?php while($producto = mysqli_fetch_assoc($productosPedido)): ?>
                <div class="pedido-producto-row">
                    <img src="<?= htmlspecialchars($producto['imagen'] ?? 'img/banner.png') ?>" alt="<?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?>">
                    <div>
                        <strong><?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?></strong>
                        <p>Cantidad: <?= (int) $producto['cantidad'] ?></p>
                        <?php if(!empty($producto['talle_label'])): ?>
                            <p>Talle: <?= htmlspecialchars($producto['talle_label']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span>$<?= number_format((float) $producto['precio'] * (int) $producto['cantidad'], 0, ',', '.') ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="pedido-panel-cliente">
        <h2>Cancelación y devolución</h2>

        <?php if(!empty($pedido['solicitud_tipo'])): ?>
            <p class="pedido-nota">
                Solicitud de <?= htmlspecialchars($pedido['solicitud_tipo']) ?>:
                <strong><?= htmlspecialchars($pedido['solicitud_estado'] ?? 'Pendiente') ?></strong>
            </p>
            <?php if(!empty($pedido['solicitud_motivo'])): ?>
                <p class="pedido-motivo"><?= htmlspecialchars($pedido['solicitud_motivo']) ?></p>
            <?php endif; ?>
        <?php elseif($puedeCancelar): ?>
            <form method="POST" class="pedido-solicitud-form">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="cancelar">
                <label>Motivo de cancelación</label>
                <textarea name="motivo" placeholder="Contanos brevemente el motivo"></textarea>
                <button type="submit">Solicitar cancelación</button>
            </form>
        <?php elseif($puedeDevolver): ?>
            <form method="POST" class="pedido-solicitud-form">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="devolucion">
                <label>Motivo de devolución</label>
                <textarea name="motivo" placeholder="Producto, talle, falla o motivo de devolución"></textarea>
                <button type="submit">Solicitar devolución</button>
            </form>
        <?php else: ?>
            <p class="pedido-nota">
                No hay acciones disponibles para este estado. Contactanos si necesitás ayuda con este pedido.
            </p>
        <?php endif; ?>
    </section>

</main>

<?php include("includes/footer.php"); ?>
