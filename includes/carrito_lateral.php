<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include(__DIR__ . "/../config/conexion.php");
include_once(__DIR__ . "/../data/carrito_helpers.php");

$BASE = "/sportstyle/";
$carrito = $_SESSION['carrito'] ?? [];

$sqlProductos = "SELECT * FROM productos";
$resultadoProductos = mysqli_query($conn, $sqlProductos);

$productos = [];

while($fila = mysqli_fetch_assoc($resultadoProductos)){
    $productos[(int) $fila['id']] = $fila;
}

$tallesPorId = [];
$resultadoTalles = mysqli_query($conn, "SELECT * FROM producto_talles");

if($resultadoTalles){
    while($talle = mysqli_fetch_assoc($resultadoTalles)){
        $tallesPorId[(int) $talle['id']] = $talle;
    }
}

$total = 0;

?>

<h2>

    Tu carrito

    <button onclick="toggleCarrito()"
            class="cerrar-carrito">

        x

    </button>

</h2>

<?php if(!empty($carrito)): ?>

    <?php foreach($carrito as $key => $cantidad): ?>

        <?php

        $id = carritoProductoId($key);
        $talleId = carritoTalleId($key);
        $cantidad = (int) $cantidad;

        if(!isset($productos[$id]) || $cantidad <= 0){
            continue;
        }

        $p = $productos[$id];
        $subtotal = $p['precio'] * $cantidad;
        $total += $subtotal;

        ?>

        <div class="item-carrito">

            <div>

                <p>
                    <?= htmlspecialchars($p['nombre']) ?>
                </p>

                <small>
                    Cantidad: <?= $cantidad ?>
                </small>

                <?php if($talleId && isset($tallesPorId[$talleId])): ?>
                    <small>
                        Talle: <?= htmlspecialchars(talleLabel($tallesPorId[$talleId])) ?>
                    </small>
                <?php endif; ?>

            </div>

            <div style="text-align:right">

                <span>
                    $<?= number_format($subtotal, 0, ',', '.') ?>
                </span>

                <br>

                <a href="<?= $BASE ?>carrito.php?eliminar=<?= urlencode($key) ?>"
                   class="btn-eliminar"
                   title="Eliminar">

                    x

                </a>

            </div>

        </div>

    <?php endforeach; ?>

    <div class="carrito-lateral-footer">

        <h3>
            Total:
            $<?= number_format($total, 0, ',', '.') ?>
        </h3>

        <a href="<?= $BASE ?>carrito.php"
           class="btn"
           style="display:block; text-align:center; margin-top:10px;">

            Ver carrito completo

        </a>

    </div>

<?php else: ?>

    <p style="color:#aaa; text-align:center; margin-top:40px;">
        Tu carrito está vacío
    </p>

<?php endif; ?>
