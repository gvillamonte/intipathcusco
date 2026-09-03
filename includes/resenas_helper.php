<?php

/**
 * resenas_helper.php
 * Infraestructura de la sección "Lo que dicen nuestros clientes" (index.php).
 *
 * AUTO-INSTALABLE e idempotente: crea la tabla de reseñas, la fila de
 * configuración en secciones_index y los datos de ejemplo la primera vez.
 * Nunca pisa lo que el admin ya ha editado.
 *
 * Uso:
 *   require_once 'includes/resenas_helper.php';
 *   asegurar_infraestructura_resenas($db);
 *   $data_resenas = obtener_datos_resenas($db);
 */

/**
 * Valores por defecto de la sección (se usan solo si la fila aún no existe).
 */
function resenas_valores_default() {
    return [
        'etiqueta' => 'Inti Path Tours',
        'plataformas' => [
            'tripadvisor' => [
                'activo'   => 1,
                'puntaje'  => '5.0',
                'opiniones' => '12910',
                'url'      => 'https://www.tripadvisor.com/Attraction_Review-g294314-d34356631-Reviews-Inti_Path_Tours-Cusco_Cusco_Region.html',
            ],
            'google' => [
                'activo'   => 1,
                'puntaje'  => '4.7',
                'opiniones' => '552',
                'url'      => 'https://www.google.com/maps/search/?api=1&query=Inti+Path+Tours+Cusco',
            ],
            'trustpilot' => [
                'activo'   => 1,
                'puntaje'  => '4.8',
                'opiniones' => '120',
                'url'      => 'https://www.trustpilot.com/reviews/68e10bab057bf5ce5cbb61e8',
            ],
        ],
        'widget_activo' => 1,
        'widget_code'   => "<script defer async src='https://cdn.trustindex.io/loader.js?ac84f2379a28094a2e76516e3bf'></script>",
        'max_por_plataforma'    => 3,
        'lineas_texto'          => 3,
        'sync_intervalo_horas'  => 6,
        'paleta_colores'        => ['#0f9b9e','#15305D','#E8AC18','#27ae60','#0ea5e9','#7c3aed','#16a34a','#dc2626','#ea580c'],
    ];
}

/**
 * Garantiza que existan: tabla resenas, columna extra_json y fila 'reviews'.
 * Idempotente: ejecutable en cada carga sin efectos secundarios.
 */
