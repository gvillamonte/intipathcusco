-- =============================================
-- Migración 006: Fundación INTI PATH TOURS
-- =============================================

-- Tabla de configuración general (1 sola fila)
CREATE TABLE IF NOT EXISTS fundacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hero_imagen VARCHAR(255) DEFAULT 'default-hero.webp',
    titulo VARCHAR(255) DEFAULT 'Fundación INTI PATH TOURS',
    titulo_en VARCHAR(255) DEFAULT 'INTI PATH TOURS Foundation',
    subtitulo VARCHAR(255) DEFAULT 'UNIDOS PARA COMUNIDADES Y MEDIO AMBIENTE',
    subtitulo_en VARCHAR(255) DEFAULT 'UNITED FOR COMMUNITIES AND ENVIRONMENT',
    descripcion LONGTEXT,
    descripcion_en LONGTEXT,
    logo VARCHAR(255) DEFAULT 'default-logo.webp',
    mision LONGTEXT,
    mision_en LONGTEXT,
    vision LONGTEXT,
    vision_en LONGTEXT,
    valores LONGTEXT,
    valores_en LONGTEXT,
    cita LONGTEXT,
    cita_en LONGTEXT,
    diferente LONGTEXT,
    diferente_en LONGTEXT,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fila inicial con valores por defecto
INSERT INTO fundacion (id, titulo, titulo_en, subtitulo, subtitulo_en, descripcion, descripcion_en, mision, mision_en, vision, vision_en, valores, valores_en, cita, cita_en, diferente, diferente_en)
VALUES (1,
    'Fundación INTI PATH TOURS',
    'INTI PATH TOURS Foundation',
    'UNIDOS PARA COMUNIDADES Y MEDIO AMBIENTE',
    'UNITED FOR COMMUNITIES AND ENVIRONMENT',
    'La Fundación INTI PATH TOURS es el área social de nuestra empresa de turismo. Inició su labor social desarrollando diversos proyectos al servicio de la población vulnerable de la región de Cusco.\n\nTiene como objetivo principal contribuir a la mejora económica, el cuidado del medio ambiente, la promoción de actividades culturales y turísticas, así como el fortalecimiento de las capacidades humanas de niñas, niños, adolescentes y mujeres en los sectores de intervención.',
    'The INTI PATH TOURS Foundation is the social arm of our tourism company. It began its social work developing various projects serving the vulnerable population of the Cusco region.\n\nIts main objective is to contribute to economic improvement, environmental care, the promotion of cultural and tourism activities, as well as strengthening the human capacities of girls, children, adolescents, and women in the intervention sectors.',
    'La Fundación INTI PATH TOURS se constituye en Cusco como una entidad sin fines de lucro que dirige, ejecuta y promueve proyectos sociales al servicio de la población vulnerable de la región, contribuyendo a la mejora de su calidad de vida.',
    'The INTI PATH TOURS Foundation is established in Cusco as a non-profit entity that directs, executes, and promotes social projects serving the vulnerable population of the region, contributing to the improvement of their quality of life.',
    'La Fundación INTI PATH TOURS es una entidad líder en el desarrollo de proyectos sociales en la región sur. Tiene como visión alcanzar el protagonismo activo de sus actores involucrados en los procesos de cambio y transformar vidas.',
    'The INTI PATH TOURS Foundation is a leading entity in the development of social projects in the southern region. Its vision is to achieve the active participation of its actors involved in processes of change and transforming lives.',
    'Durante el tiempo en INTI PATH TOURS, hemos seguido valores que definen y realzan nuestras características. Valores como la singularidad, solidaridad y la credibilidad, son algunos aspectos que nos diferencian frente a otras empresas.',
    'Throughout our time at INTI PATH TOURS, we have followed values that define and enhance our characteristics. Values such as uniqueness, solidarity, and credibility are some aspects that differentiate us from other companies.',
    'En la Fundación INTI PATH TOURS, nos esforzamos por crear un impacto positivo en todas las personas involucradas en nuestros viajes, no solo en los viajeros, sino también en nuestras comunidades locales y en nuestro equipo.',
    'At the INTI PATH TOURS Foundation, we strive to create a positive impact on all people involved in our trips, not only for travelers but also for our local communities and our team.',
    'Creemos que unas verdaderas vacaciones deben ser algo más que una habitación de hotel, un vuelo y un auto de alquiler. Deben ser más que la suma de sus partes. También creemos que un reto puede ayudarte a crecer y que un viaje puede remover el alma. Creamos viajes que merecen la pena, para el viajero, para el anfitrión y para las personas.',
    'We believe that true vacations should be more than a hotel room, a flight, and a rental car. They should be more than the sum of their parts. We also believe that a challenge can help you grow and that a trip can move the soul. We create trips worth taking, for the traveler, for the host, and for the people.'
);

-- Tabla de proyectos (grid de tarjetas)
CREATE TABLE IF NOT EXISTS fundacion_proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagen VARCHAR(255) DEFAULT 'default-proyecto.webp',
    titulo VARCHAR(255) NOT NULL,
    titulo_en VARCHAR(255) DEFAULT '',
    descripcion LONGTEXT,
    descripcion_en LONGTEXT,
    slug_pagina VARCHAR(255) DEFAULT '',
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    KEY idx_orden (orden),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Proyectos de ejemplo (el admin los edita después)
INSERT INTO fundacion_proyectos (titulo, titulo_en, slug_pagina, activo, orden) VALUES
('Campaña de Limpieza', 'Cleanliness Campaign', 'campana-limpieza', 1, 1),
('Responsabilidad Ambiental', 'Environmental Responsibility', 'responsabilidad-ambiental', 1, 2),
('Campamentos Ecológicos', 'Ecological Camps', 'campamentos-ecologicos', 1, 3),
('Proyecto de Reforestación', 'Reforestation Project', 'proyecto-reforestacion', 1, 4),
('Apoyo a la Comunidad', 'Community Support', 'apoyo-comunidad', 1, 5),
('Campaña de Navidad', 'Christmas Campaign', 'campana-navidad', 1, 6);
