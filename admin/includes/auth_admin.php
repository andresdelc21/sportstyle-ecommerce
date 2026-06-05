<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(
    !isset($_SESSION['usuario_id'])
    ||
    !isset($_SESSION['usuario_rol'])
    ||
    $_SESSION['usuario_rol'] !== 'admin'
){
    header("Location: ../login.php");
    exit;
}
