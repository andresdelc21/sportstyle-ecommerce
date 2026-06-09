<?php

session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";

include("../config/conexion.php");

/* PROTEGER */
if(!isset($_SESSION['usuario_nombre'])){
    header("Location: ../login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf()){
    header("Location: productos.php");
    exit;
}

/* VALIDAR ID */
if(!isset($_POST['id'])){
    header("Location: productos.php");
    exit;
}

$id = (int) $_POST['id'];

/* ELIMINAR PRODUCTO */
if(!isset($conn) || !$conn){
    header("Location: productos.php");
    exit;
}

$sqlUso = "SELECT COUNT(*) AS total
           FROM detalle_pedidos
           WHERE producto_id = ?";

$stmtUso = mysqli_prepare($conn, $sqlUso);

mysqli_stmt_bind_param($stmtUso, "i", $id);

mysqli_stmt_execute($stmtUso);

$uso = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUso));

if((int) ($uso['total'] ?? 0) > 0){

    $sqlOcultar = "UPDATE productos
                   SET activo = 0
                   WHERE id = ?";

    $stmtOcultar = mysqli_prepare($conn, $sqlOcultar);

    mysqli_stmt_bind_param($stmtOcultar, "i", $id);

    mysqli_stmt_execute($stmtOcultar);

    header("Location: productos.php?aviso=producto_con_pedidos");
    exit;

}

$sql = "DELETE FROM productos WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if(mysqli_stmt_execute($stmt)){

    header("Location: productos.php?aviso=producto_eliminado");
    exit;

} else {

    header("Location: productos.php?aviso=error_eliminar");
    exit;

}

?>