function asegurar_infraestructura_resenas($db) {

    // 1. Tabla de reseñas
    $db->exec("CREATE TABLE IF NOT EXISTS resenas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plataforma VARCHAR(20) NOT NULL DEFAULT 'google',
        autor VARCHAR(100) NOT NULL DEFAULT '',
        fecha VARCHAR(50) DEFAULT '',
        titulo VARCHAR(200) DEFAULT '',
        texto TEXT,
        link VARCHAR(255) DEFAULT '',
        color_avatar VARCHAR(20) DEFAULT '#0f9b9e',
        orden INT DEFAULT 0,
        activo TINYINT(1) DEFAULT 1,
        trustindex_id VARCHAR(64) DEFAULT '',
        fuente VARCHAR(10) DEFAULT 'manual'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 1b. Columnas nuevas (idempotente)
    foreach (['trustindex_id' => 'VARCHAR(64)', 'fuente' => 'VARCHAR(10)'] as $col => $tipo) {
        try { $db->query("SELECT {$col} FROM resenas LIMIT 1"); }
        catch (PDOException $e) { $db->exec("ALTER TABLE resenas ADD COLUMN {$col} {$tipo} DEFAULT ''"); }
    }

    // 2. Columna extra_json en secciones_index (si no existe)
    try {
        $db->query("SELECT extra_json FROM secciones_index LIMIT 1");
    } catch (PDOException $e) {
        $db->exec("ALTER TABLE secciones_index ADD COLUMN extra_json TEXT NULL");
    }

    // 3. Fila de configuración de la sección
    $stmt = $db->query("SELECT COUNT(*) FROM secciones_index WHERE seccion = 'reviews'");
    if ((int)$stmt->fetchColumn() === 0) {
        $db->prepare("INSERT INTO secciones_index (seccion, activo, titulo_es, titulo_en, subtitulo_es, subtitulo_en, texto_es, texto_en, extra_json)
                      VALUES ('reviews', 1, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                'Lo que dicen nuestros clientes',
                'What our clients say',
                'Estamos muy orgullosos de cuidar a nuestros clientes',
                'We are very proud of taking care of our clients',
                'Cada aventura que organizamos termina con una sonrisa. Estas son algunas de las experiencias que nuestros viajeros comparten con el mundo después de recorrer el Perú con nosotros.',
                'Every adventure we organize ends with a smile. These are some of the experiences our travelers share with the world after exploring Peru with us.',
                json_encode(resenas_valores_default(), JSON_UNESCAPED_UNICODE),
            ]);
    }

    // 4. Limpieza de reseñas de ejemplo heredadas (una sola vez, idempotente)
    $db->exec("DELETE FROM resenas WHERE fuente = '' OR fuente IS NULL");
}

/**
 * Devuelve la configuración y las reseñas listas para la vista.
 * @return array ['config' => fila seccion, 'datos' => json decodificado, 'resenas' => [plataforma => [filas]]]
 */
function obtener_datos_resenas($db) {
    $config = [];
    $stmt = $db->query("SELECT * FROM secciones_index WHERE seccion = 'reviews' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt->closeCursor();

    $json_raw = $config['extra_json'] ?? '';
    $datos = json_decode($json_raw, true);
    if (!is_array($datos)) {
        $datos = resenas_valores_default();
    }
    if (!is_array($datos['plataformas'] ?? null)) {
        $datos['plataformas'] = resenas_valores_default()['plataformas'];
    }

    $resenas = ['tripadvisor' => [], 'google' => [], 'trustpilot' => []];
    $stmt = $db->query("SELECT * FROM resenas WHERE activo = 1 ORDER BY plataforma ASC, orden ASC, id ASC");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resenas[$r['plataforma']][] = $r;
    }
    $stmt->closeCursor();

    return ['config' => $config, 'datos' => $datos, 'resenas' => $resenas];
}

/**
 * Renderiza la valoración visual según la plataforma
 * (círculos verdes TripAdvisor, estrellas doradas Google, estrellas verdes Trustpilot).
 */
function render_valoracion_resena($plataforma, $total = 5) {
    $icono = '';
    $clase = '';
    if ($plataforma === 'tripadvisor') {
        $icono = '<i class="fas fa-circle"></i>';
        $clase = 'rv-dot';
    } elseif ($plataforma === 'trustpilot') {
        $icono = '<i class="fas fa-star"></i>';
        $clase = 'rv-star-tp';
    } else {
        $icono = '<i class="fas fa-star"></i>';
        $clase = 'rv-star-g';
    }
    $html = '<span class="rv-rating ' . $clase . '">';
    for ($i = 0; $i < $total; $i++) {
        $html .= $icono;
    }
    return $html . '</span>';
}

/**
 * Petición HTTP con fallback: file_get_contents → curl.
 */
function resenas_http_get($url) {
    if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'timeout' => 10,
            'header'  => "User-Agent: IntiPath/1.0\r\nAccept: text/html\r\n",
        ]]);
        $r = @file_get_contents($url, false, $ctx);
        if ($r !== false) return $r;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT       => 10,
            CURLOPT_USERAGENT     => 'IntiPath/1.0',
            CURLOPT_HTTPHEADER    => ['Accept: text/html'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($r !== false && $code >= 200 && $code < 300) return $r;
    }
    return false;
}

/**
 * Convierte timestamp unix a fecha en español: "12 de agosto de 2026".
 */
function resenas_fecha_desde_ts($ts) {
    $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
              7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
    $d = getdate((int)$ts);
    return $d['mday'] . ' de ' . $meses[$d['mon']] . ' de ' . $d['year'];
}

/**
 * Devuelve un color determinístico de la paleta según el nombre del autor.
 */
function resenas_color_por_autor($autor, $paleta = null) {
    $paleta = $paleta ?: ['#0f9b9e','#15305D','#E8AC18','#27ae60','#0ea5e9','#7c3aed','#16a34a','#dc2626','#ea580c'];
    $idx = abs(crc32($autor)) % count($paleta);
    return $paleta[$idx];
}

/**
 * Genera un título a partir del texto si el widget no lo trae.
 */
