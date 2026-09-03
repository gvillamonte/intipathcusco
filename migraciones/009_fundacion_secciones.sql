-- Migración 009: Secciones dinámicas por proyecto de fundación
-- Cada sección = 1 imagen principal + título + N imágenes pequeñas

CREATE TABLE IF NOT EXISTS fundacion_secciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  titulo VARCHAR(255) DEFAULT '',
  imagen_principal VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (proyecto_id) REFERENCES fundacion_proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fundacion_seccion_imagenes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seccion_id INT NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  FOREIGN KEY (seccion_id) REFERENCES fundacion_secciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
