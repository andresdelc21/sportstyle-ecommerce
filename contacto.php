<?php
session_start();
include("config/conexion.php");
include("config/config.php");

$enviado = false;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Acá podés agregar envío de email con PHPMailer en el futuro
    $enviado = true;
}
?>

<?php include("includes/header.php"); ?>

<section class="contacto-header">

    <span class="productos-badge">
        Atención al cliente
    </span>

    <h1>
        Contacto
    </h1>

    <p>
        Escribinos por consultas de productos, envíos, pagos o cambios. Te respondemos lo antes posible.
    </p>

</section>

<div class="contacto-container">

    <!-- FORMULARIO -->
    <div class="contacto-form">

        <div class="contacto-form-title">

            <h2>Envianos un mensaje</h2>

            <p>
                Completá el formulario y te contactamos por email.
            </p>

        </div>

        <?php if($enviado): ?>
            <div class="alerta-ok" style="display:block;">
                ✅ ¡Mensaje enviado! Te respondemos a la brevedad.
            </div>
        <?php endif; ?>

        <form method="POST" action="contacto.php">
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" placeholder="Tu nombre" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="tu@email.com" required>
            </div>
            <div class="form-group">
                <label>Asunto</label>
                <input type="text" name="asunto" placeholder="¿En qué te podemos ayudar?">
            </div>
            <div class="form-group">
                <label>Mensaje</label>
                <textarea name="mensaje" placeholder="Escribí tu mensaje acá..." required></textarea>
            </div>
            <button type="submit" class="btn contacto-submit">
                Enviar mensaje ✉️
            </button>
        </form>
    </div>

    <!-- LATERAL -->
    <div class="contacto-lateral">

        <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $WHATSAPP_TIENDA ?? '')) ?>?text=Hola!%20Quiero%20más%20información"
           target="_blank" class="contacto-card">
            <span class="icono">💬</span>
            <div>
                <h3>WhatsApp</h3>
                <p>Respuesta rápida en horario comercial</p>
            </div>
        </a>

        <a href="<?= htmlspecialchars($INSTAGRAM_TIENDA ?? '#') ?>" target="_blank" class="contacto-card">
            <span class="icono">📸</span>
            <div>
                <h3>Instagram</h3>
                <p>Novedades, ingresos y promociones</p>
            </div>
        </a>

        <a href="mailto:<?= htmlspecialchars($EMAIL_TIENDA ?? 'contacto@sportstyle.com') ?>" class="contacto-card">
            <span class="icono">✉️</span>
            <div>
                <h3>Email</h3>
                <p><?= htmlspecialchars($EMAIL_TIENDA ?? 'contacto@sportstyle.com') ?></p>
            </div>
        </a>

        <div class="contacto-card" style="cursor:default;">
            <span class="icono">🕐</span>
            <div>
                <h3>Horarios</h3>
                <p>Lunes a Viernes: 9hs - 18hs<br>Sábados: 9hs - 13hs</p>
            </div>
        </div>

    </div>

</div>

<?php include("includes/footer.php"); ?>
