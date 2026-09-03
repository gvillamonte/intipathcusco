<?php
session_start();
require_once __DIR__ . '/../includes/auth_helper.php';
iniciarSesionAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Diagnóstico</title></head>
<body style="font-family:monospace;padding:20px;">
<h2>Diagnóstico de Sesión y Permisos</h2>
<table border="1" cellpadding="8" style="border-collapse:collapse;">
<tr><td><strong>admin_id</strong></td><td><?= $_SESSION['admin_id'] ?? 'NO SET' ?></td></tr>
<tr><td><strong>admin_nombre</strong></td><td><?= $_SESSION['admin_nombre'] ?? 'NO SET' ?></td></tr>
<tr><td><strong>admin_logeado</strong></td><td><?= isset($_SESSION['admin_logeado']) ? ($_SESSION['admin_logeado'] ? 'true' : 'false') : 'NO SET' ?></td></tr>
<tr><td><strong>esAdminSuper()</strong></td><td><?= esAdminSuper() ? 'SI (bypass activo)' : 'NO' ?></td></tr>
<tr><td><strong>Permisos en sesión</strong></td><td><?= isset($_SESSION['permisos']) ? implode(', ', $_SESSION['permisos']) : 'NO SET' ?></td></tr>
<tr><td><strong>Tiene "footer_links"?</strong></td><td><?= in_array('footer_links', $_SESSION['permisos'] ?? []) ? 'SI' : 'NO - Esta es la causa del error' ?></td></tr>
</table>
<p style="margin-top:20px;"><a href="logout.php">Cerrar sesión</a></p>
</body>
</html>
