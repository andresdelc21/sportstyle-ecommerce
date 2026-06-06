<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";

require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/includes/upload_helper.php");

/* PROTEGER */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* TRAER CATEGORÍAS */
$sqlCategorias = "SELECT * FROM categorias ORDER BY nombre ASC";
$resultadoCategorias = mysqli_query($conn, $sqlCategorias);

/* TRAER MARCAS */
$sqlMarcas = "SELECT * FROM marcas ORDER BY nombre ASC";
$resultadoMarcas = mysqli_query($conn, $sqlMarcas);

$marcas = [];

while($marca = mysqli_fetch_assoc($resultadoMarcas)){
    $marcas[] = $marca;
}

$success = "";
$error = "";

/* GUARDAR PRODUCTO */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(!validarCsrf()){
        $error = "Solicitud inválida.";
    }

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (int) $_POST['precio'];
    $precio_original = (int) $_POST['precio_original'];
    $stock = (int) $_POST['stock'];
    $categoria_id = (int) $_POST['categoria_id'];
    $marca_id = (int) $_POST['marca_id'];
    $genero = trim($_POST['genero']);

    $imagen = "";

    if(!$error && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0){

        $rutaImagen = guardarImagenSubida($_FILES['imagen'], $error);

        if($rutaImagen){
            $imagen = $rutaImagen;
        }

    }

    if(!$error){

        $sql = "INSERT INTO productos
        (
            nombre,
            descripcion,
            precio,
            precio_original,
            stock,
            categoria_id,
            marca_id,
            genero,
            imagen
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?
        )";

        $stmt = mysqli_prepare($conn, $sql);

        if($stmt){

            mysqli_stmt_bind_param(
                $stmt,
                "ssiiiiiss",
                $nombre,
                $descripcion,
                $precio,
                $precio_original,
                $stock,
                $categoria_id,
                $marca_id,
                $genero,
                $imagen
            );

            if(mysqli_stmt_execute($stmt)){
                $success = "Producto agregado correctamente";
            } else {
                $error = "Error al agregar producto";
            }

            mysqli_stmt_close($stmt);

        } else {
            $error = mysqli_error($conn);
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

    <title>Agregar Producto | Admin</title>

    <link rel="stylesheet"
          href="../css/estilos.css">

</head>

<body class="admin-body">

<div class="admin-container">

    <?php include("includes/sidebar.php"); ?>

    <main class="admin-content">

        <section class="admin-hero small-hero">

            <div>

                <span class="admin-badge">
                    Catálogo SportStyle
                </span>

                <h1>
                    Agregar producto
                </h1>

                <p>
                    Carga productos con marca, categoría, género, stock, precio e imagen.
                </p>

            </div>

            <div class="admin-hero-actions">

                <a href="productos.php"
                   class="btn-admin-secundario">
                    ← Volver a productos
                </a>

            </div>

        </section>

        <?php if($success): ?>

            <div class="admin-alert success-msg">
                <?= $success ?>
            </div>

        <?php endif; ?>

        <?php if($error): ?>

            <div class="admin-alert error-msg">
                <?= $error ?>
            </div>

        <?php endif; ?>

        <section class="pedido-panel form-producto-premium">

            <div class="admin-section-title">

                <div>
                    <h2>Información del producto</h2>
                    <p>Completa los datos principales para publicar el producto.</p>
                </div>

            </div>

            <form method="POST"
                  enctype="multipart/form-data"
                  class="form-admin-premium">

                <?= csrfInput() ?>

                <div class="admin-grid">

                    <div class="input-group">

                        <label>Nombre</label>

                        <input type="text"
                               name="nombre"
                               placeholder="Ej: Zapatillas Running Pro"
                               required>

                    </div>

                    <div class="input-group">

                        <label>Precio</label>

                        <input type="number"
                               name="precio"
                               placeholder="Ej: 35000"
                               required>

                    </div>

                    <div class="input-group">

                        <label>Precio original</label>

                        <input type="number"
                               name="precio_original"
                               placeholder="Ej: 45000">

                    </div>

                    <div class="input-group">

                        <label>Stock</label>

                        <input type="number"
                               name="stock"
                               placeholder="Ej: 20"
                               required>

                    </div>

                    <div class="input-group">

                        <label>Categoría</label>

                        <select name="categoria_id" required>

                            <option value="">
                                Seleccionar categoría
                            </option>

                            <?php while($categoria = mysqli_fetch_assoc($resultadoCategorias)): ?>

                                <option value="<?= $categoria['id'] ?>">
                                    <?= $categoria['nombre'] ?>
                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>Marca</label>

                        <select name="marca_id" required>

                            <option value="">
                                Seleccionar marca
                            </option>

                            <?php foreach($marcas as $marca): ?>

                                <option value="<?= $marca['id'] ?>">
                                    <?= $marca['nombre'] ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>Género</label>

                        <select name="genero" required>

                            <option value="">
                                Seleccionar género
                            </option>

                            <option value="Hombre">
                                Hombre
                            </option>

                            <option value="Mujer">
                                Mujer
                            </option>

                            <option value="Niños">
                                Niños
                            </option>

                        </select>

                    </div>

                </div>

                <div class="input-group">

                    <label>Imagen del producto</label>

                    <input type="file"
                           name="imagen"
                           accept="image/*"
                           required>

                </div>

                <div class="input-group">

                    <label>Descripción</label>

                    <textarea name="descripcion"
                              rows="6"
                              placeholder="Describe el producto, sus características y beneficios."></textarea>

                </div>

                <div class="form-actions-admin">

                    <button type="submit"
                            class="btn-admin-agregar">
                        Guardar producto
                    </button>

                    <a href="productos.php"
                       class="btn-admin-secundario">
                        Cancelar
                    </a>

                </div>

            </form>

        </section>

    </main>

</div>
<script src="../java/admin.js"></script>
</body>
</html>
