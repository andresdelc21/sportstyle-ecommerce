<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";

/* PROTEGER PANEL */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* VENTAS CONFIRMADAS */
$totalVentas = 0;
$totalPedidos = 0;
$totalClientes = 0;
$productoTop = "Sin datos";

/* TOTAL VENTAS */
$sqlVentas = "SELECT SUM(total) AS total
              FROM pedidos
              WHERE estado IN ('Pagado','Enviado','Entregado')";

$resVentas = mysqli_query($conn, $sqlVentas);

if($resVentas){
    $totalVentas = mysqli_fetch_assoc($resVentas)['total'] ?? 0;
}

/* TOTAL PEDIDOS CONFIRMADOS */
$sqlPedidos = "SELECT COUNT(*) AS total
               FROM pedidos
               WHERE estado IN ('Pagado','Enviado','Entregado')";

$resPedidos = mysqli_query($conn, $sqlPedidos);

if($resPedidos){
    $totalPedidos = mysqli_fetch_assoc($resPedidos)['total'] ?? 0;
}

/* TOTAL CLIENTES */
$sqlClientes = "SELECT COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'cliente'";

$resClientes = mysqli_query($conn, $sqlClientes);

if($resClientes){
    $totalClientes = mysqli_fetch_assoc($resClientes)['total'] ?? 0;
}

/* PRODUCTO MÁS VENDIDO */
$sqlTop = "SELECT
                productos.nombre,
                SUM(detalle_pedidos.cantidad) AS cantidad_total
           FROM detalle_pedidos
           LEFT JOIN productos
           ON detalle_pedidos.producto_id = productos.id
           LEFT JOIN pedidos
           ON detalle_pedidos.pedido_id = pedidos.id
           WHERE pedidos.estado IN ('Pagado','Enviado','Entregado')
           GROUP BY detalle_pedidos.producto_id
           ORDER BY cantidad_total DESC
           LIMIT 1";

$resTop = mysqli_query($conn, $sqlTop);

if($resTop && mysqli_num_rows($resTop) > 0){
    $top = mysqli_fetch_assoc($resTop);
    $productoTop = $top['nombre'];
}

/* ÚLTIMAS VENTAS */
$sqlUltimas = "SELECT
                    id,
                    nombre_cliente,
                    total,
                    estado,
                    fecha
               FROM pedidos
               WHERE estado IN ('Pagado','Enviado','Entregado')
               ORDER BY id DESC
               LIMIT 5";

$ultimasVentas = mysqli_query($conn, $sqlUltimas);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Ventas | Admin</title>

    <link rel="stylesheet"
          href="../css/estilos.css">

</head>

<body class="admin-body">

<div class="admin-container">

    <?php include("includes/sidebar.php"); ?>


    <!-- CONTENIDO -->
    <main class="admin-content">

        <!-- HERO -->
        <section class="admin-hero small-hero">

            <div>

                <span class="admin-badge">
                    Estadísticas reales
                </span>

                <h1>
                    Ventas 📊
                </h1>

                <p>
                    Visualiza ingresos, pedidos confirmados, clientes y productos más vendidos.
                </p>

            </div>

        </section>

        <!-- RESUMEN -->
        <div class="admin-metricas mini-metricas">

            <div class="admin-card metrica-card venta">

                <div class="metrica-icono">
                    💰
                </div>

                <div>

                    <span>Ventas totales</span>

                    <h2>
                        $<?= number_format($totalVentas, 0, ',', '.') ?>
                    </h2>

                    <p>Pedidos pagados/enviados/entregados</p>

                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    🧾
                </div>

                <div>

                    <span>Pedidos confirmados</span>

                    <h2>
                        <?= $totalPedidos ?>
                    </h2>

                    <p>Ventas procesadas</p>

                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    👥
                </div>

                <div>

                    <span>Clientes</span>

                    <h2>
                        <?= $totalClientes ?>
                    </h2>

                    <p>Usuarios con rol cliente</p>

                </div>

            </div>

            <div class="admin-card metrica-card alerta">

                <div class="metrica-icono">
                    🔥
                </div>

                <div>

                    <span>Producto top</span>

                    <h2 style="font-size:22px;">
                        <?= $productoTop ?>
                    </h2>

                    <p>Más vendido</p>

                </div>

            </div>

        </div>

        <!-- BLOQUE RESUMEN -->
        <section class="admin-dashboard-grid">

            <div class="admin-panel-box admin-resumen-box">

                <span class="admin-badge">
                    Rendimiento
                </span>

                <h2>
                    $<?= number_format($totalVentas, 0, ',', '.') ?>
                </h2>

                <p>
                    Total generado por pedidos marcados como pagados, enviados o entregados.
                </p>

                <a href="pedidos.php"
                   class="btn-admin-agregar">
                    Ver pedidos
                </a>

            </div>

            <div class="admin-panel-box">

                <div class="admin-section-title">

                    <div>
                        <h2>Resumen operativo</h2>
                        <p>Indicadores rápidos de actividad.</p>
                    </div>

                </div>

                <div class="quick-actions">

                    <a href="pedidos.php" class="quick-action">
                        <span>🧾</span>
                        <div>
                            <h3>Pedidos</h3>
                            <p>Revisar estados y pagos</p>
                        </div>
                    </a>

                    <a href="productos.php" class="quick-action">
                        <span>📦</span>
                        <div>
                            <h3>Productos</h3>
                            <p>Controlar stock y catálogo</p>
                        </div>
                    </a>

                </div>

            </div>

        </section>

        <!-- ÚLTIMAS VENTAS -->
        <section class="admin-section">

            <div class="admin-section-title">

                <div>
                    <h2>Últimas ventas</h2>
                    <p>Pedidos confirmados recientemente.</p>
                </div>

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

                    <?php if($ultimasVentas && mysqli_num_rows($ultimasVentas) > 0): ?>

                        <?php while($v = mysqli_fetch_assoc($ultimasVentas)): ?>

                            <tr>

                                <td>#<?= $v['id'] ?></td>

                                <td><?= $v['nombre_cliente'] ?: 'Cliente' ?></td>

                                <td>
                                    <strong>
                                        $<?= number_format($v['total'], 0, ',', '.') ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="estado <?= strtolower($v['estado']) ?>">
                                        <?= ucfirst($v['estado']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($v['fecha'])) ?>
                                </td>

                                <td class="acciones-tabla">

                                    <a href="ver_pedido.php?id=<?= $v['id'] ?>"
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

                                Todavía no hay ventas confirmadas

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>
<script src="../java/admin.js"></script>
</body>
</html>