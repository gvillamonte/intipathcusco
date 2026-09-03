<?php

/**
 * ARCHIVO: admin_menu_nosotros.php
 * DESCRIPCIÓN: Panel administrativo para IntiPath Tours.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('nosotros');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Obtener datos actuales
$stmt = $db->prepare("SELECT * FROM pagina_nosotros WHERE id = 1");
$stmt->execute();
$info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Nosotros | IntiPath Tours</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_menu_nosotros.css">
</head>

<body class="ip-admin-body">

    <?php include '../includes/sidebar.php'; ?>

    <main class="ip-main-content">

        <header class="ip-content-header">
            <div class="header-text">
                <h1><i class="fas fa-edit"></i> Editar Sección Nosotros</h1>
                <p>Gestiona el banner, historia y gerencia de IntiPath Tours.</p>
            </div>
            <a href="../nosotros.php" target="_blank" class="ip-btn-outline">Ver Página Web</a>
        </header>

        <form action="actualizar_nosotros.php" method="POST" enctype="multipart/form-data" class="ip-form">
            <input type="hidden" name="id" value="1">

            <section class="ip-form-section">
                <h3 class="ip-section-title">1. Banner de Bienvenida y Resumen</h3>
                <div class="ip-grid-row">
                    <div class="ip-control">
                        <label>Título Principal:</label>
                        <input type="text" name="titulo" value="<?= htmlspecialchars((string)$info['titulo']) ?>">
                    </div>
                    <div class="ip-control">
                        <label>Subtítulo:</label>
                        <input type="text" name="subtitulo" value="<?= htmlspecialchars((string)$info['subtitulo']) ?>">
                    </div>

                    <div class="ip-control ip-full">
                        <label>Resumen de Bienvenida (Texto al costado de la foto):</label>
                        <textarea name="resumen" rows="5" class="form-control"><?= htmlspecialchars((string)($info['resumen'] ?? '')) ?></textarea>
                    </div>

                    <div class="ip-control">
                        <label>Imagen del Resumen (Lateral):</label>
                        <div class="ip-preview-wrapper small-preview">
                            <?php if (!empty($info['imagen_resumen'])): ?>
                                <img src="../assets/img/<?= $info['imagen_resumen'] ?>" alt="Foto Resumen">
                            <?php else: ?>
                                <div class="ip-no-image">Sin imagen</div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="imagen_resumen">
                        <input type="hidden" name="img_actual_resumen" value="<?= $info['imagen_resumen'] ?? '' ?>">
                    </div>

                    <div class="ip-control">
                        <label>Imagen de Fondo (Hero/Banner):</label>
                        <div class="ip-preview-wrapper small-preview">
                            <img src="../assets/img/<?= $info['imagen_principal'] ?>" alt="Banner">
                        </div>
                        <input type="file" name="imagen_principal">
                        <input type="hidden" name="img_actual_principal" value="<?= $info['imagen_principal'] ?>">
                    </div>
                </div>
            </section>

            <section class="ip-form-section">
                <h3 class="ip-section-title">2. Sección de Gerencia (Invertida)</h3>
                <div class="ip-grid-row">
                    <div class="ip-control ip-full">
                        <label>Título de Sección:</label>
                        <input type="text" name="titulo_gerencia" value="<?= htmlspecialchars((string)$info['titulo_gerencia']) ?>">
                    </div>
                    <div class="ip-control">
                        <label>Contenido / Trayectoria:</label>
                        <textarea name="contenido_gerencia" rows="6"><?= htmlspecialchars((string)$info['contenido_gerencia']) ?></textarea>
                    </div>
                    <div class="ip-control">
                        <label>Frase del Gerente:</label>
                        <input type="text" name="frase_gerente" value="<?= htmlspecialchars((string)$info['frase_gerente']) ?>">

                        <label class="mt-20">Foto del Gerente Actual:</label>
                        <div class="ip-preview-wrapper">
                            <?php if (!empty($info['imagen_gerencia'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($info['imagen_gerencia']) ?>" alt="Gerente">
                            <?php else: ?>
                                <div class="ip-no-image">Sin foto actual</div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="imagen_gerencia">
                        <input type="hidden" name="img_actual_gerencia" value="<?= htmlspecialchars($info['imagen_gerencia'] ?? '') ?>">
                    </div>
                </div>
            </section>

            <section class="ip-form-section">
                <div class="ip-section-header">
                    <h3 class="ip-section-title">3. Equipo y Colaboradores</h3>
                </div>
                <div class="ip-grid-row">
                    <div class="ip-control">
                        <label for="equipo_json">Datos del Personal (Texto Plano):</label>
                        <textarea name="equipo_json" id="equipo_json" rows="12" placeholder="Ejemplo: Área: Operaciones..." class="ip-textarea-plano"><?= htmlspecialchars((string)($info['equipo_json'] ?? '')) ?></textarea>
                    </div>
                    <div class="ip-control">
                        <label>Subir Nuevas Fotos:</label>
                        <div class="ip-upload-container">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click para subir fotos nuevas</p>
                            <input type="file" name="fotos_equipo[]" multiple class="ip-input-file-multiple">
                        </div>
                        <div class="ip-gallery-preview mt-20">
                            <label><i class="fas fa-images"></i> Imágenes disponibles en /equipo/:</label>
                            <div class="ip-mini-gallery-container">
                                <?php
                                $directorio = "../assets/img/equipo/";
                                if (is_dir($directorio)) {
                                    $archivos = array_diff(scandir($directorio), array('.', '..'));
                                    foreach ($archivos as $archivo) {
                                        echo '<div class="ip-gallery-item">';
                                        echo '<img src="' . $directorio . $archivo . '" class="ip-img-tiny">';
                                        echo '<span class="ip-img-name">' . $archivo . '</span>';

                                        // Agregamos el botón aquí para que se repita con cada foto 🗑️
                                        echo '<button type="button" class="btn-delete-foto" onclick="borrarFotoEquipo(\'' . $archivo . '\', this)" style="background: none; border: none; color: #e74c3c; cursor: pointer;">';
                                        echo '<i class="fas fa-trash-alt"></i>';
                                        echo '</button>';

                                        echo '</div>';
                                    }
                                }
                                ?>

                                <button type="button" class="btn-delete-foto" onclick="borrarFotoEquipo('<?php echo $foto; ?>', this)" style="background: none; border: none; color: #e74c3c; cursor: pointer;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ip-form-section">
                <h3 class="ip-section-title">4. Identidad Corporativa (Misión, Visión y Valores)</h3>
                <div class="ip-grid-row">
                    <div class="ip-control ip-full">
                        <label><i class="fas fa-crosshairs"></i> Nuestra Misión:</label>
                        <textarea name="mision" rows="4" class="form-control"><?= htmlspecialchars((string)($info['mision'] ?? '')) ?></textarea>
                    </div>
                    <div class="ip-control ip-full">
                        <label><i class="fas fa-eye"></i> Nuestra Visión:</label>
                        <textarea name="vision" rows="4" class="form-control"><?= htmlspecialchars((string)($info['vision'] ?? '')) ?></textarea>
                    </div>
                    <div class="ip-control ip-full">
                        <label><i class="fas fa-handshake"></i> Valores y Políticas:</label>
                        <textarea name="valores" rows="5" class="form-control"><?= htmlspecialchars((string)($info['valores'] ?? '')) ?></textarea>
                    </div>
                </div>
            </section>

            <div class="ip-form-actions">
                <button type="submit" class="ip-btn-save">
                    <i class="fas fa-save"></i> GUARDAR CAMBIOS
                </button>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Limpiar URL después de guardar para evitar que la alerta se repita al recargar
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: 'Los cambios se actualizaron correctamente.',
                    confirmButtonColor: '#E8AC18',
                    timer: 2000
                }).then(() => {
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }
        });
    </script>


    <script>
        function borrarFotoEquipo(nombreArchivo, boton) {
            if (confirm("¿Estás seguro de que quieres eliminar esta foto?")) {
                const datos = new FormData();
                datos.append('archivo', nombreArchivo);

                fetch('eliminar_foto_equipo.php', {
                        method: 'POST',
                        body: datos
                    })
                    .then(respuesta => respuesta.text())
                    .then(data => {
                        console.log("Respuesta del servidor:", data);
                        if (data.trim() === "success") {
                            boton.closest('.ip-gallery-item').remove();
                        } else {
                            alert("Error: " + data);
                        }
                    }); // <-- Aquí cierra el segundo .then
            } // <-- Aquí cierra el if
        } // <-- Aquí cierra la función
    </script>

</body>

</html>