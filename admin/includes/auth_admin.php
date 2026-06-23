<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

if(
    $usuarioId <= 0
    ||
    !isset($_SESSION['usuario_rol'])
    ||
    $_SESSION['usuario_rol'] !== 'admin'
){
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../../config/conexion.php";

$stmtAdmin = mysqli_prepare(
    $conn,
    "SELECT rol FROM usuarios WHERE id = ? LIMIT 1"
);

if(!$stmtAdmin){
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

mysqli_stmt_bind_param($stmtAdmin, "i", $usuarioId);
mysqli_stmt_execute($stmtAdmin);
$resultadoAdmin = mysqli_stmt_get_result($stmtAdmin);
$usuarioAdmin = mysqli_fetch_assoc($resultadoAdmin);
mysqli_stmt_close($stmtAdmin);

if(!$usuarioAdmin || ($usuarioAdmin['rol'] ?? '') !== 'admin'){
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
