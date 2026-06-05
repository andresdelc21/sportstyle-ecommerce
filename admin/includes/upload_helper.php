<?php

function guardarImagenSubida(array $archivo, string &$error): ?string {
    if(($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
        return null;
    }

    $maxBytes = 5 * 1024 * 1024;

    if(($archivo['size'] ?? 0) > $maxBytes){
        $error = 'La imagen no puede superar 5 MB.';
        return null;
    }

    $tmp = $archivo['tmp_name'] ?? '';

    if(!is_uploaded_file($tmp)){
        $error = 'Archivo de imagen inválido.';
        return null;
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);

    if(!isset($permitidos[$mime])){
        $error = 'Formato no permitido. Usá JPG, PNG, WebP o AVIF.';
        return null;
    }

    $nombreSeguro = bin2hex(random_bytes(12)) . '.' . $permitidos[$mime];
    $destino = __DIR__ . "/../../uploads/" . $nombreSeguro;

    if(!move_uploaded_file($tmp, $destino)){
        $error = 'Error al subir la imagen.';
        return null;
    }

    return "uploads/" . $nombreSeguro;
}
