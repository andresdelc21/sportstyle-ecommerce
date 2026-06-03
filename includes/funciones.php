<?php

/* =========================================
   OBTENER PRODUCTO POR ID
========================================= */
function obtenerProductoPorId(array $productos, int|string $id): ?array{

    foreach($productos as $producto){

        if($producto['id'] == $id){

            return $producto;

        }

    }

    return null;

}

/* =========================================
   CALCULAR TOTAL DEL CARRITO
========================================= */
function calcularTotalCarrito(array $carrito, array $productos): float{

    $total = 0;

    foreach($carrito as $id => $cantidad){

        $producto = obtenerProductoPorId($productos, $id);

        if($producto){

            $total += $producto['precio'] * $cantidad;

        }

    }

    return $total;

}

/* =========================================
   GENERAR MENSAJE WHATSAPP
========================================= */
function generarMensajeWhatsApp(array $carrito, array $productos): string{

    $mensaje = "Hola, quiero comprar:%0A";

    foreach($carrito as $id => $cantidad){

        $producto = obtenerProductoPorId($productos, $id);

        if($producto){

            $mensaje .= "- " .
            $producto['nombre'] .
            " x" .
            $cantidad .
            "%0A";

        }

    }

    return $mensaje;

}