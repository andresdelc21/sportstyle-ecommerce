<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/includes/csrf.php";

if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

$productoId = (int) ($_GET['producto_id'] ?? $_POST['producto_id'] ?? 0);
$mensaje = '';

function tipoTalleAdmin($producto){
    $texto = strtolower(($producto['categoria_nombre'] ?? '') . ' ' . ($producto['nombre'] ?? ''));

    if(strpos($texto, 'zapat') !== false || strpos($texto, 'calzado') !== false){
        return 'calzado';
    }

    if(strpos($texto, 'media') !== false){
        return 'medias';
    }

    if(strpos($texto, 'remera') !== false || strpos($texto, 'buzo') !== false || strpos($texto, 'pantal') !== false || strpos($texto, 'short') !== false){
        return 'indumentaria';
    }

    return 'general';
}

function tallesSugeridosAdmin($tipo, $stockTotal){
    $stockTotal = max(0, (int) $stockTotal);

    if($tipo === 'calzado'){
        $base = [
            ['38', '6.5', '37', '24.5'],
            ['39', '7', '38', '25'],
            ['40', '8', '39', '26'],
            ['41', '8.5', '40', '26.5'],
            ['42', '9', '41', '27'],
            ['43', '10', '42', '28'],
        ];
    } elseif($tipo === 'indumentaria'){
        $base = [
            ['S', '', '', ''],
            ['M', '', '', ''],
            ['L', '', '', ''],
            ['XL', '', '', ''],
            ['XXL', '', '', ''],
        ];
    } elseif($tipo === 'medias'){
        return [['35-43', '', '', '', '', $stockTotal]];
    } else {
        return [['Único', '', '', '', '', $stockTotal]];
    }

    $cantidad = count($base);
    $porTalle = $cantidad > 0 ? intdiv($stockTotal, $cantidad) : 0;
    $resto = $cantidad > 0 ? $stockTotal % $cantidad : 0;
    $talles = [];

    foreach($base as $index => $item){
        $stock = $porTalle + ($index < $resto ? 1 : 0);
        $talles[] = [$item[0], $item[1], $item[2], $item[3], '', $stock];
    }

    return $talles;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!validarCsrf()){
        $mensaje = 'La sesión expiró. Volvé a intentar.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if($accion === 'crear'){
            $etiqueta = trim($_POST['etiqueta'] ?? '');
            $arg = trim($_POST['talle_arg'] ?? '');
            $us = trim($_POST['talle_us'] ?? '');
            $br = trim($_POST['talle_br'] ?? '');
            $cm = trim($_POST['cm'] ?? '');
            $stock = max(0, (int) ($_POST['stock'] ?? 0));

            if($productoId > 0){
                $stmt = mysqli_prepare($conn, "INSERT INTO producto_talles (producto_id, etiqueta, talle_arg, talle_us, talle_br, cm, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isssssi", $productoId, $etiqueta, $arg, $us, $br, $cm, $stock);
                mysqli_stmt_execute($stmt);
            }
        }

        if($accion === 'guardar' && isset($_POST['talles'])){
            foreach($_POST['talles'] as $id => $data){
                $id = (int) $id;
                $etiqueta = trim($data['etiqueta'] ?? '');
                $arg = trim($data['talle_arg'] ?? '');
                $us = trim($data['talle_us'] ?? '');
                $br = trim($data['talle_br'] ?? '');
                $cm = trim($data['cm'] ?? '');
                $stock = max(0, (int) ($data['stock'] ?? 0));

                $stmt = mysqli_prepare($conn, "UPDATE producto_talles SET etiqueta=?, talle_arg=?, talle_us=?, talle_br=?, cm=?, stock=? WHERE id=? AND producto_id=?");
                mysqli_stmt_bind_param($stmt, "sssssiii", $etiqueta, $arg, $us, $br, $cm, $stock, $id, $productoId);
                mysqli_stmt_execute($stmt);
            }
        }

        if($accion === 'eliminar'){
            $id = (int) ($_POST['id'] ?? 0);

            if($id > 0){
                $stmt = mysqli_prepare($conn, "DELETE FROM producto_talles WHERE id=? AND producto_id=?");
                mysqli_stmt_bind_param($stmt, "ii", $id, $productoId);
                mysqli_stmt_execute($stmt);
            }
        }

        if($accion === 'generar_sugeridos' && $productoId > 0){
            $stmtProductoPreset = mysqli_prepare($conn, "SELECT productos.*, categorias.nombre AS categoria_nombre FROM productos LEFT JOIN categorias ON categorias.id = productos.categoria_id WHERE productos.id = ?");
            mysqli_stmt_bind_param($stmtProductoPreset, "i", $productoId);
            mysqli_stmt_execute($stmtProductoPreset);
            $productoPreset = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtProductoPreset));

            if($productoPreset){
                $tipoPreset = tipoTalleAdmin($productoPreset);
                $tallesPreset = tallesSugeridosAdmin($tipoPreset, (int) $productoPreset['stock']);

                $stmtDelete = mysqli_prepare($conn, "DELETE FROM producto_talles WHERE producto_id=?");
                mysqli_stmt_bind_param($stmtDelete, "i", $productoId);
                mysqli_stmt_execute($stmtDelete);

                foreach($tallesPreset as $tallePreset){
                    [$etiqueta, $arg, $us, $br, $cm, $stock] = $tallePreset;
                    $stmtInsert = mysqli_prepare($conn, "INSERT INTO producto_talles (producto_id, etiqueta, talle_arg, talle_us, talle_br, cm, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmtInsert, "isssssi", $productoId, $etiqueta, $arg, $us, $br, $cm, $stock);
                    mysqli_stmt_execute($stmtInsert);
                }
            }
        }

        $stmtSync = mysqli_prepare($conn, "UPDATE productos SET stock = COALESCE((SELECT SUM(stock) FROM producto_talles WHERE producto_id = ?), 0) WHERE id = ?");
        mysqli_stmt_bind_param($stmtSync, "ii", $productoId, $productoId);
        mysqli_stmt_execute($stmtSync);

        header("Location: talles.php?producto_id=" . $productoId);
        exit;
    }
}

