<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";

/* PROTEGER PANEL */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* OBTENER USUARIOS */
$sql = "SELECT * FROM usuarios ORDER BY id DESC";
$resultado = mysqli_query($conn, $sql);

$usuarios = [];

while($u = mysqli_fetch_assoc($resultado)){
    $usuarios[] = $u;
}

/* CONTADORES */
$totalUsuarios = count($usuarios);
$totalAdmins = 0;
$totalClientes = 0;

foreach($usuarios as $u){

    if(isset($u['rol']) && strtolower($u['rol']) === 'admin'){
        $totalAdmins++;
    } else {
        $totalClientes++;
    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Administrar Usuarios</title>

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

            <a href="index.php">
                🏠 Dashboard
            </a>

            <a href="productos.php">
                📦 Productos
            </a>

            <a href="pedidos.php">
                🧾 Pedidos
            </a>

            <a href="usuarios.php"
               class="activo-admin">
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
        <section class="admin-hero small-hero">

            <div>

                <span class="admin-badge">
                    Comunidad SportStyle
                </span>

                <h1>
                    Usuarios 👥
                </h1>

                <p>
                    Consulta clientes registrados y administradores de la tienda.
                </p>

            </div>

        </section>

        <!-- RESUMEN -->
        <div class="admin-metricas mini-metricas">

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    👥
                </div>

                <div>

                    <span>Total usuarios</span>

                    <h2>
                        <?= $totalUsuarios ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card venta">

                <div class="metrica-icono">
                    🛡️
                </div>

                <div>

                    <span>Administradores</span>

                    <h2>
                        <?= $totalAdmins ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    🛍️
                </div>

                <div>

                    <span>Clientes</span>

                    <h2>
                        <?= $totalClientes ?>
                    </h2>

                </div>

            </div>

        </div>

        <!-- BUSCADOR -->
        <div class="admin-search-box">

            <input type="text"
                   id="buscarUsuario"
                   placeholder="Buscar usuario por nombre, email o rol...">

        </div>

        <!-- TABLA -->
        <div class="tabla-admin tabla-premium">

            <table id="tablaUsuarios">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php if(count($usuarios) > 0): ?>

                    <?php foreach($usuarios as $u): ?>

                        <tr>

                            <td>
                                #<?= $u['id'] ?>
                            </td>

                            <td>

                                <div class="usuario-admin-info">

                                    <div class="usuario-avatar">

                                        <?= strtoupper(substr($u['nombre'], 0, 1)) ?>

                                    </div>

                                    <strong>
                                        <?= $u['nombre'] ?>
                                    </strong>

                                </div>

                            </td>

                            <td>
                                <?= $u['email'] ?>
                            </td>

                            <td>

                                <?php if(isset($u['rol']) && strtolower($u['rol']) === 'admin'): ?>

                                    <span class="estado pagado">
                                        Admin
                                    </span>

                                <?php else: ?>

                                    <span class="estado pendiente">
                                        Cliente
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= date("d/m/Y", strtotime($u['fecha_registro'])) ?>
                            </td>

                            <td class="acciones-tabla">

                                <a href="#"
                                   class="btn-tabla editar"
                                   title="Editar usuario">

                                   ✏️

                                </a>

                                <a href="#"
                                   class="btn-tabla eliminar"
                                   title="Eliminar usuario"
                                   onclick="return confirm('¿Eliminar usuario?')">

                                   ❌

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6"
                            style="text-align:center; padding:30px;">

                            No hay usuarios registrados

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

<script>

const buscadorUsuario = document.getElementById("buscarUsuario");

buscadorUsuario.addEventListener("keyup", function(){

    const valor = this.value.toLowerCase();

    const filas = document.querySelectorAll(
        "#tablaUsuarios tbody tr"
    );

    filas.forEach(fila => {

        fila.style.display =
            fila.innerText.toLowerCase().includes(valor)
            ? ""
            : "none";

    });

});

</script>

</body>
</html>