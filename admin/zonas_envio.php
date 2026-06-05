<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/../config/conexion.php";

if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

$mensaje = '';
$editando = null;

if(isset($_GET['eliminar'])){
    $id = (int) $_GET['eliminar'];
    $stmt = mysqli_prepare($conn, "DELETE FROM zonas_envio WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: zonas_envio.php");
    exit;
}

if(isset($_GET['editar'])){
    $id = (int) $_GET['editar'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM zonas_envio WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editando = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $cp_desde = (int) ($_POST['cp_desde'] ?? 0);
    $cp_hasta = (int) ($_POST['cp_hasta'] ?? 0);
    $costo = (float) ($_POST['costo'] ?? 0);
    $envio_gratis_desde = (float) ($_POST['envio_gratis_desde'] ?? 0);

    if($nombre && $cp_desde > 0 && $cp_hasta >= $cp_desde){
        if($id > 0){
            $sql = "UPDATE zonas_envio SET nombre=?, cp_desde=?, cp_hasta=?, costo=?, envio_gratis_desde=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siiddi", $nombre, $cp_desde, $cp_hasta, $costo, $envio_gratis_desde, $id);
        } else {
            $sql = "INSERT INTO zonas_envio (nombre, cp_desde, cp_hasta, costo, envio_gratis_desde) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siidd", $nombre, $cp_desde, $cp_hasta, $costo, $envio_gratis_desde);
        }
        mysqli_stmt_execute($stmt);
        header("Location: zonas_envio.php");
        exit;
    }

    $mensaje = 'Completá nombre y rangos de CP válidos.';
}

$zonas = mysqli_query($conn, "SELECT * FROM zonas_envio ORDER BY cp_desde ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonas de envío | Admin</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="admin-body">
<div class="admin-container">
<?php include("includes/sidebar.php"); ?>
<main class="admin-content">
    <section class="admin-hero small-hero">
        <div>
            <span class="admin-badge">Logística</span>
            <h1>Zonas de envío</h1>
            <p>Administrá costos, códigos postales y mínimos para envío gratis.</p>
        </div>
    </section>

    <?php if($mensaje): ?><div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <section class="pedido-panel">
        <form method="POST" class="form-admin-premium">
            <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
            <div class="admin-grid">
                <div class="input-group">
                    <label>Nombre</label>
                    <input name="nombre" value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>" required>
                </div>
                <div class="input-group">
                    <label>CP desde</label>
                    <input type="number" name="cp_desde" value="<?= htmlspecialchars($editando['cp_desde'] ?? '') ?>" required>
                </div>
                <div class="input-group">
                    <label>CP hasta</label>
                    <input type="number" name="cp_hasta" value="<?= htmlspecialchars($editando['cp_hasta'] ?? '') ?>" required>
                </div>
                <div class="input-group">
                    <label>Costo</label>
                    <input type="number" step="0.01" name="costo" value="<?= htmlspecialchars($editando['costo'] ?? '') ?>" required>
                </div>
                <div class="input-group">
                    <label>Envío gratis desde</label>
                    <input type="number" step="0.01" name="envio_gratis_desde" value="<?= htmlspecialchars($editando['envio_gratis_desde'] ?? '0') ?>">
                </div>
            </div>
            <button class="btn-admin-agregar" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear zona' ?></button>
            <?php if($editando): ?><a class="btn-admin-secundario" href="zonas_envio.php">Cancelar</a><?php endif; ?>
        </form>
    </section>

    <div class="tabla-admin tabla-premium">
        <table>
            <thead><tr><th>Zona</th><th>Rango CP</th><th>Costo</th><th>Gratis desde</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php while($z = mysqli_fetch_assoc($zonas)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($z['nombre']) ?></strong></td>
                    <td><?= (int)$z['cp_desde'] ?> - <?= (int)$z['cp_hasta'] ?></td>
                    <td>$<?= number_format($z['costo'], 0, ',', '.') ?></td>
                    <td>$<?= number_format($z['envio_gratis_desde'], 0, ',', '.') ?></td>
                    <td class="acciones-tabla">
                        <a class="btn-tabla editar" href="zonas_envio.php?editar=<?= $z['id'] ?>">✎</a>
                        <a class="btn-tabla eliminar" href="zonas_envio.php?eliminar=<?= $z['id'] ?>" onclick="return confirm('¿Eliminar zona?')">x</a>
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
