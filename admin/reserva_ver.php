<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('reservas');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/recordatorio_helper.php';
$db = (new Database())->getConnection();

$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.precio, t.precio_nino, t.porcentaje_adelanto, t.imagen_principal FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
$stmt->execute([$id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) { 
    header("Location: reservas.php"); 
    exit; 
}

$stmt_p = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
$stmt_p->execute([$id]);
$pasajeros = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$stmt_pagos = $db->prepare("SELECT * FROM pagos WHERE id_reserva = ? ORDER BY id ASC");
$stmt_pagos->execute([$id]);
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

$total_pagado = 0;
foreach ($pagos as $p) {
    if ($p['estado'] === 'pagado') {
        $total_pagado += $p['monto'];
    }
}
$saldo_restante = $reserva['monto_total'] - $total_pagado;

// Procesar acciones de estado
if (isset($_GET['accion'])) {
    $nuevo_estado = $_GET['accion'];
    if (in_array($nuevo_estado, ['pendiente', 'parcial', 'pagado', 'cancelado'])) {
        if ($nuevo_estado === 'cancelado') {
            require_once __DIR__ . '/../includes/email_helper.php';
            $motivo = trim($_GET['motivo'] ?? '');
            $admin_nombre = $_SESSION['admin_nombre'] ?? ($_SESSION['usuario_nombre'] ?? 'Admin');

            $stmt_up = $db->prepare("UPDATE reservas SET estado = ?, motivo_cancelacion = ?, fecha_cancelacion = NOW(), cancelado_por = ?, updated_at = NOW() WHERE id = ?");
            $stmt_up->execute([$nuevo_estado, $motivo, $admin_nombre, $id]);

            enviarCorreoCancelacion($db, $id, $motivo);

            header("Location: reserva_ver.php?id=$id&res=cancelado");
            exit;
        } else {
            $stmt_up = $db->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
            $stmt_up->execute([$nuevo_estado, $id]);
            header("Location: reserva_ver.php?id=$id&res=actualizado");
            exit;
        }
    }
}

// Procesar Edición de Datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_edicion'])) {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $fecha_viaje = $_POST['fecha_viaje'] ?? '';
    $mensaje = $_POST['mensaje'] ?? '';

    $sql_edit = "UPDATE reservas SET nombre = ?, apellido = ?, email = ?, telefono = ?, whatsapp = ?, fecha_viaje = ?, mensaje = ?, updated_at = NOW() WHERE id = ?";
    $stmt_edit = $db->prepare($sql_edit);
    $stmt_edit->execute([$nombre, $apellido, $email, $telefono, $whatsapp, $fecha_viaje, $mensaje, $id]);
    header("Location: reserva_ver.php?id=$id&res=editado");
    exit;
}

// Procesar Edición de Pasajeros
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pasajeros'])) {
    // 1. Manejar Pasajeros Existentes
    if (isset($_POST['pax'])) {
        foreach ($_POST['pax'] as $pax_id => $data) {
            $stmt_up_p = $db->prepare("UPDATE pasajeros SET tipo = ?, nombres = ?, apellidos = ?, documento = ?, pais = ? WHERE id = ? AND id_reserva = ?");
            $stmt_up_p->execute([
                $data['tipo'],
                $data['nombres'],
                $data['apellidos'],
                $data['documento'],
                $data['pais'],
                $pax_id,
                $id
            ]);
        }
    }

    // 2. Manejar Nuevos Pasajeros
    if (isset($_POST['pax_nuevo'])) {
        foreach ($_POST['pax_nuevo'] as $data) {
            if (!empty($data['nombres'])) {
                $stmt_in_p = $db->prepare("INSERT INTO pasajeros (id_reserva, tipo, nombres, apellidos, documento, pais) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_in_p->execute([
                    $id,
                    $data['tipo'],
                    $data['nombres'],
                    $data['apellidos'],
                    $data['documento'],
                    $data['pais']
                ]);
            }
        }
    }
    
    // 3. RECALCULAR RESERVA
    $monto_total_anterior = (float)$reserva['monto_total'];

    // Obtener precios del tour
    $stmt_t_rec = $db->prepare("SELECT precio, precio_nino, porcentaje_adelanto FROM tours WHERE id = ?");
    $stmt_t_rec->execute([$reserva['id_tour']]);
    $tour_rec = $stmt_t_rec->fetch(PDO::FETCH_ASSOC);
    
    $p_adulto = (float)$tour_rec['precio'];
    $p_nino = (float)($tour_rec['precio_nino'] ?: $p_adulto * 0.7);

    // Contar pasajeros actuales
    $stmt_count = $db->prepare("SELECT 
        COUNT(CASE WHEN tipo = 'adulto' THEN 1 END) as total_a,
        COUNT(CASE WHEN tipo = 'nino' THEN 1 END) as total_n
        FROM pasajeros WHERE id_reserva = ?");
    $stmt_count->execute([$id]);
    $counts = $stmt_count->fetch(PDO::FETCH_ASSOC);
    
    $total_a = (int)$counts['total_a'];
    $total_n = (int)$counts['total_n'];
    
    $nuevo_monto_total = ($total_a * $p_adulto) + ($total_n * $p_nino);
    
    // Obtener lo que ya pagó el cliente (sumando todos los pagos exitosos)
    $stmt_p_sum = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
    $stmt_p_sum->execute([$id]);
    $ya_pagado = (float)$stmt_p_sum->fetchColumn();

    // El nuevo saldo es el nuevo total menos lo ya pagado
    $nuevo_saldo = $nuevo_monto_total - $ya_pagado;
    
    // Actualizar registro principal
    $stmt_up_res = $db->prepare("UPDATE reservas SET 
        total_adultos = ?, 
        total_ninos = ?, 
        monto_total = ?, 
        saldo = ?, 
        updated_at = NOW() 
        WHERE id = ?");
    $stmt_up_res->execute([$total_a, $total_n, $nuevo_monto_total, $nuevo_saldo, $id]);

    // Detectar si hubo aumento por persona extra
    $monto_extra = $nuevo_monto_total - $monto_total_anterior;
    if ($monto_extra > 0.01 && $nuevo_saldo > 0) {
        header("Location: reserva_ver.php?id=$id&res=editado_pax&extra=1&monto_extra=" . urlencode(number_format($monto_extra, 2, '.', '')));
        exit;
    }

    header("Location: reserva_ver.php?id=$id&res=editado_pax");
    exit;
}

// Procesar Registro de Pago Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago_manual'])) {
    $monto_pago = (float)($_POST['monto_pago'] ?? 0);
    $metodo_pago_man = $_POST['metodo_pago_manual'] ?? 'efectivo';
    
    if ($monto_pago > 0) {
        $stmt_ins_p = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, estado, fecha_pago) VALUES (?, ?, 'USD', ?, 'pagado', NOW())");
        $stmt_ins_p->execute([$id, $monto_pago, $metodo_pago_man]);
        
        // Recalcular saldo en la reserva principal
        $stmt_sum = $db->prepare("SELECT SUM(monto) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
        $stmt_sum->execute([$id]);
        $pagado_total = (float)$stmt_sum->fetchColumn();
        
        $nuevo_saldo_p = $reserva['monto_total'] - $pagado_total;
        $nuevo_estado_p = ($nuevo_saldo_p <= 0) ? 'pagado' : 'parcial';
        
        $stmt_up_res_p = $db->prepare("UPDATE reservas SET saldo = ?, estado = ?, updated_at = NOW() WHERE id = ?");
        $stmt_up_res_p->execute([max(0, $nuevo_saldo_p), $nuevo_estado_p, $id]);
        
        header("Location: reserva_ver.php?id=$id&res=pago_registrado");
        exit;
    }
}

