ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 AFTER rating;

UPDATE productos
SET activo = 1
WHERE activo IS NULL;
