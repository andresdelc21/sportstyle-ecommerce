<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";

/* PROTEGER */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* VALIDAR ID */
if(!isset($_GET['id'])){
    header("Location: pedidos.php");
    exit;
}

$id = (int) $_GET['id'];

/* ACTUALIZAR ESTADO */
if(isset($_POST['actualizar_estado'])){

    $nuevoEstado = trim($_POST['estado']);

    $sqlEstado = "UPDATE pedidos
                  SET estado = ?
                  WHERE id = ?";

    $stmtEstado = mysqli_prepare($conn, $sqlEstado);

    mysqli_stmt_bind_param(
        $stmtEstado,
        "si",
        $nuevoEstado,
        $id
    );

    mysqli_stmt_execute($stmtEstado);

    header("Location: ver_pedido.php?id=" . $id);
    exit;
}

/* TRAER PEDIDO */
$sqlPedido = "SELECT
                pedidos.*,
                usuarios.nombre AS cliente,
                usuarios.email AS email_cliente
              FROM pedidos
              LEFT JOIN usuarios
              ON pedidos.usuario_id = usuarios.id
              WHERE pedidos.id = ?";

$stmtPedido = mysqli_prepare($conn, $sqlPedido);

mysqli_stmt_bind_param($stmtPedido, "i", $id);

mysqli_stmt_execute($stmtPedido);

$resultadoPedido = mysqli_stmt_get_result($stmtPedido);

$pedido = mysqli_fetch_assoc($resultadoPedido);

if(!$pedido){
    header("Location: pedidos.php");
    exit;
}

/* TRAER PRODUCTOS DEL PEDIDO */
$sqlProductos = "SELECT
                    detalle_pedidos.*,
                    productos.nombre,
                    productos.imagen
                FROM detalle_pedidos
                LEFT JOIN productos
                ON detalle_pedidos.producto_id = productos.id
                WHERE detalle_pedidos.pedido_id = ?";

$stmtProductos = mysqli_prepare($conn, $sqlProductos);

mysqli_stmt_bind_param($stmtProductos, "i", $id);

mysqli_stmt_execute($stmtProductos);

$productos = mysqli_stmt_get_result($stmtProductos);

$telefonoWhatsApp = "";

