<?php

include("config/conexion.php");
include("includes/header.php");

/* TRAER PRODUCTOS DESTACADOS DESDE MYSQL */
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

        <h1>NUEVA COLECCIÓN</h1>

        <p>
            Rendimiento, estilo y comodidad en cada paso.
        </p>

        <a href="productos.php"
           class="btn-hero">

            Ver productos

        </a>

    </div>

</section>

<!-- PRODUCTOS DESTACADOS -->
<section>

    <h2 class="titulo">
        Productos Destacados
    </h2>

    <div class="grid">

        <?php if(mysqli_num_rows($resultadoDestacados) > 0): ?>

            <?php while($p = mysqli_fetch_assoc($resultadoDestacados)): ?>

                <div class="card-v2"
                     onclick="window.location='detalle.php?id=<?= $p['id'] ?>'">

                    <!-- IMAGEN -->
                    <div class="card-v2-img">

                        <img src="<?= $p['imagen'] ?>"
                             alt="<?= $p['nombre'] ?>">

                    </div>

                    <!-- INFO -->
                    <div class="card-v2-info">

                        <div class="card-v2-tags">

                            <?php if(!empty($p['categoria_nombre'])): ?>

                                <span class="badge-categoria">
                                    <?= $p['categoria_nombre'] ?>
                                </span>

                            <?php endif; ?>

                            <?php if(!empty($p['marca_nombre'])): ?>

                                <span class="badge-marca">
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

            <p style="text-align:center; width:100%; padding:40px; color:#888;">
                Todavía no hay productos destacados cargados.
            </p>

        <?php endif; ?>

    </div>

</section>

<!-- BANNER -->
<section class="banner-promo">

    <div class="banner-contenido">

        <span class="badge-oferta">
            🔥 OFERTA LIMITADA
        </span>

        <h2>
            Hasta 70% OFF
        </h2>

        <p>
            En indumentaria deportiva seleccionada
        </p>

        <span class="fecha">
            Del 10 al 30 de este mes
        </span>

        <a href="productos.php?sale=1"
           class="btn-banner">

           Ver ofertas

        </a>

    </div>

</section>

<?php include("includes/footer.php"); ?>