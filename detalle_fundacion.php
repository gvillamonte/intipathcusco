<?php
// detalle_fundacion.php — Detalle público de un proyecto de fundación
require_once 'config/database.php';
require_once 'includes/header.php';

$db = (new Database())->getConnection();

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: fundacion.php");
    exit;
}

$stmt = $db->prepare("SELECT fp.*, f.titulo AS fund_titulo, f.titulo_en AS fund_titulo_en, f.hero_imagen AS fund_hero, f.logo AS fund_logo FROM fundacion_proyectos fp CROSS JOIN fundacion f WHERE fp.slug_pagina = ? AND fp.activo = 1 AND f.id = 1 LIMIT 1");
$stmt->execute([$slug]);
$proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$proyecto) {
    header("Location: fundacion.php");
    exit;
}

$p_titulo = ($is_en && !empty($proyecto['titulo_en'])) ? $proyecto['titulo_en'] : $proyecto['titulo'];
$p_subtitulo = ($is_en && !empty($proyecto['subtitulo_en'])) ? $proyecto['subtitulo_en'] : $proyecto['subtitulo'];
$p_desc_corta = ($is_en && !empty($proyecto['descripcion_corta_en'])) ? $proyecto['descripcion_corta_en'] : $proyecto['descripcion_corta'];
$p_desc = ($is_en && !empty($proyecto['descripcion_en'])) ? $proyecto['descripcion_en'] : $proyecto['descripcion'];
$img_base = '/assets/img/fundacion/';
$p_img = $img_base . ($proyecto['imagen'] ?: 'default-proyecto.webp');
$hero_img = $img_base . ($proyecto['fund_hero'] ?: 'default-hero.webp');
$logo_img = $img_base . ($proyecto['fund_logo'] ?: 'default-logo.webp');
$fund_name = ($is_en && !empty($proyecto['fund_titulo_en'])) ? $proyecto['fund_titulo_en'] : $proyecto['fund_titulo'];

$stmt_gal = $db->prepare("SELECT * FROM fundacion_proyecto_imagenes WHERE proyecto_id=? ORDER BY orden ASC, id ASC");
$stmt_gal->execute([$proyecto['id']]);
$galeria = $stmt_gal->fetchAll(PDO::FETCH_ASSOC);
$stmt_gal->closeCursor();

function renderFD($txt) {
    $txt = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $txt);
    $txt = preg_replace('/_(.*?)_/', '<em>$1</em>', $txt);
    $txt = preg_replace_callback('/\[img:(.*?)\]/', function ($m) {
        $src = '/assets/img/fundacion/' . trim($m[1]);
        return '<div class="my-md overflow-hidden rounded-lg border border-outline-variant"><img src="' . $src . '" alt="" loading="lazy" class="w-full h-auto"></div>';
    }, $txt);
    $lineas = explode("\n", $txt);
    $html = '';
    $en_lista = false;
    foreach ($lineas as $linea) {
        $l = trim($linea);
        if (empty($l)) {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            continue;
        }
        if (strpos($l, '##') === 0) {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<h3 class="font-headline-sm text-headline-sm text-primary mt-lg mb-sm">' . trim(substr($l, 2)) . '</h3>';
        } elseif (strpos($l, '#') === 0) {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<h2 class="font-display-md text-display-md text-primary mb-md">' . trim(substr($l, 1)) . '</h2>';
        } elseif (strpos($l, '-') === 0) {
            if (!$en_lista) { $html .= '<ul class="flex flex-col gap-sm mb-md">'; $en_lista = true; }
            $html .= '<li class="flex items-start gap-sm"><span class="material-symbols-outlined text-primary mt-[2px]" style="font-variation-settings: &quot;FILL&quot; 1;">check_circle</span><span class="text-body-md text-on-surface-variant">' . trim(substr($l, 1)) . '</span></li>';
        } else {
            if ($en_lista) { $html .= '</ul>'; $en_lista = false; }
            $html .= '<p class="mb-sm">' . $l . '</p>';
        }
    }
    if ($en_lista) $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title><?= htmlspecialchars($p_titulo) ?> | Fundación INTI PATH TOURS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "secondary": "#565e74",
                "background": "#f8f9ff",
                "on-primary": "#ffffff",
                "surface-bright": "#f8f9ff",
                "surface-container": "#e5eeff",
                "on-primary-container": "#f4fffc",
                "on-surface-variant": "#3d4947",
                "primary": "#00685f",
                "on-background": "#0b1c30",
                "on-surface": "#0b1c30",
                "surface": "#f8f9ff",
                "outline-variant": "#bcc9c6",
                "primary-container": "#008378",
                "surface-container-low": "#eff4ff",
                "surface-container-lowest": "#ffffff",
                "outline": "#6d7a77"
            },
            fontFamily: {
                "label-sm": ["Hanken Grotesk"],
                "display-lg": ["Hanken Grotesk"],
                "headline-sm": ["Hanken Grotesk"],
                "title-md": ["Hanken Grotesk"],
                "body-lg": ["Hanken Grotesk"],
                "body-md": ["Hanken Grotesk"],
                "display-md": ["Hanken Grotesk"],
                "label-md": ["Hanken Grotesk"]
            },
            fontSize: {
                "label-sm": ["12px", { lineHeight: "16px", fontWeight: "500" }],
                "display-lg": ["30px", { lineHeight: "38px", letterSpacing: "-0.02em", fontWeight: "700" }],
                "headline-sm": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                "display-md": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "700" }],
                "title-md": ["16px", { lineHeight: "24px", fontWeight: "600" }],
                "body-lg": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                "label-md": ["13px", { lineHeight: "18px", letterSpacing: "0.01em", fontWeight: "600" }]
            },
            spacing: {
                "sm": "8px",
                "xl": "32px",
                "md": "16px",
                "lg": "24px",
                "xs": "4px"
            },
            borderRadius: {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            }
        }
    }
}
</script>
<style>
    body { font-family: 'Hanken Grotesk', sans-serif; }
