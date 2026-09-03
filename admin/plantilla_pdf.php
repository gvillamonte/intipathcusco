<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('config');
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

$html_default = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { size: A4 portrait; margin: 12mm 15mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; color: #1e293b; }

    .header-bar { background-color: #0C9A9E; border-radius: 0 0 8px 8px; padding: 14px 20px 16px 20px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; }
    .header-logo-cell { width: 200px; }
    .header-logo-box { background-color: #ffffff; border-radius: 6px; padding: 5px 12px; text-align: center; }
    .header-logo-box img { max-height: 40px; max-width: 160px; }
    .header-title-cell { text-align: center; }
    .header-title-cell h1 { color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; margin: 0 0 2px 0; }
    .header-title-cell .subtitle { color: #ffffff; font-size: 9px; letter-spacing: 4px; opacity: 0.9; }
    .header-meta { width: 100%; margin-top: 10px; }
    .header-meta td { text-align: center; }
    .meta-item { background-color: #0d828d; border: 1px solid #5ec3cb; border-radius: 4px; padding: 4px 14px; color: #ffffff; font-size: 9px; font-weight: 700; display: inline-block; }

    .letterhead { background-color: #f0f8f9; border: 1px solid #d5e6e8; border-radius: 6px; padding: 10px 16px; margin: 10px 0; }
    .letterhead-table { width: 100%; border-collapse: collapse; }
    .letterhead-table td { vertical-align: top; font-size: 8.5px; color: #1e293b; line-height: 1.4; }
    .letterhead-ruc { font-weight: bold; color: #0C9A9E; }

    .cards-row { width: 100%; }
    .cards-row td { width: 50%; padding: 12px 6px 4px 6px; vertical-align: top; }
    .card { border: 1px solid #d5e6e8; border-radius: 6px; padding: 12px 14px; background-color: #fbfdfd; }
    .card-title { color: #1E3A5F; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #0C9A9E; padding-bottom: 5px; margin-bottom: 7px; }
    .card table { width: 100%; }
    .card td { padding: 4px 0; font-size: 11px; vertical-align: top; }
    .card .label { font-weight: 700; color: #1E3A5F; width: 88px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
    .card .value { color: #1e293b; font-weight: 500; }

    .section-title { color: #1E3A5F; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 14px 0 6px 0; }

    table.data-table { width: 100%; border-collapse: collapse; }
    table.data-table th { background-color: #0C9A9E; color: #ffffff; padding: 7px 10px; text-align: center; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    table.data-table td { padding: 6px 10px; border-bottom: 1px solid #e4eaee; font-size: 10px; color: #1e2a3a; text-align: center; }
    table.data-table tr:nth-child(even) td { background-color: #e0f2f3; }

    .table-pago { width: 55%; border-collapse: collapse; }
    .table-pago th { padding: 7px 10px; text-align: left; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #ffffff; }
    .table-pago td { padding: 6px 10px; border-bottom: 1px solid #e4eaee; font-size: 10px; color: #1e2a3a; }
    .table-pago td.monto { text-align: right; }
    .table-pago .total-row td { background-color: #1E3A5F; color: #ffffff; font-weight: 700; border-bottom: none; }

    .total-container { text-align: right; margin: 8px 0 6px 0; }
    .total-box { background-color: #1E3A5F; color: #ffffff; padding: 5px 14px; border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: 1px; }

    .terminos-box { border: 2px solid #0C9A9E; border-radius: 6px; padding: 10px 14px; margin-top: 12px; background-color: #f0f8f9; }
    .terminos-title { font-weight: bold; color: #1E3A5F; font-size: 10px; text-transform: uppercase; margin-bottom: 6px; border-bottom: 1px solid #0C9A9E; padding-bottom: 4px; }

    .footer { background-color: #0C9A9E; border-radius: 8px 8px 0 0; padding: 10px 16px; margin-top: 18px; }
    .footer td { color: #ffffff; font-size: 9px; font-weight: 600; text-align: center; }
    .footer-note { text-align: center; color: #8aa0ad; font-size: 8px; margin-top: 6px; }
</style>
</head>
<body>

{marca_agua}

<div class="header-bar">
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header-logo-cell"><div class="header-logo-box">{logo}</div></td>
            <td class="header-title-cell">
                <h1>Solicitud de Reserva</h1>
                <div class="subtitle">CUSCO &mdash; PER&Uacute;</div>
            </td>
            <td class="header-logo-cell">&nbsp;</td>
        </tr>
    </table>
    <table class="header-meta" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%"><div class="meta-item"><strong>C&Oacute;DIGO:</strong> {codigo}</div></td>
            <td width="50%"><div class="meta-item"><strong>FECHA:</strong> {fecha_transaccion}</div></td>
        </tr>
    </table>
</div>

<div class="letterhead">
    <table class="letterhead-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <strong>{razon_social}</strong><br>
                RUC: <span class="letterhead-ruc">{ruc}</span><br>
                {direccion}
            </td>
            <td style="text-align:right;">
                {telefono_contacto}<br>
                {email_contacto}<br>
                {web}
            </td>
        </tr>
    </table>
</div>

<table class="cards-row" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <div class="card">
                <div class="card-title">Datos del Tour</div>
                <table cellpadding="0" cellspacing="0">
                    <tr><td class="label">Tour</td><td class="value">{tour}</td></tr>
                    <tr><td class="label">Fecha</td><td class="value">{fecha_viaje}</td></tr>
                    <tr><td class="label">Duraci&oacute;n</td><td class="value">{duracion}</td></tr>
                </table>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="card-title">Datos del Cliente</div>
                <table cellpadding="0" cellspacing="0">
                    <tr><td class="label">E-mail</td><td class="value">{email}</td></tr>
                    <tr><td class="label">Nombres</td><td class="value">{nombre}</td></tr>
                    <tr><td class="label">Contacto</td><td class="value">{telefono}</td></tr>
                    <tr><td class="label">Mensaje</td><td class="value">{mensaje}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="section-title">Pasajeros</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th width="30">N&deg;</th>
            <th>Tipo</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Documento</th>
            <th>Pa&iacute;s</th>
        </tr>
    </thead>
    <tbody>{pasajeros}</tbody>
</table>

<div class="section-title">Resumen de Pagos</div>
<table class="table-pago" align="right" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th style="background-color:#0C9A9E;">Concepto</th>
            <th style="background-color:#1E3A5F;">Monto</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Precio por adulto (referencia)</td><td class="monto">{precio_adulto}</td></tr>
        <tr><td><strong>Total del viaje</strong></td><td class="monto"><strong>{total}</strong></td></tr>
        <tr><td>Adelanto</td><td class="monto">{adelanto}</td></tr>
        <tr><td>Saldo en Cusco</td><td class="monto">{saldo}</td></tr>
        <tr><td>M&eacute;todo de pago</td><td class="monto">{metodo_pago}</td></tr>
        <tr class="total-row"><td>Estado</td><td class="monto">{estado}</td></tr>
    </tbody>
</table>

<div class="section-title">Datos Bancarios</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th width="30">N&deg;</th>
            <th>Banco</th>
            <th>Titular</th>
            <th>N&deg; Cuenta</th>
            <th>CCI</th>
            <th>Moneda</th>
        </tr>
    </thead>
    <tbody>{bancos}</tbody>
</table>

<div class="terminos-box">
    <div class="terminos-title">T&eacute;rminos y Condiciones</div>
    {terminos_condiciones}
</div>

<div class="footer">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%">{telefono_contacto}</td>
            <td width="33%">{email_contacto}</td>
            <td width="34%">{direccion}</td>
        </tr>
    </table>
</div>
<div class="footer-note">* Documento generado autom&aacute;ticamente. V&aacute;lido como solicitud de reserva.</div>
<div class="footer-note">* Los precios est&aacute;n expresados en D&oacute;lares Americanos.</div>

</body>
</html>';

try {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM plantilla_pdf WHERE id = 1");
    } catch (Exception $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS plantilla_pdf (
            id INT(11) NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            contenido_html LONGTEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $db->query("SELECT COUNT(*) FROM plantilla_pdf WHERE id = 1");
    }
    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare("INSERT INTO plantilla_pdf (id, nombre, contenido_html) VALUES (1, 'Plantilla por defecto', ?)");
        $stmt->execute([$html_default]);
    }

    if (isset($_POST['guardar'])) {
        $html = $_POST['contenido_html'];
        $stmt = $db->prepare("UPDATE plantilla_pdf SET contenido_html = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$html]);
        if ($stmt->rowCount() == 0) {
            $stmt = $db->prepare("INSERT INTO plantilla_pdf (id, nombre, contenido_html) VALUES (1, 'Plantilla por defecto', ?)");
            $stmt->execute([$html]);
        }
        header("Location: plantilla_pdf.php?res=ok");
        exit;
    }

    if (isset($_GET['reset'])) {
        $stmt = $db->prepare("UPDATE plantilla_pdf SET contenido_html = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$html_default]);
        header("Location: plantilla_pdf.php?res=reset");
        exit;
    }

    $stmt = $db->query("SELECT contenido_html FROM plantilla_pdf WHERE id = 1");
    $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
    $html_actual = $plantilla['contenido_html'] ?? '';

    // --- Datos de ejemplo para vista previa ---
    $pasajeros_ejemplo = '
        <tr><td style="text-align:center;">1</td><td>Adulto</td><td>Juan Carlos</td><td>Pérez García</td><td>DNI 45123678</td><td>Perú</td></tr>
        <tr><td style="text-align:center;">2</td><td>Adulto</td><td>María Elena</td><td>López Martínez</td><td>DNI 41256789</td><td>Perú</td></tr>
        <tr><td style="text-align:center;">3</td><td>Niño</td><td>Carlos Andrés</td><td>Pérez López</td><td>DNI 70123456</td><td>Perú</td></tr>';

    $bancos_ejemplo = '
        <tr>
            <td style="text-align:center;">1</td>
            <td style="font-weight:700;">INTERBANK</td>
            <td>INTI PATH TOURS PERU S.A.C.</td>
            <td>420-429-8224216</td>
            <td>003-420-90308224216-72</td>
            <td><span style="background:#0f9b9e;color:#fff;padding:2px 8px;border-radius:3px;font-size:9px;">S/</span> Soles</td>
        </tr>';

    $preview_replaces = [
        '{codigo}'             => 'RES-2026-001234',
        '{tour}'               => 'Inca Jungle 4D/3N',
        '{fecha_viaje}'        => '15 de Septiembre 2026',
        '{fecha_transaccion}'  => '28 de Agosto 2026',
        '{duracion}'           => '4 Días / 3 Noches',
        '{pasajeros}'          => $pasajeros_ejemplo,
        '{email}'              => 'juan.perez@email.com',
        '{nombre}'             => 'Juan Carlos Pérez García',
        '{telefono}'          => '+51 984 123 456',
        '{whatsapp}'           => '+51 984 123 456',
        '{mensaje}'            => 'Solicitud de reserva para grupo familiar',
        '{precio_adulto}'      => 'S/ 2,800.00',
        '{total}'              => 'S/ 8,400.00',
        '{adelanto}'           => 'S/ 4,200.00',
        '{saldo}'              => 'S/ 4,200.00',
        '{metodo_pago}'        => 'Transferencia Bancaria',
        '{estado}'             => 'Pendiente',
        '{bancos}'             => $bancos_ejemplo,
        '{banco}'              => 'INTERBANK',
        '{titular}'            => 'INTI PATH TOURS PERU S.A.C.',
        '{cuenta_soles}'       => '420-429-8224216',
        '{cuenta_dolares}'     => '',
        '{cci}'                => '003-420-90308224216-72',
        '{logo}'               => '<span style="font-weight:900;color:#fff;font-size:16px;">🏛️ INTIPATH</span>',
        '{marca_agua}'         => '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);opacity:0.06;z-index:0;pointer-events:none;text-align:center;"><span style="font-weight:900;color:#000;font-size:48px;">INTIPATH</span></div>',
        '{telefono_contacto}'  => '+51 84 123 456',
        '{email_contacto}'     => 'reservas@intipathcusco.com',
        '{web}'                => 'www.intipathtours.com',
        '{razon_social}'       => 'INTI PATH TOURS TREKKIN PERU S.A.C.',
        '{ruc}'                => '20615665984',
        '{direccion}'          => 'Av. El Sol 948 C.C. Cusco Sol Plaza (3er Piso 322)',
        '{terminos_condiciones}' => '<div style="font-size:9px;color:#333;padding:2px 0;">&#10003; Cancelaciones: Hasta 48h antes sin costo.</div><div style="font-size:9px;color:#333;padding:2px 0;">&#10003; Cambios de fecha: Con 7 días de anticipación.</div><div style="font-size:9px;color:#333;padding:2px 0;">&#10003; Seguro de viaje: Se recomienda contratar.</div>',
    ];

    $html_preview = str_replace(array_keys($preview_replaces), array_values($preview_replaces), $html_actual);

} catch (Exception $e) {
    $html_actual = '';
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantilla PDF | Admin IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --admin-blue: #15305D; --admin-accent: #E8AC18; }
        body { background:#f4f7f6;font-family:'Segoe UI',sans-serif; }
        .admin-title { color:var(--admin-blue);font-weight:800;border-bottom:4px solid var(--admin-accent);display:inline-block;padding-bottom:5px;margin-bottom:25px;text-transform:uppercase;font-size:1.4rem; }
        .card { background:#fff;border-radius:12px;padding:25px;margin-bottom:25px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid #e0e0e0; }
        textarea { width:100%;min-height:600px;font-family:'Courier New',monospace;font-size:13px;padding:15px;border:1px solid #ccc;border-radius:8px;tab-size:2; }
        .vars { background:#f8fbff;border:1px solid #d1e3fa;padding:15px;border-radius:8px;margin-bottom:15px;font-size:13px; }
        .vars code { background:#e9ecef;padding:2px 6px;border-radius:3px;font-size:12px; }
        .btn-admin { background:var(--admin-blue);color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700; }
        .btn-reset { background:#e74c3c;color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block; }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" style="padding:30px;">
        <h1 class="admin-title"><i class="fas fa-file-code"></i> Plantilla del PDF de Reserva</h1>

        <?php if (isset($error_msg)): ?>
            <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #f5c6cb;">
                <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['res']) && $_GET['res'] === 'ok'): ?>
            <script>Swal.fire({icon:'success',title:'Plantilla guardada',timer:1500,showConfirmButton:false});</script>
        <?php elseif (isset($_GET['res']) && $_GET['res'] === 'reset'): ?>
            <script>Swal.fire({icon:'info',title:'Plantilla restaurada',text:'Se restauró la plantilla por defecto',timer:2000,showConfirmButton:false});</script>
        <?php elseif (isset($_GET['res']) && $_GET['res'] === 'error'): ?>
            <script>Swal.fire({icon:'error',title:'Error al guardar',text:'No se pudo guardar la plantilla. Verifica la conexión a la base de datos.'});</script>
        <?php endif; ?>

        <div class="card">
            <div class="vars">
                <strong><i class="fas fa-info-circle"></i> Variables disponibles (se rellenan autom&aacute;ticamente):</strong><br><br>
                <strong>Datos:</strong>
                <code>{codigo}</code> <code>{tour}</code> <code>{fecha_viaje}</code> <code>{fecha_transaccion}</code> <code>{duracion}</code>
                <code>{nombre}</code> <code>{email}</code> <code>{telefono}</code> <code>{whatsapp}</code> <code>{mensaje}</code><br>
                <strong>Pasajeros:</strong>
                <code>{pasajeros}</code> (filas de tabla autom&aacute;ticas: N&deg;, Tipo, Nombres, Apellidos, Documento, Pa&iacute;s)<br>
                <strong>Pago:</strong>
                <code>{precio_adulto}</code> <code>{total}</code> <code>{adelanto}</code> <code>{saldo}</code> <code>{metodo_pago}</code> <code>{estado}</code><br>
                <strong>Bancos:</strong>
                <code>{bancos}</code> (filas autom&aacute;ticas: logo + banco + titular + N&deg; cuenta + CCI + moneda)<br>
                <strong>Compatibilidad:</strong>
                <code>{banco}</code> <code>{titular}</code> <code>{cuenta_soles}</code> <code>{cuenta_dolares}</code> <code>{cci}</code><br>
                <strong>Empresa (Letterhead):</strong>
                <code>{razon_social}</code> <code>{ruc}</code> <code>{direccion}</code><br>
                <strong>Layout:</strong>
                <code>{logo}</code> <code>{telefono_contacto}</code> <code>{email_contacto}</code> <code>{web}</code><br>
                <strong>Contenido:</strong>
                <code>{terminos_condiciones}</code> (términos y condiciones desde el panel de admin)<br>
                <strong>Marca de agua:</strong>
                <code>{marca_agua}</code> (autom&aacute;tica seg&uacute;n estado: pendiente/parcial/cancelado)
                <strong>Marca de agua:</strong>
                <code>{marca_agua}</code> (logo centrado, semi-transparente, rotado 30° — se rellena autom&aacute;ticamente)
            </div>

            <form method="POST">
                <textarea name="contenido_html" id="contenido_html" spellcheck="false"><?= htmlspecialchars($html_actual) ?></textarea>
                <div style="margin-top:15px;display:flex;gap:10px;">
                    <button type="button" class="btn-admin" style="background:#0f9b9e;" onclick="abrirPreview()"><i class="fas fa-eye"></i> Vista Previa</button>
                    <button type="submit" name="guardar" class="btn-admin"><i class="fas fa-save"></i> Guardar Plantilla</button>
                    <a href="?reset=1" class="btn-reset" onclick="return confirm('Restaurar plantilla por defecto?')"><i class="fas fa-undo"></i> Restaurar por defecto</a>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- MODAL VISTA PREVIA -->
<div id="previewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:#fff;border-radius:12px;width:90%;max-width:900px;height:85%;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="background:#15305D;color:#fff;padding:12px 20px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <span style="font-weight:700;font-size:14px;"><i class="fas fa-eye me-2"></i>Vista Previa de la Plantilla PDF</span>
            <button onclick="cerrarPreview()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;padding:5px 10px;border-radius:4px;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">&times;</button>
        </div>
        <div style="flex:1;overflow:auto;padding:0;">
            <iframe id="previewFrame" style="width:100%;height:100%;border:none;"></iframe>
        </div>
    </div>
</div>

<script>
function abrirPreview() {
    var html = document.getElementById('contenido_html').value;
    var datos = {
        '{codigo}': 'RES-2026-001234',
        '{tour}': 'Inca Jungle 4D/3N',
        '{fecha_viaje}': '15 de Septiembre 2026',
        '{fecha_transaccion}': '28 de Agosto 2026',
        '{duracion}': '4 Días / 3 Noches',
        '{pasajeros}': '<tr><td style="text-align:center;">1</td><td>Adulto</td><td>Juan Carlos</td><td>Pérez García</td><td>DNI 45123678</td><td>Perú</td></tr><tr><td style="text-align:center;">2</td><td>Adulto</td><td>María Elena</td><td>López Martínez</td><td>DNI 41256789</td><td>Perú</td></tr><tr><td style="text-align:center;">3</td><td>Niño</td><td>Carlos Andrés</td><td>Pérez López</td><td>DNI 70123456</td><td>Perú</td></tr>',
        '{email}': 'juan.perez@email.com',
        '{nombre}': 'Juan Carlos Pérez García',
        '{telefono}': '+51 984 123 456',
        '{whatsapp}': '+51 984 123 456',
        '{mensaje}': 'Solicitud de reserva para grupo familiar',
        '{precio_adulto}': 'S/ 2,800.00',
        '{total}': 'S/ 8,400.00',
        '{adelanto}': 'S/ 4,200.00',
        '{saldo}': 'S/ 4,200.00',
        '{metodo_pago}': 'Transferencia Bancaria',
        '{estado}': 'Pendiente',
        '{bancos}': '<tr><td style="text-align:center;">1</td><td style="font-weight:700;">INTERBANK</td><td>INTI PATH TOURS PERU S.A.C.</td><td>420-429-8224216</td><td>003-420-90308224216-72</td><td><span style="background:#0f9b9e;color:#fff;padding:2px 8px;border-radius:3px;font-size:9px;">S/</span> Soles</td></tr>',
        '{banco}': 'INTERBANK',
        '{titular}': 'INTI PATH TOURS PERU S.A.C.',
        '{cuenta_soles}': '420-429-8224216',
        '{cuenta_dolares}': '',
        '{cci}': '003-420-90308224216-72',
        '{logo}': '<span style="font-weight:900;color:#fff;font-size:16px;">🏛️ INTIPATH</span>',
        '{marca_agua}': '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);opacity:0.06;z-index:0;pointer-events:none;text-align:center;"><span style="font-weight:900;color:#000;font-size:48px;">INTIPATH</span></div>',
        '{telefono_contacto}': '+51 84 123 456',
        '{email_contacto}': 'reservas@intipathtours.com',
        '{web}': 'www.intipathtours.com',
        '{razon_social}': 'INTI PATH TOURS TREKKIN PERU S.A.C.',
        '{ruc}': '20615665984',
        '{direccion}': 'Av. El Sol 948 C.C. Cusco Sol Plaza (3er Piso 322)',
        '{terminos_condiciones}': '<div style="font-size:9px;color:#333;padding:2px 0;">&#10003; Cancelaciones: Hasta 48h antes sin costo.</div><div style="font-size:9px;color:#333;padding:2px 0;">&#10003; Cambios de fecha: Con 7 días de anticipación.</div>'
    };
    for (var key in datos) {
        html = html.split(key).join(datos[key]);
    }
    var frame = document.getElementById('previewFrame');
    frame.srcdoc = html;
    document.getElementById('previewModal').style.display = 'flex';
}
function cerrarPreview() {
    document.getElementById('previewModal').style.display = 'none';
    document.getElementById('previewFrame').srcdoc = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarPreview(); });
</script>
</body>
</html>
