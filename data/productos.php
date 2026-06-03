<?php

$productos = [

    // ===== REMERAS =====
    [
        "id" => 1,
        "nombre" => "Remera Deportiva Nike",
        "precio" => 15000,
        "precio_original" => 22000,

        "imagen" => "img/verde.jpg", // principal

        "imagenes" => [
            "img/verde.jpg",
            "img/remera.jpg",
            "img/puma1.jpg",
            "img/verde.jpg"
        ],

        "categoria" => "Remeras",
        "genero" => "Hombre",
        "stock" => 10,
        "rating" => 4,

        "descripcion" => "Remera cómoda y liviana, ideal para entrenamiento diario.",
        "material" => "Poliéster transpirable",

        "talles" => ["S", "M", "L", "XL"],
        "colores" => ["Negro", "Blanco", "Rojo"],

        "reviews" => [
            ["user" => "Juan", "rating" => 5, "comentario" => "Muy cómoda"],
            ["user" => "Lucas", "rating" => 4, "comentario" => "Buen material"]
        ]
    ],

    [
        "id" => 2,
        "nombre" => "Remera Adidas Climalite",
        "precio" => 18000,
        "precio_original" => 18000,

        "imagen" => "img/remera.jpg",

        "imagenes" => [
            "img/remera.jpg",
            "img/puma1.jpg",
            "img/verde.jpg",
            "img/remera.jpg"
        ],

        "categoria" => "Remeras",
        "genero" => "Mujer",
        "stock" => 15,
        "rating" => 5,

        "descripcion" => "Tecnología Climalite que aleja la humedad del cuerpo.",
        "material" => "Tela Climalite",

        "talles" => ["S", "M", "L", "XL", "XXL"],
        "colores" => ["Azul", "Negro", "Verde"],

        "reviews" => [
            ["user" => "Ana", "rating" => 5, "comentario" => "Excelente calidad"]
        ]
    ],

    [
        "id" => 3,
        "nombre" => "Remera Puma Training",
        "precio" => 13000,
        "precio_original" => 16000,

        "imagen" => "img/puma1.jpg",

        "imagenes" => [
            "img/puma1.jpg",
            "img/remera.jpg",
            "img/verde.jpg",
            "img/puma1.jpg"
        ],

        "categoria" => "Remeras",
        "genero" => "Unisex",
        "stock" => 20,
        "rating" => 4,

        "descripcion" => "Remera transpirable perfecta para el gym.",
        "material" => "Algodón + poliéster",

        "talles" => ["S", "M", "L"],
        "colores" => ["Gris", "Negro"],

        "reviews" => []
    ],

    // ===== CALZADO =====
    [
        "id" => 4,
        "nombre" => "Zapatillas Running Nike",
        "precio" => 45000,
        "precio_original" => 60000,

        "imagen" => "img/zapatillas.jpg",

        "imagenes" => [
            "img/zapatillas.jpg",
            "img/zapatillas1.jpg",
            "img/zapatillas2.jpg",
            "img/zapatillas.jpg"
        ],

        "categoria" => "Calzado",
        "genero" => "Hombre",
        "stock" => 5,
        "rating" => 5,

        "descripcion" => "Ideales para correr largas distancias con máxima comodidad.",
        "material" => "Mesh + goma",

        "talles" => [38, 39, 40, 41, 42, 43],
        "colores" => ["Negro", "Blanco"],

        "reviews" => [
            ["user" => "Carlos", "rating" => 5, "comentario" => "Muy cómodas"]
        ]
    ],

    [
        "id" => 5,
        "nombre" => "Zapatillas Adidas Ultraboost",
        "precio" => 55000,
        "precio_original" => 55000,

        "imagen" => "img/zapatillas1.jpg",

        "imagenes" => [
            "img/zapatillas1.jpg",
            "img/zapatillas.jpg",
            "img/zapatillas2.jpg",
            "img/zapatillas1.jpg"
        ],

        "categoria" => "Calzado",
        "genero" => "Mujer",
        "stock" => 8,
        "rating" => 5,

        "descripcion" => "Máxima energía de retorno con tecnología Boost.",
        "material" => "Primeknit",

        "talles" => [38, 39, 40, 41, 42],
        "colores" => ["Blanco", "Gris"],

        "reviews" => []
    ],

    // ===== ACCESORIOS =====
    [
        "id" => 8,
        "nombre" => "Mochila Deportiva Nike",
        "precio" => 22000,
        "precio_original" => 30000,

        "imagen" => "img/mochila.jpg",

        "imagenes" => [
            "img/mochila.jpg",
            "img/mochila.jpg",
            "img/mochila.jpg",
            "img/mochila.jpg"
        ],

        "categoria" => "Accesorios",
        "genero" => "Unisex",
        "stock" => 12,
        "rating" => 4,

        "descripcion" => "Mochila con múltiples compartimentos.",
        "material" => "Poliéster resistente",

        "talles" => [],
        "colores" => ["Negro", "Beige", "Azul"],

        "reviews" => []
    ]

];
?>