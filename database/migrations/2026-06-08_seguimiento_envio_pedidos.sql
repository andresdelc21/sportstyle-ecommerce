ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS empresa_envio VARCHAR(100) NULL AFTER zona_envio,
    ADD COLUMN IF NOT EXISTS numero_seguimiento VARCHAR(120) NULL AFTER empresa_envio,
    ADD COLUMN IF NOT EXISTS link_seguimiento VARCHAR(255) NULL AFTER numero_seguimiento;
