<?php

/* =========================
   DATOS GENERALES TIENDA
========================= */

$NOMBRE_TIENDA = "SportStyle";

$WHATSAPP_TIENDA = "5491112345678";

$EMAIL_TIENDA = "contacto@sportstyle.com";

$SMTP_ACTIVO = "0";

$SMTP_HOST = "";

$SMTP_PORT = "587";

$SMTP_SECURE = "tls";

$SMTP_USER = "";

$SMTP_PASS = "";

$SMTP_FROM_EMAIL = "";

$SMTP_FROM_NAME = "SportStyle";

$ALIAS_TIENDA = "sportstyle.mp";

$CBU_TIENDA = "0000000000000000000000";

$TITULAR_TIENDA = "SportStyle";

$DIRECCION_TIENDA = "SportStyle tienda";

$MAPS_TIENDA = "https://www.google.com/maps/search/?api=1&query=SportStyle%20tienda";

$URL_TIENDA = "http://localhost/sportstyle";


/* =========================
   REDES SOCIALES
========================= */

$INSTAGRAM_TIENDA = "https://www.instagram.com/sportstyle";

$FACEBOOK_TIENDA = "https://www.facebook.com/sportstyle";


/* =========================
   MERCADO PAGO
========================= */

$MP_PUBLIC_KEY = "";

$MP_ACCESS_TOKEN = "";

$MP_WEBHOOK_TOKEN = "";

/* =========================
   CONFIGURACIÓN DESDE ADMIN
========================= */

if(isset($conn)){

    $resultadoConfig = mysqli_query(
        $conn,
        "SELECT clave, valor FROM configuracion_tienda"
    );

    if($resultadoConfig){

        while($config = mysqli_fetch_assoc($resultadoConfig)){

            ${$config['clave']} = $config['valor'];

        }

    }

}
