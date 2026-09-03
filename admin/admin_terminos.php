<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('terminos');

require_once '../config/database.php';
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("UPDATE terminos_condiciones SET contenido = ? WHERE id = 1");
    $stmt->execute([$_POST['contenido']]);
    $mensaje = "¡Actualizado con éxito!";
}

$res = $db->query("SELECT contenido FROM terminos_condiciones WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Términos | Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <h1>Administrar Términos y Condiciones</h1>
            
            <?php if(isset($mensaje)): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?= $mensaje ?></div>
            <?php endif; ?>

            <div class="admin-contenedor">
                <form method="POST">
                    <div style="margin-bottom: 20px; background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 5px solid #ffc107;">
                        <strong>Guía de Formato:</strong><br>
                        <code># Título Principal</code><br>
                        <code>## Subtítulo de sección</code><br>
                        <code>- Ítem con Check (Viñeta)</code>
                    </div>

                    <textarea name="contenido" style="width: 100%; height: 500px; padding: 20px; border-radius: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 14px;" placeholder="Escribe aquí los términos..."><?= htmlspecialchars($res['contenido']) ?></textarea>
                    
                    <button type="submit" class="btn-save" style="background: #15305D; color: #fff; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; margin-top: 20px; font-weight: bold; width: 100%;">
                        <i class="fas fa-save"></i> GUARDAR CAMBIOS
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>