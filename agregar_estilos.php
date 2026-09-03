<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

$tablas = ['info_previa', 'niveles_dificultad', 'clima', 'equipaje', 'seguridad_viaje', 'como_reservar', 'alquiler', 'disponibilidad'];

echo "<h1>Agregando campos de estilo...</h1>";

foreach ($tablas as $tabla) {
    try {
        $db->exec("ALTER TABLE $tabla ADD COLUMN IF NOT EXISTS color_titulo VARCHAR(20) DEFAULT ''");
        $db->exec("ALTER TABLE $tabla ADD COLUMN IF NOT EXISTS tamano_titulo VARCHAR(20) DEFAULT ''");
        $db->exec("ALTER TABLE $tabla ADD COLUMN IF NOT EXISTS color_texto VARCHAR(20) DEFAULT ''");
        $db->exec("ALTER TABLE $tabla ADD COLUMN IF NOT EXISTS tamano_texto VARCHAR(20) DEFAULT ''");
        echo "<p style='color:green'>✓ $tabla actualizada</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>⚠ $tabla: " . $e->getMessage() . "</p>";
    }
}

echo "<h2 style='color:green'>¡Completado!</h2>";
?>