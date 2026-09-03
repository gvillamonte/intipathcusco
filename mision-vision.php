<?php
/**
 * ARCHIVO: mision-vision.php
 * DESCRIPCIÓN: Página pública de Identidad Corporativa para IntiPath Tours.
 */

// 1. Errores visibles (Solo para desarrollo, quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Conexión a la BD
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM pagina_nosotros WHERE id = 1");
    $stmt->execute();
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback por seguridad si no hay datos
    if (!$info) {
        $info = [
            'politicas_intro' => 'Comprometidos con la excelencia en el servicio turístico en Cusco.',
            'mision' => 'Nuestra misión es brindar experiencias inolvidables...',
            'vision' => 'Ser la agencia líder en turismo sostenible...',
            'valores' => "Honestidad\nRespeto\nCalidad\nSostenibilidad",
            'imagen_principal' => 'banner_default.jpg'
        ];
    }
} catch (Exception $e) {
    die("Error al cargar la identidad: " . $e->getMessage());
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/nosotros.css?v=<?= time(); ?>">

<div id="mision-vision-page">

    <section class="ip-nosotros-hero" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/<?= $info['imagen_principal'] ?>');">
        <div class="ip-nosotros-caption">
            <h1 class="ip-nosotros-title">Nuestra <span>Identidad</span></h1>
            <p class="ip-nosotros-subtitle">Misión, Visión y Valores Éticos</p>
        </div>
    </section>

    <section class="ip-valores-section">
        <div class="ip-valores-container">
            
            <div class="ip-valores-header">
                <div class="ip-valores-decorator"></div>
                <h2 class="ip-valores-main-title">Políticas de la <span>Empresa</span></h2>
                <p class="ip-valores-intro">
                    <?= nl2br(htmlspecialchars($info['politicas_intro'] ?? '')) ?>
                </p>
            </div>

            <div class="ip-valores-grid">
                <div class="ip-valores-content">
                    
                    <div class="ip-valor-item">
                        <div class="ip-valor-icon-box">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="ip-valor-text">
                            <h3>NUESTRA MISIÓN</h3>
                            <p><?= nl2br(htmlspecialchars($info['mision'] ?? '')) ?></p>
                        </div>
                    </div>

                    <div class="ip-valor-item">
                        <div class="ip-valor-icon-box">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="ip-valor-text">
                            <h3>NUESTRA VISIÓN</h3>
                            <p><?= nl2br(htmlspecialchars($info['vision'] ?? '')) ?></p>
                        </div>
                    </div>

                </div>

                <div class="ip-valores-sidebar">
                    <div class="ip-sidebar-card">
                        <h3 class="ip-sidebar-title">VALORES ÉTICOS</h3>
                        <ul class="ip-valores-list">
                            <?php 
                            $texto_valores = (string)($info['valores'] ?? '');
                            $lineas = explode("\n", $texto_valores);
                            foreach ($lineas as $linea):
                                $valor = trim($linea);
                                if (!empty($valor)): ?>
                                    <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($valor) ?></li>
                                <?php endif; 
                            endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ip-stone-cta">
        <div class="ip-stone-overlay">
            <div class="ip-stone-content-box">
                <h2 class="ip-stone-title">¿Listo para vivir la experiencia IntiPath?</h2>
                <a href="contacto.php" class="ip-stone-btn">Contáctanos ahora</a>
            </div>
        </div>
    </section>

</div>

<?php include 'includes/footer.php'; ?>