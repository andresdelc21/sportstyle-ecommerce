<?php

include("config/conexion.php");
include("config/config.php");
include("includes/mercadopago.php");

header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

if(!empty($MP_WEBHOOK_TOKEN)){
    $tokenRecibido = $_GET['token'] ?? '';

    if(!hash_equals($MP_WEBHOOK_TOKEN, $tokenRecibido)){
        http_response_code(403);
        echo json_encode([
            "ok" => false,
            "mensaje" => "Token inválido."
        ]);
        exit;
    }
}

if(empty($MP_ACCESS_TOKEN)){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Mercado Pago no tiene Access Token configurado."
    ]);
    exit;
}

$rawBody = file_get_contents("php://input");
$payload = json_decode($rawBody ?: "{}", true);

if(!is_array($payload)){
    $payload = [];
}

$tipo = $payload['type'] ?? $payload['topic'] ?? ($_GET['topic'] ?? '');
$paymentId = $payload['data']['id'] ?? $payload['id'] ?? ($_GET['data_id'] ?? $_GET['id'] ?? '');
$paymentId = preg_replace('/[^0-9]/', '', (string) $paymentId);

if($tipo !== '' && !in_array($tipo, ['payment', 'merchant_order'], true)){
    echo json_encode([
        "ok" => true,
        "mensaje" => "Notificación recibida sin acción.",
        "tipo" => $tipo
    ]);
    exit;
}

if($paymentId === ''){
    echo json_encode([
        "ok" => false,
        "mensaje" => "No llegó el ID del pago."
    ]);
    exit;
}

$resultado = mpActualizarPedidoPorPaymentId($conn, $MP_ACCESS_TOKEN, $paymentId);

echo json_encode($resultado);
exit;
