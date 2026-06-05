<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include("config/conexion.php");

/* PRODUCTOS DESTACADOS */
$sqlDestacados = "SELECT
                    productos.*,
                    categorias.nombre AS categoria_nombre,
                    marcas.nombre AS marca_nombre
                  FROM productos
                  LEFT JOIN categorias
                  ON productos.categoria_id = categorias.id
                  LEFT JOIN marcas
                  ON productos.marca_id = marcas.id
                  ORDER BY productos.id DESC
                  LIMIT 5";

$resultadoDestacados = mysqli_query($conn, $sqlDestacados);

/* BANNER PRINCIPAL */
$bannerPrincipal = null;

$sqlBanner = "SELECT *
              FROM banners
              WHERE activo = 1
              ORDER BY id DESC
              LIMIT 1";

$resultadoBanner = mysqli_query($conn, $sqlBanner);

if($resultadoBanner && mysqli_num_rows($resultadoBanner) > 0){
    $bannerPrincipal = mysqli_fetch_assoc($resultadoBanner);
}

/* FAVORITOS DEL USUARIO */
$favoritosUsuario = [];

if(isset($_SESSION['usuario_id'])){

    $usuario_id = (int) $_SESSION['usuario_id'];

    $sqlFavoritos = "SELECT producto_id
                     FROM favoritos
                     WHERE usuario_id = ?";

    $stmtFavoritos = mysqli_prepare($conn, $sqlFavoritos);

    mysqli_stmt_bind_param(
        $stmtFavoritos,
        "i",
        $usuario_id
    );

    mysqli_stmt_execute($stmtFavoritos);

    $resultadoFavoritos = mysqli_stmt_get_result($stmtFavoritos);

    while($favorito = mysqli_fetch_assoc($resultadoFavoritos)){

        $favoritosUsuario[] = (int) $favorito['producto_id'];

    }

}

include("includes/header.php");

?>

<?php

$heroTitulo = $bannerPrincipal['titulo'] ?? 'Nueva colección deportiva';
$heroSubtitulo = $bannerPrincipal['subtitulo'] ?? 'Indumentaria, calzado y accesorios para entrenar con estilo, comodidad y rendimiento.';
$heroImagen = $bannerPrincipal['imagen'] ?? 'img/pro.jpg';
$heroEnlace = !empty($bannerPrincipal['enlace'])
    ? $bannerPrincipal['enlace']
    : 'productos.php';

?>

<section class="hero"
         style="background: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.7)), url('<?= htmlspecialchars($heroImagen) ?>') center/cover no-repeat;">

    <div class="hero-overlay"></div>

    <div class="hero-contenido">

        <span class="hero-badge">SportStyle 2026</span>

        <h1><?= htmlspecialchars($heroTitulo) ?></h1>

        <p>
            <?= htmlspecialchars($heroSubtitulo) ?>
        </p>

        <div class="hero-actions">

            <a href="<?= htmlspecialchars($heroEnlace) ?>" class="btn-hero">
                Ver productos
            </a>

            <a href="productos.php?genero=Hombre" class="btn-hero-secundario">
                Explorar colección
            </a>

        </div>

    </div>

</section>

<section class="home-intro">

    <span>🔥 Nuevos ingresos</span>

    <h2>Productos destacados</h2>

    <p>
        Selección de productos recientes disponibles en SportStyle.
    </p>

</section>

<section class="productos-destacados">

    <div class="grid">

        <?php if(mysqli_num_rows($resultadoDestacados) > 0): ?>

            <?php while($p = mysqli_fetch_assoc($resultadoDestacados)): ?>

                <div class="card-v2 <?= $p['stock'] <= 0 ? 'card-sin-stock' : '' ?>"
                     onclick="window.location='detalle.php?id=<?= $p['id'] ?>'">

                    <div class="card-v2-img">

                        <img src="<?= $p['imagen'] ?>"
                             alt="<?= $p['nombre'] ?>">

                        <?php if($p['stock'] <= 0): ?>

                            <span class="stock-overlay">
                                Sin stock
                            </span>

                        <?php endif; ?>

                    </div>
                    <div class="card-v2-acciones">

                        <a href="#"
                           class="accion-btn btn-favorito-js <?= in_array((int) $p['id'], $favoritosUsuario, true) ? 'favorito-activo' : '' ?>"
                           data-producto="<?= $p['id'] ?>"
                           onclick="event.stopPropagation()"
                           title="Favoritos"
                           aria-label="Agregar o quitar favorito">

                            ❤️

                        </a>

                    </div>

                    <div class="card-v2-info">

                        <div class="card-v2-tags">

                            <?php if(!empty($p['categoria_nombre'])): ?>

                                <span class="badge-categoria">
                                    <?= $p['categoria_nombre'] ?>
                                </span>

                            <?php endif; ?>

                            <?php if(!empty($p['marca_nombre'])): ?>

                                <span class="badge-genero">
                                    <?= $p['marca_nombre'] ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <h3 class="card-v2-nombre">
                            <?= $p['nombre'] ?>
                        </h3>

                        <div class="card-v2-precio">

                            <span class="precio-actual">
                                $<?= number_format($p['precio'], 0, ',', '.') ?>
                            </span>

                        </div>

                        <div class="card-v2-botones">

                            <?php if($p['stock'] > 0): ?>

                                <a href="carrito.php?agregar=<?= $p['id'] ?>"
                                   class="btn-card btn-agregar-carrito-js"
                                   data-producto="<?= $p['id'] ?>"
                                   onclick="event.stopPropagation()">
                                    🛒
                                </a>

                            <?php else: ?>

                                <span class="sin-stock">
                                    Sin stock
                                </span>

                            <?php endif; ?>

                            <a href="detalle.php?id=<?= $p['id'] ?>"
                               class="btn-card btn-detalle"
                               onclick="event.stopPropagation()">
                                👁️
                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="home-empty">
                Todavía no hay productos destacados cargados.
            </p>

        <?php endif; ?>

    </div>

</section>

<section class="home-beneficios-pro">

    <div class="beneficio-pro">
        <strong>🚚 Envíos</strong>
        <span>Consultá disponibilidad y tiempos según tu zona.</span>
    </div>

    <div class="beneficio-pro">
        <strong>💳 Pagos</strong>
        <span>Opciones simples para comprar con seguridad.</span>
    </div>

    <div class="beneficio-pro">
        <strong>🔒 Compra segura</strong>
        <span>Tu pedido queda registrado y protegido.</span>
    </div>

    <div class="beneficio-pro">
        <strong>↩️ Cambios</strong>
        <span>Consultá cambios por talle, producto o disponibilidad.</span>
    </div>

</section>

<section class="banner-promo">

    <div class="banner-contenido">

        <span class="badge-oferta">
            🔥 OFERTA LIMITADA
        </span>

        <h2>
            Hasta 70% OFF
        </h2>

        <p>
            En indumentaria deportiva seleccionada.
        </p>

        <span class="fecha">
            Promociones disponibles por tiempo limitado.
        </span>

        <a href="productos.php"
           class="btn-banner">
           Ver ofertas
        </a>

    </div>

</section>

<?php include("includes/footer.php"); ?>
