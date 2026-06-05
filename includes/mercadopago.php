<?php

function mpBaseUrl(): string {

    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http';

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $carpeta = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    return $protocolo . '://' . $host . ($carpeta === '' ? '' : $carpeta);

}

function mpApiRequest(string $metodo, string $endpoint, string $accessToken, ?array $body = null): array {

    if(!function_exists('curl_init')){
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'La extensión cURL de PHP no está activa.'
        ];
    }

    $ch = curl_init('https://api.mercadopago.com' . $endpoint);

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if($body !== null){
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $data = json_decode($respuesta ?: '', true);

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => is_array($data) ? $data : null,
        'error' => $error
    ];

}

function mpEstadoPedidoDesdeStatus(?string $status): string {

    return match($status){
        'approved' => 'Pagado',
        'rejected',
        'cancelled',
        'refunded',
        'charged_back' => 'Cancelado',
        default => 'Pendiente'
    };

}

function mpActualizarPedidoDesdeRetorno(mysqli $conn, string $accessToken, int $pedidoId, ?int $usuarioId = null): array {

    $paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? '';
    $paymentId = preg_replace('/[^0-9]/', '', (string) $paymentId);

    if($paymentId === ''){
        return [
            'estado' => 'Pendiente',
            'mp_status' => $_GET['status'] ?? null,
            'payment_id' => null
        ];
    }

    $respuesta = mpApiRequest('GET', '/v1/payments/' . $paymentId, $accessToken);

    if(!$respuesta['ok'] || empty($respuesta['data'])){
        return [
            'estado' => 'Pendiente',
            'mp_status' => null,
            'payment_id' => $paymentId
        ];
    }

    $mpStatus = $respuesta['data']['status'] ?? null;
    $estadoPedido = mpEstadoPedidoDesdeStatus($mpStatus);

    $sql = "UPDATE pedidos
            SET estado = ?,
                mp_payment_id = ?,
                mp_status = ?
            WHERE id = ?";

    if($usuarioId !== null){
        $sql .= " AND usuario_id = ?";
    }

    $stmt = mysqli_prepare($conn, $sql);

    if($usuarioId !== null){

        mysqli_stmt_bind_param(
            $stmt,
            "sssii",
            $estadoPedido,
            $paymentId,
            $mpStatus,
            $pedidoId,
            $usuarioId
        );

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $estadoPedido,
            $paymentId,
            $mpStatus,
            $pedidoId
        );

    }

    mysqli_stmt_execute($stmt);

    return [
        'estado' => $estadoPedido,
        'mp_status' => $mpStatus,
        'payment_id' => $paymentId
    ];

}
