<?php

include("config/conexion.php");
include("includes/header.php");

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

?>

<section class="hero">

    <div class="hero-overlay"></div>

    <div class="hero-contenido">

        <span class="hero-badge">SportStyle 2026</span>

        <h1>Nueva colección deportiva</h1>

        <p>
            Indumentaria, calzado y accesorios para entrenar con estilo,
            comodidad y rendimiento.
        </p>

        <div class="hero-actions">

            <a href="productos.php" class="btn-hero">
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

                <div class="card-v2"
                     onclick="window.location='detalle.php?id=<?= $p['id'] ?>'">

                    <div class="card-v2-img">

                        <img src="<?= $p['imagen'] ?>"
                             alt="<?= $p['nombre'] ?>">

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
                                   class="btn-card"
                                   onclick="event.stopPropagation()">
                                    🛒
                                </a>

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