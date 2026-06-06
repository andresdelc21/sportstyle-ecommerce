<?php

session_start();

include("config/conexion.php");

/* VALIDAR ID */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* TRAER PRODUCTO */
$sql = "SELECT
            productos.*,
            categorias.nombre AS categoria_nombre,
            marcas.nombre AS marca_nombre
        FROM productos
        LEFT JOIN categorias
        ON productos.categoria_id = categorias.id
        LEFT JOIN marcas
        ON productos.marca_id = marcas.id
        WHERE productos.id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$producto = mysqli_fetch_assoc($resultado);

if(!$producto){
    echo "Producto no encontrado";
    exit;
}

/* IMAGENES MULTIPLES */
$imagenes = [];

$sqlImagenes = "SELECT imagen
                FROM imagenes_productos
                WHERE producto_id = ?
                ORDER BY principal DESC, orden ASC, id ASC";

$stmtImagenes = mysqli_prepare($conn, $sqlImagenes);

mysqli_stmt_bind_param($stmtImagenes, "i", $id);

mysqli_stmt_execute($stmtImagenes);

$resultadoImagenes = mysqli_stmt_get_result($stmtImagenes);

while($img = mysqli_fetch_assoc($resultadoImagenes)){
    $imagenes[] = $img['imagen'];
}

/* SI NO HAY IMÁGENES EXTRA, USA LA IMAGEN NORMAL DEL PRODUCTO */
if(empty($imagenes)){
    $imagenes[] = $producto['imagen'];
}

$producto['imagenes'] = $imagenes;

/* TALLES DEL PRODUCTO */
$tallesProducto = [];
$stockTalles = 0;

$sqlTalles = "SELECT *
              FROM producto_talles
              WHERE producto_id = ?
              ORDER BY
                CASE WHEN etiqueta = 'Único' THEN 0 ELSE 1 END,
                talle_arg + 0 ASC,
                id ASC";

$stmtTalles = mysqli_prepare($conn, $sqlTalles);
mysqli_stmt_bind_param($stmtTalles, "i", $id);
mysqli_stmt_execute($stmtTalles);
$resultadoTalles = mysqli_stmt_get_result($stmtTalles);

while($talle = mysqli_fetch_assoc($resultadoTalles)){
    $tallesProducto[] = $talle;
    $stockTalles += (int) $talle['stock'];
}

if(!empty($tallesProducto)){
    $producto['stock'] = $stockTalles;
}

function tipoTalleProducto($producto){
    $texto = strtolower(($producto['categoria_nombre'] ?? '') . ' ' . ($producto['nombre'] ?? ''));

    if(strpos($texto, 'zapat') !== false || strpos($texto, 'calzado') !== false){
        return 'calzado';
    }

    if(strpos($texto, 'media') !== false){
        return 'medias';
    }

    if(strpos($texto, 'remera') !== false || strpos($texto, 'buzo') !== false || strpos($texto, 'pantal') !== false || strpos($texto, 'short') !== false){
        return 'indumentaria';
    }

    return 'general';
}

$tipoTalle = tipoTalleProducto($producto);

/* FAVORITO DEL USUARIO */
$esFavorito = false;

if(isset($_SESSION['usuario_id'])){

    $usuario_id = (int) $_SESSION['usuario_id'];

    $sqlFavorito = "SELECT id
                    FROM favoritos
                    WHERE usuario_id = ?
                    AND producto_id = ?
                    LIMIT 1";

    $stmtFavorito = mysqli_prepare($conn, $sqlFavorito);

    mysqli_stmt_bind_param(
        $stmtFavorito,
        "ii",
        $usuario_id,
        $id
    );

    mysqli_stmt_execute($stmtFavorito);

    $resultadoFavorito = mysqli_stmt_get_result($stmtFavorito);

    $esFavorito = mysqli_num_rows($resultadoFavorito) > 0;

}

/* REVIEWS */
$sqlReviews = "SELECT *
               FROM reviews
               WHERE producto_id = ?
               AND aprobado = 1
               ORDER BY id DESC";

$stmtReviews = mysqli_prepare($conn, $sqlReviews);

