<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";
if(!isset($_SESSION['usuario_nombre'])){ header("Location: ../login.php"); exit; }
$editando = null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validarCsrf()){ header("Location: banners.php"); exit; }
    $accion = $_POST['accion'] ?? 'guardar';
    $id=(int)($_POST['id']??0);
    if($accion === 'toggle' && $id > 0){ $stmt=mysqli_prepare($conn,"UPDATE banners SET activo=IF(activo=1,0,1) WHERE id=?"); mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); header("Location: banners.php"); exit; }
    if($accion === 'eliminar' && $id > 0){ $stmt=mysqli_prepare($conn,"DELETE FROM banners WHERE id=?"); mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); header("Location: banners.php"); exit; }
    $titulo=trim($_POST['titulo']??'');
    $subtitulo=trim($_POST['subtitulo']??'');
    $imagen=trim($_POST['imagen']??'');
    $enlace=trim($_POST['enlace']??'');
    $activo=isset($_POST['activo'])?1:0;
    if($titulo && $imagen){
        if($id>0){ $stmt=mysqli_prepare($conn,"UPDATE banners SET titulo=?, subtitulo=?, imagen=?, enlace=?, activo=? WHERE id=?"); mysqli_stmt_bind_param($stmt,"ssssii",$titulo,$subtitulo,$imagen,$enlace,$activo,$id); }
        else { $stmt=mysqli_prepare($conn,"INSERT INTO banners (titulo, subtitulo, imagen, enlace, activo) VALUES (?, ?, ?, ?, ?)"); mysqli_stmt_bind_param($stmt,"ssssi",$titulo,$subtitulo,$imagen,$enlace,$activo); }
        mysqli_stmt_execute($stmt); header("Location: banners.php"); exit;
    }
}
if(isset($_GET['editar'])){ $id=(int)$_GET['editar']; $stmt=mysqli_prepare($conn,"SELECT * FROM banners WHERE id=?"); mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); $res=mysqli_stmt_get_result($stmt); $editando=mysqli_fetch_assoc($res); }
$banners=mysqli_query($conn,"SELECT * FROM banners ORDER BY id DESC");
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Banners | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Contenido</span><h1>Banners</h1><p>Administrá promociones visuales y enlaces de campañas.</p></div></section>
<section class="pedido-panel"><form method="POST" class="form-admin-premium"><?= csrfInput() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)($editando['id']??0) ?>"><div class="admin-grid">
<div class="input-group"><label>Título</label><input name="titulo" value="<?= htmlspecialchars($editando['titulo']??'') ?>" required></div>
<div class="input-group"><label>Subtítulo</label><input name="subtitulo" value="<?= htmlspecialchars($editando['subtitulo']??'') ?>"></div>
<div class="input-group"><label>Imagen</label><input name="imagen" placeholder="img/banner.png" value="<?= htmlspecialchars($editando['imagen']??'') ?>" required></div>
<div class="input-group"><label>Enlace</label><input name="enlace" placeholder="productos.php" value="<?= htmlspecialchars($editando['enlace']??'') ?>"></div>
<div class="input-group"><label>Estado</label><label class="admin-check"><input type="checkbox" name="activo" <?= !isset($editando)||(int)$editando['activo']===1?'checked':'' ?>> Activo</label></div>
</div><button class="btn-admin-agregar" type="submit"><?= $editando?'Guardar cambios':'Crear banner' ?></button> <?php if($editando): ?><a class="btn-admin-secundario" href="banners.php">Cancelar</a><?php endif; ?></form></section>
<div class="tabla-admin tabla-premium"><table><thead><tr><th>Banner</th><th>Imagen</th><th>Enlace</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php while($b=mysqli_fetch_assoc($banners)): ?><tr><td><strong><?= htmlspecialchars($b['titulo']) ?></strong><br><small><?= htmlspecialchars($b['subtitulo']??'') ?></small></td><td><?= htmlspecialchars($b['imagen']) ?></td><td><?= htmlspecialchars($b['enlace']??'-') ?></td><td><span class="estado <?= $b['activo']?'pagado':'cancelado' ?>"><?= $b['activo']?'Activo':'Inactivo' ?></span></td><td class="acciones-tabla"><a class="btn-tabla editar" href="banners.php?editar=<?= $b['id'] ?>">Editar</a><form method="POST" style="display:inline"><?= csrfInput() ?><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn-tabla editar" type="submit">Cambiar estado</button></form><form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar banner?')"><?= csrfInput() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn-tabla eliminar" type="submit">Eliminar</button></form></td></tr><?php endwhile; ?>
</tbody></table></div></main></div></body></html>
