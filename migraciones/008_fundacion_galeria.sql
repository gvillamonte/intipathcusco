-- Migración 008: Tabla de galería de imágenes por proyecto de fundación
CREATE TABLE IF NOT EXISTS fundacion_proyecto_imagenes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  FOREIGN KEY (proyecto_id) REFERENCES fundacion_proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;