mysqli_stmt_bind_param($stmtReviews, "i", $id);

mysqli_stmt_execute($stmtReviews);

$reviews = mysqli_stmt_get_result($stmtReviews);

/* DESCUENTO */
function descuento($precio, $precio_original){

    if($precio_original > $precio){
        return round(100 - ($precio * 100 / $precio_original));
    }

    return 0;
}

$desc = descuento(
    $producto['precio'],
    $producto['precio_original']
);

?>

<?php include("includes/header.php"); ?>

<a href="productos.php" class="btn-volver-detalle">
    ← Volver a productos
</a>

<section class="detalle">

    <div class="detalle-img">

    <img id="img-principal"
         src="<?= $producto['imagenes'][0] ?>"
         alt="<?= $producto['nombre'] ?>">

    <?php if($producto['stock'] <= 0): ?>

        <span class="stock-overlay detalle-stock-overlay">
            Sin stock
        </span>

    <?php endif; ?>

    <?php if(count($producto['imagenes']) > 1): ?>

        <div class="miniaturas">

            <?php foreach($producto['imagenes'] as $img): ?>

                <img src="<?= htmlspecialchars($img) ?>"
     alt="<?= htmlspecialchars($producto['nombre']) ?>"
     class="miniatura-producto"
     data-img="<?= htmlspecialchars($img) ?>">

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

    <div class="detalle-info">

        <span class="detalle-categoria">
            <?= $producto['categoria_nombre'] ?>
        </span>

        <h1>
            <?= $producto['nombre'] ?>
        </h1>

        <p>
            Calificación <?= number_format($producto['rating'] ?? 5, 1, ',', '.') ?>/5
        </p>

        <p>
            <strong>Marca:</strong>
            <?= $producto['marca_nombre'] ?? 'Sin marca' ?>
        </p>

        <div class="detalle-precio">

            <?php if($desc > 0): ?>

                <span class="precio-original">
                    $<?= number_format($producto['precio_original'], 0, ',', '.') ?>
                </span>

                <span class="descuento">
                    -<?= $desc ?>%
                </span>

            <?php endif; ?>

            <span class="precio-actual">
                $<?= number_format($producto['precio'], 0, ',', '.') ?>
            </span>

        </div>

        <p class="detalle-stock">

            <?php if($producto['stock'] > 0): ?>

                Stock disponible (<?= $producto['stock'] ?> unidades)

            <?php else: ?>

                Sin stock

            <?php endif; ?>

        </p>

        <p>
            <strong>Género:</strong>
            <?= $producto['genero'] ?>
        </p>

        <section class="guia-talles">

            <div class="guia-talles-header">

                <h3>Guía de talles</h3>

                <?php if($tipoTalle === 'calzado'): ?>
                    <p>Referencia orientativa. Para calzado, medí tu pie desde el talón hasta la punta.</p>
                <?php elseif($tipoTalle === 'indumentaria'): ?>
                    <p>Referencia orientativa para remeras, buzos, pantalones y shorts.</p>
                <?php elseif($tipoTalle === 'medias'): ?>
                    <p>Las medias suelen agruparse por rango de calzado. Revisá el rango indicado.</p>
                <?php else: ?>
                    <p>Referencia orientativa. Verificá el talle disponible antes de agregar al carrito.</p>
                <?php endif; ?>

            </div>

            <div class="guia-talles-tabla">

                <table>
                    <?php if($tipoTalle === 'calzado'): ?>
                        <thead><tr><th>ARG</th><th>US</th><th>BR</th><th>CM</th></tr></thead>
                        <tbody>
                            <tr><td>38</td><td>6.5</td><td>37</td><td>24.5</td></tr>
                            <tr><td>39</td><td>7</td><td>38</td><td>25</td></tr>
                            <tr><td>40</td><td>8</td><td>39</td><td>26</td></tr>
                            <tr><td>41</td><td>8.5</td><td>40</td><td>26.5</td></tr>
                            <tr><td>42</td><td>9</td><td>41</td><td>27</td></tr>
                            <tr><td>43</td><td>10</td><td>42</td><td>28</td></tr>
                        </tbody>
                    <?php elseif($tipoTalle === 'indumentaria'): ?>
                        <thead><tr><th>Talle</th><th>Pecho</th><th>Cintura</th><th>Referencia</th></tr></thead>
                        <tbody>
                            <tr><td>S</td><td>86-94 cm</td><td>72-80 cm</td><td>Chico</td></tr>
                            <tr><td>M</td><td>94-102 cm</td><td>80-88 cm</td><td>Medio</td></tr>
                            <tr><td>L</td><td>102-110 cm</td><td>88-96 cm</td><td>Grande</td></tr>
                            <tr><td>XL</td><td>110-118 cm</td><td>96-104 cm</td><td>Extra grande</td></tr>
                            <tr><td>XXL</td><td>118-126 cm</td><td>104-112 cm</td><td>Amplio</td></tr>
                        </tbody>
                    <?php elseif($tipoTalle === 'medias'): ?>
                        <thead><tr><th>Talle</th><th>Calzado ARG</th><th>Uso</th></tr></thead>
                        <tbody>
                            <tr><td>Único</td><td>35-43</td><td>Adulto</td></tr>
                        </tbody>
                    <?php else: ?>
                        <thead><tr><th>Talle</th><th>Referencia</th></tr></thead>
                        <tbody><tr><td>Único</td><td>Según disponibilidad del producto</td></tr></tbody>
                    <?php endif; ?>
                </table>

            </div>

            <p class="guia-talles-nota">
                Si estás entre dos talles, elegí el mayor para más comodidad.
            </p>

        </section>

        <?php if(!empty($tallesProducto)): ?>

            <section class="selector-talles-producto">

                <div class="selector-talles-header">
                    <h3>Seleccioná talle</h3>
                    <span>Stock por variante</span>
                </div>

                <div class="talles-opciones">

                    <?php foreach($tallesProducto as $talle): ?>

                        <?php
                            $labelTalle = trim(
                                !empty($talle['etiqueta']) && $talle['etiqueta'] !== 'Único'
                                    ? $talle['etiqueta']
                                    : implode(' / ', array_filter([
                                        !empty($talle['talle_arg']) ? 'ARG ' . $talle['talle_arg'] : '',
                                        !empty($talle['talle_us']) ? 'US ' . $talle['talle_us'] : '',
                                        !empty($talle['talle_br']) ? 'BR ' . $talle['talle_br'] : '',
                                        !empty($talle['cm']) ? $talle['cm'] . ' cm' : ''
                                    ]))
                            );

                            if($labelTalle === ''){
                                $labelTalle = $talle['etiqueta'] ?: 'Único';
                            }

                            $sinStockTalle = (int) $talle['stock'] <= 0;
                        ?>

                        <label class="talle-opcion <?= $sinStockTalle ? 'sin-stock-talle' : '' ?>">
                            <input type="radio"
                                   name="talle_id"
                                   value="<?= (int) $talle['id'] ?>"
                                   <?= $sinStockTalle ? 'disabled' : '' ?>
                                   <?= count($tallesProducto) === 1 && !$sinStockTalle ? 'checked' : '' ?>>

                            <strong><?= htmlspecialchars($labelTalle) ?></strong>
                            <small><?= (int) $talle['stock'] ?> disp.</small>
                        </label>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>

        <div class="detalle-botones">

            <?php if($producto['stock'] > 0): ?>

                <a href="carrito.php?agregar=<?= $producto['id'] ?>"
                   class="btn-comprar btn-agregar-carrito-js"
                   data-producto="<?= $producto['id'] ?>">
                    🛒 Agregar al carrito
                </a>

            <?php else: ?>

                <span class="sin-stock">
                    Sin stock
                </span>

            <?php endif; ?>

            <a href="#"
               class="btn-favorito-detalle btn-favorito-js <?= $esFavorito ? 'favorito-activo' : '' ?>"
               data-producto="<?= $producto['id'] ?>"
               title="<?= $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
               aria-label="Agregar o quitar favorito">

                <span class="favorito-icono">
                    ❤️
                </span>

                <span class="favorito-texto">
                    <?= $esFavorito ? 'En favoritos' : 'Guardar favorito' ?>
                </span>

            </a>

        </div>

        <div class="detalle-desc">

            <h3>Descripción</h3>

            <p>
                <?= $producto['descripcion'] ?? 'Sin descripción disponible.' ?>
            </p>

        </div>

        <div class="detalle-extra">

            <p>🚚 Envíos a todo el país</p>
            <p>💳 Hasta 6 cuotas sin interés</p>
            <p>🔒 Compra segura</p>

        </div>

    </div>

