-- Migración 001: Recordatorios de pago + Mejoras PDF
-- Fecha: 2026-08-27
-- Descripción: Tabla recordatorios, columnas en reservas/configuracion, columnas para letterhead PDF

-- ============================================================
-- 1. TABLA RECORDATORIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `recordatorios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_reserva` INT NOT NULL,
    `tipo` ENUM('email','whatsapp') DEFAULT 'email',
    `asunto` VARCHAR(255) DEFAULT NULL,
    `mensaje` TEXT DEFAULT NULL,
    `programado_para` DATETIME DEFAULT NULL,
    `enviado_en` DATETIME DEFAULT NULL,
    `estado` ENUM('pendiente','enviado','fallido','cancelado') DEFAULT 'pendiente',
    `notas` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_reserva`) REFERENCES `reservas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. COLUMNAS EN RESERVAS PARA RECORDATORIOS
-- ============================================================
-- Verificar si las columnas existen antes de agregarlas
SET @dbname = DATABASE();

SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME = 'reservas'
  AND COLUMN_NAME = 'email_recordatorio_1_enviado';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `reservas`
        ADD COLUMN `email_recordatorio_1_enviado` TINYINT(1) DEFAULT 0,
        ADD COLUMN `email_recordatorio_2_enviado` TINYINT(1) DEFAULT 0,
        ADD COLUMN `fecha_recordatorio_1` DATETIME DEFAULT NULL,
        ADD COLUMN `fecha_recordatorio_2` DATETIME DEFAULT NULL',
    'SELECT "Columnas de recordatorios ya existen en reservas"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. COLUMNAS EN CONFIGURACION PARA RECORDATORIOS
-- ============================================================
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME = 'configuracion'
  AND COLUMN_NAME = 'recordatorios_activo';

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `configuracion`
        ADD COLUMN `recordatorios_activo` TINYINT(1) DEFAULT 1,
        ADD COLUMN `dias_recordatorio_1` INT DEFAULT 1,
        ADD COLUMN `dias_recordatorio_2` INT DEFAULT 3,
        ADD COLUMN `dias_cancelacion` INT DEFAULT 7',
    'SELECT "Columnas de recordatorios ya existen en configuracion"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. INSERTAR CONFIGURACION POR DEFECTO (si no existe)
-- ============================================================
UPDATE `configuracion`
SET `recordatorios_activo` = 1,
    `dias_recordatorio_1` = 1,
    `dias_recordatorio_2` = 3,
    `dias_cancelacion` = 7
WHERE `id` = 1;

-- ============================================================
-- 5. VERIFICACIÓN
-- ============================================================
SELECT 'Migración completada: recordatorios' AS resultado;
