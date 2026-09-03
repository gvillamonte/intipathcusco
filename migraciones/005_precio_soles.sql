-- Migración 005: Agregar campo precio_soles a la tabla tours
-- Fecha: 2026-08-31
-- Descripción: Campo para precio en soles (PEN) editable por tour/caminata.
-- Se calcula automáticamente: precio_USD × tipo_cambio, pero es editable manualmente.

ALTER TABLE tours ADD COLUMN precio_soles DECIMAL(10,2) DEFAULT NULL AFTER precio;

-- Precalcular precio_soles para tours existentes que tengan precio y moneda USD
UPDATE tours 
SET precio_soles = ROUND(precio * 3.75, 2) 
WHERE precio > 0 AND (moneda = 'USD' OR moneda IS NULL) AND precio_soles IS NULL;

-- Tours ya en PEN: copiar el precio directo
UPDATE tours 
SET precio_soles = precio 
WHERE precio > 0 AND moneda = 'PEN' AND precio_soles IS NULL;
