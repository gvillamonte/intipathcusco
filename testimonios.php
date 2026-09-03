<?php
// testimonios.php - Redirige directamente al enlace de testimonios configurado
// en el panel admin (admin/testimonios_config.php). Si no hay valor en BD,
// usa el enlace por defecto de Tripadvisor.

$url_testimonios = 'https://www.tripadvisor.com.pe/Attraction_Review-g294314-d34356631-Reviews-Inti_Path_Tours-Cusco_Cusco_Region.html';

try {
    require_once __DIR__ . '/config/database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT testimonios_url FROM configuracion WHERE id = 1");
    $stmt->execute();
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fila && !empty($fila['testimonios_url'])) {
        $url_testimonios = $fila['testimonios_url'];
    }
} catch (Exception $e) {
    // Si la BD falla, seguimos con el enlace por defecto
}

header('Location: ' . $url_testimonios);
exit;