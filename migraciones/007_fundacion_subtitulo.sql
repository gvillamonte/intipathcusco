-- =============================================
-- Migración 007: Agregar subtitulo a fundacion_proyectos
-- =============================================

ALTER TABLE fundacion_proyectos
    ADD COLUMN subtitulo VARCHAR(500) DEFAULT '' AFTER titulo_en,
    ADD COLUMN subtitulo_en VARCHAR(500) DEFAULT '' AFTER subtitulo;
