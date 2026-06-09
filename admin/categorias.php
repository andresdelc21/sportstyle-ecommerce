<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";

if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
$editandoCategoria = null;
$editandoSubcategoria = null;

if(isset($_GET['editar_categoria'])){
    $id = (int) $_GET['editar_categoria'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM categorias WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editandoCategoria = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if(isset($_GET['editar_subcategoria'])){
    $id = (int) $_GET['editar_subcategoria'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM subcategorias WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editandoSubcategoria = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(!validarCsrf()){
        header("Location: categorias.php");
        exit;
    }

    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if($accion === 'guardar_categoria'){
        $nombre = trim($_POST['nombre'] ?? '');

        if($nombre !== ''){
            if($id > 0){
                $stmt = mysqli_prepare($conn, "UPDATE categorias SET nombre = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $nombre, $id);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO categorias (nombre) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $nombre);
            }

            mysqli_stmt_execute($stmt);
            header("Location: categorias.php");
            exit;
        }
    }

    if($accion === 'eliminar_categoria' && $id > 0){
        $stmtUsoProductos = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM productos WHERE categoria_id = ?");
        mysqli_stmt_bind_param($stmtUsoProductos, "i", $id);
        mysqli_stmt_execute($stmtUsoProductos);
        $usoProductos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUsoProductos))['total'];

        $stmtUsoSubcategorias = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM subcategorias WHERE categoria_id = ?");
        mysqli_stmt_bind_param($stmtUsoSubcategorias, "i", $id);
        mysqli_stmt_execute($stmtUsoSubcategorias);
        $usoSubcategorias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUsoSubcategorias))['total'];

        if($usoProductos == 0 && $usoSubcategorias == 0){
            $stmtDel = mysqli_prepare($conn, "DELETE FROM categorias WHERE id = ?");
            mysqli_stmt_bind_param($stmtDel, "i", $id);
            mysqli_stmt_execute($stmtDel);
            header("Location: categorias.php");
            exit;
        }

        $mensaje = "No se puede eliminar una categoría con productos o subcategorías asociadas.";
    }

    if($accion === 'guardar_subcategoria'){
        $nombre = trim($_POST['nombre'] ?? '');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);

        if($nombre !== '' && $categoriaId > 0){
            if($id > 0){
                $stmt = mysqli_prepare($conn, "UPDATE subcategorias SET nombre = ?, categoria_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sii", $nombre, $categoriaId, $id);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO subcategorias (nombre, categoria_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "si", $nombre, $categoriaId);
            }

            mysqli_stmt_execute($stmt);
            header("Location: categorias.php");
            exit;
        }
    }

    if($accion === 'eliminar_subcategoria' && $id > 0){
        $stmtUso = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM productos WHERE subcategoria_id = ?");
        mysqli_stmt_bind_param($stmtUso, "i", $id);
        mysqli_stmt_execute($stmtUso);
        $uso = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUso))['total'];

        if($uso == 0){
            $stmtDel = mysqli_prepare($conn, "DELETE FROM subcategorias WHERE id = ?");
            mysqli_stmt_bind_param($stmtDel, "i", $id);
            mysqli_stmt_execute($stmtDel);
            header("Location: categorias.php");
            exit;
        }

        $mensaje = "No se puede eliminar una subcategoría con productos asociados.";
    }
}

$categorias = mysqli_query(
    $conn,
    "SELECT c.*, COUNT(DISTINCT p.id) AS productos, COUNT(DISTINCT s.id) AS subcategorias
     FROM categorias c
     LEFT JOIN productos p ON p.categoria_id = c.id
     LEFT JOIN subcategorias s ON s.categoria_id = c.id
     GROUP BY c.id
     ORDER BY c.id ASC"
);

$categoriasSelect = mysqli_query($conn, "SELECT * FROM categorias ORDER BY id ASC");

