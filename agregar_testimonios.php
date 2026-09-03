<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

$tablas = ['info_previa', 'niveles_dificultad', 'clima', 'equipaje', 'seguridad_viaje', 'como_reservar', 'alquiler', 'disponibilidad'];

$sql = "ALTER TABLE %s ADD COLUMN IF NOT EXISTS aside_test_tit VARCHAR(255) DEFAULT '',
ADD COLUMN IF NOT EXISTS aside_test_tit_en VARCHAR(255) DEFAULT '',
ADD COLUMN IF NOT EXISTS aside_test_txt TEXT,
ADD COLUMN IF NOT EXISTS aside_test_txt_en TEXT,
ADD COLUMN IF NOT EXISTS aside_test_img VARCHAR(255) DEFAULT '',
ADD COLUMN IF NOT EXISTS aside_test_fecha VARCHAR(100) DEFAULT '',
ADD COLUMN IF NOT EXISTS aside_test_fecha_en VARCHAR(100) DEFAULT ''";

echo "<h1>Agregando campos de testimonio...</h1>";

foreach ($tablas as $tabla) {
    try {
        // MySQL no soporta ADD COLUMN IF NOT EXISTS, así que usamos TRY/CATCH
        $db->exec(sprintf($sql, $tabla));
        echo "<p style='color:green'>✓ $tabla actualizada</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠ $tabla: " . $e->getMessage() . "</p>";
    }
}

echo "<h2 style='color:green'>¡Completado!</h2>";
?>