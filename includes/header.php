<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include_once(__DIR__ . "/../config/conexion.php");
include_once(__DIR__ . "/../config/config.php");
include_once(__DIR__ . "/../data/carrito_helpers.php");

/* ===== PRODUCTOS MYSQL PARA CARRITO ===== */
$sqlProductosHeader = "SELECT * FROM productos";

$resultadoProductosHeader = mysqli_query(
    $conn,
    $sqlProductosHeader
);

$productos = [];

while($filaHeader = mysqli_fetch_assoc($resultadoProductosHeader)){

    $productos[] = $filaHeader;

}

/* ===== CARRITO ===== */
$cantidadItems =
    isset($_SESSION['carrito'])
    ? cantidadItems($_SESSION['carrito'])
    : 0;

/* ===== TRAER MARCAS ===== */
$sqlMarcas = "SELECT * FROM marcas ORDER BY nombre ASC";

$resultadoMarcas = mysqli_query($conn, $sqlMarcas);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($NOMBRE_TIENDA ?? 'SportStyle') ?></title>

    <link rel="stylesheet"
          href="/sportstyle/css/estilos.css">
          <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<!-- ===== OVERLAY ===== -->
<div class="overlay"
     id="overlay"
     onclick="toggleCarrito()">
</div>

<!-- ===== HEADER ===== -->
<header class="header">

    <!-- LOGO -->
    <div class="logo">
        <?= htmlspecialchars($NOMBRE_TIENDA ?? 'SportStyle') ?>
    </div>

    <!-- ===== NAV ===== -->
    <nav class="nav">

        <!-- INICIO -->
        <a href="/sportstyle/index.php"
           class="nav-link">

           Inicio

        </a>

        <!-- ===== HOMBRE ===== -->
        <div class="nav-item">

            <a href="/sportstyle/productos.php?genero=Hombre"
               class="nav-link">

               Hombre

            </a>

            <div class="mega-menu">

                <div class="mega-col">

                    <h4>Calzado</h4>

                    <a href="/sportstyle/productos.php?genero=Hombre&categoria_id=1">
                        Running
                    </a>

                    <a href="/sportstyle/productos.php?genero=Hombre&categoria_id=1">
                        Training
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Ropa</h4>

                    <a href="/sportstyle/productos.php?genero=Hombre&categoria_id=2">
                        Remeras
                    </a>

                    <a href="/sportstyle/productos.php?genero=Hombre&categoria_id=3">
                        Shorts
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Accesorios</h4>

                    <a href="/sportstyle/productos.php?genero=Hombre&categoria_id=4">
                        Mochilas
                    </a>

                </div>

            </div>

        </div>

        <!-- ===== MUJER ===== -->
        <div class="nav-item">

            <a href="/sportstyle/productos.php?genero=Mujer"
               class="nav-link">

               Mujer

            </a>

            <div class="mega-menu">

                <div class="mega-col">

                    <h4>Calzado</h4>

                    <a href="/sportstyle/productos.php?genero=Mujer&categoria_id=1">
                        Running
                    </a>

                    <a href="/sportstyle/productos.php?genero=Mujer&categoria_id=1">
                        Training
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Ropa</h4>

                    <a href="/sportstyle/productos.php?genero=Mujer&categoria_id=2">
                        Tops
                    </a>

                    <a href="/sportstyle/productos.php?genero=Mujer&categoria_id=3">
                        Leggings
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Accesorios</h4>

                    <a href="/sportstyle/productos.php?genero=Mujer&categoria_id=4">
                        Bolsos
                    </a>

                </div>

            </div>

        </div>

        <!-- ===== NIÑOS ===== -->
        <div class="nav-item">

            <a href="/sportstyle/productos.php?genero=Niños"
               class="nav-link">

               Niños

            </a>

            <div class="mega-menu">

                <div class="mega-col">

                    <h4>Calzado</h4>

                    <a href="/sportstyle/productos.php?genero=Niños&categoria_id=1">
                        Zapatillas
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Ropa</h4>

                    <a href="/sportstyle/productos.php?genero=Niños&categoria_id=2">
                        Remeras
                    </a>

                    <a href="/sportstyle/productos.php?genero=Niños&categoria_id=3">
                        Shorts
                    </a>

                </div>

                <div class="mega-col">

                    <h4>Accesorios</h4>

                    <a href="/sportstyle/productos.php?genero=Niños&categoria_id=4">
                        Accesorios
                    </a>

                </div>

            </div>

        </div>

        <!-- ===== MARCAS ===== -->
        <div class="nav-item">

            <a href="#"
               class="nav-link">

               Marcas

            </a>

            <div class="mega-menu">

                <?php while($marca = mysqli_fetch_assoc($resultadoMarcas)): ?>

                    <div class="mega-col">

                        <a href="/sportstyle/productos.php?marca_id=<?= $marca['id'] ?>">

                            <?= $marca['nombre'] ?>

                        </a>

                    </div>

                <?php endwhile; ?>

            </div>

        </div>

        <!-- ===== SALE ===== -->
        <a href="/sportstyle/productos.php?sale=1"
           class="nav-link nav-sale">

           SALE

        </a>

    </nav>

    <!-- ===== BUSCADOR ===== -->
    <form action="/sportstyle/productos.php"
          method="GET"
          class="buscador">

        <input type="text"
               name="buscar"
               placeholder="Buscar productos..."
               class="input-buscador">

        <button type="submit"
                class="btn-buscador">

            🔍

        </button>

    </form>

    <!-- ===== ACCIONES ===== -->
    <div class="acciones-header">

        <?php if(isset($_SESSION['usuario_nombre'])): ?>

            <!-- USUARIO -->
            <span class="nav-link">

                👋 Hola <?= $_SESSION['usuario_nombre'] ?>

            </span>

            <span class="rol-badge-header">
                <?= ($_SESSION['usuario_rol'] ?? 'cliente') === 'admin' ? 'Administrador' : 'Cliente' ?>
            </span>

            <!-- PANEL ADMIN SOLO ADMIN -->
            <?php if(
                isset($_SESSION['usuario_rol'])
                &&
                $_SESSION['usuario_rol'] === 'admin'
            ): ?>

                <a href="/sportstyle/admin/index.php"
                   class="btn-admin-volver">

                   ⚙️ Panel Admin

                </a>

            <?php endif; ?>

            <!-- LOGOUT -->
            <a href="/sportstyle/logout.php"
               class="nav-link">

               Cerrar sesión

            </a>

        <?php else: ?>

            <!-- LOGIN -->
            <a href="/sportstyle/login.php"
               class="nav-link">

               👤 Mi cuenta

            </a>

        <?php endif; ?>

        <!-- CONTACTO -->
        <a href="/sportstyle/contacto.php"
           class="nav-link">

           Contacto

        </a>

        <!-- CARRITO -->
        <a href="#"
           class="carrito-icono"
           onclick="toggleCarrito(); return false;">

           🛒

                <span class="carrito-badge <?= $cantidadItems > 0 ? '' : 'oculto' ?>"
                      id="carrito-badge">

                    <?= $cantidadItems > 0 ? $cantidadItems : '' ?>

                </span>

        </a>

    </div>

