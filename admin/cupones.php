<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";

if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

$editando = null;
$mensaje = '';

if(isset($_GET['editar'])){
    $id = (int) $_GET['editar'];
    $stmtEditar = mysqli_prepare($conn, "SELECT * FROM cupones WHERE id = ?");
    mysqli_stmt_bind_param($stmtEditar, "i", $id);
    mysqli_stmt_execute($stmtEditar);
    $res = mysqli_stmt_get_result($stmtEditar);
    $editando = mysqli_fetch_assoc($res);
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!validarCsrf()){
        header("Location: cupones.php");
        exit;
    }

    $accion = $_POST['accion'] ?? 'guardar';
    $id = (int) ($_POST['id'] ?? 0);

    if($accion === 'toggle' && $id > 0){
        $stmt = mysqli_prepare($conn, "UPDATE cupones SET activo = IF(activo = 1, 0, 1) WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: cupones.php");
        exit;
    }

    if($accion === 'eliminar' && $id > 0){
        $stmt = mysqli_prepare($conn, "DELETE FROM cupones WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        header("Location: cupones.php");
        exit;
    }

    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $tipo = 'porcentaje';
    $valor = (float) ($_POST['valor'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if($codigo && $valor > 0){
        if($id > 0){
            $stmt = mysqli_prepare($conn, "UPDATE cupones SET codigo=?, tipo=?, valor=?, activo=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssdii", $codigo, $tipo, $valor, $activo, $id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO cupones (codigo, tipo, valor, activo) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdi", $codigo, $tipo, $valor, $activo);
        }
        mysqli_stmt_execute($stmt);
        header("Location: cupones.php");
        exit;
    }

    $mensaje = 'Completá código y valor.';
}

$cupones = mysqli_query($conn, "SELECT * FROM cupones ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Cupones | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Promociones</span><h1>Cupones</h1><p>Creá descuentos para aplicar desde el carrito.</p></div></section>
<?php if($mensaje): ?><div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<section class="pedido-panel">
<form method="POST" class="form-admin-premium">
<?= csrfInput() ?>
<input type="hidden" name="accion" value="guardar">
<input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
<div class="admin-grid">
<div class="input-group"><label>Código</label><input name="codigo" value="<?= htmlspecialchars($editando['codigo'] ?? '') ?>" required></div>
<div class="input-group"><label>Tipo</label><input value="Porcentaje" readonly></div>
<div class="input-group"><label>Valor (%)</label><input type="number" step="0.01" name="valor" value="<?= htmlspecialchars($editando['valor'] ?? '') ?>" required></div>
<div class="input-group"><label>Estado</label><label class="admin-check"><input type="checkbox" name="activo" <?= !isset($editando) || (int)$editando['activo'] === 1 ? 'checked' : '' ?>> Activo</label></div>
</div>
<button class="btn-admin-agregar" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear cupón' ?></button>
<?php if($editando): ?><a class="btn-admin-secundario" href="cupones.php">Cancelar</a><?php endif; ?>
</form>
</section>
<div class="tabla-admin tabla-premium"><table><thead><tr><th>Código</th><th>Tipo</th><th>Valor</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php while($c = mysqli_fetch_assoc($cupones)): ?><tr><td><strong><?= htmlspecialchars($c['codigo']) ?></strong></td><td><?= htmlspecialchars($c['tipo']) ?></td><td><?= $c['tipo'] === 'porcentaje' ? number_format($c['valor'], 0, ',', '.') . '%' : '$' . number_format($c['valor'], 0, ',', '.') ?></td><td><span class="estado <?= $c['activo'] ? 'pagado' : 'cancelado' ?>"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td><td class="acciones-tabla"><a class="btn-tabla editar" href="cupones.php?editar=<?= $c['id'] ?>">Editar</a><form method="POST" style="display:inline"><?= csrfInput() ?><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn-tabla editar" type="submit">Cambiar estado</button></form><form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar cupón?')"><?= csrfInput() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn-tabla eliminar" type="submit">Eliminar</button></form></td></tr><?php endwhile; ?>
</tbody></table></div>
</main></div></body></html>
