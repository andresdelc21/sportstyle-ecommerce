<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/csrf.php";

header("Content-Type: application/json");

/* VALIDAR LOGIN */
if(!isset($_SESSION['usuario_id'])){

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesión para agregar favoritos"
    ]);

    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf()){

    echo json_encode([
        "ok" => false,
        "mensaje" => "Solicitud inválida"
    ]);

    exit;
}

/* VALIDAR PRODUCTO */
if(!isset($_POST['producto_id'])){

    echo json_encode([
        "ok" => false,
        "mensaje" => "Producto inválido"
    ]);

    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$producto_id = (int) $_POST['producto_id'];

/* VERIFICAR SI YA EXISTE */
$sqlCheck = "SELECT id
             FROM favoritos
             WHERE usuario_id = ?
             AND producto_id = ?";

$stmtCheck = mysqli_prepare($conn, $sqlCheck);

mysqli_stmt_bind_param(
    $stmtCheck,
    "ii",
    $usuario_id,
    $producto_id
);

mysqli_stmt_execute($stmtCheck);

$resultadoCheck = mysqli_stmt_get_result($stmtCheck);

if(mysqli_num_rows($resultadoCheck) > 0){

    /* QUITAR FAVORITO */
    $sqlDelete = "DELETE FROM favoritos
                  WHERE usuario_id = ?
                  AND producto_id = ?";

    $stmtDelete = mysqli_prepare($conn, $sqlDelete);

    mysqli_stmt_bind_param(
        $stmtDelete,
        "ii",
        $usuario_id,
        $producto_id
    );

    mysqli_stmt_execute($stmtDelete);

    echo json_encode([
        "ok" => true,
        "favorito" => false,
        "mensaje" => "Producto quitado de favoritos"
    ]);

    exit;

}else{

    /* AGREGAR FAVORITO */
    $sqlInsert = "INSERT INTO favoritos
    (
        usuario_id,
        producto_id
    )
    VALUES
    (
        ?,
        ?
    )";

    $stmtInsert = mysqli_prepare($conn, $sqlInsert);

    mysqli_stmt_bind_param(
        $stmtInsert,
        "ii",
        $usuario_id,
        $producto_id
    );

    mysqli_stmt_execute($stmtInsert);

    echo json_encode([
        "ok" => true,
        "favorito" => true,
        "mensaje" => "Producto agregado a favoritos"
    ]);

    exit;

}
