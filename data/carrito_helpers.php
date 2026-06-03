<?php

// Calcula el total del carrito respetando cantidades
function calcularTotal(array $carrito, array $productos, float $descuento = 0): float {
    $total = 0;
    foreach ($carrito as $id => $cantidad) {
        foreach ($productos as $p) {
            if ($p['id'] == $id) {
                $total += $p['precio'] * $cantidad;
            }
        }
    }
    if ($descuento > 0) {
        $total = $total - ($total * $descuento / 100);
    }
    return $total;
}

// Devuelve la cantidad total de items en el carrito
function cantidadItems(array $carrito): int {
    return array_sum($carrito);
}

// Cupones válidos: 'codigo' => % de descuento
function validarCupon(string $codigo) {
    $cupones = [
        "SPORT10" => 10,
        "SPORT20" => 20,
        "PROMO50" => 50
    ];
    $codigo = strtoupper(trim($codigo));
    return isset($cupones[$codigo]) ? $cupones[$codigo] : false;
}
?>