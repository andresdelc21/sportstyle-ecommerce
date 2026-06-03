<?php

session_start();

include("../config/conexion.php");

/* PROTEGER */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

/* VALIDAR ID */
if(!isset($_GET['id'])){
    header("Location: productos.php");
    exit;
}

$id = (int) $_GET['id'];

/* ELIMINAR PRODUCTO */
if(!isset($conn) || !$conn){
    header("Location: productos.php");
    exit;
}

$sql = "DELETE FROM productos WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if(mysqli_stmt_execute($stmt)){

    header("Location: productos.php");
    exit;

} else {

    echo "Error al eliminar producto";

}
?>