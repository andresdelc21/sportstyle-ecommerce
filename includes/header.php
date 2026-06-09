<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include_once(__DIR__ . "/../config/conexion.php");
include_once(__DIR__ . "/../config/config.php");
include_once(__DIR__ . "/../data/carrito_helpers.php");
include_once(__DIR__ . "/csrf.php");

/* ===== PRODUCTOS MYSQL PARA CARRITO ===== */
$sqlProductosHeader = "SELECT * FROM productos WHERE activo = 1";

$resultadoProductosHeader = mysqli_query(
    $conn,
    $sqlProductosHeader
);

$productos = [];

while($filaHeader = mysqli_fetch_assoc($resultadoProductosHeader)){

    $productos[] = $filaHeader;

}

$tallesHeader = [];
$resultadoTallesHeader = mysqli_query($conn, "SELECT * FROM producto_talles");

if($resultadoTallesHeader){
    while($talleHeader = mysqli_fetch_assoc($resultadoTallesHeader)){
        $tallesHeader[(int) $talleHeader['id']] = $talleHeader;
    }
}

/* ===== CARRITO ===== */
$cantidadItems =
    isset($_SESSION['carrito'])
    ? cantidadItems($_SESSION['carrito'])
    : 0;

/* ===== TRAER MARCAS ===== */
$sqlMarcas = "SELECT * FROM marcas ORDER BY nombre ASC";

$resultadoMarcas = mysqli_query($conn, $sqlMarcas);

/* ===== CATEGORÍAS PARA MENÚ ===== */
$sqlCategoriasHeader = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";

$resultadoCategoriasHeader = mysqli_query($conn, $sqlCategoriasHeader);

$categoriasHeader = [];

if($resultadoCategoriasHeader){

    while($categoriaHeader = mysqli_fetch_assoc($resultadoCategoriasHeader)){

        $categoriasHeader[strtolower($categoriaHeader['nombre'])] = $categoriaHeader;

    }

}

$sqlSubcategoriasHeader = "SELECT id, categoria_id, nombre FROM subcategorias ORDER BY nombre ASC";
$resultadoSubcategoriasHeader = mysqli_query($conn, $sqlSubcategoriasHeader);
$subcategoriasHeader = [];
$subcategoriasPorCategoriaHeader = [];

if($resultadoSubcategoriasHeader){
    while($subcategoriaHeader = mysqli_fetch_assoc($resultadoSubcategoriasHeader)){
        $subcategoriasHeader[strtolower($subcategoriaHeader['nombre'])] = $subcategoriaHeader;
        $subcategoriasPorCategoriaHeader[(int) $subcategoriaHeader['categoria_id']][] = $subcategoriaHeader;
    }
}

function renderMenuCategoria(
    array $categoriasHeader,
    array $subcategoriasPorCategoriaHeader,
    string $categoriaNombre,
    string $genero
): void {

    $categoria = $categoriasHeader[strtolower($categoriaNombre)] ?? null;
    $subcategorias = $categoria
        ? ($subcategoriasPorCategoriaHeader[(int) $categoria['id']] ?? [])
        : [];

    echo '<div class="mega-col">';
    echo '<h4>' . htmlspecialchars($categoriaNombre) . '</h4>';

    if($categoria){
        echo '<a class="mega-todos" href="' . htmlspecialchars(categoriaMenuUrl($categoriasHeader, $categoriaNombre, $genero)) . '">Ver todo</a>';
    }

    foreach($subcategorias as $subcategoria){
        echo '<a href="' . htmlspecialchars(categoriaMenuUrl($categoriasHeader, $subcategoria['nombre'], $genero)) . '">';
        echo htmlspecialchars($subcategoria['nombre']);
        echo '</a>';
    }

    echo '</div>';

}

