<?php
// d.php — Diagnóstico IntiPath Tours
// Sube este archivo a public_html/ (raiz) y abrelo:
//   https://www.intipathtours.com/d.php
// Muestra en pantalla TODOS los errores y el estado del sistema.
// Cuando termines, BORRALO del servidor.

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/d_error.log');

$erroresRecolectados = array();

function reporta($mensaje, $ok = true) {
    $icono = $ok ? "[OK]   " : "[FAIL] ";
    echo $icono . $mensaje . "<br>\n";
    return $ok;
}

set_error_handler(function ($no, $txt, $file, $line) use (&$erroresRecolectados) {
    $erroresRecolectados[] = "Error $no: $txt en $file linea $line";
    echo "<span style='color:#b00;'>[WARN] $txt (linea $line)</span><br>\n";
    return true;
});

echo "<pre style='font:13px monospace; background:#111; color:#eee; padding:15px;'>";
echo "=== DIAGNOSTICO INTIPATH ===\n\n";

// 1. PHP y entorno
echo "PHP version: " . PHP_VERSION . "\n";
echo "Directorio: " . __DIR__ . "\n";
echo "SAPI: " . PHP_SAPI . "\n\n";

// 2. Estado de sesion
if (session_status() === PHP_SESSION_NONE) session_start();
echo "Sesion iniciada: " . (session_status() === PHP_SESSION_ACTIVE ? 'SI' : 'NO') . "\n";
echo "Usuario en sesion (admin): " . (isset($_SESSION['admin_id']) ? 'ID=' . $_SESSION['admin_id'] : 'NO HAY sesion admin') . "\n";
if (isset($_SESSION['admin_nombre'])) echo "Nombre: " . $_SESSION['admin_nombre'] . "\n";
if (isset($_SESSION['admin_rol'])) echo "Rol: " . $_SESSION['admin_rol'] . "\n";
echo "\n";

// 3. Archivos clave que deben existir (dentro de __DIR__)
$archivosClave = array(
    'index.php', 'tours.php', 'detalle_tour.php', 'pagina.php', 'robots.php', 'sitemap.php',
    'includes/header.php', 'includes/footer.php', 'includes/sidebar.php',
    'includes/cookie_consent.php', 'includes/csrf_helper.php',
    'includes/auth_helper.php', 'includes/resenas_helper.php', 'includes/tour_scripts.php',
    'includes/pago_helper.php', 'includes/izipay_helper.php', 'includes/paypal_helper.php',
    'config/database.php', 'config/izipay.php', 'config/paypal.php',
    'admin/index.php', 'admin/admin_paginas.php', 'admin/admin_seo.php',
    'admin/configuracion.php'
);
echo "--- ARCHIVOS ---\n";
foreach ($archivosClave as $a) {
    $p = __DIR__ . '/' . $a;
    $existe = file_exists($p);
    $tam = $existe ? filesize($p) . ' bytes' : 'NO EXISTE';
    $color = $existe ? '#2a2' : '#f22';
    if (!$existe) $erroresRecolectados[] = "Falta el archivo: $a";
    echo "<span style='color:$color;'>$tam</span>  $a<br>\n";
}
echo "\n";

// 4. Conexion a BD
echo "--- BASE DE DATOS ---\n";
$db = null;
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    try {
        $db = (new Database())->getConnection();
        if ($db) {
            reporta("Conexion a BD: OK (" . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . ")");
        } else {
            reporta("Conexion a BD devolvio null", false);
        }
    } catch (Throwable $e) {
        reporta("Conexion a BD: " . $e->getMessage(), false);
    }
} else {
    reporta("config/database.php NO existe", false);
}
echo "\n";

