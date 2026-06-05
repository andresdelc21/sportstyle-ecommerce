<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";

require_once __DIR__ . '/../config/conexion.php';

/* PROTEGER PANEL */
if(!isset($_SESSION['usuario_nombre'])){

    header("Location: ../login.php");
    exit;

}

/* TRAER PEDIDOS REALES */
$sql = "SELECT
            pedidos.id,
            pedidos.subtotal,
            pedidos.costo_envio,
            pedidos.codigo_postal,
            pedidos.zona_envio,
            pedidos.total,
            pedidos.estado,
            pedidos.fecha,
            pedidos.nombre_cliente,
            usuarios.nombre AS cliente
        FROM pedidos
        LEFT JOIN usuarios
        ON pedidos.usuario_id = usuarios.id
        ORDER BY pedidos.id DESC";

$resultado = mysqli_query($conn, $sql);

/* CONTADORES */
$totalPedidos = 0;
$pendientes = 0;
$pagados = 0;
$enviados = 0;

if($resultado){

    $totalPedidos = mysqli_num_rows($resultado);

    while($estado = mysqli_fetch_assoc($resultado)){

        $estadoPedido = strtolower($estado['estado']);

        if($estadoPedido == "pendiente"){
            $pendientes++;
        }

        if($estadoPedido == "pagado"){
            $pagados++;
        }

        if($estadoPedido == "enviado"){
            $enviados++;
        }

    }

    mysqli_data_seek($resultado, 0);

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Pedidos | Admin
    </title>

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
                    Gestión Ecommerce
                </span>

                <h1>
                    Pedidos de clientes 🧾
                </h1>

                <p>
                    Controla estados, pagos, envíos y actividad reciente.
                </p>

            </div>

        </section>

        <!-- RESUMEN -->
        <div class="admin-metricas mini-metricas">

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    🧾
                </div>

                <div>

                    <span>Total pedidos</span>

                    <h2>
                        <?= $totalPedidos ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card alerta">

                <div class="metrica-icono">
                    ⏳
                </div>

                <div>

                    <span>Pendientes</span>

                    <h2>
                        <?= $pendientes ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card venta">

                <div class="metrica-icono">
                    ✅
                </div>

                <div>

                    <span>Pagados</span>

                    <h2>
                        <?= $pagados ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    🚚
                </div>

                <div>

                    <span>Enviados</span>

                    <h2>
                        <?= $enviados ?>
                    </h2>

                </div>

            </div>

        </div>

        <!-- BUSCADOR -->
        <div class="admin-search-box">

            <input type="text"
                   id="buscarPedido"
                   placeholder="Buscar pedido o cliente...">

        </div>

        <!-- TABLA -->
        <div class="tabla-admin tabla-premium">

            <table id="tablaPedidos">

                <thead>

                    <tr>

                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Subtotal</th>
                        <th>Envío</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php if($resultado && mysqli_num_rows($resultado) > 0): ?>

                    <?php while($p = mysqli_fetch_assoc($resultado)): ?>

                    <tr>

                        <td>
                            #<?= $p['id'] ?>
                        </td>

                        <td>
                            <?= $p['nombre_cliente'] ?: ($p['cliente'] ?? 'Cliente') ?>
                        </td>

                        <td>
                            $<?= number_format($p['subtotal'] ?? 0, 0, ',', '.') ?>
                        </td>

                        <td>
                            $<?= number_format($p['costo_envio'] ?? 0, 0, ',', '.') ?>
                        </td>

                        <td>
                            <strong>
                                $<?= number_format($p['total'] ?? 0, 0, ',', '.') ?>
                            </strong>
                        </td>

                        <td>

                            <?php if($p['estado'] == "Pendiente" || $p['estado'] == "pendiente"): ?>

                                <span class="estado pendiente">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php elseif($p['estado'] == "Pagado" || $p['estado'] == "pagado"): ?>

                                <span class="estado pagado">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php elseif($p['estado'] == "Enviado" || $p['estado'] == "enviado"): ?>

                                <span class="estado enviado">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php elseif($p['estado'] == "Entregado" || $p['estado'] == "entregado"): ?>

                                <span class="estado pagado">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php elseif($p['estado'] == "Cancelado" || $p['estado'] == "cancelado"): ?>

                                <span class="estado cancelado">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php else: ?>

                                <span class="estado pendiente">
                                    <?= ucfirst($p['estado']) ?>
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= date("d/m/Y", strtotime($p['fecha'])) ?>
                        </td>

                        <td class="acciones-tabla">

                            <a href="ver_pedido.php?id=<?= $p['id'] ?>"
                               class="btn-tabla editar">

                               👁️

                            </a>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="8"
                            style="text-align:center; padding:30px;">

                            No hay pedidos registrados

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

<script>

const buscador = document.getElementById("buscarPedido");

buscador.addEventListener("keyup", function(){

    const valor = this.value.toLowerCase();

    const filas = document.querySelectorAll(
        "#tablaPedidos tbody tr"
    );

    filas.forEach(fila => {

        fila.style.display =
            fila.innerText.toLowerCase().includes(valor)
            ? ""
            : "none";

    });

});

</script>
<script src="../java/admin.js"></script>
</body>
</html>
