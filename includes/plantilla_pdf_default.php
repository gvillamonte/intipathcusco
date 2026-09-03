<?php
// Plantilla por defecto del PDF de reserva, COMPATIBLE CON DOMPDF.
// Reglas: sin flexbox, sin @import remoto, sin SVG, sin box-shadow,
// sin calc(), layout con tablas. Esta misma plantilla es la que se
// inserta en plantilla_pdf (id=1) y la que usa el editor del panel.

function plantillaPdfDefault() {
    return <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitud de Reserva - IntiPath Tours</title>
<style>
    @page { size: A4 portrait; margin: 12mm 15mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #1e293b; }

    /* === ENCABEZADO TURQUESA === */
    .header-bar { background-color: #0C9A9E; border-radius: 0 0 8px 8px; padding: 14px 20px 16px 20px; }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: middle; }
    .header-logo-cell { width: 200px; }
    .header-logo-box { background-color: #ffffff; border-radius: 6px; padding: 5px 12px; text-align: center; }
    .header-logo-box img { max-height: 40px; max-width: 160px; }
    .header-title-cell { text-align: center; }
    .header-title-cell h1 { color: #ffffff; font-size: 20pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 2px 0; }
    .header-title-cell h2 { color: #ffffff; font-size: 9pt; letter-spacing: 3px; opacity: 0.9; }
    .header-meta { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .header-meta td { text-align: center; padding: 2px 0; }
    .meta-item { background-color: #0d828d; border: 1px solid #5ec3cb; border-radius: 4px; padding: 4px 14px; color: #ffffff; font-size: 9pt; font-weight: bold; display: inline-block; }

    /* === LETRAH Header de empresa === */
    .letterhead { background-color: #f0f8f9; border: 1px solid #d5e6e8; border-radius: 6px; padding: 10px 16px; margin: 10px 0; }
    .letterhead-table { width: 100%; border-collapse: collapse; }
    .letterhead-table td { vertical-align: top; font-size: 8.5pt; color: #1e293b; line-height: 1.4; }
    .letterhead-ruc { font-weight: bold; color: #0C9A9E; }

    /* === TARJETAS TOUR / CLIENTE === */
    .cards-row { width: 100%; border-collapse: collapse; }
    .cards-row td { width: 50%; padding: 12px 6px 4px 6px; vertical-align: top; }
    .card { border: 1px solid #d5e6e8; border-radius: 6px; padding: 12px 14px; background-color: #fbfdfd; }
    .card-title { color: #1E3A5F; font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #0C9A9E; padding-bottom: 5px; margin-bottom: 7px; }
    .card-field { margin-bottom: 6px; font-size: 9.5pt; }
    .card-label { font-weight: bold; color: #1E3A5F; }

    /* === SECCIONES === */
    .section-title { font-weight: bold; color: #1E3A5F; font-size: 12pt; text-transform: uppercase; margin: 14px 0 6px 0; letter-spacing: 0.5px; }

    /* === TABLAS === */
    table.data-table { width: 100%; border-collapse: collapse; }
    table.data-table th { background-color: #0C9A9E; color: #ffffff; padding: 6px 8px; text-align: center; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    table.data-table td { padding: 6px 8px; text-align: center; font-size: 9pt; color: #1e2a3a; }
    table.data-table tr.row-teal td { background-color: #e0f2f3; }
    table.data-table tr.row-navy td { background-color: #e6e7e8; }

    /* === TOTAL === */
    .total-container { text-align: right; margin: 10px 0 6px 0; }
    .total-box { background-color: #1E3A5F; color: #ffffff; padding: 6px 16px; border-radius: 6px; font-size: 11pt; font-weight: bold; letter-spacing: 1px; }

    /* === TERMINOS Y CONDICIONES === */
    .terminos-box { border: 2px solid #0C9A9E; border-radius: 6px; padding: 12px 14px; margin-top: 14px; background-color: #f0f8f9; }
    .terminos-title { font-weight: bold; color: #1E3A5F; font-size: 10pt; text-transform: uppercase; margin-bottom: 6px; border-bottom: 1px solid #0C9A9E; padding-bottom: 4px; }

    /* === FOOTER === */
    .footer-bar { background-color: #0C9A9E; border-radius: 8px 8px 0 0; padding: 10px 14px; margin-top: 18px; }
    .footer-table { width: 100%; border-collapse: collapse; }
    .footer-table td { color: #ffffff; font-size: 8.5pt; font-weight: bold; text-align: center; padding: 2px 4px; }
    .footer-note { text-align: center; color: #8aa0ad; font-size: 7.5pt; margin-top: 6px; }
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
                <h2>CUSCO &mdash; PER&Uacute;</h2>
            </td>
            <td class="header-logo-cell">&nbsp;</td>
        </tr>
    </table>
    <table class="header-meta" cellpadding="0" cellspacing="0">
        <tr>
            <td width="50%"><span class="meta-item"><strong>C&Oacute;DIGO:</strong> {codigo}</span></td>
            <td width="50%"><span class="meta-item"><strong>FECHA:</strong> {fecha_transaccion}</span></td>
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
                <div class="card-field"><span class="card-label">TOUR:</span> {tour}</div>
                <div class="card-field"><span class="card-label">FECHA DEL TOUR:</span> {fecha_viaje}</div>
                <div class="card-field"><span class="card-label">DURACI&Oacute;N:</span> {duracion}</div>
            </div>
        </td>
        <td>
            <div class="card">
                <div class="card-title">Datos del Cliente</div>
                <div class="card-field"><span class="card-label">E-MAIL:</span> {email}</div>
                <div class="card-field"><span class="card-label">NOMBRES COMPLETOS:</span> {nombre}</div>
                <div class="card-field"><span class="card-label">CONTACTO:</span> {telefono}</div>
                <div class="card-field"><span class="card-label">MENSAJE:</span> {mensaje}</div>
            </div>
        </td>
    </tr>
</table>

<div class="section-title">Detalles del Pago</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th width="30">N&deg;</th>
            <th>Total del Viaje</th>
            <th>Adelanto</th>
            <th>Saldo Cusco</th>
            <th>M&eacute;todo de Pago</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>{filas_detalles_pago}</tbody>
</table>

<div class="total-container"><span class="total-box">TOTAL: {total}</span></div>

<div class="section-title">Datos Bancarios</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th width="30">N&deg;</th>
            <th>Nombre del Banco</th>
            <th>Titular</th>
            <th>C. Soles</th>
            <th>C. D&oacute;lares</th>
            <th>CCI</th>
        </tr>
    </thead>
    <tbody>{filas_bancos}</tbody>
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

<div class="terminos-box">
    <div class="terminos-title">T&eacute;rminos y Condiciones</div>
    {terminos_condiciones}
</div>

<div class="footer-bar">
    <table class="footer-table" cellpadding="0" cellspacing="0">
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
</html>
HTML;
}