</style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col">

<!-- Hero Section -->
<section class="relative w-full h-[400px] md:h-[500px] flex items-center justify-center">
    <div class="absolute inset-0 z-0">
        <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?= $hero_img ?>');"></div>
        <div class="absolute inset-0 bg-black/40"></div>
    </div>
    <div class="relative z-10 text-center px-lg max-w-4xl">
        <h1 class="font-display-lg text-display-lg md:text-[48px] md:leading-[56px] text-white mb-md"><?= htmlspecialchars($p_titulo) ?></h1>
        <?php if (!empty($p_subtitulo)): ?>
        <p class="font-headline-sm text-headline-sm text-surface-container-low"><?= htmlspecialchars($p_subtitulo) ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Main Content Area -->
<main class="flex-grow max-w-[1440px] mx-auto w-full px-lg py-xl grid grid-cols-1 lg:grid-cols-12 gap-xl">

    <!-- Content Column (8 cols) -->
    <div class="lg:col-span-8 flex flex-col gap-xl">

        <?php if (!empty($p_desc)): ?>
        <!-- Descripción larga -->
        <section class="bg-surface rounded-lg p-lg border border-outline-variant">
            <h2 class="font-display-md text-display-md text-primary mb-md"><?= htmlspecialchars($p_titulo) ?> - <span class="text-secondary"><?= htmlspecialchars($fund_name) ?></span></h2>
            <div class="prose max-w-none text-body-lg text-on-surface">
                <?= renderFD($p_desc) ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Galería de imágenes -->
        <?php if (!empty($galeria)): ?>
        <section class="flex flex-col gap-xl">
            <div class="bg-surface rounded-lg p-lg border border-outline-variant">
                <h3 class="font-headline-sm text-headline-sm text-primary border-b border-outline-variant pb-sm mb-md"><?= $is_en ? 'Gallery' : 'Galería de Imágenes' ?></h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-sm">
                    <?php foreach (array_slice($galeria, 0, 8) as $idx => $gi): ?>
                    <?php if ($idx === 0): ?>
                    <div class="col-span-2 md:col-span-4 aspect-[21/9] overflow-hidden rounded">
                        <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?= $img_base . htmlspecialchars($gi['imagen'] ?? '') ?>');" role="img" aria-label="<?= htmlspecialchars($p_titulo) ?>"></div>
                    </div>
                    <?php else: ?>
                    <div class="aspect-video overflow-hidden rounded">
                        <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?= $img_base . htmlspecialchars($gi['imagen'] ?? '') ?>');" role="img"></div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </div>

    <!-- Sidebar Column (4 cols) -->
    <aside class="lg:col-span-4 flex flex-col gap-lg">
        <div class="sticky top-[88px] bg-surface rounded-lg p-lg border border-outline-variant shadow-sm flex flex-col items-center text-center">
            <div class="w-24 h-24 rounded-full overflow-hidden mb-md border-2 border-primary-container">
                <img alt="<?= htmlspecialchars($fund_name) ?>" class="w-full h-full object-cover" src="<?= $logo_img ?>">
            </div>
            <h4 class="font-title-md text-title-md text-on-surface mb-xs"><?= $is_en ? 'Your Specialist' : 'Tu Especialista' ?></h4>
            <p class="font-body-md text-body-md text-on-surface-variant mb-md"><?= $is_en ? 'They will take the time to understand your preferences and prepare an itinerary that fits your budget.' : 'Te dará el tiempo necesario para entender tus preferencias y preparar un itinerario que se adecúe a tu presupuesto.' ?></p>
            <div class="flex flex-col gap-sm w-full">
                <a href="tel:+51974727031" class="w-full py-sm px-md rounded-lg bg-surface-container-low text-primary border border-primary font-label-md text-label-md hover:bg-surface-container flex justify-center items-center gap-sm transition-colors">
                    <span class="material-symbols-outlined text-[18px]">call</span> Llama al +51 974 727 031
                </a>
                <a href="https://wa.me/51974727031" target="_blank" class="w-full py-sm px-md rounded-lg bg-surface-container-low text-primary border border-primary font-label-md text-label-md hover:bg-surface-container flex justify-center items-center gap-sm transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chat</span> <?= $is_en ? 'Chat with a specialist' : 'Chatea con un especialista' ?>
                </a>
                <a href="/fundacion.php" class="w-full py-sm px-md rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container flex justify-center items-center transition-colors">
                    <?= $is_en ? 'CONTACT NOW!' : 'CONSULTE AHORA!' ?>
                </a>
            </div>
        </div>
    </aside>

</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
