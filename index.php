<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include("config/conexion.php");

/* CATEGORÍAS PARA PORTADA */
$categoriasHome = [];

$sqlCategoriasHome = "SELECT
                        categorias.id,
                        categorias.nombre,
                        COALESCE(
                            (
                                SELECT ip.imagen
                                FROM imagenes_productos ip
                                WHERE ip.producto_id = productos.id
                                ORDER BY ip.principal DESC, ip.orden ASC, ip.id ASC
                                LIMIT 1
                            ),
                            productos.imagen
                        ) AS imagen
                      FROM categorias
                      INNER JOIN productos
                      ON productos.id = (
                          SELECT p2.id
                          FROM productos p2
                          WHERE p2.categoria_id = categorias.id
                          AND p2.activo = 1
                          ORDER BY p2.id DESC
                          LIMIT 1
                      )
                      ORDER BY categorias.id
                      LIMIT 5";

$resultadoCategoriasHome = mysqli_query($conn, $sqlCategoriasHome);

if($resultadoCategoriasHome){
    while($categoriaHome = mysqli_fetch_assoc($resultadoCategoriasHome)){
        $categoriasHome[] = $categoriaHome;
    }
}

$envioGratisDesdeHome = null;
$resultadoEnvioHome = mysqli_query($conn, "SELECT MIN(envio_gratis_desde) AS minimo FROM zonas_envio WHERE envio_gratis_desde > 0");
if($resultadoEnvioHome){
    $envioHome = mysqli_fetch_assoc($resultadoEnvioHome);
    if(!empty($envioHome['minimo'])){
        $envioGratisDesdeHome = (float) $envioHome['minimo'];
    }
}

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

/* CARTEL SUPERIOR DE PROMOCIÓN */
$promocionHome = null;

$sqlPromocionHome = "SELECT *
                     FROM promociones_home
                     WHERE activo = 1
                     ORDER BY id DESC
                     LIMIT 1";

$resultadoPromocionHome = mysqli_query($conn, $sqlPromocionHome);

if($resultadoPromocionHome && mysqli_num_rows($resultadoPromocionHome) > 0){
    $promocionHome = mysqli_fetch_assoc($resultadoPromocionHome);
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

<?php if($promocionHome): ?>
    <section class="promo-ticker" aria-label="Promociones de SportStyle">
        <div class="promo-ticker-track">
            <?php for($i = 0; $i < 4; $i++): ?>
                <span><?= htmlspecialchars($promocionHome['texto']) ?></span>
            <?php endfor; ?>
        </div>
    </section>
<?php endif; ?>

<section class="hero"
         style="background: linear-gradient(90deg, rgba(15,23,42,.50), rgba(15,23,42,.26)), url('<?= htmlspecialchars($heroImagen) ?>') center/cover no-repeat;">

    <div class="hero-overlay"></div>

    <div class="hero-contenido">

        <span class="hero-badge">Temporada actual</span>

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

    <span>Compra por sección</span>

    <h2>Categorías principales</h2>

    <p>
        Entrá directo a las secciones más buscadas de la tienda.
    </p>

</section>

<section class="home-categorias-imagenes">

    <?php foreach($categoriasHome as $categoriaHome): ?>
        <a href="productos.php?categoria_id=<?= (int) $categoriaHome['id'] ?>" class="categoria-imagen-card">
            <img src="<?= htmlspecialchars($categoriaHome['imagen']) ?>" alt="<?= htmlspecialchars($categoriaHome['nombre']) ?>">
            <span>Compra</span>
            <strong><?= htmlspecialchars($categoriaHome['nombre']) ?></strong>
        </a>
    <?php endforeach; ?>

</section>

<?php include("includes/footer.php"); ?>