$subcategorias = mysqli_query(
    $conn,
    "SELECT s.*, c.nombre AS categoria_nombre, COUNT(p.id) AS productos
     FROM subcategorias s
     INNER JOIN categorias c ON c.id = s.categoria_id
     LEFT JOIN productos p ON p.subcategoria_id = s.id
     GROUP BY s.id
     ORDER BY c.id ASC, s.nombre ASC"
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías | Admin</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="admin-body">
<div class="admin-container">
    <?php include("includes/sidebar.php"); ?>

    <main class="admin-content">
        <section class="admin-hero small-hero">
            <div>
                <span class="admin-badge">Catálogo</span>
                <h1>Categorías</h1>
                <p>Organizá categorías principales y subcategorías del catálogo.</p>
            </div>
        </section>

        <?php if($mensaje): ?>
            <div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <section class="pedido-panel">
            <div class="admin-section-title">
                <div>
                    <h2><?= $editandoCategoria ? 'Editar categoría principal' : 'Crear categoría principal' ?></h2>
                    <p>Ejemplos: Calzado, Ropa, Accesorios o Deportes.</p>
                </div>
            </div>

            <form method="POST" class="form-admin-premium">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="guardar_categoria">
                <input type="hidden" name="id" value="<?= (int) ($editandoCategoria['id'] ?? 0) ?>">

                <div class="admin-grid">
                    <div class="input-group">
                        <label>Nombre</label>
                        <input name="nombre" value="<?= htmlspecialchars($editandoCategoria['nombre'] ?? '') ?>" required>
                    </div>
                </div>

                <button class="btn-admin-agregar">
                    <?= $editandoCategoria ? 'Guardar cambios' : 'Crear categoría' ?>
                </button>

                <?php if($editandoCategoria): ?>
                    <a class="btn-admin-secundario" href="categorias.php">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>

        <div class="tabla-admin tabla-premium">
            <table>
                <thead>
                    <tr>
                        <th>Categoría principal</th>
                        <th>Subcategorías</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($c = mysqli_fetch_assoc($categorias)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                            <td><?= (int) $c['subcategorias'] ?></td>
                            <td><?= (int) $c['productos'] ?></td>
                            <td class="acciones-tabla">
                                <a class="btn-tabla editar" href="categorias.php?editar_categoria=<?= (int) $c['id'] ?>">Editar</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar categoría?')">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="eliminar_categoria">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button class="btn-tabla eliminar" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <section class="pedido-panel" style="margin-top:24px;">
            <div class="admin-section-title">
                <div>
                    <h2><?= $editandoSubcategoria ? 'Editar subcategoría' : 'Crear subcategoría' ?></h2>
                    <p>Ejemplos: Remeras, Camperas, Running, Padel o Bolsos y Mochilas.</p>
                </div>
            </div>

            <form method="POST" class="form-admin-premium">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="guardar_subcategoria">
                <input type="hidden" name="id" value="<?= (int) ($editandoSubcategoria['id'] ?? 0) ?>">

                <div class="admin-grid">
                    <div class="input-group">
                        <label>Categoría principal</label>
                        <select name="categoria_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php while($categoria = mysqli_fetch_assoc($categoriasSelect)): ?>
                                <option value="<?= (int) $categoria['id'] ?>"
                                    <?= (int) ($editandoSubcategoria['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Subcategoría</label>
                        <input name="nombre" value="<?= htmlspecialchars($editandoSubcategoria['nombre'] ?? '') ?>" required>
                    </div>
                </div>

                <button class="btn-admin-agregar">
                    <?= $editandoSubcategoria ? 'Guardar cambios' : 'Crear subcategoría' ?>
                </button>

                <?php if($editandoSubcategoria): ?>
                    <a class="btn-admin-secundario" href="categorias.php">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>

        <div class="tabla-admin tabla-premium">
            <table>
                <thead>
                    <tr>
                        <th>Subcategoría</th>
                        <th>Categoría principal</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($s = mysqli_fetch_assoc($subcategorias)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($s['categoria_nombre']) ?></td>
                            <td><?= (int) $s['productos'] ?></td>
                            <td class="acciones-tabla">
                                <a class="btn-tabla editar" href="categorias.php?editar_subcategoria=<?= (int) $s['id'] ?>">Editar</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar subcategoría?')">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="accion" value="eliminar_subcategoria">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