function resenas_titulo_automatico($texto) {
    $limpio = preg_replace('/\s+/', ' ', trim($texto));
    if (mb_strlen($limpio) <= 50) return $limpio;
    $palabras = explode(' ', $limpio);
    $titulo = '';
    foreach ($palabras as $p) {
        if (mb_strlen($titulo . ' ' . $p) > 45) break;
        $titulo = $titulo ? $titulo . ' ' . $p : $p;
    }
    return $titulo . '...';
}

/**
 * Descarga reseñas desde el widget Trustindex y las upsert en la tabla resenas.
 * @return array ['ok'=>bool, 'importadas'=>int, 'actualizadas'=>int, 'error'=>string]
 */
function sincronizar_resenas_trustindex($db) {
    $widget_id = 'ac84f2379a28094a2e76516e3bf';
    $p0 = substr($widget_id, 0, 2);
    $url = "https://cdn.trustindex.io/widgets/{$p0}/{$widget_id}/content.html";

    $html = resenas_http_get($url);
    if ($html === false) {
        return ['ok' => false, 'importadas' => 0, 'actualizadas' => 0, 'error' => 'No se pudo conectar al widget Trustindex. Verifica la suscripción.'];
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1, UTF-8'));
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $items = $xpath->query('//div[contains(@class,"ti-review-item")]');
    if ($items === false || $items->length === 0) {
        return ['ok' => false, 'importadas' => 0, 'actualizadas' => 0, 'error' => 'El widget no devolvió reseñas (¿suscripción vencida?).'];
    }

    $importadas = 0;
    $actualizadas = 0;

    $stmt_check = $db->prepare("SELECT id FROM resenas WHERE trustindex_id = ? LIMIT 1");
    $stmt_ins   = $db->prepare("INSERT INTO resenas (plataforma, autor, fecha, titulo, texto, link, color_avatar, orden, activo, trustindex_id, fuente)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'trustindex')");
    $stmt_upd   = $db->prepare("UPDATE resenas SET autor=?, fecha=?, titulo=?, texto=?, link=?, color_avatar=? WHERE trustindex_id=?");

    $orden = ['tripadvisor' => 0, 'google' => 0, 'trustpilot' => 0];
    foreach ($orden as $p => $_) {
        $s = $db->query("SELECT COALESCE(MAX(orden),0) FROM resenas WHERE plataforma = '{$p}' AND fuente = 'manual'");
        $orden[$p] = (int)$s->fetchColumn();
        $s->closeCursor();
    }

    $paleta = null;
    $row_cfg = $db->query("SELECT extra_json FROM secciones_index WHERE seccion = 'reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row_cfg) {
        $cfg = json_decode($row_cfg['extra_json'], true);
        if (!empty($cfg['paleta_colores']) && is_array($cfg['paleta_colores'])) {
            $paleta = $cfg['paleta_colores'];
        }
    }

    $place_id_google = null;

    for ($i = 0; $i < $items->length; $i++) {
        $item = $items->item($i);
        $cls = $item->getAttribute('class');
        $plataforma = 'google';
        if (strpos($cls, 'source-Tripadvisor') !== false)  $plataforma = 'tripadvisor';
        elseif (strpos($cls, 'source-Trustpilot') !== false) $plataforma = 'trustpilot';

        $ti_id = $item->getAttribute('data-id');
        if (empty($ti_id)) continue;

        $link = $item->getAttribute('data-platform-page-url');
        $ts   = (int)$item->getAttribute('data-time');

        $n_nodes = $xpath->query('.//div[contains(@class,"ti-name")]', $item);
        $autor = ($n_nodes && $n_nodes->length > 0) ? trim($n_nodes->item(0)->textContent) : '';

        $fecha = $ts > 0 ? resenas_fecha_desde_ts($ts) : '';
        if (empty($fecha)) {
            $d_nodes = $xpath->query('.//div[contains(@class,"ti-date")]', $item);
            if ($d_nodes && $d_nodes->length > 0) $fecha = trim($d_nodes->item(0)->textContent);
        }

        $titulo = '';
        $c_nodes = $xpath->query('.//div[contains(@class,"ti-review-content")]', $item);
        $texto = ($c_nodes && $c_nodes->length > 0) ? trim($c_nodes->item(0)->textContent) : '';
        $str_nodes = $xpath->query('.//div[contains(@class,"ti-review-content")]/strong', $item);
        if ($str_nodes && $str_nodes->length > 0) {
            $titulo = trim($str_nodes->item(0)->textContent);
            $texto  = str_replace($titulo, '', $texto);
            $texto  = preg_replace('/^\s+/', '', $texto);
        }
        if (empty($titulo)) {
            $titulo = resenas_titulo_automatico($texto);
        }
        $titulo = preg_replace('/^[^a-zA-ZáéíóúñÁÉÍÓÚÑ¡¿]+/u', '', $titulo);
        $titulo = preg_replace('/[^\p{L}\p{N}\s]+$/u', '', $titulo);
        $titulo = preg_replace('/\s{2,}/u', ' ', $titulo);
        if (mb_strlen($titulo) > 60) {
            $titulo = mb_substr($titulo, 0, 57) . '...';
        }
        $texto = preg_replace('/^[^a-zA-ZáéíóúñÁÉÍÓÚÑ¡¿]+/u', '', $texto);
        $color = resenas_color_por_autor($autor, $paleta);

        $stmt_check->execute([$ti_id]);
        $existe = $stmt_check->fetch();
        $stmt_check->closeCursor();

        if ($existe) {
            $stmt_upd->execute([$autor, $fecha, $titulo, $texto, $link, $color, $ti_id]);
            $actualizadas++;
        } else {
            $orden[$plataforma]++;
            $stmt_ins->execute([$plataforma, $autor, $fecha, $titulo, $texto, $link, $color, $orden[$plataforma], $ti_id]);
            $importadas++;
        }

        if ($place_id_google === null && strpos($link, 'query_place_id=') !== false) {
            if (preg_match('/query_place_id=([A-Za-z0-9_-]+)/', $link, $m)) {
                $place_id_google = $m[1];
            }
        }
    }

    if ($place_id_google) {
        $row = $db->query("SELECT extra_json FROM secciones_index WHERE seccion = 'reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $j = json_decode($row['extra_json'], true);
            if (is_array($j) && isset($j['plataformas']['google'])) {
                $j['plataformas']['google']['url'] = "https://www.google.com/maps/search/?api=1&query_place_id={$place_id_google}";
                $j['ultima_sync'] = date('Y-m-d H:i:s');
                $db->prepare("UPDATE secciones_index SET extra_json = ? WHERE seccion = 'reviews'")
                   ->execute([json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            }
        }
    } else {
        $row = $db->query("SELECT extra_json FROM secciones_index WHERE seccion = 'reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $j = json_decode($row['extra_json'], true);
            if (is_array($j)) {
                $j['ultima_sync'] = date('Y-m-d H:i:s');
                $db->prepare("UPDATE secciones_index SET extra_json = ? WHERE seccion = 'reviews'")
                   ->execute([json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    return ['ok' => true, 'importadas' => $importadas, 'actualizadas' => $actualizadas, 'error' => ''];
}

/**
 * Devuelve la fecha de última sync o null.
 */
function obtener_ultima_sync($db) {
    $row = $db->query("SELECT extra_json FROM secciones_index WHERE seccion = 'reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $j = json_decode($row['extra_json'], true);
    return $j['ultima_sync'] ?? null;
}

/**
 * Sincroniza si la última sync fue hace >X horas (auto-reparador).
 * Llamar desde index.php. Retorna true si sincronizó, false si no hizo falta.
 */
function verificar_sync_automatico($db) {
    $ultima = obtener_ultima_sync($db);
    $horas = 6;
    $row_cfg = $db->query("SELECT extra_json FROM secciones_index WHERE seccion = 'reviews' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row_cfg) {
        $cfg = json_decode($row_cfg['extra_json'], true);
        $horas = max(1, min(72, (int)($cfg['sync_intervalo_horas'] ?? 6)));
    }
    $intervalo = $horas * 3600;
    if ($ultima !== null) {
        $ts_ultima = strtotime($ultima);
        if ($ts_ultima !== false && (time() - $ts_ultima) < $intervalo) {
            return false;
        }
    }
    $resultado = sincronizar_resenas_trustindex($db);
    return $resultado['ok'];
}