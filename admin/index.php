<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";

/* PROTEGER PANEL */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* MÉTRICAS */
$totalProductos = 0;
$totalPedidos = 0;
$totalUsuarios = 0;
$totalVentas = 0;
$pedidosPendientes = 0;

$resProductos = mysqli_query($conn, "SELECT COUNT(*) AS total FROM productos");
if($resProductos){
    $totalProductos = mysqli_fetch_assoc($resProductos)['total'];
}

$resPedidos = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pedidos");
if($resPedidos){
    $totalPedidos = mysqli_fetch_assoc($resPedidos)['total'];
}

$resUsuarios = mysqli_query($conn, "SELECT COUNT(*) AS total FROM usuarios");
if($resUsuarios){
    $totalUsuarios = mysqli_fetch_assoc($resUsuarios)['total'];
}

$resVentas = mysqli_query($conn, "SELECT SUM(total) AS total FROM pedidos WHERE estado IN ('Pagado','Enviado','Entregado')");
if($resVentas){
    $totalVentas = mysqli_fetch_assoc($resVentas)['total'] ?? 0;
}

$resPendientes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'Pendiente'");
if($resPendientes){
    $pedidosPendientes = mysqli_fetch_assoc($resPendientes)['total'];
}

/* ÚLTIMOS PEDIDOS */
$sqlUltimos = "SELECT
                    pedidos.id,
                    pedidos.total,
                    pedidos.estado,
                    pedidos.fecha,
                    pedidos.nombre_cliente,
                    usuarios.nombre AS usuario_nombre
               FROM pedidos
               LEFT JOIN usuarios
               ON pedidos.usuario_id = usuarios.id
               ORDER BY pedidos.id DESC
               LIMIT 5";

$ultimosPedidos = mysqli_query($conn, $sqlUltimos);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Panel Admin | SportStyle</title>

    <link rel="stylesheet"
          href="../css/estilos.css">
</head>

<body class="admin-body">

