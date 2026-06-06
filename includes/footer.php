<?php

include_once(__DIR__ . "/../config/config.php");

$nombreTiendaFooter = $NOMBRE_TIENDA ?? 'SportStyle';
$whatsappTiendaFooter = preg_replace('/[^0-9]/', '', $WHATSAPP_TIENDA ?? '');
$emailTiendaFooter = $EMAIL_TIENDA ?? 'contacto@sportstyle.com';
$instagramTiendaFooter = $INSTAGRAM_TIENDA ?? '';
$facebookTiendaFooter = $FACEBOOK_TIENDA ?? '';
$direccionTiendaFooter = $DIRECCION_TIENDA ?? 'SportStyle tienda';
$mapsTiendaFooter = $MAPS_TIENDA ?? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($direccionTiendaFooter));

?>

<footer>

    <div class="footer-grid">

        <div class="footer-col">
            <h4><?= htmlspecialchars($nombreTiendaFooter) ?></h4>
            <p>Tienda online de ropa deportiva.</p>
            <p>Calidad, estilo y rendimiento.</p>
            <a class="footer-map-link" href="<?= htmlspecialchars($mapsTiendaFooter) ?>" target="_blank">Ver tienda en Maps</a>
        </div>

        <div class="footer-col">
            <h4>Ayuda</h4>
            <a href="contacto.php">Contacto</a>
            <a href="carrito.php">Cómo comprar</a>
            <a href="carrito.php#envio">Envíos</a>
            <a href="contacto.php">Pagos</a>
            <a href="mis_pedidos.php">Compra segura</a>
            <a href="mis_pedidos.php">Cambios y devoluciones</a>
            <a href="mis_pedidos.php">Envío y seguimiento</a>
            <a href="contacto.php">Formas de pago</a>
            <a href="login.php">Mi cuenta</a>
        </div>

        <div class="footer-col">
            <h4>Quick links</h4>
            <a href="index.php">Inicio</a>
            <a href="productos.php">Productos</a>
            <a href="favoritos.php">Favoritos</a>
            <a href="mis_pedidos.php">Mis pedidos</a>
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

    <div class="footer-comercial">

        <div class="footer-legales">
            <a href="contacto.php">Arrepentimiento</a>
            <a href="contacto.php">Libro de quejas</a>
            <a href="<?= htmlspecialchars($mapsTiendaFooter) ?>" target="_blank">Tienda / sucursales</a>
        </div>

        <div class="footer-medios">
        <div class="footer-medios-bloque">
            <h4>Medios de pago</h4>
            <div class="medios-logos">
                <span class="medio-logo visa">VISA</span>
                <span class="medio-logo master">Mastercard</span>
                <span class="medio-logo mp">Mercado Pago</span>
                <span class="medio-logo trans">Transferencia</span>
            </div>
        </div>

        <div class="footer-medios-bloque">
            <h4>Promos bancarias</h4>
            <div class="medios-logos">
                <span class="medio-logo macro">Banco Macro</span>
                <span class="medio-logo galicia">Galicia</span>
                <span class="medio-logo cuotas">Cuotas</span>
            </div>
        </div>

        <div class="footer-qr-box">
            <h4>Datos fiscales</h4>
            <div class="qr-placeholder" aria-label="QR fiscal pendiente de carga">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <p>QR fiscal pendiente</p>
        </div>
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
<!-- CHATBOT SPORTSTYLE -->
<div class="asistente-float" id="asistenteFloat">

    <button class="asistente-btn" id="asistenteBtn" type="button" aria-label="Abrir chat de tienda">
        Chat
    </button>

    <div class="asistente-box" id="asistenteBox">

        <div class="asistente-header">
            <div>
                <strong>Chat <?= htmlspecialchars($nombreTiendaFooter) ?></strong>
                <span>Asistente de tienda</span>
            </div>
            <button id="cerrarAsistente" type="button" aria-label="Cerrar chat">×</button>
        </div>

        <div class="asistente-body">

            <div class="chat-mensajes" id="chatMensajes">
                <div class="chat-msg bot">
                    <p>Hola, soy el asistente de <?= htmlspecialchars($nombreTiendaFooter) ?>. Te puedo ayudar con productos, talles, pagos, envíos o seguimiento de pedidos.</p>
                </div>
            </div>

            <div class="chat-opciones">
                <button type="button" data-chat="productos">Busco productos</button>
                <button type="button" data-chat="talles">Tengo dudas con talles</button>
                <button type="button" data-chat="pagos">Medios de pago</button>
                <button type="button" data-chat="envios">Envíos</button>
                <button type="button" data-chat="pedidos">Seguir pedido</button>
                <button type="button" data-chat="cambios">Cambios y devoluciones</button>
            </div>

            <form class="chat-input" id="chatInputForm">
                <input type="text" id="chatInput" placeholder="Escribí tu consulta..." autocomplete="off">
                <button type="submit">Enviar</button>
            </form>

            <div class="chat-links">
                <a href="productos.php">Ver productos</a>
                <?php if(!empty($whatsappTiendaFooter)): ?>
                    <a href="https://wa.me/<?= htmlspecialchars($whatsappTiendaFooter) ?>" target="_blank">WhatsApp</a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

</footer>

<script src="/sportstyle/java/main.js"></script>
</body>
</html>
