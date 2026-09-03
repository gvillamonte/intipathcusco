<?php
// ============================================================
// INICIO: LÓGICA DE ADMINISTRACIÓN DEL FOOTER
// ============================================================
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('header_footer');

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// ============================================================
// PARCHE AUTOMÁTICO: CREAR FILAS FALTANTES EN MYSQL
// ============================================================
$claves_necesarias = ['premios_logos', 'asociaciones_logos', 'logo', 'mountain_img', 'mountain_title_es', 'mountain_title_en', 'mountain_subtitle_es', 'mountain_subtitle_en'];
foreach ($claves_necesarias as $c) {
    $existe = $db->prepare("SELECT COUNT(*) FROM footer_config WHERE clave = ?");
    $existe->execute([$c]);
    if ($existe->fetchColumn() == 0) {
        $insert = $db->prepare("INSERT INTO footer_config (clave, valor) VALUES (?, '')");
        $insert->execute([$c]);
    }
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $directorio_destino = '../assets/img/certificaciones/';
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }

    // 1. Guardar los inputs normales de contacto y redes (config[])
    if (isset($_POST['config'])) {
        foreach ($_POST['config'] as $clave => $valor) {
            $stmt = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = ?");
            $stmt->execute([trim($valor), $clave]);
        }
    }


    // Función auxiliar para extraer solo los nombres de los archivos
    function extraerNombresArchivos($cadena)
    {
        $archivos = [];
        $lista = array_filter(array_map('trim', explode(',', $cadena)));
        foreach ($lista as $item) {
            $parts = explode('|', $item);
            $img = trim($parts[0]);
            if (!empty($img)) $archivos[] = $img;
        }
        return $archivos;
    }

    // Obtener los datos actuales de la BD ANTES de actualizarlos
    $stmt_actuales = $db->query("SELECT clave, valor FROM footer_config WHERE clave IN ('premios_logos', 'asociaciones_logos')");
    $valores_actuales = [];
    while ($row = $stmt_actuales->fetch(PDO::FETCH_ASSOC)) {
        $valores_actuales[$row['clave']] = $row['valor'];
    }

    // ------------------------------------------------------------
    // NUEVO: GUARDAR LO QUE ESCRIBES EN EL TEXTAREA
    // ------------------------------------------------------------
    if (isset($_POST['premios_logos'])) {
        $stmt = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'premios_logos'");
        $stmt->execute([trim($_POST['premios_logos'])]);
    }

    if (isset($_POST['asociaciones_logos'])) {
        $stmt = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'asociaciones_logos'");
        $stmt->execute([trim($_POST['asociaciones_logos'])]);
    }
    // ------------------------------------------------------------

    // 2. Lógica para Premios: GUARDAR TEXTO MANUAL Y DETECTAR BORRADOS
    if (isset($_POST['premios_logos'])) {
        $viejos_premios = extraerNombresArchivos($valores_actuales['premios_logos'] ?? '');
        $nuevos_premios = extraerNombresArchivos($_POST['premios_logos']);
        $premios_a_borrar = array_diff($viejos_premios, $nuevos_premios);

        foreach ($premios_a_borrar as $archivo) {
            $ruta_fisica = $directorio_destino . $archivo;
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                unlink($ruta_fisica);
            }
        }

        // ESTA LÍNEA ES CRÍTICA: Guarda lo que escribiste a mano
        $stmt = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'premios_logos'");
        $stmt->execute([trim($_POST['premios_logos'])]);
    }

    // 3. Lógica para Asociaciones: GUARDAR TEXTO MANUAL Y DETECTAR BORRADOS
    if (isset($_POST['asociaciones_logos'])) {
        $viejas_aso = extraerNombresArchivos($valores_actuales['asociaciones_logos'] ?? '');
        $nuevas_aso = extraerNombresArchivos($_POST['asociaciones_logos']);
        $aso_a_borrar = array_diff($viejas_aso, $nuevas_aso);

        foreach ($aso_a_borrar as $archivo) {
            $ruta_fisica = $directorio_destino . $archivo;
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                unlink($ruta_fisica);
            }
        }

        // ESTA LÍNEA ES CRÍTICA: Guarda lo que escribiste a mano
        $stmt = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'asociaciones_logos'");
        $stmt->execute([trim($_POST['asociaciones_logos'])]);
    }

    // 4. Subir Nuevo Premio
    if (isset($_FILES['nuevo_premio_img']) && $_FILES['nuevo_premio_img']['error'] == 0) {
        $ext = pathinfo($_FILES['nuevo_premio_img']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'premio_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['nuevo_premio_img']['tmp_name'], $directorio_destino . $nombre_archivo)) {
            $url = !empty($_POST['nuevo_premio_url']) ? trim($_POST['nuevo_premio_url']) : '#';
            $nuevo_registro = $nombre_archivo . '|' . $url;

            // Refrescamos el valor actual DESPUÉS de haber guardado lo manual arriba
            $stmt = $db->query("SELECT valor FROM footer_config WHERE clave = 'premios_logos'");
            $actual = trim($stmt->fetchColumn());
            $nuevo_valor_bd = ($actual != '') ? $actual . ', ' . $nuevo_registro : $nuevo_registro;

            $stmt_upd = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'premios_logos'");
            $stmt_upd->execute([$nuevo_valor_bd]);
        }
    }

    // 5. Subir Nueva Asociación
    if (isset($_FILES['nueva_aso_img']) && $_FILES['nueva_aso_img']['error'] == 0) {
        $ext = pathinfo($_FILES['nueva_aso_img']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'aso_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['nueva_aso_img']['tmp_name'], $directorio_destino . $nombre_archivo)) {
            $url = !empty($_POST['nueva_aso_url']) ? trim($_POST['nueva_aso_url']) : '#';
            $nuevo_registro = $nombre_archivo . '|' . $url;

            $stmt = $db->query("SELECT valor FROM footer_config WHERE clave = 'asociaciones_logos'");
            $actual = trim($stmt->fetchColumn());
            $nuevo_valor_bd = ($actual != '') ? $actual . ', ' . $nuevo_registro : $nuevo_registro;

            $stmt_upd = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'asociaciones_logos'");
            $stmt_upd->execute([$nuevo_valor_bd]);
        }
    }
    // --- LÓGICA PARA SUBIR LOGO DEL FOOTER ---
    if (isset($_FILES['logo_footer']) && $_FILES['logo_footer']['error'] == 0) {
        $ext_logo = pathinfo($_FILES['logo_footer']['name'], PATHINFO_EXTENSION);
        $nombre_logo = "logo_footer_" . time() . "." . $ext_logo;
        $ruta_logo = '../assets/img/' . $nombre_logo;

        if (move_uploaded_file($_FILES['logo_footer']['tmp_name'], $ruta_logo)) {
            // Actualizar en la base de datos
            $stmt_logo = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'logo'");
            $stmt_logo->execute([$nombre_logo]);
            // Actualizamos la variable local para que se vea el cambio sin refrescar dos veces
            $config['logo'] = $nombre_logo;
        }
    }

    // --- LÓGICA PARA SUBIR IMAGEN DE LA SECCIÓN MONTAÑA ---
    if (isset($_FILES['mountain_img']) && $_FILES['mountain_img']['error'] == 0) {
        $ext_mtn = pathinfo($_FILES['mountain_img']['name'], PATHINFO_EXTENSION);
        $nombre_mtn = "mountain_" . time() . "." . $ext_mtn;
        $ruta_mtn = '../assets/img/' . $nombre_mtn;

        if (move_uploaded_file($_FILES['mountain_img']['tmp_name'], $ruta_mtn)) {
            $stmt_mtn = $db->prepare("UPDATE footer_config SET valor = ? WHERE clave = 'mountain_img'");
            $stmt_mtn->execute([$nombre_mtn]);
            $config['mountain_img'] = $nombre_mtn;
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=ok");
    exit;
}

// OBTENER DATOS PARA MOSTRAR
$stmt = $db->query("SELECT * FROM footer_config");
$config = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $config[$row['clave']] = $row['valor'];
}