<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

        <h2 class="admin-logo">
            Sport<span>Style</span>
        </h2>

        <p class="admin-user">
            👋 <?= $_SESSION['usuario_nombre'] ?>
        </p>

        <nav class="admin-menu">

            <a href="index.php"
               class="activo-admin">
                🏠 Dashboard
            </a>

            <a href="productos.php">
                📦 Productos
            </a>

            <a href="pedidos.php">
                🧾 Pedidos
            </a>

            <a href="usuarios.php">
                👥 Usuarios
            </a>

            <a href="ventas.php">
                📊 Ventas
            </a>

            <a href="../index.php">
                🏪 Ver tienda
            </a>

            <a href="../logout.php"
               class="logout-btn">
               🚪 Cerrar sesión
            </a>

        </nav>

    </aside>

    <!-- CONTENIDO -->
    <main class="admin-content">

        <!-- HERO DASHBOARD -->
        <section class="admin-hero">

            <div>

                <span class="admin-badge">
                    Dashboard SportStyle
                </span>

                <h1>
                    Bienvenido, <?= $_SESSION['usuario_nombre'] ?> 👋
                </h1>

                <p>
                    Controla pedidos, productos, ventas y actividad general de tu tienda.
                </p>

            </div>

            <div class="admin-hero-actions">

                <a href="agregar_productos.php"
                   class="btn-admin-agregar">
                    + Agregar producto
                </a>

                <a href="../index.php"
                   class="btn-admin-secundario">
                    Ver tienda
                </a>

            </div>

        </section>

        <!-- MÉTRICAS -->
        <section class="admin-metricas">

            <div class="admin-card metrica-card">

                <div class="metrica-icono">📦</div>

                <div>
                    <span>Productos</span>
                    <h2><?= $totalProductos ?></h2>
                    <p>Productos cargados</p>
                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">🧾</div>

                <div>
                    <span>Pedidos</span>
                    <h2><?= $totalPedidos ?></h2>
                    <p>Pedidos registrados</p>
                </div>

            </div>

            <div class="admin-card metrica-card alerta">

                <div class="metrica-icono">⏳</div>

                <div>
                    <span>Pendientes</span>
                    <h2><?= $pedidosPendientes ?></h2>
                    <p>Pedidos por revisar</p>
                </div>

            </div>

            <div class="admin-card metrica-card venta">

                <div class="metrica-icono">💰</div>

                <div>
                    <span>Ventas</span>
                    <h2>$<?= number_format($totalVentas, 0, ',', '.') ?></h2>
                    <p>Ventas confirmadas</p>
                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">👥</div>

                <div>
                    <span>Usuarios</span>
                    <h2><?= $totalUsuarios ?></h2>
                    <p>Usuarios registrados</p>
                </div>

            </div>

        </section>

        <!-- GRID ADMIN -->
        <section class="admin-dashboard-grid">

            <!-- ACCESOS RÁPIDOS -->
            <div class="admin-panel-box">

                <div class="admin-section-title">

                    <div>
                        <h2>Accesos rápidos</h2>
                        <p>Gestiona las áreas principales de la tienda.</p>
                    </div>

                </div>

                <div class="quick-actions">

                    <a href="productos.php" class="quick-action">
                        <span>📦</span>
                        <div>
                            <h3>Productos</h3>
                            <p>Precios, imágenes y stock</p>
                        </div>
                    </a>

                    <a href="pedidos.php" class="quick-action">
                        <span>🧾</span>
                        <div>
                            <h3>Pedidos</h3>
                            <p>Estados, pagos y envíos</p>
                        </div>
                    </a>

                    <a href="usuarios.php" class="quick-action">
                        <span>👥</span>
                        <div>
                            <h3>Usuarios</h3>
                            <p>Clientes y administradores</p>
                        </div>
                    </a>

                    <a href="ventas.php" class="quick-action">
                        <span>📊</span>
                        <div>
                            <h3>Ventas</h3>
                            <p>Ingresos y estadísticas</p>
                        </div>
                    </a>

                </div>

            </div>

            <!-- RESUMEN VISUAL -->
            <div class="admin-panel-box admin-resumen-box">

                <span class="admin-badge">
                    Actividad
                </span>

                <h2>
                    <?= $pedidosPendientes ?> pedidos pendientes
                </h2>

                <p>
                    Revisa los pedidos pendientes, actualiza estados y controla el flujo de ventas.
                </p>

                <a href="pedidos.php"
                   class="btn-admin-agregar">
                    Revisar pedidos
                </a>

            </div>

        </section>

        <!-- ÚLTIMOS PEDIDOS -->
        <section class="admin-section">

            <div class="admin-section-title">

                <div>
                    <h2>Últimos pedidos</h2>
                    <p>Actividad reciente de compras en la tienda.</p>
                </div>

                <a href="pedidos.php"
                   class="btn-admin-secundario">
                    Ver todos
                </a>

            </div>

            <div class="tabla-admin tabla-premium">

                <table>

                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if($ultimosPedidos && mysqli_num_rows($ultimosPedidos) > 0): ?>

                        <?php while($pedido = mysqli_fetch_assoc($ultimosPedidos)): ?>

                            <tr>

                                <td>#<?= $pedido['id'] ?></td>

                                <td>
                                    <?= $pedido['nombre_cliente'] ?: ($pedido['usuario_nombre'] ?? 'Cliente') ?>
                                </td>

                                <td>
                                    <strong>
                                        $<?= number_format($pedido['total'], 0, ',', '.') ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="estado <?= strtolower($pedido['estado']) ?>">
                                        <?= ucfirst($pedido['estado']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($pedido['fecha'])) ?>
                                </td>

                                <td class="acciones-tabla">

                                    <a href="ver_pedido.php?id=<?= $pedido['id'] ?>"
                                       class="btn-tabla editar">
                                       👁️
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6"
                                style="text-align:center; padding:30px;">
                                Todavía no hay pedidos registrados
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>