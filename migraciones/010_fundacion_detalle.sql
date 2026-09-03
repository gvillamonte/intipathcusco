-- =============================================
-- Migración 010: Descripción corta para proyectos de fundación
-- =============================================

ALTER TABLE fundacion_proyectos
    ADD COLUMN descripcion_corta VARCHAR(1000) DEFAULT '' AFTER subtitulo_en,
    ADD COLUMN descripcion_corta_en VARCHAR(1000) DEFAULT '' AFTER descripcion_corta;
