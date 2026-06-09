<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";

require_once(dirname(__FILE__) . "/../config/conexion.php");
require_once(__DIR__ . "/includes/upload_helper.php");

/* PROTEGER */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* VALIDAR ID */
if(!isset($_GET['id'])){
    header("Location: productos.php");
    exit;
}

$id = (int) $_GET['id'];

$success = "";
$error = "";

/* TRAER CATEGORÍAS */
$sqlCategorias = "SELECT * FROM categorias ORDER BY nombre ASC";
$resultadoCategorias = mysqli_query($conn, $sqlCategorias);
$categorias = [];

if($resultadoCategorias){
    while($categoriaFila = mysqli_fetch_assoc($resultadoCategorias)){
        $categorias[] = $categoriaFila;
    }
}

/* TRAER SUBCATEGORÍAS */
$sqlSubcategorias = "SELECT * FROM subcategorias ORDER BY categoria_id ASC, nombre ASC";
$resultadoSubcategorias = mysqli_query($conn, $sqlSubcategorias);
$subcategorias = [];

if($resultadoSubcategorias){
    while($subcategoriaFila = mysqli_fetch_assoc($resultadoSubcategorias)){
        $subcategorias[] = $subcategoriaFila;
    }
}

/* ACCIONES IMÁGENES */
if(isset($_GET['principal'])){

    if(($_GET['csrf_token'] ?? '') !== csrfToken()){
        header("Location: editar_productos.php?id=$id");
        exit;
    }

    $img_id = (int) $_GET['principal'];

    $stmtResetPrincipal = mysqli_prepare($conn, "UPDATE imagenes_productos SET principal = 0 WHERE producto_id = ?");
    mysqli_stmt_bind_param($stmtResetPrincipal, "i", $id);
    mysqli_stmt_execute($stmtResetPrincipal);

    $stmtNuevaPrincipal = mysqli_prepare($conn, "UPDATE imagenes_productos SET principal = 1 WHERE id = ? AND producto_id = ?");
    mysqli_stmt_bind_param($stmtNuevaPrincipal, "ii", $img_id, $id);
    mysqli_stmt_execute($stmtNuevaPrincipal);

    $stmtPrincipal = mysqli_prepare($conn, "SELECT imagen FROM imagenes_productos WHERE id = ? AND producto_id = ?");
    mysqli_stmt_bind_param($stmtPrincipal, "ii", $img_id, $id);
    mysqli_stmt_execute($stmtPrincipal);
    $resPrincipal = mysqli_stmt_get_result($stmtPrincipal);
    $imgPrincipal = mysqli_fetch_assoc($resPrincipal);

    if($imgPrincipal){
        $rutaPrincipal = $imgPrincipal['imagen'];
        $stmtProductoPrincipal = mysqli_prepare($conn, "UPDATE productos SET imagen = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmtProductoPrincipal, "si", $rutaPrincipal, $id);
        mysqli_stmt_execute($stmtProductoPrincipal);
    }

    header("Location: editar_productos.php?id=$id");
    exit;
}

if(isset($_GET['eliminar_img'])){

    if(($_GET['csrf_token'] ?? '') !== csrfToken()){
        header("Location: editar_productos.php?id=$id");
        exit;
    }

    $img_id = (int) $_GET['eliminar_img'];

    $stmtImg = mysqli_prepare($conn, "SELECT imagen, principal FROM imagenes_productos WHERE id = ? AND producto_id = ?");
    mysqli_stmt_bind_param($stmtImg, "ii", $img_id, $id);
    mysqli_stmt_execute($stmtImg);
    $resImg = mysqli_stmt_get_result($stmtImg);
    $imgData = mysqli_fetch_assoc($resImg);

    if($imgData){

        $rutaArchivo = "../" . $imgData['imagen'];
        $rutaReal = realpath($rutaArchivo);
        $raizProyecto = realpath(__DIR__ . "/..");

        if($rutaReal && $raizProyecto && strpos($rutaReal, $raizProyecto) === 0){
            unlink($rutaReal);
        }

        $stmtDeleteImg = mysqli_prepare($conn, "DELETE FROM imagenes_productos WHERE id = ? AND producto_id = ?");
        mysqli_stmt_bind_param($stmtDeleteImg, "ii", $img_id, $id);
        mysqli_stmt_execute($stmtDeleteImg);

        if($imgData['principal'] == 1){

            $stmtNueva = mysqli_prepare($conn, "SELECT * FROM imagenes_productos WHERE producto_id = ? ORDER BY orden ASC, id ASC LIMIT 1");
            mysqli_stmt_bind_param($stmtNueva, "i", $id);
            mysqli_stmt_execute($stmtNueva);
            $resNueva = mysqli_stmt_get_result($stmtNueva);
            $nueva = mysqli_fetch_assoc($resNueva);

            if($nueva){
                $nuevaId = (int) $nueva['id'];
                $nuevaImagen = $nueva['imagen'];

                $stmtSetPrincipal = mysqli_prepare($conn, "UPDATE imagenes_productos SET principal = 1 WHERE id = ?");
                mysqli_stmt_bind_param($stmtSetPrincipal, "i", $nuevaId);
                mysqli_stmt_execute($stmtSetPrincipal);

                $stmtSetProducto = mysqli_prepare($conn, "UPDATE productos SET imagen = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmtSetProducto, "si", $nuevaImagen, $id);
                mysqli_stmt_execute($stmtSetProducto);
            }

        }

    }

    header("Location: editar_productos.php?id=$id");
    exit;
}

/* TRAER PRODUCTO */
$sql = "SELECT * FROM productos WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$producto = mysqli_fetch_assoc($resultado);

if(!$producto){
    header("Location: productos.php");
    exit;
}

/* ACTUALIZAR */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(!validarCsrf()){
        $error = "Solicitud inválida.";
    }

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float) $_POST['precio'];
    $precio_original = (float) $_POST['precio_original'];
    $stock = (int) $_POST['stock'];
    $categoria = (int) $_POST['categoria'];
    $subcategoria = !empty($_POST['subcategoria'])
        ? (int) $_POST['subcategoria']
        : null;
    $genero = trim($_POST['genero']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $imagen = $producto['imagen'];

    /* SUBIR IMÁGENES MÚLTIPLES */
    if(empty($error) && isset($_FILES['imagenes'])){

        $totalImagenes = count($_FILES['imagenes']['name']);

        for($i = 0; $i < $totalImagenes; $i++){

            if($_FILES['imagenes']['error'][$i] === 0){

                $archivo = [
                    'name' => $_FILES['imagenes']['name'][$i],
                    'type' => $_FILES['imagenes']['type'][$i],
                    'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                    'error' => $_FILES['imagenes']['error'][$i],
                    'size' => $_FILES['imagenes']['size'][$i]
                ];

                $rutaBD = guardarImagenSubida($archivo, $error);

                if($rutaBD){

                    $stmtOrden = mysqli_prepare($conn, "SELECT COALESCE(MAX(orden), 0) + 1 AS nuevo_orden FROM imagenes_productos WHERE producto_id = ?");
                    mysqli_stmt_bind_param($stmtOrden, "i", $id);
                    mysqli_stmt_execute($stmtOrden);
                    $resOrden = mysqli_stmt_get_result($stmtOrden);
                    $ordenData = mysqli_fetch_assoc($resOrden);
                    $orden = (int) $ordenData['nuevo_orden'];

                    $stmtTieneImagenes = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM imagenes_productos WHERE producto_id = ?");
                    mysqli_stmt_bind_param($stmtTieneImagenes, "i", $id);
                    mysqli_stmt_execute($stmtTieneImagenes);
                    $resTieneImagenes = mysqli_stmt_get_result($stmtTieneImagenes);
                    $dataTieneImagenes = mysqli_fetch_assoc($resTieneImagenes);

                    $principal = ($dataTieneImagenes['total'] == 0) ? 1 : 0;

                    $sqlInsertImg = "INSERT INTO imagenes_productos
                    (
                        producto_id,
                        imagen,
                        orden,
                        principal
                    )
                    VALUES
                    (
                        ?, ?, ?, ?
                    )";

                    $stmtImg = mysqli_prepare($conn, $sqlInsertImg);

                    mysqli_stmt_bind_param(
                        $stmtImg,
                        "isii",
                        $id,
                        $rutaBD,
                        $orden,
                        $principal
                    );

                    mysqli_stmt_execute($stmtImg);

                    if($principal == 1){
                        $imagen = $rutaBD;
                    }

                }

            }

        }

    }

    if(empty($error)){

        $update = "UPDATE productos SET
            nombre = ?,
            descripcion = ?,
            precio = ?,
            precio_original = ?,
            stock = ?,
            categoria_id = ?,
            subcategoria_id = ?,
            genero = ?,
            imagen = ?,
            activo = ?
            WHERE id = ?";

        $stmtUpdate = mysqli_prepare($conn, $update);

        mysqli_stmt_bind_param(
            $stmtUpdate,
            "ssddiiissii",
            $nombre,
            $descripcion,
            $precio,
            $precio_original,
            $stock,
            $categoria,
            $subcategoria,
            $genero,
            $imagen,
            $activo,
            $id
        );

        if(mysqli_stmt_execute($stmtUpdate)){

            $success = "Producto actualizado correctamente";

            $producto['nombre'] = $nombre;
            $producto['descripcion'] = $descripcion;
            $producto['precio'] = $precio;
            $producto['precio_original'] = $precio_original;
            $producto['stock'] = $stock;
            $producto['categoria_id'] = $categoria;
            $producto['subcategoria_id'] = $subcategoria;
            $producto['genero'] = $genero;
            $producto['imagen'] = $imagen;
            $producto['activo'] = $activo;

        } else {

            $error = "Error al actualizar producto";

        }

    }

}

/* TRAER IMÁGENES */
$sqlImagenes = "SELECT *
                FROM imagenes_productos
                WHERE producto_id = ?
                ORDER BY principal DESC, orden ASC, id ASC";

$stmtImagenes = mysqli_prepare($conn, $sqlImagenes);
mysqli_stmt_bind_param($stmtImagenes, "i", $id);
mysqli_stmt_execute($stmtImagenes);
$imagenesProducto = mysqli_stmt_get_result($stmtImagenes);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Editar Producto</title>

    <link rel="stylesheet"
          href="../css/estilos.css">

</head>

<body class="admin-body">

<div class="admin-container">

    <?php include("includes/sidebar.php"); ?>

    <main class="admin-content">

        <div class="admin-top">

            <div>

                <h1>Editar Producto</h1>

                <p>Modifica la información del producto y administra sus imágenes.</p>

            </div>

            <a href="productos.php" class="btn-admin-volver">
                ← Volver
            </a>

        </div>

        <?php if($success): ?>
            <div class="admin-alert success-msg"><?= $success ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="admin-alert error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data"
              class="form-admin form-admin-premium">

            <?= csrfInput() ?>

            <div class="admin-grid">

                <div class="input-group">

                    <label>Nombre</label>

                    <input type="text"
                           name="nombre"
                           value="<?= htmlspecialchars($producto['nombre']) ?>"
                           required>

                </div>

                <div class="input-group">

                    <label>Precio</label>

                    <input type="number"
                           step="0.01"
                           name="precio"
                           value="<?= $producto['precio'] ?>"
                           required>

                </div>

                <div class="input-group">

                    <label>Precio Original</label>

                    <input type="number"
                           step="0.01"
                           name="precio_original"
                           value="<?= $producto['precio_original'] ?>">

                </div>

                <div class="input-group">

                    <label>Stock</label>

                    <input type="number"
                           name="stock"
                           value="<?= $producto['stock'] ?>"
                           required>

                </div>

                <div class="input-group">

                    <label>Categoría</label>

                    <select name="categoria"
                            id="categoriaSelect"
                            required>

                        <option value="">
                            Seleccionar categoría
                        </option>

                        <?php foreach($categorias as $categoria): ?>

                            <option value="<?= (int) $categoria['id'] ?>"
                                <?= (int) $producto['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="input-group">

                    <label>Subcategoría</label>

                    <select name="subcategoria"
                            id="subcategoriaSelect">

                        <option value="">
                            Seleccionar subcategoría
                        </option>

                        <?php foreach($subcategorias as $subcategoriaFila): ?>

                            <option value="<?= (int) $subcategoriaFila['id'] ?>"
                                    data-categoria="<?= (int) $subcategoriaFila['categoria_id'] ?>"
                                <?= (int) ($producto['subcategoria_id'] ?? 0) === (int) $subcategoriaFila['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subcategoriaFila['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="input-group">

                    <label>Género</label>

                    <select name="genero" required>

                        <option value="Hombre" <?= $producto['genero'] == 'Hombre' ? 'selected' : '' ?>>
                            Hombre
                        </option>

                        <option value="Mujer" <?= $producto['genero'] == 'Mujer' ? 'selected' : '' ?>>
                            Mujer
                        </option>

                        <option value="Niños" <?= $producto['genero'] == 'Niños' ? 'selected' : '' ?>>
                            Niños
                        </option>

                    </select>

                </div>

            </div>

            <div class="input-group">

                <label>Agregar nuevas imágenes</label>

                <input type="file"
                       name="imagenes[]"
                       accept="image/*"
                       multiple>

            </div>

            <div class="input-group">

                <label class="admin-check">
                    <input type="checkbox"
                           name="activo"
                           <?= (int) ($producto['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    Visible en tienda
                </label>

                <small>
                    Si lo ocultás, no aparece en la tienda pero se conserva para pedidos anteriores.
                </small>

            </div>

            <div class="input-group">

                <label>Descripción</label>

                <textarea name="descripcion"
                          rows="5"><?= htmlspecialchars($producto['descripcion']) ?></textarea>

            </div>

            <button type="submit"
                    class="btn-admin-agregar">
                Guardar Cambios
            </button>

        </form>

        <section class="pedido-panel" style="margin-top:30px;">

            <div class="admin-section-title">

                <div>

                    <h2>Galería del producto</h2>

                    <p>Administra las imágenes visibles en el detalle del producto.</p>

                </div>

            </div>

            <div class="admin-galeria-producto">

                <?php if(mysqli_num_rows($imagenesProducto) > 0): ?>

                    <?php while($img = mysqli_fetch_assoc($imagenesProducto)): ?>

                        <div class="admin-img-card">

                            <img src="../<?= $img['imagen'] ?>" alt="Imagen producto">

                            <?php if($img['principal'] == 1): ?>

                                <span class="badge-principal">
                                    Principal
                                </span>

                            <?php endif; ?>

                            <div class="admin-img-actions">

                                <a href="editar_productos.php?id=<?= $id ?>&principal=<?= $img['id'] ?>&csrf_token=<?= csrfToken() ?>">
                                    Marcar principal
                                </a>

                                <a href="editar_productos.php?id=<?= $id ?>&eliminar_img=<?= $img['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                   onclick="return confirm('¿Eliminar esta imagen?')">
                                    Eliminar
                                </a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p style="color:#aaa;">
                        Este producto todavía no tiene imágenes en la galería.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>
<script src="../java/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const categoriaSelect = document.getElementById('categoriaSelect');
    const subcategoriaSelect = document.getElementById('subcategoriaSelect');

    if(!categoriaSelect || !subcategoriaSelect){
        return;
    }

    function filtrarSubcategorias(){
        const categoriaId = categoriaSelect.value;

        Array.from(subcategoriaSelect.options).forEach(function(option){
            if(!option.value){
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.categoria !== categoriaId;
        });

        if(subcategoriaSelect.selectedOptions[0] && subcategoriaSelect.selectedOptions[0].hidden){
            subcategoriaSelect.value = '';
        }
    }

    categoriaSelect.addEventListener('change', filtrarSubcategorias);
    filtrarSubcategorias();
});
</script>
</body>
</html>
