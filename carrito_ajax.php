<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/data/carrito_helpers.php";

header("Content-Type: application/json");

if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

$producto_id = isset($_POST['producto_id'])
    ? (int) $_POST['producto_id']
    : 0;

$talle_id = isset($_POST['talle_id'])
    ? (int) $_POST['talle_id']
    : 0;

if($producto_id <= 0){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Producto inválido"
    ]);
    exit;
}

$sqlProducto = "SELECT *
                FROM productos
                WHERE id = ?
                LIMIT 1";

$stmtProducto = mysqli_prepare($conn, $sqlProducto);

mysqli_stmt_bind_param(
    $stmtProducto,
    "i",
    $producto_id
);

mysqli_stmt_execute($stmtProducto);

$resultadoProducto = mysqli_stmt_get_result($stmtProducto);

if(mysqli_num_rows($resultadoProducto) === 0){
    echo json_encode([
        "ok" => false,
        "mensaje" => "El producto ya no está disponible"
    ]);
    exit;
}

$producto = mysqli_fetch_assoc($resultadoProducto);
$sqlTalles = "SELECT *
              FROM producto_talles
              WHERE producto_id = ?
              ORDER BY id ASC";

$stmtTalles = mysqli_prepare($conn, $sqlTalles);
mysqli_stmt_bind_param($stmtTalles, "i", $producto_id);
mysqli_stmt_execute($stmtTalles);
$resultadoTalles = mysqli_stmt_get_result($stmtTalles);

$talles = [];
while($talle = mysqli_fetch_assoc($resultadoTalles)){
    $talles[] = $talle;
}

$talleSeleccionado = null;

if(count($talles) === 1 && $talle_id <= 0){
    $talleSeleccionado = $talles[0];
    $talle_id = (int) $talleSeleccionado['id'];
} elseif($talle_id > 0) {
    foreach($talles as $talle){
        if((int) $talle['id'] === $talle_id){
            $talleSeleccionado = $talle;
            break;
        }
    }
}

if(!empty($talles) && !$talleSeleccionado){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Elegí un talle para agregar el producto"
    ]);
    exit;
}

$stock = $talleSeleccionado
    ? (int) $talleSeleccionado['stock']
    : (int) $producto['stock'];

$carritoKey = carritoKey($producto_id, $talleSeleccionado ? $talle_id : null);

$cantidadActual = isset($_SESSION['carrito'][$carritoKey])
    ? (int) $_SESSION['carrito'][$carritoKey]
    : 0;

if($stock <= 0){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Este producto está sin stock"
    ]);
    exit;
}

if($cantidadActual >= $stock){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Ya agregaste el máximo disponible"
    ]);
    exit;
}

$_SESSION['carrito'][$carritoKey] = $cantidadActual + 1;

echo json_encode([
    "ok" => true,
    "mensaje" => "Producto agregado al carrito",
    "cantidad_items" => cantidadItems($_SESSION['carrito']),
    "cantidad_producto" => $_SESSION['carrito'][$carritoKey],
    "stock" => $stock
]);
