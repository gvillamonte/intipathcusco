-- Migración 004: columna tipo_cambio_fecha para cache automático del tipo de cambio
-- Ejecutar en la BD intipath

ALTER TABLE configuracion
ADD COLUMN tipo_cambio_fecha DATETIME NULL DEFAULT NULL
AFTER tipo_cambio;
