<?php
session_start();

$enviado = false;
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Acá podés agregar envío de email con PHPMailer en el futuro
    $enviado = true;
}
?>

<?php include("includes/header.php"); ?>

<h1 class="titulo">Contacto</h1>

<div class="contacto-container">

    <!-- FORMULARIO -->
    <div class="contacto-form">
        <h2>Envianos un mensaje</h2>

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
            <button type="submit" class="btn" style="width:100%; padding:14px;">
                Enviar mensaje ✉️
            </button>
        </form>
    </div>

    <!-- LATERAL -->
    <div class="contacto-lateral">

        <a href="https://wa.me/549XXXXXXXXXX?text=Hola!%20Quiero%20más%20información"
           target="_blank" class="contacto-card">
            <span class="icono">💬</span>
            <h3>WhatsApp</h3>
            <p>Respondemos al instante en horario comercial</p>
        </a>

        <a href="https://instagram.com/sportstyle" target="_blank" class="contacto-card">
            <span class="icono">📸</span>
            <h3>Instagram</h3>
            <p>@sportstyle — Seguinos para ver novedades</p>
        </a>

        <a href="mailto:sportstyle@gmail.com" class="contacto-card">
            <span class="icono">✉️</span>
            <h3>Email</h3>
            <p>sportstyle@gmail.com</p>
        </a>

        <div class="contacto-card" style="cursor:default;">
            <span class="icono">🕐</span>
            <h3>Horarios</h3>
            <p>Lunes a Viernes: 9hs - 18hs<br>Sábados: 9hs - 13hs</p>
        </div>

    </div>

</div>

<?php include("includes/footer.php"); ?>