// Procesar Registro de Reembolso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_reembolso'])) {
    $monto_reembolso = (float)($_POST['monto_reembolso'] ?? 0);
    $metodo_reembolso = $_POST['metodo_reembolso'] ?? '';
    $notas_reembolso = trim($_POST['notas_reembolso'] ?? '');

    if ($monto_reembolso > 0 && !empty($metodo_reembolso)) {
        // Marcar el primer pago no reembolsado como reembolsado
        $stmt_pago = $db->prepare("SELECT id, monto FROM pagos WHERE id_reserva = ? AND estado = 'pagado' AND reembolsado = 0 ORDER BY id ASC LIMIT 1");
        $stmt_pago->execute([$id]);
        $pago_a_reembolsar = $stmt_pago->fetch(PDO::FETCH_ASSOC);

        if ($pago_a_reembolsar) {
            $stmt_up_pago = $db->prepare("UPDATE pagos SET reembolsado = 1, monto_reembolsado = ?, fecha_reembolso = NOW(), metodo_reembolso = ? WHERE id = ?");
            $stmt_up_pago->execute([$monto_reembolso, $metodo_reembolso, $pago_a_reembolsar['id']]);
        }

        header("Location: reserva_ver.php?id=$id&res=reembolso_registrado");
        exit;
    }
}

// Procesar Reenvío de Confirmación
if (isset($_GET['reenviar'])) {
    require_once __DIR__ . '/../includes/pdf_helper.php';
    require_once __DIR__ . '/../includes/email_helper.php';
    
    // Obtener datos frescos
    $stmt_r_e = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion, t.precio FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt_r_e->execute([$id]);
    $reserva_e = $stmt_r_e->fetch(PDO::FETCH_ASSOC);
    
    $stmt_p_e = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
    $stmt_p_e->execute([$id]);
    $pasajeros_e = $stmt_p_e->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar nuevo PDF
    $pdf_res = generarPdfReservaParaEmail($reserva_e, $pasajeros_e, $db);
    
    $cuerpo_e = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin:0 auto; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
            <div style='background: #15305D; padding: 30px; text-align: center;'>
                <h1 style='color: #c6d544; margin:0; font-size:22px;'>IntiPath Tours</h1>
                <p style='color: #fff; margin:5px 0 0; opacity:0.8;'>INFORMACION ACTUALIZADA DE RESERVA</p>
            </div>
            <div style='background: #fff; padding: 30px;'>
                <p style='font-size:16px; color:#333;'>Hola <strong>" . $reserva_e['nombre'] . "</strong>,</p>
                <p style='color:#666; line-height:1.6;'>Te enviamos los detalles actualizados de tu reserva <strong>#" . $reserva_e['codigo'] . "</strong>. Hemos procesado los cambios en el listado de pasajeros o la información del tour.</p>
                
                <div style='background: #f8fbff; border: 1px solid #d1e3fa; border-radius: 12px; padding: 20px; margin: 25px 0;'>
                    <h3 style='margin:0 0 15px; font-size:14px; color:#15305D; text-transform:uppercase;'>Resumen del Servicio</h3>
                    <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                        <tr><td style='padding:8px 0; color:#888;'><strong>Tour:</strong></td><td style='padding:8px 0; text-align:right;'>" . $reserva_e['titulo'] . "</td></tr>
                        <tr><td style='padding:8px 0; color:#888;'><strong>Fecha:</strong></td><td style='padding:8px 0; text-align:right;'>" . date('d/m/Y', strtotime($reserva_e['fecha_viaje'])) . "</td></tr>
                        <tr><td style='padding:8px 0; color:#888;'><strong>Pasajeros:</strong></td><td style='padding:8px 0; text-align:right;'>" . ($reserva_e['total_adultos'] + $reserva_e['total_ninos']) . " Persona(s)</td></tr>
                        <tr><td style='padding:15px 0 0; border-top:1px solid #eee; font-weight:bold; color:#E8AC18;'>SALDO PENDIENTE:</td><td style='padding:15px 0 0; border-top:1px solid #eee; text-align:right; font-weight:bold; color:#E8AC18; font-size:18px;'>$" . number_format($reserva_e['saldo'], 2) . "</td></tr>
                    </table>
                </div>

                <p style='color:#666; font-size:14px;'>Adjunto a este correo encontrarás tu <strong>Voucher de Confirmación actualizado</strong> en formato PDF con la hoja membretada oficial.</p>
                
                <div style='background:#fff9e6; border-radius:8px; padding:15px; margin:20px 0; font-size:13px; color:#856404;'>
                    <i class='fas fa-info-circle'></i> <strong>Nota:</strong> Recuerda presentar este documento (digital o impreso) al inicio de tu servicio en Cusco.
                </div>

                <p style='text-align:center; margin-top:30px;'>
                    <a href='https://wa.me/51920307331' style='background:#25d366; color:#fff; padding:12px 25px; border-radius:10px; text-decoration:none; font-weight:bold; font-size:14px;'>Contactar por WhatsApp</a>
                </p>
            </div>
            <div style='background:#f9f9f9; padding:20px; text-align:center; font-size:11px; color:#999; border-top:1px solid #eee;'>
                <strong>IntiPath Tours Peru S.A.C.</strong><br>
                Agencia de Viajes y Operador de Turismo<br>
                Cusco, Perú
            </div>
        </div>";

    try {
        enviarCorreoIntipath(
            $reserva_e['email'],
            $reserva_e['nombre'],
            "ACTUALIZACION: Reserva #" . $reserva_e['codigo'] . " - " . $reserva_e['titulo'],
            $cuerpo_e,
            $pdf_res['path'],
            $pdf_res['filename']
        );
        header("Location: reserva_ver.php?id=$id&res=enviado");
        exit;
    } catch (\Throwable $e) {
        header("Location: reserva_ver.php?id=$id&res=error_envio&msg=" . urlencode($e->getMessage()));
        exit;
    }
}