function categoriaMenuUrl(array $categoriasHeader, string $nombre, string $genero = ''): string {

    global $subcategoriasHeader;

    $categoria = $categoriasHeader[strtolower($nombre)] ?? null;
    $subcategoria = $subcategoriasHeader[strtolower($nombre)] ?? null;
    $url = "/sportstyle/productos.php";
    $params = [];

    if($genero !== ''){
        $params['genero'] = $genero;
    }

    if($categoria){
        $params['categoria_id'] = $categoria['id'];
    } elseif($subcategoria){
        $params['subcategoria_id'] = $subcategoria['id'];
    } else {
        $params['buscar'] = $nombre;
    }

    return $url . '?' . http_build_query($params);

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="<?= htmlspecialchars(csrfToken()) ?>">

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
    <?php
        $nombreTiendaHeader = $NOMBRE_TIENDA ?? 'SportStyle';
        $textoLogoHeader = stripos($nombreTiendaHeader, 'sportstyle') === 0
            ? substr($nombreTiendaHeader, 1)
            : $nombreTiendaHeader;
    ?>

    <a href="/sportstyle/index.php" class="logo" aria-label="Ir al inicio de SportStyle">
        <span class="logo-mark">S</span>
        <span class="logo-text"><?= htmlspecialchars($textoLogoHeader) ?></span>
    </a>

    <button type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Abrir menú"
            aria-expanded="false">

        ☰

    </button>

    <!-- ===== NAV ===== -->
    <nav class="nav"
         id="menuPrincipal">

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

                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Calzado', 'Hombre'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Ropa', 'Hombre'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Accesorios', 'Hombre'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Deportes', 'Hombre'); ?>

            </div>

        </div>

        <!-- ===== MUJER ===== -->
        <div class="nav-item">

            <a href="/sportstyle/productos.php?genero=Mujer"
               class="nav-link">

               Mujer

            </a>

            <div class="mega-menu">

                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Calzado', 'Mujer'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Ropa', 'Mujer'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Accesorios', 'Mujer'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Deportes', 'Mujer'); ?>

            </div>

        </div>

        <!-- ===== NIÑOS ===== -->
        <div class="nav-item">

            <a href="/sportstyle/productos.php?genero=Niños"
               class="nav-link">

               Niños

            </a>

            <div class="mega-menu">

                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Calzado', 'Niños'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Ropa', 'Niños'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Accesorios', 'Niños'); ?>
                <?php renderMenuCategoria($categoriasHeader, $subcategoriasPorCategoriaHeader, 'Deportes', 'Niños'); ?>

            </div>

        </div>

        <!-- ===== MARCAS ===== -->
        <div class="nav-item nav-marcas">

            <a href="#"
               class="nav-link">

               Marcas

            </a>

            <div class="mega-menu">

                <?php while($marca = mysqli_fetch_assoc($resultadoMarcas)): ?>

                    <div class="mega-col">

                        <a href="/sportstyle/productos.php?marca_id=<?= $marca['id'] ?>">

                            <?php if(!empty($marca['logo'])): ?>
                                <span class="marca-logo-box">
                                    <img src="/sportstyle/<?= htmlspecialchars($marca['logo']) ?>"
                                         alt="<?= htmlspecialchars($marca['nombre']) ?>">
                                </span>
                            <?php endif; ?>

                            <span><?= htmlspecialchars($marca['nombre']) ?></span>

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

            <div class="user-menu">

                <button type="button"
                        class="user-menu-btn"
                        aria-haspopup="true">

                    Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                    <span>⌄</span>

                </button>

                <div class="user-dropdown">

                    <a href="/sportstyle/favoritos.php">
                        Favoritos
                    </a>

                    <?php if(
                        isset($_SESSION['usuario_rol'])
                        &&
                        $_SESSION['usuario_rol'] === 'admin'
                    ): ?>

                        <a href="/sportstyle/admin/index.php">
                            Panel de gestión
                        </a>

                    <?php endif; ?>

                    <a href="/sportstyle/logout.php">
                        Cerrar sesión
                    </a>

                </div>

            </div>

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

        <?php foreach($_SESSION['carrito'] as $key => $cantidad): ?>

            <?php $id = carritoProductoId($key); $talleId = carritoTalleId($key); ?>

            <?php foreach($productos as $p): ?>

                <?php if($p['id'] == $id): ?>

                    <div class="item-carrito">

                        <div>

                            <p><?= $p['nombre'] ?></p>

                            <small>

                                Cantidad: <?= $cantidad ?>

                            </small>

                            <?php if($talleId && isset($tallesHeader[$talleId])): ?>
                                <small>
                                    Talle: <?= htmlspecialchars(talleLabel($tallesHeader[$talleId])) ?>
                                </small>
                            <?php endif; ?>

                        </div>

                        <div style="text-align:right">

                            <span>

                                $<?= number_format($p['precio'] * $cantidad, 0, ',', '.') ?>

                            </span>

                            <br>

                            <a href="/sportstyle/carrito.php?eliminar=<?= urlencode($key) ?>"
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