</header>

<!-- ===== CARRITO LATERAL ===== -->
<div id="carrito-lateral"
     class="carrito-lateral">

    <h2>

        Tu Carrito

        <button class="cerrar-carrito"
                onclick="toggleCarrito()">

            ✕

        </button>

    </h2>

    <?php if(isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0): ?>

        <?php foreach($_SESSION['carrito'] as $id => $cantidad): ?>

            <?php foreach($productos as $p): ?>

                <?php if($p['id'] == $id): ?>

                    <div class="item-carrito">

                        <div>

                            <p><?= $p['nombre'] ?></p>

                            <small>

                                Cantidad: <?= $cantidad ?>

                            </small>

                        </div>

                        <div style="text-align:right">

                            <span>

                                $<?= number_format($p['precio'] * $cantidad, 0, ',', '.') ?>

                            </span>

                            <br>

                            <a href="/sportstyle/carrito.php?eliminar=<?= $p['id'] ?>"
                               class="btn-eliminar">

                               ❌

                            </a>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        <?php endforeach; ?>

        <div class="carrito-lateral-footer">

            <h3>

                Total:
                $<?= number_format(calcularTotal($_SESSION['carrito'], $productos), 0, ',', '.') ?>

            </h3>

            <a href="/sportstyle/carrito.php"
               class="btn"
               style="display:block; text-align:center; margin-top:10px;">

                Ver carrito completo

            </a>

        </div>

    <?php else: ?>

        <p style="color:#aaa; text-align:center; margin-top:40px;">

            Tu carrito está vacío 🛒

        </p>

    <?php endif; ?>

</div>

<!-- ===== SCRIPT ===== -->
<script>

const BASE_URL = "/sportstyle/";

function toggleCarrito(){

    document
        .getElementById("carrito-lateral")
        .classList
        .toggle("activo");

    document
        .getElementById("overlay")
        .classList
        .toggle("activo");

}

</script>
