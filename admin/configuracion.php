<?php
session_start();

require_once __DIR__ . "/includes/auth_admin.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/conexion.php";
if(!isset($_SESSION['usuario_nombre'])){ header("Location: ../login.php"); exit; }

$claves = [
    'NOMBRE_TIENDA' => 'Nombre de tienda',
    'WHATSAPP_TIENDA' => 'WhatsApp',
    'EMAIL_TIENDA' => 'Email',
    'SMTP_ACTIVO' => 'SMTP activo (1 sí / 0 no)',
    'SMTP_HOST' => 'SMTP servidor',
    'SMTP_PORT' => 'SMTP puerto',
    'SMTP_SECURE' => 'SMTP seguridad (tls / ssl / none)',
    'SMTP_USER' => 'SMTP usuario',
    'SMTP_PASS' => 'SMTP contraseña',
    'SMTP_FROM_EMAIL' => 'SMTP email remitente',
    'SMTP_FROM_NAME' => 'SMTP nombre remitente',
    'ALIAS_TIENDA' => 'Alias de pago',
    'CBU_TIENDA' => 'CBU/CVU',
    'TITULAR_TIENDA' => 'Titular',
    'DIRECCION_TIENDA' => 'Dirección de tienda',
    'MAPS_TIENDA' => 'Link de Google Maps',
    'URL_TIENDA' => 'URL pública de la tienda',
    'INSTAGRAM_TIENDA' => 'Instagram',
    'FACEBOOK_TIENDA' => 'Facebook',
    'MP_PUBLIC_KEY' => 'MercadoPago Public Key',
    'MP_ACCESS_TOKEN' => 'MercadoPago Access Token',
    'MP_WEBHOOK_TOKEN' => 'MercadoPago Webhook Token'
];

$clavesSecretas = ['MP_ACCESS_TOKEN', 'MP_WEBHOOK_TOKEN', 'SMTP_PASS'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validarCsrf()){
        header("Location: configuracion.php");
        exit;
    }

    foreach($claves as $clave => $label){
        $valor = trim($_POST[$clave] ?? '');

        if(in_array($clave, $clavesSecretas, true) && $valor === ''){
            continue;
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO configuracion_tienda (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        mysqli_stmt_bind_param($stmt, "ss", $clave, $valor);
        mysqli_stmt_execute($stmt);
    }
    header("Location: configuracion.php?ok=1");
    exit;
}

$valores = [];
$res = mysqli_query($conn, "SELECT clave, valor FROM configuracion_tienda");
while($fila = mysqli_fetch_assoc($res)){ $valores[$fila['clave']] = $fila['valor']; }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Configuración | Admin</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body class="admin-body"><div class="admin-container"><?php include("includes/sidebar.php"); ?><main class="admin-content">
<section class="admin-hero small-hero"><div><span class="admin-badge">Tienda</span><h1>Configuración</h1><p>Datos comerciales, redes y credenciales principales.</p></div></section>
<?php if(isset($_GET['ok'])): ?><div class="admin-alert success-msg">Configuración guardada.</div><?php endif; ?>
<section class="pedido-panel"><form method="POST" class="form-admin-premium"><?= csrfInput() ?><div class="admin-grid">
<?php foreach($claves as $clave => $label): ?>
<?php $esSecreta = in_array($clave, $clavesSecretas, true); ?>
<div class="input-group">
    <label><?= htmlspecialchars($label) ?></label>
    <input type="<?= $esSecreta ? 'password' : 'text' ?>"
           name="<?= $clave ?>"
           value="<?= $esSecreta ? '' : htmlspecialchars($valores[$clave] ?? '') ?>"
           placeholder="<?= $esSecreta && !empty($valores[$clave] ?? '') ? 'Credencial cargada. Escribí una nueva solo si querés cambiarla.' : '' ?>"
           autocomplete="<?= $esSecreta ? 'new-password' : 'off' ?>">
</div>
<?php endforeach; ?>
</div><button class="btn-admin-agregar" type="submit">Guardar configuración</button></form></section>
</main></div></body></html>
