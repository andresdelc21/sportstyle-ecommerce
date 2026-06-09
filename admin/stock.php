<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";
if(!isset($_SESSION['usuario_nombre'])){ header("Location: ../login.php"); exit; }

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['stock'])){
    if(!validarCsrf()){
        header("Location: stock.php");
        exit;
    }

    foreach($_POST['stock'] as $id => $stock){
        $id=(int)$id;
        $stock=max(0, (int)$stock);

        $stmtTalles=mysqli_prepare($conn,"SELECT id, etiqueta FROM producto_talles WHERE producto_id=? ORDER BY id ASC");
        mysqli_stmt_bind_param($stmtTalles,"i",$id);
        mysqli_stmt_execute($stmtTalles);
        $resTalles=mysqli_stmt_get_result($stmtTalles);
        $tallesProducto=[];
        while($t=mysqli_fetch_assoc($resTalles)){
            $tallesProducto[]=$t;
        }

        if(count($tallesProducto)===0){
            $stmt=mysqli_prepare($conn,"UPDATE productos SET stock=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,"ii",$stock,$id);
            mysqli_stmt_execute($stmt);
        } elseif(count($tallesProducto)===1 && trim((string)$tallesProducto[0]['etiqueta'])==='Único'){
            $talleId=(int)$tallesProducto[0]['id'];
            $stmt=mysqli_prepare($conn,"UPDATE producto_talles SET stock=? WHERE id=? AND producto_id=?");
            mysqli_stmt_bind_param($stmt,"iii",$stock,$talleId,$id);
            mysqli_stmt_execute($stmt);
        }

        $stmtSync=mysqli_prepare($conn,"UPDATE productos SET stock = COALESCE((SELECT SUM(stock) FROM producto_talles WHERE producto_id = ?), stock) WHERE id = ?");
        mysqli_stmt_bind_param($stmtSync,"ii",$id,$id);
        mysqli_stmt_execute($stmtSync);
    }
    header("Location: stock.php");
    exit;
}

$filtro = $_GET['filtro'] ?? 'todos';
$where = '';
if($filtro === 'bajo'){ $where = 'WHERE stock > 0 AND stock <= 5'; }
if($filtro === 'sin'){ $where = 'WHERE stock <= 0'; }
$productos=mysqli_query($conn,"SELECT id,nombre,imagen,stock,precio FROM productos $where ORDER BY stock ASC, nombre ASC");
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Stock | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Inventario</span><h1>Gestión de stock</h1><p>Actualizá cantidades rápidas en productos simples. Para variantes, usá Talles.</p></div></section>
<div class="admin-toolbar">
<a class="btn-admin-secundario" href="stock.php">Todos</a>
<a class="btn-admin-secundario" href="stock.php?filtro=bajo">Stock bajo</a>
<a class="btn-admin-secundario" href="stock.php?filtro=sin">Sin stock</a>
</div>
<form method="POST">
<?= csrfInput() ?>
<div class="tabla-admin tabla-premium"><table><thead><tr><th>Producto</th><th>Precio</th><th>Stock total</th><th>Estado</th><th>Talles</th></tr></thead><tbody>
<?php while($p=mysqli_fetch_assoc($productos)): ?><tr><td><div class="producto-admin-info"><img src="../<?= htmlspecialchars($p['imagen']) ?>" class="admin-img-producto"><strong><?= htmlspecialchars($p['nombre']) ?></strong></div></td><td>$<?= number_format($p['precio'],0,',','.') ?></td><td><input class="stock-input-admin" type="number" min="0" name="stock[<?= $p['id'] ?>]" value="<?= (int)$p['stock'] ?>"></td><td><?php if($p['stock']<=0): ?><span class="stock-bajo">Sin stock</span><?php elseif($p['stock']<=5): ?><span class="estado pendiente">Stock bajo</span><?php else: ?><span class="stock-ok">Disponible</span><?php endif; ?></td><td><a class="btn-tabla editar" href="talles.php?producto_id=<?= (int)$p['id'] ?>">Ver</a></td></tr><?php endwhile; ?>
</tbody></table></div>
<div class="form-actions-admin"><button class="btn-admin-agregar" type="submit">Guardar stock</button></div>
</form>
</main></div></body></html>