</section>

<!-- REVIEWS -->
<section class="reviews-section">

    <div class="reviews-header">

        <span class="productos-badge">
            Opiniones
        </span>

        <h2>
            Experiencias de clientes
        </h2>

        <p>
            Leé reseñas del producto o compartí tu experiencia para ayudar a otros compradores.
        </p>

    </div>

    <div class="reviews-grid">

    <?php if(isset($_SESSION['usuario_id'])): ?>

        <form method="POST"
      action="agregar_review.php"
      class="review-form review-form-premium">

    <input type="hidden"
           name="producto_id"
           value="<?= $producto['id'] ?>">

    <div class="review-form-header">

        <span class="review-icon">
            Nota
        </span>

        <div>

            <h3>
                Comparte tu experiencia
            </h3>

            <p>
                Tu opinión ayuda a otros clientes a elegir mejor.
            </p>

        </div>

    </div>

    <div class="rating-premium">

        <label>
            Calificación
        </label>

        <div class="rating-options">

            <label class="rating-option">

                <input type="radio"
                       name="rating"
                       value="5"
                       required
                       checked>

                <span>5/5</span>
                <small>Excelente</small>

            </label>

            <label class="rating-option">

                <input type="radio"
                       name="rating"
                       value="4">

                <span>4/5</span>
                <small>Muy bueno</small>

            </label>

            <label class="rating-option">

                <input type="radio"
                       name="rating"
                       value="3">

                <span>3/5</span>
                <small>Bueno</small>

            </label>

            <label class="rating-option">

                <input type="radio"
                       name="rating"
                       value="2">

                <span>2/5</span>
                <small>Regular</small>

            </label>

            <label class="rating-option">

                <input type="radio"
                       name="rating"
                       value="1">

                <span>1/5</span>
                <small>Malo</small>

            </label>

        </div>

    </div>

    <div class="review-textarea-box">

        <label>
            Comentario
        </label>

        <textarea name="comentario"
                  rows="5"
                  placeholder="Contanos qué te pareció el producto, la calidad, el talle o la experiencia de compra..."></textarea>

    </div>

    <button type="submit"
            class="btn-review-premium">

        Enviar opinión

    </button>

