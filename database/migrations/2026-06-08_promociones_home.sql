CREATE TABLE IF NOT EXISTS promociones_home (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(255) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO promociones_home (texto, activo)
SELECT 'Banco Macro: 6 cuotas sin interés en productos seleccionados', 1
WHERE NOT EXISTS (SELECT 1 FROM promociones_home);
