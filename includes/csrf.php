<?php

function csrfToken(): string {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function validarCsrf(): bool {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    $token = $_POST['csrf_token'] ?? '';

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
