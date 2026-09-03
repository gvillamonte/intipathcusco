<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('equipo');

if (isset($_POST['archivo'])) {
    $archivo = basename($_POST['archivo']);
    $ruta = __DIR__ . "/../assets/img/equipo/" . $archivo;

    $real_base = realpath(__DIR__ . "/../assets/img/equipo/");
    $real_ruta = realpath($ruta);

    if ($real_ruta === false || strpos($real_ruta, $real_base) !== 0) {
        echo "archivo_invalido";
        exit;
    }

    if (file_exists($real_ruta)) {
        if (unlink($real_ruta)) {
            echo "success";
        } else {
            echo "error_permisos";
        }
    } else {
        echo "archivo_no_existe";
    }
}
?>