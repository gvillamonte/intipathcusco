<?php
include 'includes/header.php';
require_once 'config/database.php';
$db = (new Database())->getConnection();

$page = $db->query("SELECT * FROM reservas_info WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$idioma = $_SESSION['lang'] ?? 'es';

$btit = ($idioma == 'en') ? ($page['banner_titulo_en'] ?? 'Book your trip') : ($page['banner_titulo'] ?? 'Reserva tu viaje');
$bsub = ($idioma == 'en') ? ($page['banner_subtitulo_en'] ?? '') : ($page['banner_subtitulo'] ?? '');

// Tarjetas
$t1tit = ($idioma == 'en') ? ($page['tarjeta1_titulo_en'] ?? '') : ($page['tarjeta1_titulo'] ?? '');
$t1enl = $page['tarjeta1_enlace'] ?? '';
$t1img = $page['tarjeta1_imagen'] ?? '';

$t2tit = ($idioma == 'en') ? ($page['tarjeta2_titulo_en'] ?? '') : ($page['tarjeta2_titulo'] ?? '');
$t2enl = $page['tarjeta2_enlace'] ?? '';
$t2img = $page['tarjeta2_imagen'] ?? '';

$t3tit = ($idioma == 'en') ? ($page['tarjeta3_titulo_en'] ?? '') : ($page['tarjeta3_titulo'] ?? '');
$t3enl = $page['tarjeta3_enlace'] ?? '';
$t3img = $page['tarjeta3_imagen'] ?? '';

$t4tit = ($idioma == 'en') ? ($page['tarjeta4_titulo_en'] ?? '') : ($page['tarjeta4_titulo'] ?? '');
$t4enl = $page['tarjeta4_enlace'] ?? '';
$t4img = $page['tarjeta4_imagen'] ?? '';

// Sección motivacional
$mtit = ($idioma == 'en') ? ($page['motiva_titulo_en'] ?? '') : ($page['motiva_titulo'] ?? '');
$mtxt = ($idioma == 'en') ? ($page['motiva_texto_en'] ?? '') : ($page['motiva_texto'] ?? '');
$mcta = ($idioma == 'en') ? ($page['motiva_cta_en'] ?? '') : ($page['motiva_cta'] ?? '');
$mfondo = $page['motiva_fondo'] ?? '';

// Estilos
$color_titulo = $page['color_titulo'] ?? '#0f9b9e';
$tamano_titulo = $page['tamano_titulo'] ?? '3rem';
$fuente_titulo = $page['fuente_titulo'] ?? 'Poppins';
$color_texto = $page['color_texto'] ?? '#444444';
$tamano_texto = $page['tamano_texto'] ?? '1.1rem';
$fuente_texto = $page['fuente_texto'] ?? 'Poppins';
?>

<style>
    :root { --esmeralda: #0f9b9e; --limon: #c6d544; --dark: #fefefe; }
    
    .hero-reservas {
        height: 60vh;
        min-height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('assets/img/reservas-info/<?= $page["banner_imagen"] ?? "banner.jpg" ?>');
        background-size: cover;
        background-position: center;
    }
    .hero-reservas h1 {
        font-size: <?= $tamano_titulo ?>;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0;
        line-height: 1.1;
        text-shadow: 2px 4px 15px rgba(0,0,0,0.5);
        font-family: <?= $fuente_titulo ?>;
        color: <?= $color_titulo ?>;
    }
    .hero-reservas .sub-banner {
        font-size: 1.3rem;
        font-weight: 300;
        text-transform: uppercase;
        letter-spacing: 6px;
        margin-top: 15px;
        color: #f1f1f1;
    }
    
    .reservas-wrapper {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }
    
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-bottom: 80px;
    }
    
    .reserva-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    .reserva-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    .reserva-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .reserva-card-content {
        padding: 25px;
        text-align: center;
    }
    .reserva-card h3 {
        font-family: <?= $fuente_titulo ?>;
        color: <?= $color_titulo ?>;
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    .reserva-card a {
        display: inline-block;
        padding: 12px 25px;
        background: var(--esmeralda);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
        transition: 0.3s;
    }
    .reserva-card a:hover {
        background: var(--limon);
        color: var(--dark);
    }
    
    .motiva-section {
        padding: 100px 20px;
        text-align: center;
        color: #fff;
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/img/reservas-info/<?= $mfondo ?: "motiva.jpg" ?>');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }
    .motiva-section h2 {
        font-family: <?= $fuente_titulo ?>;
        font-size: <?= $tamano_titulo ?>;
        color: <?= $color_titulo ?>;
        margin-bottom: 30px;
    }
    .motiva-section p {
        font-family: <?= $fuente_texto ?>;
        font-size: <?= $tamano_texto ?>;
        max-width: 700px;
        margin: 0 auto 40px;
        line-height: 1.8;
        color: #eee;
    }
    .motiva-cta {
        display: inline-block;
        padding: 18px 40px;
        background: var(--limon);
        color: var(--dark);
        text-decoration: none;
        border-radius: 50px;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 1.1rem;
        transition: 0.3s;
    }
    .motiva-cta:hover {
        background: var(--esmeralda);
        color: #fff;
        transform: scale(1.05);
    }
    
    @media (max-width: 992px) {
        .cards-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .cards-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Banner -->
<section class="hero-reservas">
    <div>
        <h1><?= $btit ?></h1>
        <div class="sub-banner"><?= $bsub ?></div>
    </div>
</section>

<!-- Tarjetas -->
<section class="reservas-wrapper">
    <div class="cards-grid">
        <!-- Tarjeta 1 -->
        <div class="reserva-card">
            <img src="assets/img/reservas-info/<?= $t1img ?: 'default.jpg' ?>" alt="<?= $t1tit ?>" loading="lazy">
            <div class="reserva-card-content">
                <h3><?= $t1tit ?></h3>
                <a href="<?= $t1enl ?>"><?= $idioma == 'en' ? 'Read more' : 'Leer más' ?></a>
            </div>
        </div>
        
        <!-- Tarjeta 2 -->
        <div class="reserva-card">
            <img src="assets/img/reservas-info/<?= $t2img ?: 'default.jpg' ?>" alt="<?= $t2tit ?>" loading="lazy">
            <div class="reserva-card-content">
                <h3><?= $t2tit ?></h3>
                <a href="<?= $t2enl ?>"><?= $idioma == 'en' ? 'Read more' : 'Leer más' ?></a>
            </div>
        </div>
        
        <!-- Tarjeta 3 -->
        <div class="reserva-card">
            <img src="assets/img/reservas-info/<?= $t3img ?: 'default.jpg' ?>" alt="<?= $t3tit ?>" loading="lazy">
            <div class="reserva-card-content">
                <h3><?= $t3tit ?></h3>
                <a href="<?= $t3enl ?>"><?= $idioma == 'en' ? 'Read more' : 'Leer más' ?></a>
            </div>
        </div>
        
        <!-- Tarjeta 4 -->
        <div class="reserva-card">
            <img src="assets/img/reservas-info/<?= $t4img ?: 'default.jpg' ?>" alt="<?= $t4tit ?>" loading="lazy">
            <div class="reserva-card-content">
                <h3><?= $t4tit ?></h3>
                <a href="<?= $t4enl ?>"><?= $idioma == 'en' ? 'Read more' : 'Leer más' ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Sección Motivacional -->
<section class="motiva-section">
    <h2><?= $mtit ?></h2>
    <p><?= $mtxt ?></p>
    <a href="contacto.php" class="motiva-cta"><?= $mcta ?></a>
</section>

<?php include 'includes/footer.php'; ?>