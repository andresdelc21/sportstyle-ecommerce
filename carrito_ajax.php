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
$stock = (int) $producto['stock'];
$cantidadActual = isset($_SESSION['carrito'][$producto_id])
    ? (int) $_SESSION['carrito'][$producto_id]
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

$_SESSION['carrito'][$producto_id] = $cantidadActual + 1;

echo json_encode([
    "ok" => true,
    "mensaje" => "Producto agregado al carrito",
    "cantidad_items" => cantidadItems($_SESSION['carrito']),
    "cantidad_producto" => $_SESSION['carrito'][$producto_id],
    "stock" => $stock
]);
