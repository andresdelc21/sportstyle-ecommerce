CREATE TABLE IF NOT EXISTS subcategorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_subcategoria_categoria_nombre (categoria_id, nombre),
    KEY idx_subcategorias_categoria (categoria_id)
);

ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS subcategoria_id INT NULL AFTER categoria_id;

UPDATE categorias SET nombre = 'Calzado' WHERE id = 1;
UPDATE categorias SET nombre = 'Ropa' WHERE id = 2;
UPDATE categorias SET nombre = 'Accesorios' WHERE id = 3;
UPDATE categorias SET nombre = 'Deportes' WHERE id = 4;

UPDATE productos SET categoria_id = 2 WHERE categoria_id = 5;
DELETE FROM categorias WHERE id = 5;

INSERT IGNORE INTO subcategorias (categoria_id, nombre) VALUES
(1, 'Fútbol'),
(1, 'Lifestyle'),
(1, 'Running'),
(1, 'Training'),
(1, 'Motorsport'),
(1, 'Basketball'),
(1, 'Outdoor'),
(1, 'Padel'),
(1, 'Ojotas'),
(2, 'Camperas'),
(2, 'Conjuntos'),
(2, 'Buzos'),
(2, 'Pantalones'),
(2, 'Remeras'),
(2, 'Shorts'),
(2, 'Camisetas'),
(2, 'Medias'),
(2, 'Calzas'),
(3, 'Gorros'),
(3, 'Pelotas'),
(3, 'Bolsos y Mochilas'),
(3, 'Otros'),
(4, 'Fútbol'),
(4, 'Running'),
(4, 'Training'),
(4, 'Motorsport'),
(4, 'Basketball'),
(4, 'Outdoor'),
(4, 'Padel');

UPDATE productos
SET subcategoria_id = (
    SELECT id FROM subcategorias WHERE categoria_id = 1 AND nombre = 'Running' LIMIT 1
)
WHERE id = 1;

UPDATE productos
SET categoria_id = 2,
    subcategoria_id = (
        SELECT id FROM subcategorias WHERE categoria_id = 2 AND nombre = 'Remeras' LIMIT 1
    )
WHERE id IN (2, 6);

UPDATE productos
SET categoria_id = 2,
    subcategoria_id = (
        SELECT id FROM subcategorias WHERE categoria_id = 2 AND nombre = 'Medias' LIMIT 1
    )
WHERE id = 4;

UPDATE productos
SET categoria_id = 2,
    subcategoria_id = (
        SELECT id FROM subcategorias WHERE categoria_id = 2 AND nombre = 'Camperas' LIMIT 1
    )
WHERE id = 5;
