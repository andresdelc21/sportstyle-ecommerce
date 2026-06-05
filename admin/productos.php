<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";

require_once __DIR__ . "/../config/conexion.php";

/* PROTEGER PANEL */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* TRAER PRODUCTOS */
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
    $productos[] = $fila;
}

/* CONTADORES */
$totalProductos = count($productos);
$stockBajo = 0;
$sinStock = 0;

foreach($productos as $p){

    if($p['stock'] <= 3 && $p['stock'] > 0){
        $stockBajo++;
    }

    if($p['stock'] <= 0){
        $sinStock++;
    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Administrar Productos</title>

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
                    Catálogo Ecommerce
                </span>

                <h1>
                    Productos 📦
                </h1>

                <p>
                    Gestiona productos, precios, marcas, categorías, imágenes y stock.
                </p>

            </div>

            <div class="admin-hero-actions">

                <a href="agregar_productos.php"
                   class="btn-admin-agregar">

                   + Agregar producto

                </a>

            </div>

        </section>

        <!-- RESUMEN -->
        <div class="admin-metricas mini-metricas">

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    📦
                </div>

                <div>

                    <span>Total productos</span>

                    <h2>
                        <?= $totalProductos ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card alerta">

                <div class="metrica-icono">
                    ⚠️
                </div>

                <div>

                    <span>Stock bajo</span>

                    <h2>
                        <?= $stockBajo ?>
                    </h2>

                </div>

            </div>

            <div class="admin-card metrica-card">

                <div class="metrica-icono">
                    ❌
                </div>

                <div>

                    <span>Sin stock</span>

                    <h2>
                        <?= $sinStock ?>
                    </h2>

                </div>

            </div>

        </div>

        <!-- BUSCADOR -->
        <div class="admin-search-box">

            <input type="text"
                   id="buscarProducto"
                   placeholder="Buscar producto, marca o categoría...">

        </div>

        <!-- TABLA -->
        <div class="tabla-admin tabla-premium">

            <table id="tablaProductos">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php if(count($productos) > 0): ?>

                    <?php foreach($productos as $p): ?>

                        <tr>

                            <td>
                                #<?= $p['id'] ?>
                            </td>

                            <td>

                                <div class="producto-admin-info">

                                    <img src="../<?= $p['imagen'] ?>"
                                         class="admin-img-producto">

                                    <div>

                                        <strong>
                                            <?= $p['nombre'] ?>
                                        </strong>

                                        <small>
                                            <?= $p['genero'] ?? 'Sin género' ?>
                                        </small>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <?= $p['categoria_nombre'] ?? 'Sin categoría' ?>
                            </td>

                            <td>
                                <?= $p['marca_nombre'] ?? 'Sin marca' ?>
                            </td>

                            <td>
                                <strong>
                                    $<?= number_format($p['precio'], 0, ',', '.') ?>
                                </strong>
                            </td>

                            <td>

                                <?php if($p['stock'] <= 0): ?>

                                    <span class="stock-bajo">
                                        Sin stock
                                    </span>

                                <?php elseif($p['stock'] <= 3): ?>

                                    <span class="stock-bajo">
                                        <?= $p['stock'] ?> unidades
                                    </span>

                                <?php else: ?>

                                    <span class="stock-ok">
                                        <?= $p['stock'] ?> unidades
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="acciones-tabla">

                                <a href="editar_productos.php?id=<?= $p['id'] ?>"
                                   class="btn-tabla editar">

                                   ✏️

                                </a>

                                <form method="POST"
                                      action="eliminar_productos.php"
                                      class="form-inline-admin"
                                      onsubmit="return confirm('¿Eliminar producto?')">

                                    <?= csrfInput() ?>

                                    <input type="hidden"
                                           name="id"
                                           value="<?= $p['id'] ?>">

                                    <button type="submit"
                                            class="btn-tabla eliminar">

                                       ❌

                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7"
                            style="text-align:center; padding:30px;">

                            No hay productos cargados

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

<script>

const buscadorProducto = document.getElementById("buscarProducto");

buscadorProducto.addEventListener("keyup", function(){

    const valor = this.value.toLowerCase();

    const filas = document.querySelectorAll(
        "#tablaProductos tbody tr"
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