// 5. Consultas que usa el panel de admin (cada una aislada)
if ($db) {
    echo "--- CONSULTAS DEL PANEL ADMIN ---\n";
    $consultas = array(
        'mensajes'           => "SELECT COUNT(*) FROM mensajes WHERE DATE(fecha_creacion) = CURDATE()",
        'tours'              => "SELECT COUNT(*) FROM tours WHERE estado = 'activo'",
        'usuarios'           => "SELECT COUNT(*) FROM usuarios WHERE estado = 1",
        'reservas'           => "SELECT COUNT(*) FROM reservas WHERE DATE(created_at) = CURDATE()",
        'reservas_pend'      => "SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'",
        'pagos'              => "SELECT COUNT(*) FROM pagos WHERE estado = 'pagado'",
        'configuracion'      => "SELECT tipo_cambio FROM configuracion WHERE id = 1",
        'configuracion_ga4'  => "SELECT ga4_id FROM configuracion WHERE id = 1",
        'paginas'            => "SELECT COUNT(*) FROM paginas",
        'metas_pagina'       => "SELECT COUNT(*) FROM metas_pagina",
        'mensajes_tabla'     => "SELECT * FROM mensajes ORDER BY id DESC LIMIT 3",
    );
    foreach ($consultas as $nombre => $sql) {
        try {
            $q = $db->query($sql);
            $v = $q->fetchColumn();
            reporta("$nombre: OK (" . var_export($v, true) . ")");
        } catch (Throwable $e) {
            reporta("$nombre: " . substr($e->getMessage(), 0, 200), false);
        }
    }
    echo "\n";

    // 6. Tablas y columnas de los metodos de pago
    echo "--- METODOS DE PAGO ---\n";
    foreach (array('config_pagos', 'izipay', 'paypal') as $tabla) {
        try {
            $db->query("SELECT 1 FROM $tabla LIMIT 1");
            reporta("Tabla $tabla: OK");
        } catch (Throwable $e) {
            reporta("Tabla $tabla: " . substr($e->getMessage(), 0, 160), false);
        }
    }
    echo "\n";
}

// 7. Funciones del auth
echo "--- AUTENTICACION ---\n";
if (file_exists(__DIR__ . '/includes/auth_helper.php')) {
    require_once __DIR__ . '/includes/auth_helper.php';
    reporta("iniciarSesionAdmin existe: " . (function_exists('iniciarSesionAdmin') ? 'SI' : 'NO'));
    reporta("requierePermiso existe: " . (function_exists('requierePermiso') ? 'SI' : 'NO'));
} else {
    reporta("includes/auth_helper.php NO existe", false);
}
echo "\n";

// 8. Errores recolectados durante este diagnostico
echo "--- ERRORES DETECTADOS ---\n";
if (count($erroresRecolectados) > 0) {
    foreach ($erroresRecolectados as $e) {
        echo "<span style='color:#f77;'>" . htmlspecialchars($e) . "</span><br>\n";
    }
} else {
    echo "Ningun error detectado en estas pruebas.\n";
}

// 9. PRUEBA DEFINITIVA: renderizar el panel real con sesion simulada
echo "\n--- RENDER REAL DEL PANEL ADMIN (sesion simulada) ---\n";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['admin_logeado'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_nombre'] = 'Administrador';
$_SESSION['admin_rol'] = 'admin';
$_SESSION['permisos'] = array('tours', 'blog', 'paginas', 'seo');
ob_start();
try {
    include __DIR__ . '/admin/index.php';
    $html = ob_get_clean();
    $tam = strlen($html);
    echo "Panel SI se ejecuto. HTML generado: <b>$tam bytes</b>\n";
    if ($tam > 0) {
        $soloTexto = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        echo "Texto visible: " . mb_substr($soloTexto, 0, 300) . "\n";
    } else {
        echo "<span style='color:#f55;'>ATENCION: el panel genero CERO bytes de HTML = pagina vacia</span>\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "<span style='color:#f55;'>FATAL en admin/index.php: " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
    echo "Archivo: " . htmlspecialchars($e->getFile()) . " linea " . $e->getLine() . "<br>\n";
    echo "Stack: " . nl2br(htmlspecialchars(substr($e->getTraceAsString(), 0, 800))) . "\n";
}

echo "\n=== FIN DEL DIAGNOSTICO ===\n";
echo "</pre>";