<?php
session_start();
include("data/productos.php");
include("data/carrito_helpers.php");

// ⚠️ Instalá el SDK con: composer require mercadopago/dx-php
require_once('vendor/autoload.php');

MercadoPago\SDK::setAccessToken('TU_ACCESS_TOKEN_ACÁ');

$carrito   = $_SESSION['carrito'];
$descuento = $_SESSION['descuento'] ?? 0;
$total     = calcularTotal($carrito, $productos, $descuento);

// Armar items para MP
$items = [];
foreach($carrito as $id => $cantidad){
    foreach($productos as $p){
        if($p['id'] == $id){
            $item = new MercadoPago\Item();
            $item->title     = $p['nombre'];
            $item->quantity  = $cantidad;
            $item->unit_price = (float)$p['precio'];
            $items[] = $item;
        }
    }
}

// Si hay descuento, agregamos un item negativo
if($descuento > 0){
    $itemDesc = new MercadoPago\Item();
    $itemDesc->title     = "Descuento " . $descuento . "%";
    $itemDesc->quantity  = 1;
    $subtotal = calcularTotal($carrito, $productos, 0);
    $itemDesc->unit_price = (float)-($subtotal * $descuento / 100);
    $items[] = $itemDesc;
}

$preference = new MercadoPago\Preference();
$preference->items = $items;
$preference->back_urls = [
    "success" => "http://localhost/sportstyle/gracias.php",
    "failure" => "http://localhost/sportstyle/carrito.php",
    "pending" => "http://localhost/sportstyle/carrito.php"
];
$preference->auto_return = "approved";
$preference->save();

header("Location: " . $preference->init_point);
exit();
?>