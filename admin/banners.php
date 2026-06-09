<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/includes/upload_helper.php";

if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

$editando = null;
$mensaje = "";

$destinosBanner = [
    "productos.php" => "Todos los productos",
    "productos.php?genero=Hombre" => "Colección hombre",
    "productos.php?genero=Mujer" => "Colección mujer",
    "productos.php?genero=Niños" => "Colección niños",
    "productos.php?sale=1" => "Ofertas",
    "contacto.php" => "Contacto"
];

if(isset($_GET['editar'])){
    $id = (int) $_GET['editar'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM banners WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editando = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(!validarCsrf()){
        header("Location: banners.php");
        exit;
    }

    $accion = $_POST['accion'] ?? 'guardar_banner';
    $id = (int) ($_POST['id'] ?? 0);

    if($accion === 'toggle_banner' && $id > 0){
        $stmt = mysqli_prepare($conn, "UPDATE banners SET activo = IF(activo = 1, 0, 1) WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: banners.php");
        exit;
    }

    if($accion === 'eliminar_banner' && $id > 0){
        $stmt = mysqli_prepare($conn, "DELETE FROM banners WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: banners.php");
        exit;
    }

    if($accion === 'guardar_promo'){
        $texto = trim($_POST['texto'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if($texto !== ''){
            $stmt = mysqli_prepare($conn, "INSERT INTO promociones_home (texto, activo) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "si", $texto, $activo);
            mysqli_stmt_execute($stmt);
            header("Location: banners.php");
            exit;
        }

        $mensaje = "Escribí el texto del cartel superior.";
    }

    if($accion === 'toggle_promo' && $id > 0){
        $stmt = mysqli_prepare($conn, "UPDATE promociones_home SET activo = IF(activo = 1, 0, 1) WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: banners.php");
        exit;
    }

    if($accion === 'eliminar_promo' && $id > 0){
        $stmt = mysqli_prepare($conn, "DELETE FROM promociones_home WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: banners.php");
        exit;
    }

    if($accion === 'guardar_banner'){
        $titulo = trim($_POST['titulo'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $imagen = trim($_POST['imagen_actual'] ?? '');
        $destino = trim($_POST['destino'] ?? 'productos.php');
        $enlaceManual = trim($_POST['enlace_manual'] ?? '');
        $enlace = $enlaceManual !== '' ? $enlaceManual : $destino;
        $activo = isset($_POST['activo']) ? 1 : 0;

        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE){
            $imagenSubida = guardarImagenSubida($_FILES['imagen'], $mensaje);

            if($imagenSubida){
                $imagen = $imagenSubida;
            }
        }

        if($titulo !== '' && $imagen !== '' && $mensaje === ''){
            if($id > 0){
                $stmt = mysqli_prepare($conn, "UPDATE banners SET titulo = ?, subtitulo = ?, imagen = ?, enlace = ?, activo = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssssii", $titulo, $subtitulo, $imagen, $enlace, $activo, $id);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO banners (titulo, subtitulo, imagen, enlace, activo) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssi", $titulo, $subtitulo, $imagen, $enlace, $activo);
            }

            mysqli_stmt_execute($stmt);
            header("Location: banners.php");
            exit;
        }

        if($mensaje === ''){
            $mensaje = "Completá el título y subí una imagen para la portada.";
        }
    }
}

$banners = mysqli_query($conn, "SELECT * FROM banners ORDER BY id DESC");
$promociones = mysqli_query($conn, "SELECT * FROM promociones_home ORDER BY id DESC");

$enlaceActual = $editando['enlace'] ?? 'productos.php';
$destinoActual = array_key_exists($enlaceActual, $destinosBanner) ? $enlaceActual : 'productos.php';
$enlaceManualActual = array_key_exists($enlaceActual, $destinosBanner) ? '' : $enlaceActual;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners | Admin</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="admin-body">
<div class="admin-container">
    <?php include("includes/sidebar.php"); ?>

    <main class="admin-content">
        <section class="admin-hero small-hero">
            <div>
                <span class="admin-badge">Contenido</span>
                <h1>Banners y promociones</h1>
                <p>Administrá la portada principal y el cartel superior de la tienda.</p>
            </div>
        </section>

        <?php if($mensaje): ?>
            <div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="pedido-panel">
            <h2><?= $editando ? 'Editar portada' : 'Crear portada' ?></h2>

            <form method="POST"
                  enctype="multipart/form-data"
                  class="form-admin-premium">
                <?= csrfInput() ?>

                <input type="hidden" name="accion" value="guardar_banner">
                <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">
                <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($editando['imagen'] ?? '') ?>">

                <div class="admin-grid">
                    <div class="input-group">
                        <label>Título</label>
                        <input name="titulo"
                               value="<?= htmlspecialchars($editando['titulo'] ?? '') ?>"
                               placeholder="Ej: Nueva colección fútbol"
                               required>
                    </div>

                    <div class="input-group">
                        <label>Subtítulo</label>
                        <input name="subtitulo"
                               value="<?= htmlspecialchars($editando['subtitulo'] ?? '') ?>"
                               placeholder="Ej: Botines, camisetas y accesorios para la temporada.">
                    </div>

                    <div class="input-group">
                        <label>Imagen de portada</label>
                        <input type="file"
                               name="imagen"
                               accept="image/*">
                        <small>Subí la imagen desde tu PC. Recomendado: horizontal y de buena calidad.</small>

                        <?php if(!empty($editando['imagen'])): ?>
                            <div style="margin-top:10px; display:flex; align-items:center; gap:10px;">
                                <img src="../<?= htmlspecialchars($editando['imagen']) ?>"
                                     alt="<?= htmlspecialchars($editando['titulo'] ?? 'Banner') ?>"
                                     style="width:110px; height:54px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                                <small><?= htmlspecialchars($editando['imagen']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label>Destino del botón</label>
                        <select name="destino">
                            <?php foreach($destinosBanner as $url => $label): ?>
                                <option value="<?= htmlspecialchars($url) ?>" <?= $destinoActual === $url ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Elegí una sección sin escribir rutas técnicas.</small>
                    </div>

                    <div class="input-group">
                        <label>Enlace personalizado</label>
                        <input name="enlace_manual"
                               value="<?= htmlspecialchars($enlaceManualActual) ?>"
                               placeholder="Opcional. Solo si necesitás una ruta especial.">
                    </div>

                    <div class="input-group">
                        <label>Estado</label>
                        <label class="admin-check">
                            <input type="checkbox"
                                   name="activo"
                                   <?= !isset($editando) || (int) $editando['activo'] === 1 ? 'checked' : '' ?>>
                            Activo
                        </label>
                    </div>
                </div>

                <button class="btn-admin-agregar" type="submit">
                    <?= $editando ? 'Guardar cambios' : 'Crear portada' ?>
                </button>

                <?php if($editando): ?>
                    <a class="btn-admin-secundario" href="banners.php">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="pedido-panel">
            <h2>Cartel superior</h2>

            <form method="POST" class="form-admin-premium">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="guardar_promo">

                <div class="admin-grid">
                    <div class="input-group">
                        <label>Texto del cartel</label>
                        <input name="texto"
                               placeholder="Ej: Banco Macro: 6 cuotas sin interés en productos seleccionados"
                               required>
                        <small>Este texto aparece en la barra roja que pasa arriba de la portada.</small>
                    </div>

                    <div class="input-group">
                        <label>Estado</label>
                        <label class="admin-check">
                            <input type="checkbox" name="activo" checked>
                            Activo
                        </label>
                    </div>
                </div>

                <button class="btn-admin-agregar" type="submit">Crear cartel</button>
            </form>
        </section>

        <div class="tabla-admin tabla-premium">
            <table>
                <thead>
                    <tr>
                        <th>Portada</th>
                        <th>Imagen</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($b = mysqli_fetch_assoc($banners)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($b['titulo']) ?></strong><br>
                                <small><?= htmlspecialchars($b['subtitulo'] ?? '') ?></small>
                            </td>
                            <td>
                                <span style="display:inline-flex; align-items:center; gap:10px;">
                                    <img src="../<?= htmlspecialchars($b['imagen']) ?>"
                                         alt="<?= htmlspecialchars($b['titulo']) ?>"
                                         style="width:76px; height:40px; object-fit:cover; border-radius:5px;">
                                    <small><?= htmlspecialchars($b['imagen']) ?></small>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($b['enlace'] ?? '-') ?></td>
                            <td>
                                <span class="estado <?= $b['activo'] ? 'pagado' : 'cancelado' ?>">
                                    <?= $b['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="acciones-tabla">
                                <a class="btn-tabla editar" href="banners.php?editar=<?= (int) $b['id'] ?>">Editar</a>

                                <form method="POST" style="display:inline">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="toggle_banner">
                                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                    <button class="btn-tabla editar" type="submit">Cambiar estado</button>
                                </form>

                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar portada?')">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="eliminar_banner">
                                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                    <button class="btn-tabla eliminar" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="tabla-admin tabla-premium">
            <table>
                <thead>
                    <tr>
                        <th>Cartel superior</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($promo = mysqli_fetch_assoc($promociones)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($promo['texto']) ?></strong></td>
                            <td>
                                <span class="estado <?= $promo['activo'] ? 'pagado' : 'cancelado' ?>">
                                    <?= $promo['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="acciones-tabla">
                                <form method="POST" style="display:inline">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="toggle_promo">
                                    <input type="hidden" name="id" value="<?= (int) $promo['id'] ?>">
                                    <button class="btn-tabla editar" type="submit">Cambiar estado</button>
                                </form>

                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar cartel?')">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="eliminar_promo">
                                    <input type="hidden" name="id" value="<?= (int) $promo['id'] ?>">
                                    <button class="btn-tabla eliminar" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
