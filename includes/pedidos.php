<?php

/* =========================================
   CREAR PEDIDO
========================================= */
function crearPedido(
    mysqli $conn,
    int $usuario_id,
    string $nombre_cliente,
    string $telefono_cliente,
    string $direccion_envio,
    float $subtotal,
    float $costo_envio,
    string $codigo_postal,
    string $zona_envio,
    string $metodo_pago,
    float $total
): int {

    $sql = "INSERT INTO pedidos
    (
        usuario_id,
        nombre_cliente,
        telefono_cliente,
        direccion_envio,
        subtotal,
        costo_envio,
        codigo_postal,
        zona_envio,
        metodo_pago,
        total
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "isssddsssd",
        $usuario_id,
        $nombre_cliente,
        $telefono_cliente,
        $direccion_envio,
        $subtotal,
        $costo_envio,
        $codigo_postal,
        $zona_envio,
        $metodo_pago,
        $total
    );

    mysqli_stmt_execute($stmt);

    return mysqli_insert_id($conn);

}

/* =========================================
   GUARDAR DETALLE PEDIDO
========================================= */
function guardarDetallePedido(
    mysqli $conn,
    int $pedido_id,
    int $producto_id,
    int $cantidad,
    float $precio
){

    $sql = "INSERT INTO detalle_pedidos
    (
        pedido_id,
        producto_id,
        cantidad,
        precio
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?
    )";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiid",
        $pedido_id,
        $producto_id,
        $cantidad,
        $precio
    );

    mysqli_stmt_execute($stmt);

}

/* =========================================
   DESCONTAR STOCK
========================================= */
function descontarStock(
    mysqli $conn,
    int $producto_id,
    int $cantidad
){

    $sql = "UPDATE productos
    SET stock = stock - ?
    WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $cantidad,
        $producto_id
    );

    mysqli_stmt_execute($stmt);

}