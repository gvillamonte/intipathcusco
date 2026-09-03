<?php 
include 'includes/header.php'; 
require_once 'config/database.php';

$db = (new Database())->getConnection();
$res = $db->query("SELECT contenido FROM terminos_condiciones WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$texto = $res['contenido'] ?? '';

// Función para convertir texto plano en HTML
function parsearTerminos($txt) {
    $lineas = explode("\n", $txt);
    $html = "";
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;

        if (strpos($linea, '##') === 0) {
            $html .= "<h3 class='tc-subtitulo'>" . substr($linea, 2) . "</h3>";
        } elseif (strpos($linea, '#') === 0) {
            $html .= "<h2 class='tc-titulo-principal'>" . substr($linea, 1) . "</h2>";
        } elseif (strpos($linea, '-') === 0) {
            $html .= "<div class='tc-check-item'><i class='fas fa-check-circle'></i> <span>" . substr($linea, 1) . "</span></div>";
        } else {
            $html .= "<p class='tc-parrafo'>$linea</p>";
        }
    }
    return $html;
}
?>

<style>
    .tc-hero {
        background: linear-gradient(rgba(21,48,93,0.7), rgba(21,48,93,0.7)), url('assets/img/hero_tours.jpg');
        background-size: cover; background-position: center; height: 300px;
        display: flex; justify-content: center; align-items: center; color: #fff; margin-top: 80px;
    }
    .tc-container { max-width: 900px; margin: 50px auto; background: #fff; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eee; }
    .tc-titulo-principal { color: #15305D; font-size: 2rem; border-bottom: 3px solid #f39c12; padding-bottom: 10px; margin-bottom: 30px; text-align: center; }
    .tc-subtitulo { color: #15305D; font-size: 1.3rem; margin-top: 30px; margin-bottom: 15px; font-weight: 800; }
    .tc-parrafo { color: #555; line-height: 1.8; margin-bottom: 15px; font-size: 1rem; }
    .tc-check-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; color: #444; }
    .tc-check-item i { color: #f39c12; margin-top: 5px; }
</style>

<section class="tc-hero">
    <h1 style="font-size: 3rem; font-weight: 800;">POLÍTICAS DE VIAJE</h1>
</section>

<div class="tc-container">
    <?= parsearTerminos($texto) ?>
</div>

<?php include 'includes/footer.php'; ?>