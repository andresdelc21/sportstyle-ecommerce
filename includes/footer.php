<footer>

    <div class="footer-grid">

        <div class="footer-col">
            <h4>SportStyle</h4>
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
            <p>Email: contacto@sportstyle.com</p>
            <p>WhatsApp: +54 9 387 330 4414</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© <?= date("Y") ?> SportStyle - Todos los derechos reservados</p>
    </div>
    <!-- BOTÓN WHATSAPP FLOTANTE -->
<a href="https://wa.me/+5493873304414"
   class="whatsapp-float"
   target="_blank"
   title="Escribinos por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<!-- ASISTENTE VIRTUAL SPORTSTYLE -->
<div class="asistente-float" id="asistenteFloat">

    <button class="asistente-btn" id="asistenteBtn">
        🤖
    </button>

    <div class="asistente-box" id="asistenteBox">

        <div class="asistente-header">
            <strong>Asistente SportStyle</strong>
            <button id="cerrarAsistente">×</button>
        </div>

       <div class="asistente-body">

    <p>
        👋 Hola, soy el asistente de SportStyle.
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

    <a href="https://wa.me/5493873304414" target="_blank">
        <i class="fab fa-whatsapp"></i> Hablar por WhatsApp
    </a>

</div>

    </div>

</div>

</footer>

<script src="/sportstyle/java/main.js"></script>
</body>
</html>