// Enviar correo de notificación de persona extra
if (isset($_GET['reenviar_extra']) && isset($_GET['monto_extra'])) {
    require_once __DIR__ . '/../includes/pdf_helper.php';
    require_once __DIR__ . '/../includes/email_helper.php';

    $monto_extra_email = floatval($_GET['monto_extra']);
    $link_pago_email = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/intipathcusco/seleccionar_pago.php?t=" . urlencode($reserva['token'] ?? '');

    $stmt_r_ex = $db->prepare("SELECT r.*, t.titulo, t.titulo_en, t.duracion FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
    $stmt_r_ex->execute([$id]);
    $reserva_ex = $stmt_r_ex->fetch(PDO::FETCH_ASSOC);

    $stmt_p_ex = $db->prepare("SELECT * FROM pasajeros WHERE id_reserva = ?");
    $stmt_p_ex->execute([$id]);
    $pasajeros_ex = $stmt_p_ex->fetchAll(PDO::FETCH_ASSOC);

    $pdf_res_ex = generarPdfReservaParaEmail($reserva_ex, $pasajeros_ex, $db);

    $cuerpo_ex = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
            <div style='background:#e67e22;padding:30px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:22px;'>IntiPath Tours</h1>
                <p style='color:#fff;margin:5px 0 0;opacity:0.9;'>AJUSTE DE RESERVA - PERSONA ADICIONAL</p>
            </div>
            <div style='background:#fff;padding:30px;'>
                <p style='font-size:16px;color:#333;'>Hola <strong>" . $reserva_ex['nombre'] . "</strong>,</p>
                <p style='color:#666;line-height:1.6;'>Se ha agregado una persona adicional a tu reserva <strong>#" . $reserva_ex['codigo'] . "</strong>. A continuación el detalle del ajuste:</p>

                <div style='background:#fff8e1;border:1px solid #f0ad4e;border-radius:12px;padding:20px;margin:25px 0;'>
                    <h3 style='margin:0 0 15px;font-size:14px;color:#e67e22;text-transform:uppercase;'>Detalle del Ajuste</h3>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Monto adicional:</strong></td><td style='padding:8px 0;text-align:right;font-weight:bold;color:#e67e22;font-size:18px;'>\$" . number_format($monto_extra_email, 2) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Nuevo total del tour:</strong></td><td style='padding:8px 0;text-align:right;font-weight:bold;color:#15305D;'>\$" . number_format($reserva_ex['monto_total'], 2) . "</td></tr>
                        <tr><td style='padding:8px 0;color:#888;'><strong>Total pagado:</strong></td><td style='padding:8px 0;text-align:right;color:#2d8a56;'>\$" . number_format($reserva_ex['monto_total'] - $reserva_ex['saldo'], 2) . "</td></tr>
                        <tr><td style='padding:12px 0 0;border-top:1px solid #eee;color:#c0392b;'><strong>Saldo pendiente:</strong></td><td style='padding:12px 0 0;border-top:1px solid #eee;text-align:right;font-weight:bold;color:#c0392b;font-size:18px;'>\$" . number_format($reserva_ex['saldo'], 2) . "</td></tr>
                    </table>
                </div>

                <p style='color:#666;font-size:14px;'>Adjunto encontrarás el <strong>Voucher actualizado</strong> con el listado completo de pasajeros.</p>

                <div style='text-align:center;margin:30px 0;'>
                    <a href='" . htmlspecialchars($link_pago_email) . "' style='background:#e67e22;color:#fff;padding:14px 30px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;'>Pagar Monto Adicional</a>
                </div>

                <div style='background:#fff9e6;border-radius:8px;padding:15px;margin:20px 0;font-size:13px;color:#856404;'>
                    <i class='fas fa-info-circle'></i> <strong>Nota:</strong> Este monto corresponde al cargo por la persona adicional agregada a tu reserva.
                </div>
            </div>
            <div style='background:#f9f9f9;padding:20px;text-align:center;font-size:11px;color:#999;border-top:1px solid #eee;'>
                <strong>IntiPath Tours Peru S.A.C.</strong><br>
                Agencia de Viajes y Operador de Turismo<br>
                Cusco, Perú
            </div>
        </div>";

    try {
        enviarCorreoIntipath(
            $reserva_ex['email'],
            $reserva_ex['nombre'],
            "Ajuste Reserva #" . $reserva_ex['codigo'] . " - Persona Adicional",
            $cuerpo_ex,
            $pdf_res_ex['path'],
            $pdf_res_ex['filename']
        );
        header("Location: reserva_ver.php?id=$id&res=enviado");
        exit;
    } catch (\Throwable $e) {
        header("Location: reserva_ver.php?id=$id&res=error_envio&msg=" . urlencode($e->getMessage()));
        exit;
    }
}

// Asegurar que existan las llaves para evitar warnings en registros antiguos
$reserva['nombre'] = $reserva['nombre'] ?? 'S/N';
$reserva['apellido'] = $reserva['apellido'] ?? '';

$badge_estado = [
    'pendiente' => '<span class="badge-status status-pendiente">Pendiente</span>',
    'parcial' => '<span class="badge-status status-parcial">Parcial</span>',
    'pagado' => '<span class="badge-status status-pagado">Pagado</span>',
    'cancelado' => '<span class="badge-status status-cancelado">Cancelado</span>',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva #<?= $reserva['codigo'] ?> | Panel Administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { 
            --ip-primary: #15305D; 
            --ip-accent: #c6d544; 
            --ip-text: #2c3e50;
            --ip-bg: #f8fafb;
        }
        body { background: var(--ip-bg); color: var(--ip-text); font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        .admin-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { margin: 0; font-weight: 800; color: var(--ip-primary); font-size: 1.5rem; text-transform: uppercase; }
        
        .card-custom { 
            background: #fff; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
            border: none; 
            margin-bottom: 25px;
            overflow: hidden;
        }
        .card-header-custom { 
            background: #fff; 
            padding: 20px 25px; 
            border-bottom: 1px solid #f0f0f0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .card-header-custom h5 { margin: 0; font-weight: 700; color: var(--ip-primary); font-size: 1rem; }
        .card-body-custom { padding: 25px; }

        .badge-status { 
            padding: 6px 16px; 
            border-radius: 50px; 
            font-size: 0.8rem; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        .status-pendiente { background: #fff8e1; color: #f57c00; }
        .status-parcial { background: #e3f2fd; color: #1976d2; }
        .status-pagado { background: #e8f5e9; color: #2e7d32; }
        .status-cancelado { background: #ffebee; color: #c62828; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 0.75rem; color: #888; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { font-size: 0.95rem; font-weight: 600; color: var(--ip-primary); }

        .btn-action { 
            padding: 10px 18px; 
            border-radius: 10px; 
            font-weight: 700; 
            font-size: 0.85rem; 
            transition: all 0.3s; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            border: none;
        }
        .btn-pdf { background: #fdf0f0; color: #e74c3c; }
        .btn-pdf:hover { background: #e74c3c; color: #fff; }
        .btn-email { background: #eef2f7; color: var(--ip-primary); }
        .btn-email:hover { background: var(--ip-primary); color: #fff; }
        .btn-confirm { background: #e8f5e9; color: #2e7d32; }
        .btn-confirm:hover { background: #2e7d32; color: #fff; }
        
        .table-custom { margin: 0; }
        .table-custom th { background: #f8fafb; border: none; font-size: 0.75rem; text-transform: uppercase; color: #888; padding: 12px 15px; }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }

        .monto-highlight { font-size: 1.2rem; font-weight: 800; color: var(--ip-primary); }
        .monto-pagado-text { color: #2e7d32; }
        .monto-pendiente-text { color: #e74c3c; }

        .tour-img-mini { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; }
    </style>
</head>
<body>
<div class="admin-container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="reservas.php" class="text-decoration-none">Reservas</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detalle #<?= $reserva['codigo'] ?></li>
                    </ol>
                </nav>
                <h1 class="page-title">Reserva #<?= $reserva['codigo'] ?></h1>
            </div>
            <div class="d-flex gap-2">
                <button onclick="confirmarReenvio()" class="btn-action btn-email" style="background: #e3f2fd; color: #0d47a1;">
                    <i class="fas fa-paper-plane"></i> Reenviar Correo
                </button>
                <a href="ver_pdf.php?id=<?= $reserva['id'] ?>" target="_blank" class="btn-action btn-pdf">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <div class="dropdown">
                    <button class="btn-action btn-email dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cog"></i> Acciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px;">
                        <?php if ($saldo_restante <= 0 && $reserva['estado'] !== 'pagado'): ?>
                            <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="cambiarEstado('pagado')"><i class="fas fa-check-circle text-success me-2"></i> Marcar como Pagado</a></li>
                        <?php elseif ($saldo_restante > 0): ?>
                            <li><a class="dropdown-item py-2 disabled text-muted" href="javascript:void(0)" title="Debe registrar pagos por el total para marcar como pagado"><i class="fas fa-lock me-2"></i> Marcar como Pagado (Saldar primero)</a></li>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="cambiarEstado('pendiente')"><i class="fas fa-clock text-warning me-2"></i> Marcar como Pendiente</a></li>
                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="cambiarEstado('cancelado')"><i class="fas fa-times-circle text-danger me-2"></i> Cancelar Reserva</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['res'])): ?>
            <script>
                // Limpiar URL después de cargar para evitar repetir alertas si se refresca
                <?php if (isset($_GET['res'])): ?>
                    let msg = 'Reserva actualizada correctamente.';
                    if ('<?= $_GET['res'] ?>' === 'editado') msg = 'Datos del cliente actualizados.';
                    if ('<?= $_GET['res'] ?>' === 'editado_pax') msg = 'Listado de pasajeros actualizado.';
                    if ('<?= $_GET['res'] ?>' === 'enviado') msg = 'Correo de confirmación enviado al cliente.';
                    if ('<?= $_GET['res'] ?>' === 'pago_registrado') msg = 'Pago manual registrado con éxito.';
                    if ('<?= $_GET['res'] ?>' === 'cancelado') { msg = 'Reserva cancelada. Se envió correo de notificación al cliente.'; icon = 'info'; }
                    if ('<?= $_GET['res'] ?>' === 'reembolso_registrado') { msg = 'Reembolso registrado exitosamente.'; icon = 'success'; }
                    
                    Swal.fire({
                        icon: icon || 'success',
                        title: '¡Hecho!',
                        text: msg,
                        timer: ('<?= $_GET['res'] ?>' === 'cancelado') ? 4000 : 2000,
                        showConfirmButton: false
                    });
                <?php endif; ?>
                window.history.replaceState({}, document.title, window.location.pathname + "?id=<?= $id ?>");
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['extra']) && isset($_GET['monto_extra'])): ?>
            <?php
                $monto_extra_val = floatval($_GET['monto_extra']);
                $link_pago_extra = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/intipathcusco/seleccionar_pago.php?t=" . urlencode($reserva['token'] ?? '');
                $whatsapp_num = preg_replace('/[^0-9]/', '', $reserva['whatsapp'] ?? '');
                $nombre_cliente = $reserva['nombre'] ?? '';
                $total_actual = number_format($reserva['monto_total'], 2);
                $saldo_actual = number_format($reserva['saldo'], 2);
            ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #fff8e1, #fff3cd); border-left: 5px solid #f0ad4e !important;">
                <div class="d-flex align-items-start">
                    <div class="me-3 mt-1">
                        <i class="fas fa-user-plus fa-2x" style="color: #e67e22;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading fw-bold mb-2" style="color: #856404;">
                            <i class="fas fa-exclamation-triangle me-1"></i> Persona(s) Extra Detectada(s)
                        </h5>
                        <p class="mb-3" style="color: #856404; font-size: 14px;">
                            Se agregó(n) persona(s) adicional(es) a la reserva, generando un monto pendiente de cobro.
                        </p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="bg-white rounded-3 p-3 text-center border">
                                    <div class="text-muted small mb-1">Monto Adicional</div>
                                    <div class="fw-bold" style="color: #e67e22; font-size: 20px;">$<?= number_format($monto_extra_val, 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-white rounded-3 p-3 text-center border">
                                    <div class="text-muted small mb-1">Nuevo Total</div>
                                    <div class="fw-bold" style="color: #15305D; font-size: 20px;">$<?= $total_actual ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-white rounded-3 p-3 text-center border">
                                    <div class="text-muted small mb-1">Saldo Pendiente</div>
                                    <div class="fw-bold" style="color: #c0392b; font-size: 20px;">$<?= $saldo_actual ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" class="form-control form-control-sm border-0 bg-white" id="pagoLinkExtra" value="<?= htmlspecialchars($link_pago_extra) ?>" readonly>
                                <button class="btn btn-primary btn-sm px-3" onclick="copiarLinkExtra()" title="Copiar link"><i class="fas fa-copy"></i></button>
                            </div>
                            <a href="https://wa.me/<?= $whatsapp_num ?>?text=Hola%20<?= urlencode($nombre_cliente) ?>,%20tu%20reserva%20ha%20tenido%20un%20ajuste%20con%20un%20monto%20adicional%20de%20$<?= urlencode(number_format($monto_extra_val, 2)) ?>.%20Puedes%20realizar%20el%20pago%20en%20este%20link:%20<?= urlencode($link_pago_extra) ?>" target="_blank" class="btn btn-success btn-sm rounded-3 px-3 shadow-sm">
                                <i class="fab fa-whatsapp me-1"></i> Enviar Link por WhatsApp
                            </a>
                            <a href="reserva_ver.php?id=<?= $id ?>&reenviar_extra=1&monto_extra=<?= urlencode(number_format($monto_extra_val, 2, '.', '')) ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3" onclick="return confirm('Enviar correo de notificación de cargo adicional al cliente?')">
                                <i class="fas fa-envelope me-1"></i> Enviar por Correo
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" onclick="this.closest('.alert').remove()" aria-label="Close"></button>
                </div>
            </div>
            <script>
                function copiarLinkExtra() {
                    var copyText = document.getElementById("pagoLinkExtra");
                    copyText.select();
                    document.execCommand("copy");
                    Swal.fire({ icon: 'success', title: 'Link copiado', text: 'El link de pago se copió al portapapeles.', timer: 1500, showConfirmButton: false });
                }
            </script>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Información del Tour -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5>Detalles del Tour</h5>
                        <?= $badge_estado[$reserva['estado']] ?>
                    </div>
                    <div class="card-body-custom">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../assets/img/tours/<?= $reserva['imagen_principal'] ?>" class="tour-img-mini me-3">
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($reserva['titulo']) ?></h6>
                                <small class="text-muted"><i class="far fa-clock me-1"></i> <?= $reserva['duracion'] ?></small>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Fecha de Viaje</span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($reserva['fecha_viaje'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Pasajeros</span>
                                <span class="info-value">
                                    <?php 
                                    $c_adultos = 0; $c_ninos = 0;
                                    foreach($pasajeros as $p) { if($p['tipo'] == 'adulto') $c_adultos++; else $c_ninos++; }
                                    echo "$c_adultos Adultos, $c_ninos Niños";
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha Reserva</span>
                                <span class="info-value"><?= date('d/m/Y H:i', strtotime($reserva['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pasajeros -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5>Listado de Pasajeros</h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarPasajeros">
                            <i class="fas fa-users-cog"></i> Editar Pasajeros
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tipo</th>
                                    <th>Nombres Completos</th>
                                    <th>Documento</th>
                                    <th>Nacionalidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $it = 1; foreach ($pasajeros as $pax): ?>
                                <tr>
                                    <td><?= $it++ ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $pax['tipo'] == 'adulto' ? 'bg-primary' : 'bg-success' ?> bg-opacity-10 text-<?= $pax['tipo'] == 'adulto' ? 'primary' : 'success' ?>" style="font-size: 0.7rem;">
                                            <?= strtoupper($pax['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($pax['nombres'] . ' ' . $pax['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($pax['documento']) ?></td>
                                    <td><?= htmlspecialchars($pax['pais']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historial de Pagos -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5>Historial de Pagos</h5>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalPagoManual">
                            <i class="fas fa-plus"></i> Registrar Pago Manual
                        </button>
                    </div>
                    <?php if (empty($pagos)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-receipt fa-2x mb-3 opacity-25"></i>
                            <p>No se han registrado pagos para esta reserva.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Método</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos as $p): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_pago'] ?? $p['created_at'])) ?></td>
                                        <td class="text-uppercase fw-bold small"><?= $p['metodo'] ?></td>
                                        <td class="fw-bold">$<?= number_format($p['monto'], 2) ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success" style="font-size: 0.7rem;">PAGADO</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($reserva['estado'] === 'cancelado' && $total_pagado > 0): ?>
                <div class="card-custom mt-3" style="border-left: 4px solid #c62828;">
                    <div class="card-header-custom" style="background: #ffebee;">
                        <h5 style="color: #c62828;"><i class="fas fa-undo me-1"></i> Reembolso</h5>
                    </div>
                    <div class="card-body-custom">
                        <p style="font-size: 13px; color: #666; margin-bottom: 12px;">Esta reserva fue cancelada y tiene pagos registrados que deben ser devueltos.</p>
                        <div class="d-flex justify-content-between mb-2 p-2 bg-light rounded">
                            <span class="small text-muted">Monto pagado:</span>
                            <span class="fw-bold" style="color: #2d8a56;">$<?= number_format($total_pagado, 2) ?></span>
                        </div>
                        <?php
                        $total_reembolsado = 0;
                        foreach ($pagos as $p) {
                            if ($p['reembolsado']) $total_reembolsado += $p['monto_reembolsado'];
                        }
                        ?>
                        <?php if ($total_reembolsado > 0): ?>
                        <div class="d-flex justify-content-between mb-2 p-2 bg-light rounded">
                            <span class="small text-muted">Ya reembolsado:</span>
                            <span class="fw-bold" style="color: #1565c0;">$<?= number_format($total_reembolsado, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($total_pagado - $total_reembolsado > 0): ?>
                        <div class="d-flex justify-content-between mb-3 p-2 rounded" style="background: #fff3cd;">
                            <span class="small fw-bold" style="color: #856404;">Pendiente de reembolso:</span>
                            <span class="fw-bold" style="color: #c62828; font-size: 16px;">$<?= number_format($total_pagado - $total_reembolsado, 2) ?></span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100 rounded-3" data-bs-toggle="modal" data-bs-target="#modalReembolso">
                            <i class="fas fa-undo me-1"></i> Registrar Reembolso
                        </button>
                        <?php else: ?>
                        <div class="text-center p-2 rounded" style="background: #d4edda;">
                            <span class="small fw-bold" style="color: #155724;"><i class="fas fa-check-circle me-1"></i> Reembolso completo</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Resumen Financiero -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5>Resumen de Pago</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total del Tour</span>
                            <span class="fw-bold">$<?= number_format($reserva['monto_total'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total Pagado Hoy</span>
                            <span class="monto-pagado-text fw-bold">$<?= number_format($total_pagado, 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-3">
                            <span class="fw-bold">Saldo Pendiente</span>
                            <span class="monto-highlight monto-pendiente-text">$<?= number_format($saldo_restante, 2) ?></span>
                        </div>

                        <?php $link_pago = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/intipathcusco/seleccionar_pago.php?t=" . urlencode($reserva['token'] ?? ''); ?>
                        <?php if ($saldo_restante > 0): ?>
                            <div class="mt-3 p-3 rounded-4 border" style="background: #fcfcfc;">
                                <label class="info-label mb-2" style="color:var(--ip-primary);"><i class="fas fa-link me-1"></i> Link de Pago (Saldo)</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control form-control-sm border-0 bg-white" id="pagoLink" value="<?= htmlspecialchars($link_pago) ?>" readonly>
                                    <button class="btn btn-primary btn-sm px-3" onclick="copiarLink()"><i class="fas fa-copy"></i></button>
                                </div>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $reserva['whatsapp']) ?>?text=Hola%20<?= urlencode($reserva['nombre']) ?>,%20puedes%20realizar%20el%20pago%20del%20saldo%20de%20tu%20reserva%20en%20este%20link:%20<?= urlencode($link_pago) ?>" target="_blank" class="btn btn-sm btn-success w-100 mt-2 rounded-3 py-2 shadow-sm">
                                    <i class="fab fa-whatsapp me-2"></i> Enviar Link por WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5>Datos de Contacto</h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarReserva">
                            <i class="fas fa-edit"></i> Editar Datos
                        </button>
                    </div>
                    <div class="card-body-custom">
                        <div class="mb-3">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($reserva['email']) ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"><?= htmlspecialchars($reserva['telefono']) ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="info-label">WhatsApp</span>
                            <span class="info-value">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $reserva['whatsapp']) ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($reserva['whatsapp']) ?> <i class="fab fa-whatsapp text-success ms-1"></i>
                                </a>
                            </span>
                        </div>
                        <div class="mb-0">
                            <span class="info-label">Comentarios / Mensaje</span>
                            <p class="small mb-0 text-muted" style="line-height: 1.5;"><?= nl2br(htmlspecialchars($reserva['mensaje'] ?? 'Sin mensaje')) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recordatorios -->
                <div class="card-custom mt-3">
                    <div class="card-header-custom">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2"></i> Recordatorios</h6>
                    </div>
                    <div class="card-body-custom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Recordatorio 1 (24h):</span>
                            <?php if ((int)$reserva['email_recordatorio_1_enviado']): ?>
                                <span style="color:#27ae60;font-weight:700;font-size:12px;"><i class="fas fa-check-circle"></i> Enviado</span>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:12px;"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">Recordatorio 2 (72h):</span>
                            <?php if ((int)$reserva['email_recordatorio_2_enviado']): ?>
                                <span style="color:#27ae60;font-weight:700;font-size:12px;"><i class="fas fa-check-circle"></i> Enviado</span>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:12px;"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </div>
                        <?php
                        $recordatorios = obtenerRecordatoriosReserva($db, $reserva['id']);
                        if (!empty($recordatorios)):
                        ?>
                        <div class="mb-3" style="max-height:120px;overflow-y:auto;">
                            <?php foreach ($recordatorios as $rec): ?>
                                <div style="font-size:11px;color:#666;padding:3px 0;border-bottom:1px solid #f0f0f0;">
                                    <i class="fas fa-envelope" style="color:var(--ip-turq);"></i>
                                    <?= date('d/m/Y H:i', strtotime($rec['created_at'])) ?>
                                    - <?= htmlspecialchars($rec['asunto'] ?? $rec['tipo']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Editar Reserva -->
<div class="modal fade" id="modalEditarReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i> Editar Información del Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="form-editar-reserva">
                <input type="hidden" name="guardar_edicion" value="1">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nombres</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($reserva['nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Apellidos</label>
                            <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($reserva['apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($reserva['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($reserva['telefono']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($reserva['whatsapp']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Fecha de Viaje</label>
                            <input type="date" name="fecha_viaje" class="form-control" value="<?= $reserva['fecha_viaje'] ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Comentarios / Mensaje Interno</label>
                            <textarea name="mensaje" class="form-control" rows="3"><?= htmlspecialchars($reserva['mensaje']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-3 px-4" onclick="confirmarGuardarDatos()">
                        <i class="fas fa-save me-2"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- Modal Editar Pasajeros -->
<div class="modal fade" id="modalEditarPasajeros" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-dark text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-users-cog me-2"></i> Gestionar Listado de Pasajeros</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="form-editar-pasajeros">
                <input type="hidden" name="guardar_pasajeros" value="1">
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tabla-pasajeros-edit">
                            <thead class="table-light">
                                <tr>
                                    <th width="120">Tipo</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Documento / Pasaporte</th>
                                    <th>Nacionalidad</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pasajeros as $p): ?>
                                <tr>
                                    <td>
                                        <select name="pax[<?= $p['id'] ?>][tipo]" class="form-select form-select-sm rounded-3">
                                            <option value="adulto" <?= $p['tipo'] == 'adulto' ? 'selected' : '' ?>>Adulto</option>
                                            <option value="nino" <?= $p['tipo'] == 'nino' ? 'selected' : '' ?>>Niño</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="pax[<?= $p['id'] ?>][nombres]" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($p['nombres']) ?>" required></td>
                                    <td><input type="text" name="pax[<?= $p['id'] ?>][apellidos]" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($p['apellidos']) ?>" required></td>
                                    <td><input type="text" name="pax[<?= $p['id'] ?>][documento]" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($p['documento']) ?>"></td>
                                    <td><input type="text" name="pax[<?= $p['id'] ?>][pais]" class="form-control form-control-sm rounded-3" value="<?= htmlspecialchars($p['pais']) ?>"></td>
                                    <td></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 mt-2" onclick="agregarFilaPasajero()">
                        <i class="fas fa-plus-circle"></i> Agregar Pasajero
                    </button>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-toggle="modal">Cancelar</button>
                    <button type="button" class="btn btn-dark rounded-3 px-4" onclick="confirmarGuardarPasajeros()">
                        <i class="fas fa-save me-2"></i> Actualizar Pasajeros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registro Pago Manual -->
<div class="modal fade" id="modalPagoManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-success text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2"></i> Registrar Pago Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="form-pago-manual">
                <input type="hidden" name="registrar_pago_manual" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Monto a Registrar (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="monto_pago" class="form-control" value="<?= $saldo_restante ?>" required>
                        </div>
                        <p class="text-muted small mt-1">Saldo pendiente: $<?= number_format($saldo_restante, 2) ?></p>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">Método de Pago</label>
                        <select name="metodo_pago_manual" class="form-select">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="yape_manual">Yape (Manual)</option>
                            <option value="tarjeta_pos">Tarjeta (POS Físico)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success rounded-3 px-4" onclick="confirmarPagoManual()">
                        <i class="fas fa-check-circle me-2"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($reserva['estado'] === 'cancelado' && $total_pagado > 0): ?>
<div class="modal fade" id="modalReembolso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header text-white border-0 py-3" style="background: #c62828; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo me-2"></i> Registrar Reembolso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="form-reembolso">
                <input type="hidden" name="registrar_reembolso" value="1">
                <div class="modal-body p-4">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Registra el reembolso del monto pagado al cliente por la cancelación.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Monto a Reembolsar (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="monto_reembolso" class="form-control" value="<?= number_format($total_pagado, 2, '.', '') ?>" max="<?= number_format($total_pagado, 2, '.', '') ?>" required>
                        </div>
                        <p class="text-muted small mt-1">Monto pagado: $<?= number_format($total_pagado, 2) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Método de Devolución</label>
                        <select name="metodo_reembolso" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="yape">Yape</option>
                            <option value="paypal">PayPal</option>
                            <option value="tarjeta">Devolución a tarjeta</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">Notas (opcional)</label>
                        <textarea name="notas_reembolso" class="form-control" rows="2" placeholder="Número de operación, referencia, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger rounded-3 px-4" onclick="confirmarReembolso()">
                        <i class="fas fa-check-circle me-2"></i> Registrar Reembolso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarPagoManual() {
    Swal.fire({
        title: '¿Registrar este pago?',
        text: "Este pago se añadirá al historial y se descontará del saldo pendiente.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, registrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-pago-manual').submit();
        }
    });
}
function confirmarReembolso() {
    Swal.fire({
        title: '¿Registrar reembolso?',
        text: "Se marcará el pago como reembolsado. Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c62828',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, registrar reembolso',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-reembolso').submit();
        }
    });
}
function confirmarGuardarDatos() {
    Swal.fire({
        title: '¿Guardar cambios?',
        text: "Se actualizará la información de contacto del cliente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-editar-reserva').submit();
        }
    });
}

function confirmarGuardarPasajeros() {
    Swal.fire({
        title: '¿Actualizar pasajeros?',
        text: "Se recalculará el monto total y el saldo de la reserva.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-editar-pasajeros').submit();
        }
    });
}

function copiarLink() {
    var copyText = document.getElementById("pagoLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    Swal.fire({
        icon: 'success',
        title: 'Link Copiado',
        text: 'El link de pago ha sido copiado al portapapeles.',
        timer: 1500,
        showConfirmButton: false
    });
}

function agregarFilaPasajero() {
    var tabla = document.getElementById('tabla-pasajeros-edit').getElementsByTagName('tbody')[0];
    var index = Date.now(); // Usamos timestamp para nombres únicos de inputs
    var fila = tabla.insertRow();
    fila.innerHTML = `
        <td>
            <select name="pax_nuevo[${index}][tipo]" class="form-select form-select-sm rounded-3">
                <option value="adulto">Adulto</option>
                <option value="nino">Niño</option>
            </select>
        </td>
        <td><input type="text" name="pax_nuevo[${index}][nombres]" class="form-control form-control-sm rounded-3" placeholder="Nombres" required></td>
        <td><input type="text" name="pax_nuevo[${index}][apellidos]" class="form-control form-control-sm rounded-3" placeholder="Apellidos" required></td>
        <td><input type="text" name="pax_nuevo[${index}][documento]" class="form-control form-control-sm rounded-3" placeholder="DNI/Pasaporte"></td>
        <td><input type="text" name="pax_nuevo[${index}][pais]" class="form-control form-control-sm rounded-3" placeholder="Nacionalidad"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
}

function confirmarReenvio() {
    Swal.fire({
        title: '¿Reenviar confirmación?',
        text: "Se generará un nuevo PDF con los datos actualizados y se enviará al cliente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, enviar ahora',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?id=<?= $id ?>&reenviar=1';
        }
    });
}
function cambiarEstado(nuevo) {
    if (nuevo === 'cancelado') {
        var totalFmt = '<?= number_format($reserva["monto_total"], 2) ?>';
        var pagadoFmt = '<?= number_format($reserva["monto_total"] - $reserva["saldo"], 2) ?>';
        var saldoFmt = '<?= number_format($reserva["saldo"], 2) ?>';
        var codigoRes = '<?= $reserva["codigo"] ?>';
        var nombreRes = '<?= htmlspecialchars(addslashes($reserva["nombre"])) ?>';

        Swal.fire({
            title: '¿Confirmar CANCELACIÓN?',
            html:
                '<div style="text-align:left;font-size:14px;">' +
                '<p style="margin:0 0 10px;color:#555;">Reserva <strong>#' + codigoRes + '</strong> — ' + nombreRes + '</p>' +
                '<table style="width:100%;font-size:13px;border-collapse:collapse;">' +
                '<tr><td style="padding:4px 0;color:#888;">Total del tour:</td><td style="padding:4px 0;text-align:right;font-weight:bold;">$' + totalFmt + '</td></tr>' +
                '<tr><td style="padding:4px 0;color:#888;">Ya pagado:</td><td style="padding:4px 0;text-align:right;color:#2d8a56;">$' + pagadoFmt + '</td></tr>' +
                '<tr><td style="padding:4px 0;color:#888;">Saldo pendiente:</td><td style="padding:4px 0;text-align:right;color:#c62828;">$' + saldoFmt + '</td></tr>' +
                '</table>' +
                '<div style="margin-top:15px;">' +
                '<label style="font-weight:bold;font-size:13px;color:#555;display:block;margin-bottom:5px;">Motivo de cancelación:</label>' +
                '<textarea id="motivo-cancelacion" class="swal2-textarea" placeholder="Escribe el motivo..." style="border-radius:8px;min-height:80px;"></textarea>' +
                '</div>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c62828',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, cancelar reserva',
            cancelButtonText: 'Volver',
            reverseButtons: true,
            customClass: { popup: 'swal2-popup-custom' },
            preConfirm: function() {
                var motivo = document.getElementById('motivo-cancelacion').value.trim();
                if (!motivo) {
                    Swal.showValidationMessage('Debes escribir el motivo de la cancelación');
                    return false;
                }
                return { motivo: motivo };
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                var motivo = encodeURIComponent(result.value.motivo);
                window.location.href = '?id=<?= $id ?>&accion=cancelado&motivo=' + motivo;
            }
        });
        return;
    }

    let titulo = '';
    let color = '';
    
    switch(nuevo) {
        case 'pagado': titulo = '¿Marcar como PAGADO?'; color = '#2e7d32'; break;
        case 'pendiente': titulo = '¿Mover a PENDIENTE?'; color = '#f57c00'; break;
    }

    Swal.fire({
        title: titulo,
        text: "El estado de la reserva cambiará inmediatamente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: color,
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?id=<?= $id ?>&accion=' + nuevo;
        }
    });
}
</script>
</body>
</html>
