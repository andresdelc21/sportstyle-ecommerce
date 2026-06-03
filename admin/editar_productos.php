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
    $precio = (int) $_POST['precio'];
    $precio_original = (int) $_POST['precio_original'];
    $stock = (int) $_POST['stock'];
    $categoria = trim($_POST['categoria']);
    $genero = trim($_POST['genero']);

    /* IMAGEN ACTUAL */
    $imagen = $producto['imagen'];

    /* SUBIR NUEVA IMAGEN */
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0){

        $nombreImagen = time() . "_" . basename($_FILES['imagen']['name']);

        $rutaDestino = "../uploads/" . $nombreImagen;

        if(move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)){

            $imagen = "uploads/" . $nombreImagen;

        } else {

            $error = "Error al subir la imagen";

        }

    }

    /* UPDATE */
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
            "ssiiisssi",
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

            /* RECARGAR DATOS */
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

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

        <h2 class="admin-logo">
            Sport<span>Style</span>
        </h2>

        <nav class="admin-menu">

            <a href="index.php">
                🏠 Dashboard
            </a>

            <a href="productos.php"
               class="activo-admin">
                📦 Productos
            </a>

            <a href="pedidos.php">
                🧾 Pedidos
            </a>

            <a href="usuarios.php">
                👥 Usuarios
            </a>

            <a href="ventas.php">
                📊 Ventas
            </a>

        </nav>

    </aside>

    <!-- CONTENIDO -->
    <main class="admin-content">

        <div class="admin-top">

            <div>

                <h1>
                    Editar Producto
                </h1>

                <p>
                    Modifica la información del producto
                </p>

            </div>

        </div>

        <?php if($success): ?>
            <p class="success-msg"><?= $success ?></p>
        <?php endif; ?>

        <?php if($error): ?>
            <p class="error-msg"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data"
              class="form-admin">

            <div class="admin-grid">

                <div class="input-group">

                    <label>Nombre</label>

                    <input type="text"
                           name="nombre"
                           value="<?= $producto['nombre'] ?>"
                           required>

                </div>

                <div class="input-group">

                    <label>Precio</label>

                    <input type="number"
                           name="precio"
                           value="<?= $producto['precio'] ?>"
                           required>

                </div>

                <div class="input-group">

                    <label>Precio Original</label>

                    <input type="number"
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

                <!-- CATEGORÍA -->
                <div class="input-group">

                    <label>Categoría</label>

                    <select name="categoria" required>

                        <option value="1"
                            <?= $producto['categoria_id'] == '1' ? 'selected' : '' ?>>
                            Calzado
                        </option>

                        <option value="2"
                            <?= $producto['categoria_id'] == '2' ? 'selected' : '' ?>>
                            Remeras
                        </option>

                        <option value="3"
                            <?= $producto['categoria_id'] == '3' ? 'selected' : '' ?>>
                            Pantalones / Shorts
                        </option>

                        <option value="4"
                            <?= $producto['categoria_id'] == '4' ? 'selected' : '' ?>>
                            Accesorios
                        </option>

                    </select>

                </div>

                <!-- GÉNERO -->
                <div class="input-group">

                    <label>Género</label>

                    <select name="genero" required>

                        <option value="Hombre"
                            <?= $producto['genero'] == 'Hombre' ? 'selected' : '' ?>>
                            Hombre
                        </option>

                        <option value="Mujer"
                            <?= $producto['genero'] == 'Mujer' ? 'selected' : '' ?>>
                            Mujer
                        </option>

                        <option value="Niños"
                            <?= $producto['genero'] == 'Niños' ? 'selected' : '' ?>>
                            Niños
                        </option>

                    </select>

                </div>

            </div>

            <!-- IMAGEN ACTUAL -->
            <div class="input-group">

                <label>Imagen Actual</label>

                <br>

                <img src="../<?= $producto['imagen'] ?>"
                     width="120"
                     style="border-radius:10px; margin-top:10px;">

            </div>

            <!-- NUEVA IMAGEN -->
            <div class="input-group">

                <label>Nueva Imagen</label>

                <input type="file"
                       name="imagen"
                       accept="image/*">

            </div>

            <div class="input-group">

                <label>Descripción</label>

                <textarea name="descripcion"
                          rows="5"><?= $producto['descripcion'] ?></textarea>

            </div>

            <button type="submit"
                    class="btn-admin-agregar">

                Guardar Cambios

            </button>

        </form>

    </main>

</div>

</body>
</html>