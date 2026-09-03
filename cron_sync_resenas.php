<?php
// cron_sync_resenas.php
//
// Sincroniza resenas desde el widget Trustindex.
// Ejecutable via cron de cPanel o linea de comandos:
//   php /home/usuario/carpeta/cron_sync_resenas.php
//
// Cron sugerido (cada 6 horas):
//   0 0,6,12,18 * * * php /home/usuario/carpeta/cron_sync_resenas.php

// Proteccion: solo CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Este script solo es ejecutable desde linea de comandos.\n";
    exit(1);
}

$dir = dirname(__FILE__);
require_once $dir . '/config/database.php';
require_once $dir . '/config/lang.php';
require_once $dir . '/includes/resenas_helper.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    echo "ERROR | No se pudo conectar a la base de datos.\n";
    exit(1);
}

asegurar_infraestructura_resenas($db);
$resultado = sincronizar_resenas_trustindex($db);

if ($resultado['ok']) {
    echo "OK | Importadas: {$resultado['importadas']} | Actualizadas: {$resultado['actualizadas']}\n";
    exit(0);
} else {
    echo "ERROR | {$resultado['error']}\n";
    exit(1);
}