</form>

    <?php else: ?>

        <div class="review-login-box">

            <h3>
                ¿Ya compraste o probaste este producto?
            </h3>

            <p>
                Iniciá sesión para dejar una opinión y ayudar a otros clientes.
            </p>

            <a href="login.php"
               class="btn-review-premium">
                Iniciar sesión
            </a>

        </div>

    <?php endif; ?>

    <div class="reviews-lista">

        <div class="reviews-lista-header">

            <h3>
                Reseñas publicadas
            </h3>

        </div>

        <?php if(mysqli_num_rows($reviews) > 0): ?>

            <?php while($r = mysqli_fetch_assoc($reviews)): ?>

                <div class="review-card">

                    <h3>
                        <?= $r['nombre_cliente'] ?? 'Cliente' ?>
                    </h3>

                    <p class="review-stars">
                        <?= (int)$r['rating'] ?>/5
                    </p>

                    <p>
                        <?= $r['comentario'] ?: 'Sin comentario.' ?>
                    </p>

                    <small>
                        <?= date("d/m/Y", strtotime($r['fecha'])) ?>
                    </small>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="reviews-empty">

                <strong>
                    Todavía no hay opiniones
                </strong>

                <p>
                    Sé el primero en contar cómo te resultó este producto.
                </p>

            </div>

        <?php endif; ?>

    </div>

    </div>

<div class="zoom-overlay" id="zoomOverlay">
    <img id="zoomImage" src="">
</div>
</section>
<script src="java/detalle.js"></script>



<?php include("includes/footer.php"); ?>
