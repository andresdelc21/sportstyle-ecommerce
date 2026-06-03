<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once __DIR__ . "/config/conexion.php";

/* PROTEGER */
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];

/* TOGGLE FAVORITO */
if(isset($_GET['toggle'])){

    $producto_id = (int) $_GET['toggle'];

    $sqlCheck = "SELECT id FROM favoritos
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

    } else {

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

    }

    header("Location: favoritos.php");
    exit;

}

/* TRAER FAVORITOS */
$sql = "SELECT productos.*
        FROM favoritos
        INNER JOIN productos
        ON favoritos.producto_id = productos.id
        WHERE favoritos.usuario_id = ?
        ORDER BY favoritos.id DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $usuario_id
);

mysqli_stmt_execute($stmt);

$favoritos = mysqli_stmt_get_result($stmt);

include("includes/header.php");

?>

<h1 class="titulo">Mis Favoritos ❤️</h1>

<div class="grid">

<?php if(mysqli_num_rows($favoritos) > 0): ?>

    <?php while($p = mysqli_fetch_assoc($favoritos)): ?>

        <div class="card-v2">

            <div class="card-v2-img">

                <img src="<?= $p['imagen'] ?>"
                     alt="<?= $p['nombre'] ?>">

            </div>

            <div class="card-v2-info">

                <h3 class="card-v2-nombre">
                    <?= $p['nombre'] ?>
                </h3>

                <div class="card-v2-precio">

                    <span class="precio-actual">
                        $<?= number_format($p['precio'], 0, ',', '.') ?>
                    </span>

                </div>

                <div class="card-v2-botones">

                    <a href="detalle.php?id=<?= $p['id'] ?>"
                       class="btn-card">
                       👁️ Ver
                    </a>

                    <a href="favoritos.php?toggle=<?= $p['id'] ?>"
                       class="btn-card">
                       💔 Quitar
                    </a>

                </div>

            </div>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p class="carrito-vacio">
        No tienes favoritos todavía ❤️
    </p>

<?php endif; ?>

</div>

<?php include("includes/footer.php"); ?>