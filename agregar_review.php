<?php

session_start();

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/csrf.php";

/* USUARIO LOGUEADO */
if(!isset($_SESSION['usuario_id'])){

    header("Location: login.php");
    exit;

}

/* VALIDAR POST */
if($_SERVER["REQUEST_METHOD"] !== "POST"){

    header("Location: productos.php");
    exit;

}

if(!validarCsrf()){

    header("Location: productos.php");
    exit;

}

$usuario_id = (int) $_SESSION['usuario_id'];
$nombre_cliente = $_SESSION['usuario_nombre'];

$producto_id = (int) $_POST['producto_id'];
$rating = (float) $_POST['rating'];
$comentario = trim($_POST['comentario']);

/* VALIDACIONES */
if($producto_id <= 0 || $rating < 1 || $rating > 5){

    header("Location: detalle.php?id=" . $producto_id);
    exit;

}

/* INSERTAR REVIEW */
$sql = "INSERT INTO reviews
(
    usuario_id,
    producto_id,
    nombre_cliente,
    rating,
    comentario,
    aprobado
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    1
)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "iisds",
    $usuario_id,
    $producto_id,
    $nombre_cliente,
    $rating,
    $comentario
);

mysqli_stmt_execute($stmt);

/* ACTUALIZAR PROMEDIO DEL PRODUCTO */
$sqlPromedio = "SELECT AVG(rating) AS promedio
                FROM reviews
                WHERE producto_id = ?
                AND aprobado = 1";

$stmtPromedio = mysqli_prepare($conn, $sqlPromedio);

mysqli_stmt_bind_param(
    $stmtPromedio,
    "i",
    $producto_id
);

mysqli_stmt_execute($stmtPromedio);

$resultadoPromedio = mysqli_stmt_get_result($stmtPromedio);

$dataPromedio = mysqli_fetch_assoc($resultadoPromedio);

$promedio = round($dataPromedio['promedio'], 1);

/* GUARDAR PROMEDIO EN PRODUCTOS */
$sqlUpdate = "UPDATE productos
              SET rating = ?
              WHERE id = ?";

$stmtUpdate = mysqli_prepare($conn, $sqlUpdate);

mysqli_stmt_bind_param(
    $stmtUpdate,
    "di",
    $promedio,
    $producto_id
);

mysqli_stmt_execute($stmtUpdate);

/* VOLVER AL DETALLE */
header("Location: detalle.php?id=" . $producto_id);
exit;
