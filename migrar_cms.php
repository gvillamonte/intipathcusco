<?php
/**
 * migrar_cms.php
 * Migración para el CMS: páginas libres + SEO por página + GA4.
 * Ejecutar UNA SOLA VEZ en el servidor (https://tudominio/migrar_cms.php)
 * y luego ELIMINAR este archivo.
 *
 * Es idempotente: si ya se aplicó, no repite nada. No borra datos.
 */
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
if (!$db) {
    die("ERROR: no se pudo conectar a la base de datos.");
}

$ok = 0; $skip = 0; $err = 0;
function columnaExiste($db, $tabla, $columna) {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$tabla, $columna]);
    return (int)$s->fetchColumn() > 0;
}
function tablaExiste($db, $tabla) {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $s->execute([$tabla]);
    return (int)$s->fetchColumn() > 0;
}
function paso($db, $nombre, $sql, $condition) {
    global $ok, $skip, $err;
    if ($condition) { echo "[SKIP] $nombre (ya existe)\n"; $skip++; return; }
    try {
        $db->exec($sql);
        echo "[OK]   $nombre\n"; $ok++;
    } catch (Exception $e) {
        echo "[ERROR] $nombre -> " . $e->getMessage() . "\n"; $err++;
    }
}

echo "<pre>\n=== Migración CMS / SEO / Cookies ===\n\n";

// 1. Tabla de páginas libres
paso($db, "tabla paginas", "CREATE TABLE IF NOT EXISTS paginas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    titulo_en VARCHAR(200) NOT NULL,
    contenido MEDIUMTEXT NULL,
    contenido_en MEDIUMTEXT NULL,
    meta_title VARCHAR(200) NULL,
    meta_description VARCHAR(300) NULL,
    og_imagen VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_paginas_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", tablaExiste($db, 'paginas'));

// 2. Tabla de metas SEO por página (claves: home, tours, blog, contacto, nosotros, etc.)
paso($db, "tabla metas_pagina", "CREATE TABLE IF NOT EXISTS metas_pagina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(60) NOT NULL,
    meta_title VARCHAR(200) NULL,
    meta_description VARCHAR(300) NULL,
    og_imagen VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_metas_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", tablaExiste($db, 'metas_pagina'));

// 3. Columna GA4 en configuracion
paso($db, "configuracion.ga4_id (columna)", "ALTER TABLE configuracion ADD COLUMN ga4_id VARCHAR(30) NULL AFTER tipo_cambio",
    columnaExiste($db, 'configuracion', 'ga4_id'));

// 4. Filas iniciales de metas (home + páginas principales) — no sobreescribe las existentes
$metas_count = (int)$db->query("SELECT COUNT(*) FROM metas_pagina")->fetchColumn();
if ($metas_count === 0) {
    $db->exec("INSERT INTO metas_pagina (clave, meta_title, meta_description) VALUES
        ('home', '', ''),
        ('tours', '', ''),
        ('blog', '', ''),
        ('contacto', '', ''),
        ('nosotros', '', ''),
        ('preguntas', '', ''),
        ('reservas_info', '', ''),
        ('garantia', '', ''),
        ('seguridad', '', ''),
        ('terminos', '', ''),
        ('privacidad', '', ''),
        ('paginas', '', '')");
    echo "[OK]   metas_pagina: filas iniciales (11)\n"; $ok++;
} else {
    echo "[SKIP] metas_pagina: filas iniciales (ya existen)\n"; $skip++;
}

echo "\nResumen: $ok aplicado(s), $skip ya existía(n), $err error(es).\n";
if ($err > 0) echo "Revisa los errores anteriores.\n";
echo "</pre>\n";