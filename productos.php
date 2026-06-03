<?php

session_start();

/* ===== BASE URL ===== */
$BASE = "/sportstyle/";

include("config/conexion.php");

/* ===== TRAER PRODUCTOS + CATEGORÍAS + MARCAS ===== */
$sql = "SELECT
            productos.*,
            categorias.nombre AS categoria_nombre,
            marcas.nombre AS marca_nombre
        FROM productos
        LEFT JOIN categorias
        ON productos.categoria_id = categorias.id
        LEFT JOIN marcas
        ON productos.marca_id = marcas.id
        ORDER BY productos.id DESC";

$resultado = mysqli_query($conn, $sql);

$productos = [];

while($fila = mysqli_fetch_assoc($resultado)){

    $fila['imagenes'] = [$fila['imagen']];

    $productos[] = $fila;

}

/* ===== TRAER CATEGORÍAS ===== */
$sqlCategorias = "SELECT * FROM categorias ORDER BY nombre ASC";

$resultadoCategorias = mysqli_query($conn, $sqlCategorias);

$categorias = [];

while($cat = mysqli_fetch_assoc($resultadoCategorias)){

    $categorias[] = $cat;

}

/* ===== FILTROS ===== */
$categoriaActiva = $_GET['categoria_id'] ?? 'todas';

$marcaActiva = $_GET['marca_id'] ?? 'todas';

$generoActivo = $_GET['genero'] ?? 'todos';

$saleActivo = isset($_GET['sale']);

$buscar = trim($_GET['buscar'] ?? '');

/* ===== FILTRADO ===== */
$productosFiltrados = array_filter(
    $productos,
    function($p) use (
        $categoriaActiva,
        $marcaActiva,
        $generoActivo,
        $saleActivo,
        $buscar
    ){

        $okCategoria =
        (
            $categoriaActiva === 'todas'
            ||
            $p['categoria_id'] == $categoriaActiva
        );

        $okMarca =
        (
            $marcaActiva === 'todas'
            ||
            $p['marca_id'] == $marcaActiva
        );

        $okGenero =
        (
            $generoActivo === 'todos'
            ||
            $p['genero'] === $generoActivo
        );

        $okSale =
        (
            !$saleActivo
            ||
            ($p['precio_original'] > $p['precio'])
        );

        $okBusqueda =
        (
            empty($buscar)
            ||
            stripos($p['nombre'], $buscar) !== false
            ||
            stripos($p['categoria_nombre'], $buscar) !== false
            ||
            stripos($p['marca_nombre'], $buscar) !== false
            ||
            stripos($p['genero'], $buscar) !== false
        );

        return
            $okCategoria
            &&
            $okMarca
            &&
            $okGenero
            &&
            $okSale
            &&
            $okBusqueda;

    }
);

/* ===== HELPERS ===== */
function estrellas(int $rating){

    $html = '';

    for($i = 1; $i <= 5; $i++){

        $html .= $i <= $rating ? '★' : '☆';

    }

    return $html;

}

function descuento(
    int $precio,
    int $precio_original
): int {

    return ($precio_original > $precio)
        ? round(100 - ($precio * 100 / $precio_original))
        : 0;

}

?>

<?php include("includes/header.php"); ?>

<h1 class="titulo">
    Productos
</h1>

<!-- ===== FILTROS ===== -->
<div class="filtros">

    <a href="<?= $BASE ?>productos.php"
       class="btn-filtro <?= $categoriaActiva === 'todas' ? 'activo' : '' ?>">

       Todos

    </a>

    <?php foreach($categorias as $cat): ?>

        <a href="<?= $BASE ?>productos.php?categoria_id=<?= $cat['id'] ?>"
           class="btn-filtro <?= $categoriaActiva == $cat['id'] ? 'activo' : '' ?>">

            <?= $cat['nombre'] ?>

        </a>

    <?php endforeach; ?>

</div>

<!-- ===== GRID ===== -->
<div class="grid">

<?php if(count($productosFiltrados) > 0): ?>

<?php foreach($productosFiltrados as $p):

    $desc = descuento(
        $p['precio'],
        $p['precio_original']
    );

    $stockMax = 20;

    $stockPct = min(
        100,
        round($p['stock'] * 100 / $stockMax)
    );

    $stockColor =
        $p['stock'] <= 3
        ? '#e53935'
        : (
            $p['stock'] <= 8
            ? '#ff6d00'
            : '#00c853'
        );

?>

<div class="card-v2"
     onclick="window.location='<?= $BASE ?>detalle.php?id=<?= $p['id'] ?>'">

    <!-- ===== IMAGEN ===== -->
    <div class="card-v2-img">

        <img class="img-principal"
             src="<?= $BASE . $p['imagenes'][0] ?>"
             data-imgs='<?= json_encode($p['imagenes']) ?>'>

    </div>

    <!-- ===== ACCIONES ===== -->
    <div class="card-v2-acciones">

        <a href="favoritos.php?toggle=<?= $p['id'] ?>"
           class="accion-btn"
           onclick="event.stopPropagation()">

           ❤️

        </a>

        <a href="detalle.php?id=<?= $p['id'] ?>"
           class="accion-btn"
           onclick="event.stopPropagation()">

           👁️

        </a>

        <?php if($p['stock'] > 0): ?>

            <a href="carrito.php?agregar=<?= $p['id'] ?>"
               class="accion-btn"
               onclick="event.stopPropagation()">

               🛒

            </a>

        <?php endif; ?>

    </div>

    <!-- ===== INFO ===== -->
    <div class="card-v2-info">

        <!-- ===== TAGS ===== -->
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

            <?php if(!empty($p['genero'])): ?>

                <span class="badge-genero">

                    <?= $p['genero'] ?>

                </span>

            <?php endif; ?>

        </div>

        <h3 class="card-v2-nombre">

            <?= $p['nombre'] ?>

        </h3>

        <div class="card-v2-rating">

            <?= estrellas($p['rating']) ?>

            <span class="rating-num">

                (<?= $p['rating'] ?>)

            </span>

        </div>

        <div class="card-v2-precio">

            <?php if($desc > 0): ?>

                <span class="precio-original">

                    $<?= number_format($p['precio_original'], 0, ',', '.') ?>

                </span>

            <?php endif; ?>

            <span class="precio-actual">

                $<?= number_format($p['precio'], 0, ',', '.') ?>

            </span>

        </div>

        <!-- ===== STOCK ===== -->
        <div class="card-v2-stock">

            <div class="stock-barra">

                <div class="stock-fill"
                     style="width:<?= $stockPct ?>%; background:<?= $stockColor ?>">

                </div>

            </div>

        </div>

        <!-- ===== BOTONES ===== -->
        <div class="card-v2-botones">

            <?php if($p['stock'] > 0): ?>

                <a href="<?= $BASE ?>carrito.php?agregar=<?= $p['id'] ?>"
                   class="btn-card"
                   onclick="event.stopPropagation()">

                   🛒 Agregar

                </a>

                <a href="<?= $BASE ?>detalle.php?id=<?= $p['id'] ?>"
                   class="btn-card btn-detalle"
                   onclick="event.stopPropagation()">

                   👁️ Ver

                </a>

            <?php else: ?>

                <span class="sin-stock">

                    Sin stock

                </span>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php endforeach; ?>

<?php else: ?>

<p style="text-align:center; width:100%; padding:40px; color:#888;">

    No hay productos en esta selección.

</p>

<?php endif; ?>

</div>

<?php include("includes/footer.php"); ?>