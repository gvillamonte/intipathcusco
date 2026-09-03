<?php

function iniciarSesionAdmin($timeout = 600) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['ultimo_acceso'])) {
        $antiguedad = time() - $_SESSION['ultimo_acceso'];
        if ($antiguedad > $timeout) {
            session_unset();
            session_destroy();
            header("Location: login.php?res=sesion_expirada");
            exit;
        }
    }
    $_SESSION['ultimo_acceso'] = time();

    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}

function esAdminSuper() {
    $nombre = $_SESSION['admin_nombre'] ?? '';
    if (in_array(strtolower($nombre), ['admin', 'administrador'])) {
        return true;
    }
    if (isset($_SESSION['usuario']) && strtolower($_SESSION['usuario']) === 'admin') {
        return true;
    }
    return false;
}

function requierePermiso($permiso) {
    iniciarSesionAdmin();

    if (esAdminSuper()) {
        return;
    }

    $permisos = $_SESSION['permisos'] ?? [];
    if (!in_array($permiso, $permisos)) {
        header("Location: index.php?res=sin_permiso");
        exit;
    }
}
