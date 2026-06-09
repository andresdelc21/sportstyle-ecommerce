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
$categorias = [];

if($resultadoCategorias){
    while($categoria = mysqli_fetch_assoc($resultadoCategorias)){
        $categorias[] = $categoria;
    }
}

/* TRAER SUBCATEGORÍAS */
$sqlSubcategorias = "SELECT * FROM subcategorias ORDER BY categoria_id ASC, nombre ASC";
$resultadoSubcategorias = mysqli_query($conn, $sqlSubcategorias);
$subcategorias = [];

if($resultadoSubcategorias){
    while($subcategoria = mysqli_fetch_assoc($resultadoSubcategorias)){
        $subcategorias[] = $subcategoria;
    }
}

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
    $subcategoria_id = !empty($_POST['subcategoria_id'])
        ? (int) $_POST['subcategoria_id']
        : null;
    $marca_id = (int) $_POST['marca_id'];
    $genero = trim($_POST['genero']);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $imagenesSubidas = [];
    $imagen = "";

    if(!$error && isset($_FILES['imagenes'])){

        $totalImagenes = count($_FILES['imagenes']['name']);

        for($i = 0; $i < $totalImagenes; $i++){

            if($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_NO_FILE){
                continue;
            }

            $archivo = [
                'name' => $_FILES['imagenes']['name'][$i],
                'type' => $_FILES['imagenes']['type'][$i],
                'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                'error' => $_FILES['imagenes']['error'][$i],
                'size' => $_FILES['imagenes']['size'][$i]
            ];

            $rutaImagen = guardarImagenSubida($archivo, $error);

            if($rutaImagen){
                $imagenesSubidas[] = $rutaImagen;
            }

            if($error){
                break;
            }
        }

    }

    if(!$error && empty($imagenesSubidas)){
        $error = "Cargá al menos una imagen del producto.";
    }

    if(!$error){
        $imagen = $imagenesSubidas[0];
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
            subcategoria_id,
            marca_id,
            genero,
            imagen,
            activo
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?
        )";

        $stmt = mysqli_prepare($conn, $sql);

        if($stmt){

            mysqli_stmt_bind_param(
                $stmt,
                "ssiiiiiissi",
                $nombre,
                $descripcion,
                $precio,
                $precio_original,
                $stock,
                $categoria_id,
                $subcategoria_id,
                $marca_id,
                $genero,
                $imagen,
                $activo
            );

            if(mysqli_stmt_execute($stmt)){
                $productoId = mysqli_insert_id($conn);

                foreach($imagenesSubidas as $orden => $rutaImagen){
                    $principal = $orden === 0 ? 1 : 0;
                    $ordenImagen = $orden + 1;

                    $sqlImagen = "INSERT INTO imagenes_productos
                                  (producto_id, imagen, orden, principal)
                                  VALUES (?, ?, ?, ?)";

                    $stmtImagen = mysqli_prepare($conn, $sqlImagen);

                    if($stmtImagen){
                        mysqli_stmt_bind_param(
                            $stmtImagen,
                            "isii",
                            $productoId,
                            $rutaImagen,
                            $ordenImagen,
                            $principal
                        );

                        mysqli_stmt_execute($stmtImagen);
                        mysqli_stmt_close($stmtImagen);
                    }
                }

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

                        <select name="categoria_id"
                                id="categoriaSelect"
                                required>

                            <option value="">
                                Seleccionar categoría
                            </option>

                            <?php foreach($categorias as $categoria): ?>

                                <option value="<?= $categoria['id'] ?>">
                                    <?= $categoria['nombre'] ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="input-group">

                        <label>Subcategoría</label>

                        <select name="subcategoria_id"
                                id="subcategoriaSelect">

                            <option value="">
                                Seleccionar subcategoría
                            </option>

                            <?php foreach($subcategorias as $subcategoria): ?>

                                <option value="<?= (int) $subcategoria['id'] ?>"
                                        data-categoria="<?= (int) $subcategoria['categoria_id'] ?>">
                                    <?= htmlspecialchars($subcategoria['nombre']) ?>
                                </option>

                            <?php endforeach; ?>

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

                    <label>Imágenes del producto</label>

                    <input type="file"
                           name="imagenes[]"
                           accept="image/*"
                           multiple
                           required>

                    <small>
                        La primera imagen queda como principal. Las demás se muestran como galería.
                    </small>

                </div>

                <div class="input-group">

                    <label class="admin-check">
                        <input type="checkbox"
                               name="activo"
                               checked>
                        Visible en tienda
                    </label>

                    <small>
                        Si lo desmarcás, queda guardado en el admin pero no aparece para clientes.
                    </small>

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
