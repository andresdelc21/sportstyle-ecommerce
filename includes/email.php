<?php

function smtpRespuesta($socket): array {
    $respuesta = '';

    while(($linea = fgets($socket, 515)) !== false){
        $respuesta .= $linea;

        if(strlen($linea) >= 4 && $linea[3] === ' '){
            break;
        }
    }

    $codigo = (int) substr($respuesta, 0, 3);

    return [$codigo, $respuesta];
}

function smtpComando($socket, string $comando, array $codigosEsperados, string &$error): bool {
    fwrite($socket, $comando . "\r\n");

    [$codigo, $respuesta] = smtpRespuesta($socket);

    if(!in_array($codigo, $codigosEsperados, true)){
        $error = trim($respuesta);
        return false;
    }

    return true;
}

function smtpEncodedHeader(string $texto): string {
    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

function smtpNormalizarLineas(string $contenido): string {
    $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
    $lineas = explode("\n", $contenido);

    foreach($lineas as &$linea){
        if(str_starts_with($linea, '.')){
            $linea = '.' . $linea;
        }
    }

    return implode("\r\n", $lineas);
}

function enviarEmailSmtp(string $para, string $asunto, string $html, string $textoPlano = '', string &$error = ''): bool {
    global $SMTP_ACTIVO,
           $SMTP_HOST,
           $SMTP_PORT,
           $SMTP_SECURE,
           $SMTP_USER,
           $SMTP_PASS,
           $SMTP_FROM_EMAIL,
           $SMTP_FROM_NAME,
           $EMAIL_TIENDA,
           $NOMBRE_TIENDA;

    if((string) ($SMTP_ACTIVO ?? '0') !== '1'){
        $error = 'SMTP no está activo.';
        return false;
    }

    $host = trim((string) ($SMTP_HOST ?? ''));
    $port = (int) ($SMTP_PORT ?? 587);
    $secure = strtolower(trim((string) ($SMTP_SECURE ?? 'tls')));
    $user = trim((string) ($SMTP_USER ?? ''));
    $pass = (string) ($SMTP_PASS ?? '');
    $fromEmail = trim((string) ($SMTP_FROM_EMAIL ?: $EMAIL_TIENDA ?? $user));
    $fromName = trim((string) ($SMTP_FROM_NAME ?: $NOMBRE_TIENDA ?? 'SportStyle'));

    if($host === '' || $port <= 0 || $user === '' || $pass === '' || $fromEmail === ''){
        $error = 'Faltan datos SMTP.';
        return false;
    }

    if(!filter_var($para, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)){
        $error = 'Email inválido.';
        return false;
    }

    $remoto = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remoto, $errno, $errstr, 20);

    if(!$socket){
        $error = 'No se pudo conectar al SMTP: ' . $errstr;
        return false;
    }

    stream_set_timeout($socket, 20);

    [$codigo, $respuesta] = smtpRespuesta($socket);

    if($codigo !== 220){
        fclose($socket);
        $error = trim($respuesta);
        return false;
    }

    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';

    if(!smtpComando($socket, 'EHLO ' . $hostname, [250], $error)){
        fclose($socket);
        return false;
    }

    if($secure === 'tls'){
        if(!smtpComando($socket, 'STARTTLS', [220], $error)){
            fclose($socket);
            return false;
        }

        if(!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)){
            fclose($socket);
            $error = 'No se pudo activar TLS.';
            return false;
        }

        if(!smtpComando($socket, 'EHLO ' . $hostname, [250], $error)){
            fclose($socket);
            return false;
        }
    }

    if(!smtpComando($socket, 'AUTH LOGIN', [334], $error)
        || !smtpComando($socket, base64_encode($user), [334], $error)
        || !smtpComando($socket, base64_encode($pass), [235], $error)){
        fclose($socket);
        return false;
    }

    if(!smtpComando($socket, 'MAIL FROM:<' . $fromEmail . '>', [250], $error)
        || !smtpComando($socket, 'RCPT TO:<' . $para . '>', [250, 251], $error)
        || !smtpComando($socket, 'DATA', [354], $error)){
        fclose($socket);
        return false;
    }

    if($textoPlano === ''){
        $textoPlano = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
    }

    $boundary = 'sportstyle_' . bin2hex(random_bytes(12));

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . smtpEncodedHeader($fromName) . ' <' . $fromEmail . '>',
        'To: <' . $para . '>',
        'Subject: ' . smtpEncodedHeader($asunto),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $hostname . '>'
    ];

    $cuerpo = implode("\r\n", $headers) . "\r\n\r\n";
    $cuerpo .= '--' . $boundary . "\r\n";
    $cuerpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $cuerpo .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $cuerpo .= $textoPlano . "\r\n\r\n";
    $cuerpo .= '--' . $boundary . "\r\n";
    $cuerpo .= "Content-Type: text/html; charset=UTF-8\r\n";
    $cuerpo .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $cuerpo .= $html . "\r\n\r\n";
    $cuerpo .= '--' . $boundary . "--\r\n";

    fwrite($socket, smtpNormalizarLineas($cuerpo) . "\r\n.\r\n");
    [$codigo, $respuesta] = smtpRespuesta($socket);

    smtpComando($socket, 'QUIT', [221], $error);
    fclose($socket);

    if($codigo !== 250){
        $error = trim($respuesta);
        return false;
    }

    return true;
}

function enviarEmailRecuperacionPassword(string $para, string $nombre, string $linkReset, string &$error = ''): bool {
    global $NOMBRE_TIENDA;

    $nombreTienda = $NOMBRE_TIENDA ?? 'SportStyle';
    $nombreSeguro = htmlspecialchars($nombre ?: 'cliente', ENT_QUOTES, 'UTF-8');
    $linkSeguro = htmlspecialchars($linkReset, ENT_QUOTES, 'UTF-8');

    $asunto = 'Recuperá tu contraseña - ' . $nombreTienda;

    $html = '
        <div style="font-family:Arial,sans-serif;line-height:1.5;color:#111827">
            <h2 style="margin-bottom:8px">' . htmlspecialchars($nombreTienda, ENT_QUOTES, 'UTF-8') . '</h2>
            <p>Hola ' . $nombreSeguro . ', recibimos una solicitud para restablecer tu contraseña.</p>
            <p>Ingresá al siguiente enlace para crear una nueva contraseña:</p>
            <p>
                <a href="' . $linkSeguro . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px">
                    Restablecer contraseña
                </a>
            </p>
            <p>Este enlace vence en 1 hora. Si no solicitaste este cambio, podés ignorar este correo.</p>
        </div>';

    $texto = "Hola " . ($nombre ?: 'cliente') . ",\n\n"
        . "Recibimos una solicitud para restablecer tu contraseña en " . $nombreTienda . ".\n"
        . "Abrí este enlace para crear una nueva contraseña:\n"
        . $linkReset . "\n\n"
        . "El enlace vence en 1 hora. Si no solicitaste este cambio, ignorá este correo.";

    return enviarEmailSmtp($para, $asunto, $html, $texto, $error);
}
