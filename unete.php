<?php 
include 'includes/header.php'; 
require_once 'config/database.php';

$db = (new Database())->getConnection();

// 1. Obtener la Fila 1 (Configuración de Banner y Cuerpo de Texto Plano)
$c = $db->query("SELECT * FROM vacantes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

$titulo_hero = ($idioma == 'en') ? $c['banner_titulo_en'] : $c['banner_titulo'];
$subtitulo_hero = ($idioma == 'en') ? $c['banner_subtitulo_en'] : $c['banner_subtitulo'];
$cuerpo_plano = ($idioma == 'en') ? $c['intro_descripcion_en'] : $c['intro_descripcion'];

// 2. Obtener convocatorias abiertas
$vacantes = $db->query("SELECT * FROM vacantes WHERE id != 1 AND estado = 'Abierto' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- MOTOR DE TRADUCCIÓN DE TEXTO PLANO A COMPONENTES VISUALES CON ICONOS ---
function renderizarContenidoUnete($texto) {
    // Convertir # Título Central de Introducción
    $texto = preg_replace('/^# (.*)$/m', '<h2 class="unete-title">$1</h2>', $texto);
    
    // Convertir **Negrita** e _Subrayado_
    $texto = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $texto);
    $texto = preg_replace('/_(._?)_/', '<u style="text-decoration: underline;">$1</u>', $texto);
    
    // DETECTOR DE TARJETAS (##) CON SOPORTE DE ICONOS DINÁMICOS
    if (preg_match_all('/^## (.*)$/m', $texto, $matches, PREG_OFFSET_CAPTURE)) {
        $partes = preg_split('/^## (.*)$/m', $texto);
        $html_introduccion = nl2br(trim($partes[0]));
        
        $html_tarjetas = '<div class="valores-grid">';
        for ($i = 1; $i < count($partes); $i++) {
            $tit_tarjeta = htmlspecialchars(trim($matches[1][$i-1][0]));
            $cuerpo_tarjeta = trim($partes[$i]);
            
            // Extracción nativa de etiqueta de icono [icon:nombre]
            $icono = 'star'; 
            if (preg_match('/\[icon:(.*?)\]/', $cuerpo_tarjeta, $icon_match)) {
                $icono = trim($icon_match[1]);
                $cuerpo_tarjeta = preg_replace('/\[icon:(.*?)\]/', '', $cuerpo_tarjeta);
            }
            
            $desc_tarjeta = nl2br(trim($cuerpo_tarjeta));
            
            $html_tarjetas .= '
            <div class="valor-card">
                <div class="valor-icon"><i class="fas fa-' . htmlspecialchars($icono) . '"></i></div>
                <h4>' . $tit_tarjeta . '</h4>
                <p>' . $desc_tarjeta . '</p>
            </div>';
        }
        $html_tarjetas .= '</div>';
        return $html_introduccion . $html_tarjetas;
    }
    
    return nl2br($texto);
}

$t_unete = [
    'es' => [
        'intro_tag' => 'TRABAJA CON NOSOTROS',
        'vacantes_tit' => 'Posiciones Disponibles Actuales',
        'vacantes_sub' => 'Haz clic en la vacante de tu interés para revisar las bases o postular enviando tu CV.',
        'ubi' => 'Ubicación', 'btn_postular' => 'Postular Ahora',
        'btn_pdf' => 'Ver Requisitos (PDF)',
        'vacio' => 'Por el momento no contamos con vacantes abiertas, pero puedes dejarnos tu CV escribiéndonos a nuestro correo oficial.'
    ],
    'en' => [
        'intro_tag' => 'JOIN OUR TEAM',
        'vacantes_tit' => 'Current Job Openings',
        'vacantes_sub' => 'Click on the vacancy of your interest to review job requirements or apply by sending your CV.',
        'ubi' => 'Location', 'btn_postular' => 'Apply Now',
        'btn_pdf' => 'Job Details (PDF)',
        'vacio' => 'We currently do not have open vacancies, but you can leave us your CV by writing to our official email.'
    ]
];
$txt = $t_unete[$idioma];
$banner_unete = "assets/img/banner_unete_header.jpg?v=" . (file_exists("assets/img/banner_unete_header.jpg") ? filemtime("assets/img/banner_unete_header.jpg") : time());
?>

<style>
    :root { --inti-esmeralda: #0f9b9e; --inti-limon: #c6d544; --inti-bg-light: #f9fbfb; --inti-dark: #2d3748; }
    
    .unete-hero { background-size: cover; background-position: center; height: 450px; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; text-align: center; padding: 0 20px; margin-top: 0 !important; }
    .unete-hero h1 { font-size: 3.8rem; font-weight: 900; text-transform: uppercase; text-shadow: 2px 2px 12px rgba(0,0,0,0.85); margin: 0; }
    .unete-hero p { font-size: 1.3rem; max-width: 750px; text-shadow: 1px 1px 8px rgba(0,0,0,0.85); margin-top: 12px; }
    
    .unete-section { max-width: 1100px; margin: 80px auto; padding: 0 20px; text-align: center; color: #555; font-size: 1.15rem; line-height: 1.8; }
    .unete-tag { color: var(--inti-esmeralda); font-weight: 800; font-size: 0.9rem; letter-spacing: 2px; text-transform: uppercase; display: block; margin-bottom: 10px; }
    .unete-title { color: var(--inti-dark); font-size: 2.3rem; font-weight: 900; margin: 20px 0; }
    
    .valores-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 50px; }
    .valor-card { background: #fff; padding: 40px 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border-bottom: 5px solid var(--inti-limon); text-align: center; transition: 0.3s; }
    .valor-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(15, 155, 158, 0.1); }
    .valor-icon { font-size: 2.2rem; color: var(--inti-esmeralda); margin-bottom: 15px; }
    .valor-card h4 { color: var(--inti-dark); font-size: 1.3rem; font-weight: 800; margin: 0 0 10px 0; }
    .valor-card p { color: #666; font-size: 0.95rem; line-height: 1.6; margin: 0; }

    .vacantes-wrapper { background: var(--inti-bg-light); padding: 80px 0; border-top: 1px solid #edf2f7; }
    .vacantes-list { max-width: 950px; margin: 40px auto 0; padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }
    .vacante-item { background: #fff; padding: 25px 35px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; transition: 0.3s; border-left: 5px solid var(--inti-esmeralda); }
    .vacante-item:hover { transform: translateX(5px); box-shadow: 0 8px 25px rgba(15, 155, 158, 0.08); }
    .vacante-info h3 { color: var(--inti-esmeralda); font-size: 1.4rem; font-weight: 800; margin: 0 0 5px 0; }
    .vacante-info p { margin: 0; font-size: 0.95rem; color: #718096; }
    
    .actions-vacante { display: flex; gap: 12px; align-items: center; }
    .btn-apply { padding: 12px 25px; background: var(--inti-esmeralda); color: #fff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 0.9rem; transition: 0.3s; border: 2px solid var(--inti-esmeralda); display: inline-flex; align-items: center; gap: 5px; }
    .btn-apply:hover { background: var(--inti-limon); border-color: var(--inti-limon); color: #fff; }
    
    /* Botón Profesional de PDF Informativo */
    .btn-pdf-info { padding: 12px 22px; background: #fff; color: #e11d48; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 0.9rem; transition: 0.3s; border: 2px solid #fecdd3; display: inline-flex; align-items: center; gap: 6px; }
    .btn-pdf-info:hover { background: #fff1f2; border-color: #e11d48; color: #e11d48; }
    
    @media (max-width: 768px) { .unete-hero h1 { font-size: 2.6rem; } .unete-title { font-size: 1.8rem; } .vacante-item { flex-direction: column; text-align: center; gap: 20px; } .actions-vacante { flex-direction: column; width: 100%; } .btn-apply, .btn-pdf-info { width: 100%; justify-content: center; } }
</style>

<section class="unete-hero" data-bg-lazy="<?= $banner_unete ?>">
    <h1><?= htmlspecialchars($titulo_hero ?: 'ÚNETE A NUESTRO EQUIPO') ?></h1>
    <p><?= htmlspecialchars($subtitulo_hero ?: '') ?></p>
</section>

<section class="unete-section">
    <span class="unete-tag"><?= $txt['intro_tag'] ?></span>
    <?= renderizarContenidoUnete($cuerpo_plano) ?>
</section>

<section class="vacantes-wrapper">
    <div style="text-align: center; max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h2 class="unete-title" style="margin-bottom: 10px;"><?= $txt['vacantes_tit'] ?></h2>
        <p style="color: #666; margin: 0;"><?= $txt['vacantes_sub'] ?></p>
    </div>

    <div class="vacantes-list">
        <?php if ($vacantes): ?>
            <?php foreach ($vacantes as $v): 
                $puesto_final = ($idioma == 'en') ? $v['titulo_en'] : $v['titulo_es'];
                $ubicacion_final = ($idioma == 'en') ? $v['ubicacion_en'] : $v['ubicacion_es'];
            ?>
                <div class="vacante-item">
                    <div class="vacante-info">
                        <h3><?= htmlspecialchars($puesto_final) ?></h3>
                        <p><i class="fas fa-map-marker-alt" style="color: var(--inti-limon); margin-right: 5px;"></i> <?= $txt['ubi'] ?>: <?= htmlspecialchars($ubicacion_final) ?></p>
                    </div>
                    
                    <div class="actions-vacante">
                        <?php if (!empty($v['archivo_pdf'])): ?>
                            <a href="assets/pdf/vacantes/<?= $v['archivo_pdf'] ?>" target="_blank" class="btn-pdf-info">
                                <i class="fas fa-file-pdf"></i> <?= $txt['btn_pdf'] ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="mailto:info@intipathtours.com?subject=Postulacion%20-%20<?= urlencode($puesto_final) ?>" class="btn-apply">
                            <?= $txt['btn_postular'] ?> <i class="fas fa-chevron-right" style="font-size:0.8rem;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; background: #fff; padding: 40px; border-radius: 15px; border: 1px dashed #cbd5e1;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--inti-limon); margin-bottom: 15px;"></i>
                <p style="color: #555; font-size: 1.05rem; margin: 0; line-height: 1.6; max-width: 600px; margin: 0 auto;"><?= $txt['vacio'] ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>