<?php
/**
 * enviar_consulta.php
 * Maneja el envio de formulario: guarda consulta o reserva + pasajeros + PDF + email.
 * Seguridad: CSRF + honeypot + validación de inputs + anti-duplicados.
 * Un solo correo al crear la reserva; el correo de pago se envía SOLO al confirmar el pago.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcion_pdf_reserva.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../index.php");
    exit;
}

/**
 * Detecta si la petición espera JSON (fetch/AJAX) vs. un envío normal de formulario.
 * Acepta: campo oculto "ajax", header X-Requested-With, o Accept: application/json.
 */
function esPeticionAjax() {
    if (!empty($_POST['ajax'])) return true;
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return true;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false;
}

// --- PROTECCIÓN CSRF ---
if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    if (esPeticionAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['exito' => false, 'error' => 'Sesión de seguridad expirada. Recarga la página e intenta de nuevo.']);
        exit;
    }
    header("Location: ../index.php?res=csrferror");
    exit;
}

// --- HONEYPOT ANTI-BOTS ---
if (esHoneypotLlenado()) {
    header("Location: ../index.php");
    exit;
}

$id_tour         = (int)($_POST['id_tour'] ?? 0);
$tour_nombre     = trim($_POST['tour_interes'] ?? 'Consulta General');
$nombre_input    = trim(substr($_POST['nombre'] ?? '', 0, 100));
$apellido_input  = trim(substr($_POST['apellido'] ?? '', 0, 100));
$nombre_completo = $nombre_input . " " . $apellido_input;
$email_cliente   = trim($_POST['email'] ?? '');
$telefono        = trim(substr($_POST['telefono'] ?? '', 0, 50));
$whatsapp        = trim($_POST['whatsapp'] ?? '') ?: $telefono;
$pais            = trim(substr($_POST['pais'] ?? '', 0, 100));
$fecha_viaje     = $_POST['fecha_viaje'] ?? null;
$adultos         = max(0, (int)($_POST['adultos'] ?? 0));
$ninos           = max(0, (int)($_POST['ninos'] ?? 0));
$mensaje_usuario = trim(substr($_POST['mensaje'] ?? '', 0, 3000));
$pasajeros_arr   = $_POST['pasajeros'] ?? null;
$accion          = ($_POST['accion'] ?? 'consultar') === 'pagar' ? 'pagar' : 'consultar';

// --- VALIDACIÓN DE INPUTS ---
if ($adultos + $ninos <= 0) {
    $adultos = 1;
}
if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['exito' => false, 'error' => 'Correo electrónico inválido.']);
    exit;
}
if ($fecha_viaje && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_viaje)) {
    $fecha_viaje = null;
}

