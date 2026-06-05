<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php"; require_once __DIR__ . "/../config/conexion.php";
if(!isset($_SESSION['usuario_nombre'])){ header("Location: ../login.php"); exit; }
$editando=null; $mensaje='';
if(isset($_GET['eliminar'])){ $id=(int)$_GET['eliminar']; $uso=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM productos WHERE marca_id=$id"))['total']; if($uso==0){ mysqli_query($conn,"DELETE FROM marcas WHERE id=$id"); } else { $mensaje='No se puede eliminar una marca con productos asociados.'; } }
if(isset($_GET['editar'])){ $id=(int)$_GET['editar']; $editando=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM marcas WHERE id=$id")); }
if($_SERVER['REQUEST_METHOD']==='POST'){ $id=(int)($_POST['id']??0); $nombre=trim($_POST['nombre']??''); $logo=trim($_POST['logo']??''); if($nombre){ if($id>0){$stmt=mysqli_prepare($conn,"UPDATE marcas SET nombre=?, logo=? WHERE id=?"); mysqli_stmt_bind_param($stmt,"ssi",$nombre,$logo,$id);}else{$stmt=mysqli_prepare($conn,"INSERT INTO marcas (nombre, logo) VALUES (?, ?)"); mysqli_stmt_bind_param($stmt,"ss",$nombre,$logo);} mysqli_stmt_execute($stmt); header("Location: marcas.php"); exit; } }
$marcas=mysqli_query($conn,"SELECT m.*, COUNT(p.id) AS productos FROM marcas m LEFT JOIN productos p ON p.marca_id=m.id GROUP BY m.id ORDER BY m.nombre ASC");
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Marcas | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head><body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Catálogo</span><h1>Marcas</h1><p>Gestioná las marcas disponibles en la tienda.</p></div></section>
<?php if($mensaje): ?><div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<section class="pedido-panel"><form method="POST" class="form-admin-premium"><input type="hidden" name="id" value="<?= (int)($editando['id']??0) ?>"><div class="admin-grid"><div class="input-group"><label>Nombre</label><input name="nombre" value="<?= htmlspecialchars($editando['nombre']??'') ?>" required></div><div class="input-group"><label>Logo</label><input name="logo" value="<?= htmlspecialchars($editando['logo']??'') ?>"></div></div><button class="btn-admin-agregar"><?= $editando?'Guardar cambios':'Crear marca' ?></button> <?php if($editando): ?><a class="btn-admin-secundario" href="marcas.php">Cancelar</a><?php endif; ?></form></section>
<div class="tabla-admin tabla-premium"><table><thead><tr><th>Marca</th><th>Logo</th><th>Productos</th><th>Acciones</th></tr></thead><tbody><?php while($m=mysqli_fetch_assoc($marcas)): ?><tr><td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td><td><?= htmlspecialchars($m['logo']??'-') ?></td><td><?= (int)$m['productos'] ?></td><td class="acciones-tabla"><a class="btn-tabla editar" href="marcas.php?editar=<?= $m['id'] ?>">✎</a><a class="btn-tabla eliminar" href="marcas.php?eliminar=<?= $m['id'] ?>" onclick="return confirm('¿Eliminar marca?')">x</a></td></tr><?php endwhile; ?></tbody></table></div>
</main></div></body></html>
