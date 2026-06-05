<?php

include_once(__DIR__ . "/../config/config.php");

$nombreTiendaFooter = $NOMBRE_TIENDA ?? 'SportStyle';
$whatsappTiendaFooter = preg_replace('/[^0-9]/', '', $WHATSAPP_TIENDA ?? '');
$emailTiendaFooter = $EMAIL_TIENDA ?? 'contacto@sportstyle.com';
$instagramTiendaFooter = $INSTAGRAM_TIENDA ?? '';
$facebookTiendaFooter = $FACEBOOK_TIENDA ?? '';

?>

<footer>

    <div class="footer-grid">

        <div class="footer-col">
            <h4><?= htmlspecialchars($nombreTiendaFooter) ?></h4>
            <p>Tienda online de ropa deportiva.</p>
            <p>Calidad, estilo y rendimiento.</p>
        </div>

        <div class="footer-col">
            <h4>Navegación</h4>
            <a href="index.php">Inicio</a>
            <a href="productos.php">Productos</a>
            <a href="contacto.php">Contacto</a>
        </div>

        <div class="footer-col">
            <h4>Categorías</h4>
            <a href="productos.php?categoria=Calzado">Calzado</a>
            <a href="productos.php?categoria=Remeras">Remeras</a>
            <a href="productos.php?categoria=Accesorios">Accesorios</a>
        </div>

        <div class="footer-col">
            <h4>Contacto</h4>
            <p>Email: <?= htmlspecialchars($emailTiendaFooter) ?></p>
            <p>WhatsApp: <?= htmlspecialchars($whatsappTiendaFooter) ?></p>
            <?php if(!empty($instagramTiendaFooter)): ?>
                <a href="<?= htmlspecialchars($instagramTiendaFooter) ?>" target="_blank">Instagram</a>
            <?php endif; ?>
            <?php if(!empty($facebookTiendaFooter)): ?>
                <a href="<?= htmlspecialchars($facebookTiendaFooter) ?>" target="_blank">Facebook</a>
            <?php endif; ?>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© <?= date("Y") ?> <?= htmlspecialchars($nombreTiendaFooter) ?> - Todos los derechos reservados</p>
    </div>
    <!-- BOTÓN WHATSAPP FLOTANTE -->
<?php if(!empty($whatsappTiendaFooter)): ?>
<a href="https://wa.me/<?= htmlspecialchars($whatsappTiendaFooter) ?>"
   class="whatsapp-float"
   target="_blank"
   title="Escribinos por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>
<!-- ASISTENTE VIRTUAL SPORTSTYLE -->
<div class="asistente-float" id="asistenteFloat">

    <button class="asistente-btn" id="asistenteBtn">
        🤖
    </button>

    <div class="asistente-box" id="asistenteBox">

        <div class="asistente-header">
            <strong>Asistente <?= htmlspecialchars($nombreTiendaFooter) ?></strong>
            <button id="cerrarAsistente">×</button>
        </div>

       <div class="asistente-body">

    <p>
        👋 Hola, soy el asistente de <?= htmlspecialchars($nombreTiendaFooter) ?>.
    </p>

    <p>
        Elegí una opción:
    </p>

    <a href="productos.php">🛍 Ver productos</a>

    <a href="carrito.php">🛒 Ver carrito</a>

    <a href="favoritos.php">❤️ Mis favoritos</a>

    <a href="login.php">👤 Mi cuenta</a>

    <a href="contacto.php">📩 Contacto</a>

    <a href="#">🚚 Información de envíos</a>

    <a href="#">💳 Medios de pago</a>

    <a href="#">↩️ Cambios y devoluciones</a>

    <?php if(!empty($whatsappTiendaFooter)): ?>
    <a href="https://wa.me/<?= htmlspecialchars($whatsappTiendaFooter) ?>" target="_blank">
        <i class="fab fa-whatsapp"></i> Hablar por WhatsApp
    </a>
    <?php endif; ?>

</div>

    </div>

</div>

</footer>

<script src="/sportstyle/java/main.js"></script>
</body>
</html>