// Respuesta JSON o redirección según el origen de la petición
function responderConsulta($exito, $codigo, $token, $id_tour, $es_ajax, $accion, $id_reserva = 0, $error = '') {
    header('Content-Type: application/json');
    if ($es_ajax) {
        $resp = ['exito' => $exito];
        if ($exito) {
            $resp['codigo'] = $codigo;
            $resp['id_reserva'] = $id_reserva;
            if ($accion === 'pagar' && $token) {
                $resp['token'] = $token;
                $resp['redirect'] = '../seleccionar_pago.php?t=' . urlencode($token);
            }
        } else {
            $resp['error'] = $error;
        }
        echo json_encode($resp);
        exit;
    }

    if (!$exito) {
        header("Location: ../" . ($id_tour > 0 ? "detalle_tour.php?id=$id_tour" : "contacto.php") . "&res=error");
        exit;
    }

    if ($accion === 'pagar' && $token) {
        header("Location: ../seleccionar_pago.php?t=" . urlencode($token));
        exit;
    }

    if ($id_tour > 0) {
        header("Location: ../detalle_tour.php?id=$id_tour&res=success#consultar-det");
        exit;
    }
    header("Location: ../contacto.php?res=success");
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $es_ajax = esPeticionAjax();

    // Si la BD no está disponible, responder JSON claro (no fatal que rompe el JS)
    if (!$db) {
        responderConsulta(false, '', '', $id_tour, $es_ajax, $accion, 0, 'Error de conexión a la base de datos. Intenta en unos minutos.');
    }

    $tiene_pasajeros = is_array($pasajeros_arr) && count($pasajeros_arr) > 0;

    if ($tiene_pasajeros) {
        // ================================================================
        // MODO RESERVA: sp_crear_reserva() atomico + PDF + email
        // ================================================================

        // ANTI-DUPLICADOS: si ya existe la misma reserva (email + tour + fecha)
        // en los últimos 10 minutos, NO creamos otra ni reenviamos correo.
        $stmt_dup = $db->prepare("SELECT id, codigo, token FROM reservas
                                  WHERE email = ? AND id_tour = ? AND fecha_viaje = ?
                                    AND created_at >= (NOW() - INTERVAL 10 MINUTE)
                                  ORDER BY id DESC LIMIT 1");
        $stmt_dup->execute([$email_cliente, $id_tour, $fecha_viaje]);
        $dup = $stmt_dup->fetch(PDO::FETCH_ASSOC);

        if ($dup) {
            responderConsulta(true, $dup['codigo'], $dup['token'], $id_tour, $es_ajax, $accion, (int)$dup['id']);
        }

        $stmt_tour = $db->prepare("SELECT precio, precio_nino, porcentaje_adelanto, max_personas, titulo, titulo_en, duracion FROM tours WHERE id = ?");
        $stmt_tour->execute([$id_tour]);
        $tour_data = $stmt_tour->fetch(PDO::FETCH_ASSOC);

        if (!$tour_data) {
            throw new Exception("Tour no encontrado");
        }

        $precio_adulto = $tour_data['precio'];
        $precio_nino   = ($tour_data['precio_nino'] ?? 0) ?: $precio_adulto * 0.7;
        $porc_adelanto = (int)($tour_data['porcentaje_adelanto'] ?? 30);
        $max_personas  = (int)($tour_data['max_personas'] ?? 0);

        $total_personas = $adultos + $ninos;
        if ($max_personas > 0 && $total_personas > $max_personas) {
            throw new Exception("El numero maximo de personas por reserva es $max_personas");
        }

        $monto_total = ($adultos * $precio_adulto) + ($ninos * $precio_nino);

        $tipo_pago = $_POST['tipo_pago'] ?? 'adelanto';
        if ($tipo_pago === 'total') {
            $adelanto = $monto_total;
            $saldo    = 0;
        } else {
            $adelanto = $monto_total * ($porc_adelanto / 100);
            $saldo    = $monto_total - $adelanto;
        }

        // Convertir pasajeros a JSON para el SP (con saneamiento básico)
        $pasajeros_json = json_encode(array_map(function($p) {
            return [
                'tipo'      => ($p['tipo'] ?? 'adulto') === 'nino' ? 'nino' : 'adulto',
                'nombres'   => trim(substr($p['nombres'] ?? '', 0, 100)),
                'apellidos' => trim(substr($p['apellidos'] ?? '', 0, 100)),
                'documento' => trim(substr($p['documento'] ?? '', 0, 50)),
                'pais'      => trim(substr($p['pais'] ?? '', 0, 100))
            ];
        }, $pasajeros_arr));

        // Llamar SP transaccional
        $stmt_sp = $db->prepare("CALL sp_crear_reserva(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_sp->execute([
            $nombre_input, $apellido_input, $id_tour, $fecha_viaje,
            $email_cliente, $telefono, $whatsapp, $pais, $mensaje_usuario,
            $adultos, $ninos, $precio_adulto, $monto_total, $adelanto, $saldo,
            $pasajeros_json, 'reserva'
        ]);
        $result = $stmt_sp->fetch(PDO::FETCH_ASSOC);
        $id_reserva = $result['reserva_id'];
        $codigo = $result['codigo'];
        $stmt_sp->closeCursor();

        // Token de URL segura (no enumerable)
        $token_reserva = bin2hex(random_bytes(16));
        $stmt_tok = $db->prepare("UPDATE reservas SET token = ? WHERE id = ?");
        $stmt_tok->execute([$token_reserva, $id_reserva]);

        // Obtener pasajeros para el PDF
        $stmt_pdf = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
        $stmt_pdf->execute([$id_reserva]);
        $pasajeros_pdf = $stmt_pdf->fetchAll(PDO::FETCH_ASSOC);

        // Obtener datos de la reserva para el PDF
        $stmt_r_pdf = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
        $stmt_r_pdf->execute([$id_reserva]);
        $reserva_pdf = $stmt_r_pdf->fetch(PDO::FETCH_ASSOC);

        // Generar PDF
        $pdf_resultado = generarPdfReserva($reserva_pdf, $pasajeros_pdf, $db);
        $pdf_path = $pdf_resultado['path'];
        $pdf_filename = $pdf_resultado['filename'];

        // Enviar EMAIL con PDF adjunto (solo aquí: 1 correo por reserva creada)
        $email_porc = $porc_adelanto;
        $cuerpo = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin:0 auto; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                <div style='background: #15305D; padding: 30px; text-align: center;'>
                    <h1 style='color: #c6d544; margin:0; font-size:22px;'>IntiPath Tours</h1>
                    <p style='color: #fff; margin:5px 0 0; opacity:0.8;'>CONFIRMACION DE RESERVA</p>
                </div>
                <div style='background: #fff; padding: 30px;'>
                    <div style='text-align:center;margin-bottom:25px;'>
                        <div style='background: #f0f8e8; padding:15px; border-radius:10px; display:inline-block;'>
                            <p style='margin:0;font-size:12px;color:#888;'>CODIGO DE RESERVA</p>
                            <p style='margin:0;font-size:28px;font-weight:bold;color:#15305D;letter-spacing:3px;'>#" . $codigo . "</p>
                        </div>
                    </div>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;width:120px;'><strong>Tour:</strong></td><td style='padding:10px 0;border-bottom:1px solid #eee;'>" . htmlspecialchars($tour_nombre) . "</td></tr>
                        <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;'><strong>Fecha Viaje:</strong></td><td style='padding:10px 0;border-bottom:1px solid #eee;'>" . htmlspecialchars($fecha_viaje ?? '') . "</td></tr>
                        <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;'><strong>Pasajeros:</strong></td><td style='padding:10px 0;border-bottom:1px solid #eee;'>" . $adultos . " adulto(s), " . $ninos . " nino(s)</td></tr>
                        <tr><td style='padding:10px 0;border-bottom:1px solid #eee;color:#888;'><strong>Cliente:</strong></td><td style='padding:10px 0;border-bottom:1px solid #eee;'>" . htmlspecialchars($nombre_completo) . " - " . htmlspecialchars($email_cliente) . "</td></tr>
                    </table>
                    <div style='background:#f8fbff;border:1px solid #d1e3fa;border-radius:10px;padding:15px;margin:20px 0;'>
                        <h3 style='margin:0 0 10px;font-size:14px;color:#15305D;'>DETALLE DE PAGO</h3>
                        <table style='width:100%;border-collapse:collapse;font-size:13px;'>
                            <tr><td style='padding:6px 0;'>Precio adulto:</td><td style='padding:6px 0;text-align:right;'>$" . number_format($precio_adulto, 2) . "</td></tr>
                            <tr><td style='padding:6px 0;'>Precio nino:</td><td style='padding:6px 0;text-align:right;'>$" . number_format($precio_nino, 2) . "</td></tr>
                            <tr><td style='padding:6px 0;border-top:1px solid #eee;font-weight:bold;'>Total viaje:</td><td style='padding:6px 0;border-top:1px solid #eee;text-align:right;font-weight:bold;'>$" . number_format($monto_total, 2) . "</td></tr>
                            <tr><td style='padding:6px 0;color:#27ae60;font-weight:bold;'>Adelanto (" . $email_porc . "%):</td><td style='padding:6px 0;text-align:right;color:#27ae60;font-weight:bold;'>$" . number_format($adelanto, 2) . "</td></tr>
                            <tr><td style='padding:6px 0;color:#E8AC18;font-weight:bold;'>Saldo en Cusco:</td><td style='padding:6px 0;text-align:right;color:#E8AC18;font-weight:bold;'>$" . number_format($saldo, 2) . "</td></tr>
                        </table>
                    </div>
                    <div style='background:#f9f9f9;padding:15px;border-radius:8px;border-left:4px solid #c6d544;margin:15px 0;'>
                        <strong style='font-size:13px;'>Mensaje:</strong>
                        <p style='color:#555;font-size:13px;line-height:1.5;margin:5px 0 0;'>" . nl2br(htmlspecialchars($mensaje_usuario)) . "</p>
                    </div>
                    <p style='margin-top:20px;text-align:center;font-size:13px;color:#888;'>Se adjunta PDF con el detalle completo de la reserva.</p>
                </div>
                <div style='background:#f9f9f9;padding:15px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;'>
                    IntiPath Tours - Cusco, Peru<br>
                    Este correo es generado automaticamente.
                </div>
            </div>
        ";

        enviarCorreoIntipath(
            $email_cliente,
            $nombre_completo,
            "Reserva #" . $codigo . " - " . $tour_nombre . " | IntiPath Tours",
            $cuerpo,
            $pdf_path,
            $pdf_filename
        );

        // Limpiar PDF temporal
        if ($pdf_path && file_exists($pdf_path) && strpos($pdf_path, 'temp_pdfs') !== false) {
            @unlink($pdf_path);
        }

        responderConsulta(true, $codigo, $token_reserva, $id_tour, $es_ajax, $accion, (int)$id_reserva);

    } else {
        // ================================================================
        // MODO CONSULTA: solo lead (mensajes)
        // ================================================================

        // ANTI-DUPLICADOS para consultas
        $stmt_dup_m = $db->prepare("SELECT COUNT(*) FROM mensajes
                                    WHERE email = ? AND tour_interes = ?
                                      AND fecha_creacion >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt_dup_m->execute([$email_cliente, $tour_nombre]);
        $ya_existe = (int)$stmt_dup_m->fetchColumn() > 0;

        if (!$ya_existe && $db) {
            $query = "INSERT INTO mensajes (nombre, email, telefono, pais, adultos, ninos, fecha_viaje, tour_interes, mensaje, leido, fecha_creacion)
                      VALUES (:nom, :em, :tel, :pai, :ad, :ni, :fec, :tour, :msg, 0, NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':nom'  => $nombre_completo,
                ':em'   => $email_cliente,
                ':tel'  => $telefono,
                ':pai'  => $pais,
                ':ad'   => $adultos,
                ':ni'   => $ninos,
                ':fec'  => $fecha_viaje,
                ':tour' => $tour_nombre,
                ':msg'  => $mensaje_usuario
            ]);
        }

        // Aviso al admin (solo si es consulta nueva)
        if (!$ya_existe) {
            $cuerpo_consulta = "
                <div style='font-family: Arial, sans-serif; border: 2px solid #f39c12; padding: 25px; max-width: 600px; border-radius: 15px; background-color: #ffffff;'>
                    <h2 style='color: #f39c12; text-align: center; margin-top: 0;'>Nueva Consulta de Viaje</h2>
                    <div style='background: #fdf2e2; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                        <p style='margin: 0;'><strong>Tour de Interes:</strong> <span style='font-size: 1.1em; color: #333;'>" . htmlspecialchars($tour_nombre) . "</span></p>
                    </div>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Cliente:</strong></td><td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . htmlspecialchars($nombre_completo) . "</td></tr>
                        <tr><td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Email:</strong></td><td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . htmlspecialchars($email_cliente) . "</td></tr>
                        <tr><td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Telefono:</strong></td><td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . htmlspecialchars($telefono) . " (" . htmlspecialchars($pais) . ")</td></tr>
                        <tr><td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Fecha Tentativa:</strong></td><td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . htmlspecialchars($fecha_viaje ?? '') . "</td></tr>
                        <tr><td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Pasajeros:</strong></td><td style='padding: 8px 0; border-bottom: 1px solid #eee;'>Adultos: " . $adultos . " | Ninos: " . $ninos . "</td></tr>
                    </table>
                    <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #f39c12;'>
                        <strong>Mensaje del cliente:</strong><br>
                        <p style='color: #555; line-height: 1.5;'>" . nl2br(htmlspecialchars($mensaje_usuario)) . "</p>
                    </div>
                </div>
            ";

            enviarCorreoIntipath(
                IP_CORREO_FROM,
                'IntiPath Tours Web',
                "SOLICITUD DE TOUR: " . $tour_nombre,
                $cuerpo_consulta,
                null,
                null,
                $email_cliente,
                $nombre_completo
            );
        }

        responderConsulta(true, 'CONSULTA', '', $id_tour, $es_ajax, 'consultar', 0);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    if (esPeticionAjax()) {
        echo json_encode([
            'exito' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    header("Location: ../detalle_tour.php?id=$id_tour&res=error#consultar-det");
    exit;
}