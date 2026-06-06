<?php

// Calcula el total del carrito respetando cantidades
function calcularTotal(array $carrito, array $productos, float $descuento = 0): float {
    $total = 0;
    foreach ($carrito as $key => $cantidad) {
        $id = carritoProductoId($key);

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

function carritoKey(int $productoId, ?int $talleId = null): string {
    return $talleId ? $productoId . ':' . $talleId : (string) $productoId;
}

function carritoProductoId($key): int {
    $partes = explode(':', (string) $key);
    return (int) $partes[0];
}

function carritoTalleId($key): ?int {
    $partes = explode(':', (string) $key);
    return isset($partes[1]) && (int) $partes[1] > 0
        ? (int) $partes[1]
        : null;
}

function talleLabel(?array $talle): string {
    if(!$talle){
        return '';
    }

    if(!empty($talle['etiqueta']) && $talle['etiqueta'] !== 'Único'){
        return $talle['etiqueta'];
    }

    $partes = [];

    if(!empty($talle['talle_arg'])){ $partes[] = 'ARG ' . $talle['talle_arg']; }
    if(!empty($talle['talle_us'])){ $partes[] = 'US ' . $talle['talle_us']; }
    if(!empty($talle['talle_br'])){ $partes[] = 'BR ' . $talle['talle_br']; }
    if(!empty($talle['cm'])){ $partes[] = $talle['cm'] . ' cm'; }

    return !empty($partes)
        ? implode(' / ', $partes)
        : ($talle['etiqueta'] ?? '');
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

function validarCuponDb(mysqli $conn, string $codigo) {
    $codigo = strtoupper(trim($codigo));

    if($codigo === ''){
        return false;
    }

    $sql = "SELECT codigo, tipo, valor
            FROM cupones
            WHERE codigo = ?
            AND activo = 1
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){
        return validarCupon($codigo);
    }

    mysqli_stmt_bind_param($stmt, "s", $codigo);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($resultado) === 0){
        return false;
    }

    $cupon = mysqli_fetch_assoc($resultado);

    return $cupon;
}
?>
