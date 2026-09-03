<?php
// includes/csrf_helper.php
// Protección CSRF: token por sesión para todos los formularios POST públicos y de admin.

function iniciarSesionParaCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function generarTokenCSRF() {
    iniciarSesionParaCSRF();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function campoCSRF() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generarTokenCSRF()) . '">';
}

function verificarTokenCSRF($token) {
    iniciarSesionParaCSRF();
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Devuelve true si el honeypot fue llenado (posible bot) -> rechazar
function esHoneypotLlenado() {
    return !empty($_POST['website']) || !empty($_POST['empresa_web']) || !empty($_POST['url_web']);
}