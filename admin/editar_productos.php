<?php

session_start();

require_once(dirname(__FILE__) . "/../config/conexion.php");

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

/* ACCIONES IMÁGENES */
if(isset($_GET['principal'])){

    $img_id = (int) $_GET['principal'];

    mysqli_query($conn, "UPDATE imagenes_productos SET principal = 0 WHERE producto_id = $id");
    mysqli_query($conn, "UPDATE imagenes_productos SET principal = 1 WHERE id = $img_id AND producto_id = $id");

    $sqlPrincipal = "SELECT imagen FROM imagenes_productos WHERE id = $img_id AND producto_id = $id";
    $resPrincipal = mysqli_query($conn, $sqlPrincipal);
    $imgPrincipal = mysqli_fetch_assoc($resPrincipal);

    if($imgPrincipal){
        $rutaPrincipal = $imgPrincipal['imagen'];
        mysqli_query($conn, "UPDATE productos SET imagen = '$rutaPrincipal' WHERE id = $id");
    }

    header("Location: editar_productos.php?id=$id");
    exit;
}

if(isset($_GET['eliminar_img'])){

    $img_id = (int) $_GET['eliminar_img'];

    $sqlImg = "SELECT imagen, principal FROM imagenes_productos WHERE id = $img_id AND producto_id = $id";
    $resImg = mysqli_query($conn, $sqlImg);
    $imgData = mysqli_fetch_assoc($resImg);

    if($imgData){

        $rutaArchivo = "../" . $imgData['imagen'];

        if(file_exists($rutaArchivo)){
            unlink($rutaArchivo);
        }

        mysqli_query($conn, "DELETE FROM imagenes_productos WHERE id = $img_id AND producto_id = $id");

        if($imgData['principal'] == 1){

            $sqlNueva = "SELECT * FROM imagenes_productos WHERE producto_id = $id ORDER BY orden ASC, id ASC LIMIT 1";
            $resNueva = mysqli_query($conn, $sqlNueva);
            $nueva = mysqli_fetch_assoc($resNueva);

            if($nueva){
                mysqli_query($conn, "UPDATE imagenes_productos SET principal = 1 WHERE id = {$nueva['id']}");
                mysqli_query($conn, "UPDATE productos SET imagen = '{$nueva['imagen']}' WHERE id = $id");
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

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float) $_POST['precio'];
    $precio_original = (float) $_POST['precio_original'];
    $stock = (int) $_POST['stock'];
    $categoria = (int) $_POST['categoria'];
    $genero = trim($_POST['genero']);

    $imagen = $producto['imagen'];

    /* SUBIR IMÁGENES MÚLTIPLES */
    if(isset($_FILES['imagenes'])){

        $totalImagenes = count($_FILES['imagenes']['name']);

        for($i = 0; $i < $totalImagenes; $i++){

            if($_FILES['imagenes']['error'][$i] === 0){

                $nombreOriginal = basename($_FILES['imagenes']['name'][$i]);
                $nombreImagen = time() . "_" . $i . "_" . $nombreOriginal;

                $rutaTemporal = $_FILES['imagenes']['tmp_name'][$i];
                $rutaDestino = "../uploads/" . $nombreImagen;

                if(move_uploaded_file($rutaTemporal, $rutaDestino)){

                    $rutaBD = "uploads/" . $nombreImagen;

                    $sqlOrden = "SELECT COALESCE(MAX(orden), 0) + 1 AS nuevo_orden
                                 FROM imagenes_productos
                                 WHERE producto_id = $id";

                    $resOrden = mysqli_query($conn, $sqlOrden);
                    $ordenData = mysqli_fetch_assoc($resOrden);
                    $orden = (int) $ordenData['nuevo_orden'];

                    $sqlTieneImagenes = "SELECT COUNT(*) AS total FROM imagenes_productos WHERE producto_id = $id";
                    $resTieneImagenes = mysqli_query($conn, $sqlTieneImagenes);
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

                } else {

                    $error = "Error al subir una imagen";

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
            genero = ?,
            imagen = ?
            WHERE id = ?";

        $stmtUpdate = mysqli_prepare($conn, $update);

        mysqli_stmt_bind_param(
            $stmtUpdate,
            "ssddiissi",
            $nombre,
            $descripcion,
            $precio,
            $precio_original,
            $stock,
            $categoria,
            $genero,
            $imagen,
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
            $producto['genero'] = $genero;
            $producto['imagen'] = $imagen;

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

    <aside class="admin-sidebar">

        <h2 class="admin-logo">
            Sport<span>Style</span>
        </h2>

        <nav class="admin-menu">

            <a href="index.php">🏠 Dashboard</a>

            <a href="productos.php" class="activo-admin">📦 Productos</a>

            <a href="pedidos.php">🧾 Pedidos</a>

            <a href="usuarios.php">👥 Usuarios</a>

            <a href="ventas.php">📊 Ventas</a>

            <a href="../index.php">🏪 Ver tienda</a>

            <a href="../logout.php" class="logout-btn">🚪 Cerrar sesión</a>

        </nav>

    </aside>

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
            <div class="admin-alert success-msg">✅ <?= $success ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="admin-alert error-msg">❌ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data"
              class="form-admin form-admin-premium">

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

                    <select name="categoria" required>

                        <option value="1" <?= $producto['categoria_id'] == 1 ? 'selected' : '' ?>>
                            Calzado
                        </option>

                        <option value="2" <?= $producto['categoria_id'] == 2 ? 'selected' : '' ?>>
                            Remeras
                        </option>

                        <option value="3" <?= $producto['categoria_id'] == 3 ? 'selected' : '' ?>>
                            Pantalones / Shorts
                        </option>

                        <option value="4" <?= $producto['categoria_id'] == 4 ? 'selected' : '' ?>>
                            Accesorios
                        </option>

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

                                <a href="editar_productos.php?id=<?= $id ?>&principal=<?= $img['id'] ?>">
                                    ⭐ Principal
                                </a>

                                <a href="editar_productos.php?id=<?= $id ?>&eliminar_img=<?= $img['id'] ?>"
                                   onclick="return confirm('¿Eliminar esta imagen?')">
                                    🗑 Eliminar
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

</body>
</html>