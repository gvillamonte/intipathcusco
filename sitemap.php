<?php
// sitemap.php — Sitemap XML dinámico (tours, blog, páginas libres y principales)
header("Content-Type: application/xml; charset=utf-8");
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    $base = 'https://www.intipathtours.com/';

    // 1. Páginas principales
    $principales = [
        ['', '1.0'],
        ['tours.php', '0.9'],
        ['blog.php', '0.8'],
        ['contacto.php', '0.8'],
        ['nosotros.php', '0.8'],
        ['preguntas.php', '0.7'],
        ['como-reservar.php', '0.7'],
        ['reservas-info.php', '0.7'],
        ['garantia.php', '0.6'],
        ['seguridad.php', '0.6'],
        ['terminos-y-condiciones.php', '0.5'],
        ['politica-privacidad.php', '0.5'],
    ];
    foreach ($principales as $p) {
        echo '<url><loc>' . $base . $p[0] . '</loc><priority>' . $p[1] . '</priority></url>';
    }

    if ($db) {
        // 2. Tours activos (slug a partir de la URL con ?id=)
        $stmt = $db->query("SELECT id FROM tours WHERE estado = 'activo'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url><loc>' . $base . 'detalle_tour.php?id=' . $row['id'] . '</loc><priority>0.9</priority></url>';
        }
        $stmt->closeCursor();

        // 3. Blog activo
        $stmt = $db->query("SELECT id, fecha FROM blog WHERE estado = 'activo'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url><loc>' . $base . 'detalle_blog.php?id=' . $row['id'] . '</loc>';
            if (!empty($row['fecha'])) echo '<lastmod>' . date('Y-m-d', strtotime($row['fecha'])) . '</lastmod>';
            echo '<priority>0.7</priority></url>';
        }
        $stmt->closeCursor();

        // 4. Páginas libres del CMS
        $stmt = $db->query("SELECT slug, updated_at FROM paginas WHERE activo = 1 ORDER BY orden ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<url><loc>' . $base . 'pagina.php?slug=' . urlencode($row['slug']) . '</loc>';
            if (!empty($row['updated_at'])) echo '<lastmod>' . date('Y-m-d', strtotime($row['updated_at'])) . '</lastmod>';
            echo '<priority>0.6</priority></url>';
        }
        $stmt->closeCursor();
    }
    echo '</urlset>';
} catch (Exception $e) { }