-- Migración 003: Cancelación profesional y reembolsos
-- Fecha: 2026-08-27

-- Campos de cancelación en reservas
ALTER TABLE reservas ADD COLUMN motivo_cancelacion TEXT NULL AFTER updated_at;
ALTER TABLE reservas ADD COLUMN fecha_cancelacion DATETIME NULL AFTER motivo_cancelacion;
ALTER TABLE reservas ADD COLUMN cancelado_por VARCHAR(100) NULL AFTER fecha_cancelacion;

-- Campos de reembolso en pagos
ALTER TABLE pagos ADD COLUMN reembolsado TINYINT(1) DEFAULT 0 AFTER estado;
ALTER TABLE pagos ADD COLUMN monto_reembolsado DECIMAL(10,2) DEFAULT 0 AFTER reembolsado;
ALTER TABLE pagos ADD COLUMN fecha_reembolso DATETIME NULL AFTER monto_reembolsado;
ALTER TABLE pagos ADD COLUMN metodo_reembolso VARCHAR(50) NULL AFTER fecha_reembolso;
