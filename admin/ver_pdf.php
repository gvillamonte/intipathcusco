<?php
// Vista de impresión del PDF de reserva.
// Renderiza la plantilla EXACTA de la BD (plantilla_pdf id=1) en el navegador,
// reemplazando solo las variables con los datos reales de la reserva.
// Al imprimir (Ctrl+P / botón) se genera un PDF idéntico al preview del editor,
// porque usa el mismo motor del navegador (flex, SVG, Google Fonts, colores).

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/pdf_template_renderer.php';

requierePermiso('reservas');

$id_reserva = intval($_GET['id'] ?? 0);
if (!$id_reserva) {
    die("Error: ID de reserva no proporcionado.");
}

try {
    $db = (new Database())->getConnection();

    $query = "SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.imagen_principal 
              FROM reservas r 
              LEFT JOIN tours t ON r.id_tour = t.id 
              WHERE r.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        die("Error: Reserva no encontrada.");
    }

    $stmt_p = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
    $stmt_p->execute([$id_reserva]);
    $pasajeros = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    $stmt_conf = $db->query("SELECT * FROM configuracion WHERE id = 1");
    $config = $stmt_conf->fetch(PDO::FETCH_ASSOC);

    $logo_base64 = '';
    $rutas_logo = [
        __DIR__ . '/../assets/img/' . ($config['logo'] ?? ''),
        __DIR__ . '/../assets/img/' . ($config['logo_light'] ?? ''),
        __DIR__ . '/../assets/img/logo_footer_1777474226.png',
        __DIR__ . '/../assets/img/logo_intipath.png'
    ];
    foreach ($rutas_logo as $ruta) {
        if (!empty($ruta) && file_exists($ruta) && is_file($ruta)) {
            $logo_data = file_get_contents($ruta);
            $logo_base64 = 'data:image/' . pathinfo($ruta, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logo_data);
            break;
        }
    }

    $html = renderPdfTemplate($reserva, $pasajeros, $db, $config, $logo_base64);
} catch (\Exception $e) {
    die("Error al preparar el PDF: " . $e->getMessage());
}

$codigo = htmlspecialchars($reserva['codigo'] ?? '');

// --- Marca de agua condicional según estado de pago ---
// La marca de agua ya viene incluida en el HTML desde renderPdfTemplate()
// Solo agregamos la barra de impresión
$barra = '
<div class="pdf-bar">
    <span>PDF Reserva ' . $codigo . '</span>
    <button onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<style>
    .pdf-bar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
        background: #0f172a; color: #ffffff;
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 18px; box-shadow: 0 2px 10px rgba(0,0,0,0.35);
        font-family: Arial, sans-serif; font-size: 13px; font-weight: 700;
    }
    .pdf-bar button {
        background: #0C9A9E; color: #ffffff; border: none; border-radius: 6px;
        padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    }
    .pdf-bar button:hover { background: #0b8a8e; }
    @media print {
        .pdf-bar { display: none !important; }
        /* La plantilla desborda ~4% de la hoja A4: se ajusta la escala al imprimir
           para que todo salga en UNA sola hoja, sin cortes. Solo afecta impresión. */
        body { zoom: 95%; }
    }
</style>
';

if (stripos($html, '</body>') !== false) {
    $html = str_ireplace('</body>', $barra . '</body>', $html);
} else {
    echo $barra . $html;
    exit;
}

echo $html;