$f['premios_logos'] = $config['premios_logos'] ?? '';
$f['asociaciones_logos'] = $config['asociaciones_logos'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin Footer Premium | IntiPath</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <div class="admin-wrapper">

        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="header-main">
                <h1>Editor de <span>Footer Premium</span></h1>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
                <div class="alerta-exito" style="background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:5px; border:1px solid #c3e6cb;">✅ ¡Configuración del Footer Premium y Logos actualizada!</div>
            <?php endif; ?>

            <div class="admin-contenedor">
                <form method="POST" class="formulario-admin" enctype="multipart/form-data">

                    <div class="categoria-form" style="border-left: 5px solid #E8AC18; background: #fefefe; margin-bottom: 30px;">
                        <h3><i class="fas fa-image"></i> Logotipo del Footer</h3>
                        <div style="display: flex; align-items: center; gap: 25px; padding: 15px; border: 1px solid #eee; border-radius: 10px;">

                            <div style="flex: 0 0 180px; text-align: center; background: #15305D; padding: 15px; border-radius: 8px; box-shadow: inset 0 0 10px rgba(0,0,0,0.2);">
                                <span style="color: #E8AC18; font-size: 10px; font-weight: bold; display: block; margin-bottom: 8px; text-transform: uppercase;">Logo Actual</span>
                                <img src="../assets/img/<?php echo !empty($config['logo']) ? $config['logo'] : 'logo.png'; ?>"
                                    alt="Logo Footer"
                                    style="max-width: 100%; height: 50px; object-fit: contain;">
                            </div>

                            <div style="flex: 1;">
                                <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Cambiar Imagen del Logo</label>
                                <input type="file" name="logo_footer" accept="image/png, image/jpeg, image/webp" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                                <p style="margin-top: 8px; font-size: 0.8rem; color: #666;">
                                    <i class="fas fa-info-circle"></i> Se recomienda un <strong>PNG transparente</strong> blanco o de colores claros.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="categoria-form" style="border-left: 5px solid #2d6a4f; background: #fefefe; margin-bottom: 30px;">
                        <h3><i class="fas fa-mountain"></i> Sección Montaña (footer-mountain-top)</h3>
                        <div style="display: flex; align-items: center; gap: 25px; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-bottom: 20px;">
                            <div style="flex: 0 0 150px; text-align: center; background: #15305D; padding: 15px; border-radius: 8px; box-shadow: inset 0 0 10px rgba(0,0,0,0.2);">
                                <span style="color: #E8AC18; font-size: 10px; font-weight: bold; display: block; margin-bottom: 8px; text-transform: uppercase;">Imagen Actual</span>
                                <?php if (!empty($config['mountain_img'])): ?>
                                    <img src="../assets/img/<?php echo htmlspecialchars($config['mountain_img']); ?>" alt="Mountain" style="max-width: 100%; height: 80px; object-fit: contain;">
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.8rem;">Sin imagen</span>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Cambiar Imagen</label>
                                <input type="file" name="mountain_img" accept="image/png, image/jpeg, image/webp" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
                                <p style="margin-top: 8px; font-size: 0.8rem; color: #666;">
                                    <i class="fas fa-info-circle"></i> La imagen ocupará todo el ancho como fondo. Se recomienda una imagen apaisada (1600x600px aprox).
                                </p>
                            </div>
                        </div>
                        <div class="grid-form">
                            <div class="form-grupo">
                                <label>Título (Español)</label>
                                <input type="text" name="config[mountain_title_es]" value="<?php echo htmlspecialchars($config['mountain_title_es'] ?? ''); ?>" placeholder="Ej. La vida es corta y el mundo es amplio">
                            </div>
                            <div class="form-grupo">
                                <label>Título (Inglés)</label>
                                <input type="text" name="config[mountain_title_en]" value="<?php echo htmlspecialchars($config['mountain_title_en'] ?? ''); ?>" placeholder="Ej. Life is short and the world is wide">
                            </div>
                            <div class="form-grupo">
                                <label>Subtítulo (Español)</label>
                                <input type="text" name="config[mountain_subtitle_es]" value="<?php echo htmlspecialchars($config['mountain_subtitle_es'] ?? ''); ?>" placeholder="Ej. cuanto antes empieces a explorarlo, mejor.">
                            </div>
                            <div class="form-grupo">
                                <label>Subtítulo (Inglés)</label>
                                <input type="text" name="config[mountain_subtitle_en]" value="<?php echo htmlspecialchars($config['mountain_subtitle_en'] ?? ''); ?>" placeholder="Ej. the sooner you start exploring it, the better.">
                            </div>
                        </div>
                    </div>

                    <div class="categoria-form">
                        <h3><i class="fas fa-address-book"></i> Datos de Contacto</h3>
                        <div class="grid-form">
                            <div class="form-grupo">
                                <label>Teléfono Corporativo</label>
                                <input type="text" name="config[telefono]" value="<?php echo htmlspecialchars($config['telefono'] ?? ''); ?>">
                            </div>
                            <div class="form-grupo">
                                <label>Email de Reservas</label>
                                <input type="email" name="config[email]" value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-grupo" style="margin-top:15px;">
                            <label>Dirección Oficina Cusco</label>
                            <input type="text" name="config[direccion]" value="<?php echo htmlspecialchars($config['direccion'] ?? ''); ?>">
                        </div>
                        <div class="form-grupo" style="margin-top:15px;">
                            <label>Horario de Atención</label>
                            <input type="text" name="config[horario]" value="<?php echo htmlspecialchars($config['horario'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="categoria-form" style="margin-top:30px;">
                        <h3><i class="fas fa-hashtag"></i> Redes Sociales & WhatsApp</h3>
                        <div class="grid-form">
                            <div class="form-grupo">
                                <label><i class="fab fa-facebook"></i> Facebook URL</label>
                                <input type="text" name="config[facebook]" value="<?php echo htmlspecialchars($config['facebook'] ?? ''); ?>">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fab fa-instagram"></i> Instagram URL</label>
                                <input type="text" name="config[instagram]" value="<?php echo htmlspecialchars($config['instagram'] ?? ''); ?>">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fab fa-tiktok"></i> TikTok URL</label>
                                <input type="text" name="config[tiktok]" value="<?php echo htmlspecialchars($config['tiktok'] ?? ''); ?>">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fab fa-youtube" style="color: #FF0000;"></i> YouTube Channel URL</label>
                                <input type="text" name="config[youtube]" value="<?php echo htmlspecialchars($config['youtube'] ?? ''); ?>" placeholder="https://www.youtube.com/@IntiPathTours">
                            </div>
                            <div class="form-grupo">
                                <label><i class="fab fa-whatsapp"></i> WhatsApp</label>
                                <input type="text" name="config[whatsapp]" value="<?php echo htmlspecialchars($config['whatsapp'] ?? ''); ?>" placeholder="51984000000">
                            </div>

                        </div>
                    </div>

                    <div class="categoria-form" style="margin-top:30px;">
                        <h3><i class="fas fa-file-invoice"></i> Configuración Legal del Footer</h3>
                        <div class="grid-form">

                            <div class="form-grupo">
                                <label><i class="fas fa-building"></i> Razón Social (Empresa)</label>
                                <input type="text" name="config[razon_social]" value="<?php echo htmlspecialchars($config['razon_social'] ?? ''); ?>" placeholder="Ej. Intipath Tours Peru S.A.C.">
                            </div>

                            <div class="form-grupo">
                                <label><i class="fas fa-id-card"></i> Número de RUC</label>
                                <input type="text" name="config[ruc]" value="<?php echo htmlspecialchars($config['ruc'] ?? ''); ?>" placeholder="Ej. 20606083182">
                            </div>

                        </div>
                    </div>

                    <div class="categoria-form" style="margin-top:30px;">
                        <h3><i class="fas fa-align-left"></i> Resumen de Agencia</h3>
                        <div class="form-grupo">
                            <label>Descripción corta para la primera columna del pie de página</label>
                            <textarea name="config[descripcion]" rows="4"><?php echo htmlspecialchars($config['descripcion'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="categoria-form" style="margin-top:30px; background:#fdfdfd; border-top: 4px solid #198754; padding:25px; border-radius:8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <h3 style="color:#198754; margin-bottom:20px; font-size:1.3rem;"><i class="fas fa-images"></i> Gestor Visual de Certificaciones</h3>

                        <div style="display:flex; gap:30px; flex-wrap:wrap;">

                            <div style="flex:1; min-width:300px; padding-right:20px; border-right:2px dashed #dee2e6;">
                                <label style="font-weight:bold; color:#198754; display:block; margin-bottom:8px;"><i class="fas fa-upload"></i> Subir Nuevo Premio (Color)</label>
                                <input type="file" name="nuevo_premio_img" accept="image/*" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px; background:#fff;">
                                <!-- <input type="text" name="nuevo_premio_url" placeholder="URL opcional (Ej: https://tripadvisor.com)" style="width:100%; padding:10px; margin-bottom:20px; border:1px solid #ccc; border-radius:4px;"> -->

                                <!-- <textarea name="premios_logos" style="display:none;" placeholder="Ejemplo: premio1.png|https://link.com, premio2.png|#"><?php echo htmlspecialchars($f['premios_logos']); ?></textarea> -->
                                <textarea name="premios_logos" style="width:100%; height:100px; display:block; border:1px solid #ccc; border-radius:5px; padding:10px; font-family:monospace;">
<?php echo htmlspecialchars($config['premios_logos'] ?? ''); ?>
</textarea>
                                <label style="font-weight:bold; font-size:0.95rem; color:#495057; display:block; margin-bottom:8px;">Premios Actuales:</label>

                                <div style="display:flex; flex-wrap:wrap; gap:15px; padding:15px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; min-height: 100px;">
                                    <?php
                                    $premios_lista = array_filter(array_map('trim', explode(',', $f['premios_logos'])));
                                    if (!empty($premios_lista)):
                                        foreach ($premios_lista as $item):
                                            $data = explode('|', $item);
                                            $img = trim($data[0]);
                                            if (!empty($img)):
                                                $id_thumb = 'premio_' . md5($img);
                                    ?>
                                                <div id="<?php echo $id_thumb; ?>" style="position:relative; display:flex; flex-direction:column; align-items:center; width:95px; background:#fff; border:1px solid #ddd; padding:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                                                    <button type="button" onclick="eliminarLogo('premios_logos', '<?php echo htmlspecialchars($img); ?>', '<?php echo $id_thumb; ?>')" style="position:absolute; top:-10px; right:-10px; background:#dc3545; color:white; border:none; border-radius:50%; width:24px; height:24px; font-weight:bold; cursor:pointer; box-shadow:0 2px 4px rgba(0,0,0,0.2); transition: 0.2s;">X</button>
                                                    <img src="../assets/img/certificaciones/<?php echo htmlspecialchars($img); ?>" alt="Premio" style="width: 65px; height: 65px; object-fit: contain; margin-bottom:5px;">
                                                    <span style="font-size: 0.65rem; color: #6c757d; font-family: monospace; word-break: break-all; text-align:center; line-height:1.1; font-weight:bold;"><?php echo htmlspecialchars($img); ?></span>
                                                </div>
                                    <?php
                                            endif;
                                        endforeach;
                                    else:
                                        echo '<div style="width:100%; text-align:center; padding: 20px 0;"><span style="color:#adb5bd; font-style:italic;"><i class="fas fa-image fa-2x mb-2 d-block"></i><br>Aún no hay premios.<br>¡Sube tu primera imagen arriba!</span></div>';
                                    endif;
                                    ?>
                                </div>
                            </div>

                            <div style="flex:1; min-width:300px;">
                                <label style="font-weight:bold; color:#0d6efd; display:block; margin-bottom:8px;"><i class="fas fa-upload"></i> Subir Nueva Asociación (Gris)</label>
                                <input type="file" name="nueva_aso_img" accept="image/*" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px; background:#fff;">
                                <input type="text" name="nueva_aso_url" placeholder="URL opcional (Ej: https://gob.pe)" style="width:100%; padding:10px; margin-bottom:20px; border:1px solid #ccc; border-radius:4px;">

                                <!-- <textarea name="asociaciones_logos" style="display:none;" placeholder="Ejemplo: mincetur.png|https://gob.pe, atta.png|#"><?php echo htmlspecialchars($f['asociaciones_logos']); ?></textarea> -->
                                <textarea name="asociaciones_logos" style="width:100%; height:100px; display:block; border:2px solid #0d6efd; border-radius:5px; padding:10px; font-family:monospace;"><?php echo htmlspecialchars($config['asociaciones_logos'] ?? ''); ?></textarea>
                                <label style="font-weight:bold; font-size:0.95rem; color:#495057; display:block; margin-bottom:8px;">Asociaciones Actuales:</label>

                                <div style="display:flex; flex-wrap:wrap; gap:15px; padding:15px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; min-height: 100px;">
                                    <?php
                                    $asociaciones_lista = array_filter(array_map('trim', explode(',', $f['asociaciones_logos'])));
                                    if (!empty($asociaciones_lista)):
                                        foreach ($asociaciones_lista as $item):
                                            $data = explode('|', $item);
                                            $img = trim($data[0]);
                                            if (!empty($img)):
                                                $id_thumb = 'aso_' . md5($img);
                                    ?>
                                                <div id="<?php echo $id_thumb; ?>" style="position:relative; display:flex; flex-direction:column; align-items:center; width:95px; background:#fff; border:1px solid #ddd; padding:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                                                    <button type="button" onclick="eliminarLogo('asociaciones_logos', '<?php echo htmlspecialchars($img); ?>', '<?php echo $id_thumb; ?>')" style="position:absolute; top:-10px; right:-10px; background:#dc3545; color:white; border:none; border-radius:50%; width:24px; height:24px; font-weight:bold; cursor:pointer; box-shadow:0 2px 4px rgba(0,0,0,0.2); transition: 0.2s;">X</button>
                                                    <img src="../assets/img/certificaciones/<?php echo htmlspecialchars($img); ?>" alt="Asociación" style="width: 65px; height: 65px; object-fit: contain; filter: grayscale(100%); margin-bottom:5px;">
                                                    <span style="font-size: 0.65rem; color: #6c757d; font-family: monospace; word-break: break-all; text-align:center; line-height:1.1; font-weight:bold;"><?php echo htmlspecialchars($img); ?></span>
                                                </div>
                                    <?php
                                            endif;
                                        endforeach;
                                    else:
                                        echo '<div style="width:100%; text-align:center; padding: 20px 0;"><span style="color:#adb5bd; font-style:italic;"><i class="fas fa-image fa-2x mb-2 d-block"></i><br>Aún no hay asociaciones.<br>¡Sube tu primera imagen arriba!</span></div>';
                                    endif;
                                    ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <button type="submit" class="btn-login" style="width:100%; margin-top:30px; padding:18px; background:#198754; color:white; border:none; border-radius:5px; font-size:1.2rem; cursor:pointer; font-weight:bold; box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);">
                        <i class="fas fa-save"></i> GUARDAR TODOS LOS CAMBIOS
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function eliminarLogo(nombreTextarea, nombreImagen, idMiniatura) {
            if (confirm("¿Estás seguro de quitar '" + nombreImagen + "' del Footer?\n\n(Al hacer clic en 'Guardar Todos los Cambios', el archivo se borrará permanentemente del servidor).")) {

                let miniatura = document.getElementById(idMiniatura);
                miniatura.style.opacity = '0.5';
                miniatura.style.pointerEvents = 'none';
                miniatura.innerHTML = '<span style="color:red; font-size:0.8rem; font-weight:bold; margin-top:20px;">Eliminando...</span>';

                let textarea = document.querySelector('textarea[name="' + nombreTextarea + '"]');
                let itemsActuales = textarea.value.split(',').map(item => item.trim());

                let itemsNuevos = itemsActuales.filter(item => {
                    let imagenExtraida = item.split('|')[0].trim();
                    return imagenExtraida !== nombreImagen && imagenExtraida !== '';
                });

                textarea.value = itemsNuevos.join(', ');
            }
        }
    </script>

    <script>
        // 1. Alerta de confirmación al guardar
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('msg') === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Excelente!',
                    text: 'La configuración del Footer y los logos se han actualizado correctamente.',
                    confirmButtonColor: '#198754',
                    timer: 3000,
                    timerProgressBar: true
                });
                // Limpia la URL para que no vuelva a salir la alerta al refrescar
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // 2. Función mejorada para eliminar logos con SweetAlert
        function eliminarLogo(nombreTextarea, nombreImagen, idMiniatura) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Se quitará '" + nombreImagen + "' del Footer. Para que el cambio sea permanente, deberás guardar los cambios al final.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let miniatura = document.getElementById(idMiniatura);
                    miniatura.style.opacity = '0.3';
                    miniatura.style.pointerEvents = 'none';

                    let textarea = document.querySelector('textarea[name="' + nombreTextarea + '"]');
                    let itemsActuales = textarea.value.split(',').map(item => item.trim());

                    let itemsNuevos = itemsActuales.filter(item => {
                        let imagenExtraida = item.split('|')[0].trim();
                        return imagenExtraida !== nombreImagen && imagenExtraida !== '';
                    });

                    textarea.value = itemsNuevos.join(', ');

                    Swal.fire({
                        title: 'Eliminado de la lista',
                        text: 'Recuerda hacer clic en "Guardar Todos los Cambios" para finalizar.',
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    </script>
</body>

</html>