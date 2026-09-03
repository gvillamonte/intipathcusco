<?php
// robots.php — robots.txt dinámico con referencia al sitemap
header("Content-Type: text/plain; charset=utf-8");
$dominio = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? 'http://localhost/intipathcusco/'
    : 'https://www.intipathtours.com/';
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /includes/
Disallow: /config/
Disallow: /assets/css/
Disallow: /checkout_izipay.php
Disallow: /checkout_paypal.php
Disallow: /seleccionar_pago.php
Disallow: /retorno_izipay.php
Disallow: /retorno_paypal.php
Disallow: /ipn_izipay.php
Disallow: /ipn_paypal.php
Disallow: /pago_exitoso.php
Disallow: /obtener_eventos_cliente.php
Disallow: /procesar_newsletter.php
Disallow: /procesar_reclamo.php
Disallow: /cron_sync_resenas.php
Disallow: /migrar_*.php

Sitemap: <?= $dominio ?>sitemap.php