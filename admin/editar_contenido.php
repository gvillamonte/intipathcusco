<?php
// admin/editar_contenido.php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

$archivo = $_GET['file'] ?? '';
$ruta = "../" . basename($archivo);
$contenido_editable = "";

// 1. LEER EL ARCHIVO
if (!empty($archivo) && file_exists($ruta)) {
    $codigo_completo = file_get_contents($ruta);
    // Buscamos lo que esté dentro de <div class="contenido-editable">...</div>
    preg_match('/<div class="contenido-editable">(.*?)<\/div>/s', $codigo_completo, $matches);
    $contenido_editable = $matches[1] ?? "Escribe el contenido aquí...";
}

// 2. GUARDAR CAMBIOS
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuevo_texto = $_POST['texto_web'];
    $original = file_get_contents($ruta);
    
    // Reemplazo exacto usando Expresiones Regulares
    $patron = '/<div class="contenido-editable">.*?<\/div>/s';
    $reemplazo = '<div class="contenido-editable">' . $nuevo_texto . '</div>';
    $resultado = preg_replace($patron, $reemplazo, $original);
    
    file_put_contents($ruta, $resultado);
    header("Location: editar_contenido.php?file=$archivo&saved=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor Visual - IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        <main class="main-content">
            <h1>Editando: <span style="color:#E8AC18;"><?php echo $archivo; ?></span></h1>
            <section class="admin-contenedor">
                <form method="POST">
                    <textarea id="editor_inti" name="texto_web" style="height: 500px;">
                        <?php echo htmlspecialchars($contenido_editable); ?>
                    </textarea>
                    <button type="submit" class="btn-login" style="margin-top:20px;">💾 Guardar Cambios en el PHP</button>
                    <a href="admin_navegacion.php" style="margin-left:20px; color:#666;">Volver</a>
                </form>
            </section>
        </main>
    </div>
    <script>
        tinymce.init({
            selector: '#editor_inti',
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | bold italic forecolor | alignleft aligncenter alignright | bullist numlist | removeformat | code',
            language: 'es',
            branding: false, // Quita el logo de TinyMCE
            promotion: false // Quita el botón de "Upgrade"
        });
    </script>
</body>
</html>