$productos = mysqli_query($conn, "SELECT id, nombre, imagen, stock FROM productos ORDER BY nombre ASC");
$producto = null;
$talles = null;
$tipoProducto = 'general';

if($productoId > 0){
    $stmtProducto = mysqli_prepare($conn, "SELECT productos.*, categorias.nombre AS categoria_nombre FROM productos LEFT JOIN categorias ON categorias.id = productos.categoria_id WHERE productos.id = ?");
    mysqli_stmt_bind_param($stmtProducto, "i", $productoId);
    mysqli_stmt_execute($stmtProducto);
    $producto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtProducto));
    $tipoProducto = $producto ? tipoTalleAdmin($producto) : 'general';

    $stmtTalles = mysqli_prepare($conn, "SELECT * FROM producto_talles WHERE producto_id = ? ORDER BY id ASC");
    mysqli_stmt_bind_param($stmtTalles, "i", $productoId);
    mysqli_stmt_execute($stmtTalles);
    $talles = mysqli_stmt_get_result($stmtTalles);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talles | Admin</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="admin-body">
<div class="admin-container">
<?php include("includes/sidebar.php"); ?>
<main class="admin-content">
    <section class="admin-hero small-hero">
        <div>
            <span class="admin-badge">Inventario</span>
            <h1>Talles y variantes</h1>
            <p>Administrá talles según el producto: calzado, indumentaria o medias.</p>
        </div>
    </section>

    <?php if($mensaje): ?><div class="admin-alert error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <section class="pedido-panel">
        <form method="GET" class="form-admin-premium">
            <div class="input-group">
                <label>Producto</label>
                <select name="producto_id" onchange="this.form.submit()">
                    <option value="">Seleccionar producto</option>
                    <?php while($p = mysqli_fetch_assoc($productos)): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $productoId === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?> - stock total <?= (int)$p['stock'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </section>

    <?php if($producto): ?>
        <section class="pedido-panel">
            <div class="pedido-panel-header">
                <div>
                    <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
                    <p>Tipo sugerido: <?= htmlspecialchars(ucfirst($tipoProducto)) ?> · Stock total actual: <?= (int)$producto['stock'] ?></p>
                </div>
                <form method="POST">
                    <?= csrfInput() ?>
                    <input type="hidden" name="accion" value="generar_sugeridos">
                    <input type="hidden" name="producto_id" value="<?= (int)$productoId ?>">
                    <button class="btn-admin-secundario" type="submit" onclick="return confirm('Esto reemplaza los talles actuales por los sugeridos para este tipo de producto. ¿Continuar?')">Generar sugeridos</button>
                </form>
            </div>

            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="producto_id" value="<?= (int)$productoId ?>">

                <div class="tabla-admin tabla-premium">
                    <table>
                        <thead>
                            <tr><th>Talle</th><th>ARG</th><th>US</th><th>BR</th><th>CM</th><th>Stock</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php while($t = mysqli_fetch_assoc($talles)): ?>
                                <tr>
                                    <td><input name="talles[<?= (int)$t['id'] ?>][etiqueta]" value="<?= htmlspecialchars($t['etiqueta'] ?? '') ?>"></td>
                                    <td><input name="talles[<?= (int)$t['id'] ?>][talle_arg]" value="<?= htmlspecialchars($t['talle_arg'] ?? '') ?>"></td>
                                    <td><input name="talles[<?= (int)$t['id'] ?>][talle_us]" value="<?= htmlspecialchars($t['talle_us'] ?? '') ?>"></td>
                                    <td><input name="talles[<?= (int)$t['id'] ?>][talle_br]" value="<?= htmlspecialchars($t['talle_br'] ?? '') ?>"></td>
                                    <td><input name="talles[<?= (int)$t['id'] ?>][cm]" value="<?= htmlspecialchars($t['cm'] ?? '') ?>"></td>
                                    <td><input type="number" min="0" name="talles[<?= (int)$t['id'] ?>][stock]" value="<?= (int)$t['stock'] ?>"></td>
                                    <td>
                                        <button class="btn-tabla eliminar" type="submit" name="accion" value="eliminar" formaction="talles.php" onclick="this.form.id.value='<?= (int)$t['id'] ?>'; return confirm('¿Eliminar talle?')">x</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="id" value="0">
                <div class="form-actions-admin">
                    <button class="btn-admin-agregar" type="submit">Guardar talles</button>
                </div>
            </form>
        </section>

        <section class="pedido-panel">
            <h2>Agregar talle</h2>
            <form method="POST" class="form-admin-premium">
                <?= csrfInput() ?>
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="producto_id" value="<?= (int)$productoId ?>">
                <div class="admin-grid">
                    <div class="input-group"><label>Talle</label><input name="etiqueta" placeholder="<?= $tipoProducto === 'indumentaria' ? 'S, M, L, XL' : ($tipoProducto === 'medias' ? '35-43' : 'Opcional') ?>"></div>
                    <div class="input-group"><label>ARG</label><input name="talle_arg" placeholder="<?= $tipoProducto === 'calzado' ? '42' : 'Solo calzado' ?>"></div>
                    <div class="input-group"><label>US</label><input name="talle_us" placeholder="<?= $tipoProducto === 'calzado' ? '9' : 'Solo calzado' ?>"></div>
                    <div class="input-group"><label>BR</label><input name="talle_br" placeholder="<?= $tipoProducto === 'calzado' ? '41' : 'Solo calzado' ?>"></div>
                    <div class="input-group"><label>CM</label><input name="cm" placeholder="<?= $tipoProducto === 'calzado' ? '27' : 'Opcional' ?>"></div>
                    <div class="input-group"><label>Stock</label><input type="number" min="0" name="stock" value="0"></div>
                </div>
                <button class="btn-admin-agregar" type="submit">Agregar talle</button>
            </form>
        </section>
    <?php endif; ?>
</main>
</div>
</body>
</html>
