<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php"; require_once __DIR__ . "/../config/conexion.php";
if(!isset($_SESSION['usuario_nombre'])){ header("Location: ../login.php"); exit; }
$editando=null; $mensaje='';
if(isset($_GET['eliminar'])){
    $id=(int)$_GET['eliminar'];
    $uso=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM productos WHERE categoria_id=$id"))['total'];
    if($uso==0){ mysqli_query($conn,"DELETE FROM categorias WHERE id=$id"); }
    else { $mensaje='No se puede eliminar una categoría con productos asociados.'; }
}
if(isset($_GET['editar'])){ $id=(int)$_GET['editar']; $editando=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM categorias WHERE id=$id")); }
if($_SERVER['REQUEST_METHOD']==='POST'){ $id=(int)($_POST['id']??0); $nombre=trim($_POST['nombre']??''); if($nombre){ if($id>0){$stmt=mysqli_prepare($conn,"UPDATE categorias SET nombre=? WHERE id=?"); mysqli_stmt_bind_param($stmt,"si",$nombre,$id);}else{$stmt=mysqli_prepare($conn,"INSERT INTO categorias (nombre) VALUES (?)"); mysqli_stmt_bind_param($stmt,"s",$nombre);} mysqli_stmt_execute($stmt); header("Location: categorias.php"); exit; } }
$categorias=mysqli_query($conn,"SELECT c.*, COUNT(p.id) AS productos FROM categorias c LEFT JOIN productos p ON p.categoria_id=c.id GROUP BY c.id ORDER BY c.nombre ASC");
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Categorías | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head><body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Catálogo</span><h1>Categorías</h1><p>Organizá el catálogo de productos.</p></div></section>
<?php if($mensaje): ?><div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<section class="pedido-panel"><form method="POST" class="form-admin-premium"><input type="hidden" name="id" value="<?= (int)($editando['id']??0) ?>"><div class="admin-grid"><div class="input-group"><label>Nombre</label><input name="nombre" value="<?= htmlspecialchars($editando['nombre']??'') ?>" required></div></div><button class="btn-admin-agregar"><?= $editando?'Guardar cambios':'Crear categoría' ?></button> <?php if($editando): ?><a class="btn-admin-secundario" href="categorias.php">Cancelar</a><?php endif; ?></form></section>
<div class="tabla-admin tabla-premium"><table><thead><tr><th>Categoría</th><th>Productos</th><th>Acciones</th></tr></thead><tbody><?php while($c=mysqli_fetch_assoc($categorias)): ?><tr><td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td><td><?= (int)$c['productos'] ?></td><td class="acciones-tabla"><a class="btn-tabla editar" href="categorias.php?editar=<?= $c['id'] ?>">✎</a><a class="btn-tabla eliminar" href="categorias.php?eliminar=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar categoría?')">x</a></td></tr><?php endwhile; ?></tbody></table></div>
</main></div></body></html>
