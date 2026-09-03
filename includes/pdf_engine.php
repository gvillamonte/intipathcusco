<?php
// includes/pdf_engine.php
// Genera el PDF de reserva con el MISMO motor que el panel de administración
// (Chrome/Edge headless = navegador), para que el PDF adjunto al correo se vea
// EXACTAMENTE igual a la plantilla del editor (flexbox, Google Fonts, SVG, etc.).
// Si no hay navegador disponible (hosting sin Chrome), cae a dompdf como respaldo.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/pdf_template_renderer.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Busca un Chrome/Chromium/Edge instalado en el sistema.
 * Retorna la ruta al ejecutable o null.
 */
function buscarNavegadorPdf() {
    $env = getenv('CHROME_PATH') ?: getenv('PUPPETEER_EXECUTABLE_PATH');
    $candidatos = [
        $env,
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/brave-browser',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge'
    ];
    foreach ($candidatos as $c) {
        if ($c && is_file($c) && is_executable($c)) {
            return $c;
        }
    }
    return null;
}

/**
 * Genera un PDF a partir de HTML usando Chrome/Edge headless.
 * Devuelve true si el PDF se generó correctamente en $path_destino.
 */
function generarPdfConNavegador($html, $path_destino) {
    if (!function_exists('exec')) return false;

    $chrome = buscarNavegadorPdf();
    if (!$chrome) return false;

    $dir = dirname($path_destino);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    // Fix de paginación (NO modifica la plantilla del usuario):
    // la plantilla usa min-height:297mm + margen 5mm -> 302mm > A4 (287mm utiles),
    // lo que desborda a una 2ª hoja y corta el footer. Solo ajuste de impresión.
    $fix_print = '<style>@media print { @page { size: A4 portrait; margin: 12mm 15mm !important; } body { min-height: 287mm !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; } }</style>';
    if (strpos($html, '</head>') !== false) {
        $html = str_replace('</head>', $fix_print . '</head>', $html);
    } else {
        $html .= $fix_print;
    }

    $tmp_dir = sys_get_temp_dir() . '/intipath_pdf_' . bin2hex(random_bytes(4));
    @mkdir($tmp_dir, 0777, true);

    $html_file = $tmp_dir . '/reserva.html';
    file_put_contents($html_file, $html);

    if (file_exists($path_destino)) @unlink($path_destino);

    $url = 'file:///' . str_replace('\\', '/', $html_file);
    $user_data = $tmp_dir . '/perfil';

    $banderas = [
        '--headless=new',
        '--disable-gpu',
        '--no-sandbox',
        '--no-pdf-header-footer',
        '--disable-extensions',
        '--mute-audio',
        '--hide-scrollbars',
        '--user-data-dir="' . $user_data . '"',
        '--virtual-time-budget=10000',
        '--print-to-pdf="' . $path_destino . '"',
        '"' . $url . '"'
    ];
    $cmd = '"' . $chrome . '" ' . implode(' ', $banderas) . ' 2>&1';

    exec($cmd, $salida, $code);

    // Chrome puede tardar unos segundos: esperar hasta 20s
    for ($i = 0; $i < 40; $i++) {
        if (file_exists($path_destino) && filesize($path_destino) > 200) break;
        usleep(500000);
    }

    @unlink($html_file);
    @unlink($user_data);

    return file_exists($path_destino) && filesize($path_destino) > 200;
}

/**
 * Genera el PDF de una reserva con el motor del navegador (recomendado)
 * o con dompdf (respaldo). Devuelve el contenido binario del PDF.
 */
function generarPdfReservaBinario($reserva, $pasajeros, $db, $path_destino, $filename) {
    if (ob_get_length()) ob_end_clean();

    $stmt_conf = $db->query("SELECT * FROM configuracion WHERE id = 1");
    $config = $stmt_conf->fetch(PDO::FETCH_ASSOC);

    $logo_base64 = '';
    $rutas_logo = [
        __DIR__ . '/../assets/img/' . ($config['logo_light'] ?? ''),
        __DIR__ . '/../assets/img/' . ($config['logo'] ?? ''),
        __DIR__ . '/../assets/img/logo_footer_1777474226.png',
        __DIR__ . '/../assets/img/logo_intipath_azul (1).png',
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

    // 1) Motor navegador: resultado idéntico al panel
    if (generarPdfConNavegador($html, $path_destino)) {
        return file_get_contents($path_destino);
    }

    // 2) Respaldo: dompdf (CSS limitado, solo si no hay navegador)
    // Sanitizar CSS: convertir SOLO propiedades que dompdf no soporta
    // SIN tocar estructura de tablas (table/tr/td deben mantenerse intactos)
    $dompdf_fix = '<style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        * { box-shadow: none !important; text-shadow: none !important; transform: none !important; }
        body { font-family: "DejaVu Sans", Arial, sans-serif !important; width: auto !important; min-height: auto !important; position: static !important; margin: 0 !important; background-color: #ffffff !important; }
        
        .header-container { height: auto !important; min-height: 100px !important; border-radius: 0 0 8px 8px !important; position: static !important; }
        .header-bar { border-radius: 0 0 8px 8px !important; }
        .header-logo-box { border-radius: 4px !important; }
        .meta-item { display: inline !important; background-color: #0d828d; border: 1px solid #5ec3cb; border-radius: 3px !important; padding: 4px 12px; color: #ffffff; font-size: 10px; font-weight: 600; margin: 2px 4px; }
        
        .logo-badge { border-radius: 4px !important; display: inline-block !important; }
        .logo-container { display: block !important; text-align: right !important; margin-top: 5px !important; }
        
        .content { margin-top: 10px !important; position: static !important; z-index: auto !important; padding: 0 20px !important; }
        .info-card { box-shadow: none !important; border: 1px solid #d5e6e8 !important; border-radius: 6px !important; margin-bottom: 12px !important; background-color: #ffffff !important; }
        .card { border-radius: 6px !important; }
        .card-field { margin-bottom: 8px !important; font-size: 10px !important; }
        
        table { border-collapse: collapse !important; }
        .custom-table th::before { content: none !important; }
        
        .total-box { border-radius: 4px !important; font-size: 12px !important; padding: 8px 25px !important; }
        
        .footer { position: static !important; bottom: auto !important; left: auto !important; width: 100% !important; border-radius: 8px 8px 0 0 !important; margin-top: 20px !important; }
        
        svg { display: none !important; }
        
        div[style*="position:fixed"], div[style*="position: fixed"] { position: absolute !important; top: 35% !important; left: 10% !important; width: 80% !important; text-align: center !important; transform: none !important; }
        div[style*="position:absolute"], div[style*="position: absolute"] { position: relative !important; top: auto !important; left: auto !important; margin-left: 0 !important; width: 100% !important; text-align: center !important; opacity: 0.05 !important; }
        
        .meta-fields { display: block !important; }
        .top-bar { display: block !important; }
        .cards-row:not(table) { display: block !important; }
        .info-card { display: block !important; }
        .total-container { display: block !important; text-align: right !important; }
    </style>';
    $html = str_replace('</head>', $dompdf_fix . '</head>', $html);

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    if (!is_dir(dirname($path_destino))) @mkdir(dirname($path_destino), 0777, true);
    file_put_contents($path_destino, $dompdf->output());

    return $dompdf->output();
}