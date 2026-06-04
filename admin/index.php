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
$ventasMes = 0;
$productosStockCritico = [];
$ventasPorMesLabels = [];
$ventasPorMesData = [];

/* TOTAL PRODUCTOS */
$resProductos = mysqli_query($conn, "SELECT COUNT(*) AS total FROM productos");
if($resProductos){
    $totalProductos = mysqli_fetch_assoc($resProductos)['total'];
}

/* TOTAL PEDIDOS */
$resPedidos = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pedidos");
if($resPedidos){
    $totalPedidos = mysqli_fetch_assoc($resPedidos)['total'];
}

/* TOTAL USUARIOS */
$resUsuarios = mysqli_query($conn, "SELECT COUNT(*) AS total FROM usuarios");
if($resUsuarios){
    $totalUsuarios = mysqli_fetch_assoc($resUsuarios)['total'];
}

/* VENTAS TOTALES */
$resVentas = mysqli_query($conn, "
    SELECT SUM(total) AS total 
    FROM pedidos 
    WHERE estado IN ('Pagado','Enviado','Entregado')
");

if($resVentas){
    $totalVentas = mysqli_fetch_assoc($resVentas)['total'] ?? 0;
}

/* PEDIDOS PENDIENTES */
$resPendientes = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM pedidos 
    WHERE estado = 'Pendiente'
");

if($resPendientes){
    $pedidosPendientes = mysqli_fetch_assoc($resPendientes)['total'];
}

/* VENTAS DEL MES */
$resVentasMes = mysqli_query($conn, "
    SELECT SUM(total) AS total
    FROM pedidos
    WHERE estado IN ('Pagado','Enviado','Entregado')
    AND MONTH(fecha) = MONTH(CURRENT_DATE())
    AND YEAR(fecha) = YEAR(CURRENT_DATE())
");

if($resVentasMes){
    $ventasMes = mysqli_fetch_assoc($resVentasMes)['total'] ?? 0;
}

/* PRODUCTOS CON STOCK CRÍTICO */
$resStockCritico = mysqli_query($conn, "
    SELECT id, nombre, stock, imagen
    FROM productos
    WHERE stock > 0 AND stock <= 5
    ORDER BY stock ASC
    LIMIT 5
");

if($resStockCritico){
    while($p = mysqli_fetch_assoc($resStockCritico)){
        $productosStockCritico[] = $p;
    }
}
/* DATOS DEL GRÁFICO */
$resVentasGrafico = mysqli_query($conn, "
    SELECT
        DATE_FORMAT(fecha,'%m/%Y') AS mes,
        SUM(total) AS total
    FROM pedidos
    WHERE estado IN ('Pagado','Enviado','Entregado')
    GROUP BY YEAR(fecha), MONTH(fecha)
    ORDER BY YEAR(fecha), MONTH(fecha)
");

if($resVentasGrafico){

    while($fila = mysqli_fetch_assoc($resVentasGrafico)){

        $ventasPorMesLabels[] = $fila['mes'];
        $ventasPorMesData[] = (float)$fila['total'];

    }

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

    <?php include("includes/sidebar.php"); ?>

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
                    <span>Ventas totales</span>
                    <h2>$<?= number_format($totalVentas, 0, ',', '.') ?></h2>
                    <p>Ventas confirmadas</p>
                </div>

            </div>

            <div class="admin-card metrica-card venta">

                <div class="metrica-icono">📅</div>

                <div>
                    <span>Ventas del mes</span>
                    <h2>$<?= number_format($ventasMes, 0, ',', '.') ?></h2>
                    <p>Ingresos confirmados este mes</p>
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

        <!-- STOCK CRÍTICO -->
        <section class="admin-section">

            <div class="admin-section-title">

                <div>
                    <h2>Stock crítico</h2>
                    <p>Productos que necesitan reposición pronto.</p>
                </div>

                <a href="productos.php"
                   class="btn-admin-secundario">
                    Ver productos
                </a>

            </div>

            <div class="tabla-admin tabla-premium">

                <table>

                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if(count($productosStockCritico) > 0): ?>

                        <?php foreach($productosStockCritico as $p): ?>

                            <tr>

                                <td>

                                    <div class="producto-admin-info">

                                        <img src="../<?= $p['imagen'] ?>"
                                             class="admin-img-producto">

                                        <strong>
                                            <?= $p['nombre'] ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <span class="stock-bajo">
                                        <?= $p['stock'] ?> unidades
                                    </span>
                                </td>

                                <td class="acciones-tabla">

                                    <a href="editar_productos.php?id=<?= $p['id'] ?>"
                                       class="btn-tabla editar">
                                       ✏️
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3"
                                style="text-align:center; padding:30px;">
                                No hay productos con stock crítico
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ventasLabels =
<?= json_encode($ventasPorMesLabels) ?>;

const ventasData =
<?= json_encode($ventasPorMesData) ?>;

</script>

<script src="../java/admin.js"></script>

</body>
</html>