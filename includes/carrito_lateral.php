<?php
if (session_status() === PHP_SESSION_NONE) 
    session_start();
include(__DIR__ . "/../data/productos.php");

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
$BASE = "/sportstyle/";
?>

<div class="carrito-lateral" id="carritoLateral">   <!-- ✅ Contenedor agregado -->
    <h2>
        Tu carrito  
        <button onclick="toggleCarrito()" class="cerrar-carrito">✖</button>
    </h2>

    <?php if (!empty($carrito)): ?>
        <?php foreach ($carrito as $id => $cantidad): ?>
            <?php foreach ($productos as $p): ?>
                <?php if ($p['id'] == $id): ?>
                    <?php
                        $subtotal = $p['precio'] * $cantidad;
                        $total += $subtotal;
                    ?>
                    <!-- INFO -->
                    <div>
                        <p><?= htmlspecialchars($p['nombre']) ?></p>
                        <small>Cant: <?= (int)$cantidad ?></small>
                    </div>

                    <!-- PRECIO + ACCIONES -->
                    <div>
                        <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
                        <a href="<?= $BASE ?>carrito.php?eliminar=<?= (int)$id ?>"
                           class="btn-eliminar"
                           title="Eliminar">
                           ❌
                        </a>
                    </div>
                    <!-- ✅ Se eliminó el </div> de más -->

                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <!-- FOOTER -->
        <div class="carrito-lateral-footer">
            <h3>Total: $<?= number_format($total, 0, ',', '.') ?></h3>
            <a href="<?= $BASE ?>carrito.php"
   class="btn-card"
   onclick="window.location.href='<?= $BASE ?>carrito.php'">
   Ver carrito completo
</a>

    <?php else: ?>
        <p style="text-align:center; margin-top:40px;">Tu carrito está vacío 🛒</p>
    <?php endif; ?>
</div>   <!-- ✅ Cierre del contenedor -->