if(!empty($pedido['telefono_cliente'])){
    $telefonoWhatsApp = preg_replace('/[^0-9]/', '', $pedido['telefono_cliente']);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Pedido #<?= $pedido['id'] ?> | Admin
    </title>

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

        <nav class="admin-menu">

            <a href="index.php">
                🏠 Dashboard
            </a>

            <a href="productos.php">
                📦 Productos
            </a>

            <a href="pedidos.php"
               class="activo-admin">
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

        <!-- HERO -->
        <section class="admin-hero small-hero pedido-hero">

            <div>

                <span class="admin-badge">
                    Detalle de pedido
                </span>

                <h1>
                    Pedido #<?= $pedido['id'] ?>
                </h1>

                <p>
                    Cliente:
                    <?= $pedido['nombre_cliente'] ?: ($pedido['cliente'] ?? 'Cliente') ?>
                </p>

            </div>

            <div class="admin-hero-actions">

                <a href="pedidos.php"
                   class="btn-admin-secundario">
                    ← Volver
                </a>

            </div>

        </section>

        <!-- ESTADO -->
        <section class="pedido-panel">

            <div class="pedido-panel-header">

                <div>
                    <h2>Estado del pedido</h2>
                    <p>Actualiza el avance del pedido.</p>
                </div>

                <span class="estado <?= strtolower($pedido['estado']) ?>">
                    <?= ucfirst($pedido['estado']) ?>
                </span>

            </div>

            <form method="POST"
                  class="estado-form">

                <select name="estado"
                        class="input-cupon">

                    <option value="Pendiente"
                        <?= $pedido['estado'] == 'Pendiente' ? 'selected' : '' ?>>
                        Pendiente
                    </option>

                    <option value="Pagado"
                        <?= $pedido['estado'] == 'Pagado' ? 'selected' : '' ?>>
                        Pagado
                    </option>

                    <option value="Enviado"
                        <?= $pedido['estado'] == 'Enviado' ? 'selected' : '' ?>>
                        Enviado
                    </option>

                    <option value="Entregado"
                        <?= $pedido['estado'] == 'Entregado' ? 'selected' : '' ?>>
                        Entregado
                    </option>

                    <option value="Cancelado"
                        <?= $pedido['estado'] == 'Cancelado' ? 'selected' : '' ?>>
                        Cancelado
                    </option>

                </select>

                <button type="submit"
                        name="actualizar_estado"
                        class="btn-admin-agregar">
                    Guardar estado
                </button>

            </form>

        </section>

        <!-- GRID DETALLE -->
        <section class="pedido-grid">

            <!-- CLIENTE -->
            <div class="pedido-card">

                <h2>Datos del cliente</h2>

                <div class="pedido-dato">
                    <span>👤 Nombre</span>
                    <strong><?= $pedido['nombre_cliente'] ?? '-' ?></strong>
                </div>

                <div class="pedido-dato">
                    <span>📞 Teléfono</span>
                    <strong><?= $pedido['telefono_cliente'] ?? '-' ?></strong>
                </div>

                <div class="pedido-dato">
                    <span>📍 Dirección</span>
                    <strong><?= $pedido['direccion_envio'] ?? '-' ?></strong>
                </div>

                <div class="pedido-dato">
                    <span>💳 Método de pago</span>
                    <strong><?= ucfirst($pedido['metodo_pago'] ?? '-') ?></strong>
                </div>

                <?php if(!empty($telefonoWhatsApp)): ?>

                    <a href="https://wa.me/54<?= $telefonoWhatsApp ?>?text=Hola%20<?= urlencode($pedido['nombre_cliente']) ?>,%20te%20contactamos%20desde%20SportStyle%20por%20tu%20pedido%20%23<?= $pedido['id'] ?>"
                       target="_blank"
                       class="btn-whatsapp-admin">
                        📲 Contactar por WhatsApp
                    </a>

                <?php endif; ?>

            </div>

            <!-- RESUMEN -->
            <div class="pedido-card resumen-pedido-card">

                <h2>Resumen de compra</h2>

                <div class="pedido-total-linea">
                    <span>Subtotal</span>
                    <strong>
                        $<?= number_format($pedido['subtotal'] ?? 0, 0, ',', '.') ?>
                    </strong>
                </div>

                <div class="pedido-total-linea">
                    <span>Envío</span>
                    <strong>
                        $<?= number_format($pedido['costo_envio'] ?? 0, 0, ',', '.') ?>
                    </strong>
                </div>

                <div class="pedido-total-linea">
                    <span>Código postal</span>
                    <strong>
                        <?= $pedido['codigo_postal'] ?? 'Sin CP' ?>
                    </strong>
                </div>

                <div class="pedido-total-linea">
                    <span>Zona</span>
                    <strong>
                        <?= $pedido['zona_envio'] ?? 'Sin zona' ?>
                    </strong>
                </div>

                <div class="pedido-total-final">
                    <span>Total</span>
                    <strong>
                        $<?= number_format($pedido['total'] ?? 0, 0, ',', '.') ?>
                    </strong>
                </div>

                <p class="pedido-fecha">
                    Fecha: <?= date("d/m/Y", strtotime($pedido['fecha'])) ?>
                </p>

            </div>

        </section>

        <!-- PRODUCTOS -->
        <section class="admin-section">

            <div class="admin-section-title">

                <div>
                    <h2>Productos del pedido</h2>
                    <p>Detalle de productos comprados.</p>
                </div>

            </div>

            <div class="tabla-admin tabla-premium">

                <table>

                    <thead>

                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while($p = mysqli_fetch_assoc($productos)): ?>

                        <tr>

                            <td>

                                <img src="../<?= $p['imagen'] ?>"
                                     class="admin-img-producto">

                            </td>

                            <td>
                                <strong><?= $p['nombre'] ?></strong>
                            </td>

                            <td>
                                <?= $p['cantidad'] ?>
                            </td>

                            <td>
                                $<?= number_format($p['precio'], 0, ',', '.') ?>
                            </td>

                            <td>
                                <strong>
                                    $<?= number_format(
                                        $p['precio'] * $p['cantidad'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </strong>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>