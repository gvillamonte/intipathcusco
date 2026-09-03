<?php
// admin/configuracion.php
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';
requierePermiso('config');

$usuario_nombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$pagina_actual = basename($_SERVER['PHP_SELF']);

$mis_accesos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : [];
if (!is_array($mis_accesos)) {
    $mis_accesos = [];
}

$database = new Database();
$db = $database->getConnection();
$mensaje_tipo = isset($_GET['status']) ? $_GET['status'] : "";

// 2. CARGA DE CONFIGURACIÓN (CON PROTECCIÓN)
$stmt = $db->prepare("SELECT * FROM configuracion WHERE id = 1");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    $config = [
        'id' => 1,
        'titulo_web' => 'IntiPath Tours',
        'meta_descripcion' => '',
        'logo' => 'logo.png',
        'favicon' => 'favicon.png',
        'testimonios_url' => '',
        'tipo_cambio' => 3.75
    ];
    $db->query("INSERT IGNORE INTO configuracion (id, titulo_web, tipo_cambio) VALUES (1, 'IntiPath Tours', 3.75)");
}

// 3. LÓGICA DE PROCESAMIENTO (CORREGIDA SIN ERRORES)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'actualizar_config') {
    $titulo_web = htmlspecialchars($_POST['titulo_web']);
    $meta_desc = htmlspecialchars($_POST['meta_descripcion']);

    // Inicializamos variables para evitar el error de variable indefinida
    $logo_sql_part = "";
    $favicon_sql_part = "";
    $header_btn_texto = htmlspecialchars($_POST['header_btn_texto']);
    $header_btn_texto_en = htmlspecialchars($_POST['header_btn_texto_en']);
    $header_btn_url = htmlspecialchars($_POST['header_btn_url']);
    $testimonios_url = htmlspecialchars(trim($_POST['testimonios_url'] ?? ''));
    $tipo_cambio = floatval(str_replace(',', '.', $_POST['tipo_cambio'] ?? '3.75'));
    if ($tipo_cambio <= 0) $tipo_cambio = 3.75;
    $ga4_id = trim($_POST['ga4_id'] ?? '');

    $params = [
        ':titulo' => $titulo_web,
        ':meta' => $meta_desc,
        ':header_btn_texto' => $header_btn_texto,
        ':header_btn_texto_en' => $header_btn_texto_en,
        ':header_btn_url' => $header_btn_url,
        ':testimonios_url' => $testimonios_url,
        ':tipo_cambio' => $tipo_cambio,
        ':ga4_id' => $ga4_id,
        ':id' => 1
    ];

    $dir = "../assets/img/";

    // PROCESAR LOGO
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext_logo = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nuevo_logo = "logo-inti-" . substr(md5(time()), 0, 4) . "." . $ext_logo;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $nuevo_logo)) {
            if (!empty($config['logo']) && file_exists($dir . $config['logo'])) {
                @unlink($dir . $config['logo']);
            }
            $logo_sql_part = ", logo = :logo";
            $params[':logo'] = $nuevo_logo;
        }
    }

    // PROCESAR FAVICON (Nombre único para forzar a Google)
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $ext_fav = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $nuevo_fav = "fav-inti-" . substr(md5(microtime()), 0, 4) . "." . $ext_fav;

        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $dir . $nuevo_fav)) {
            if (!empty($config['favicon']) && file_exists($dir . $config['favicon'])) {
                @unlink($dir . $config['favicon']);
            }
            $favicon_sql_part = ", favicon = :fav";
            $params[':fav'] = $nuevo_fav;
        }
    }

    // ACTUALIZACIÓN FINAL
    $query_upd = "UPDATE configuracion SET 
                    titulo_web = :titulo, 
                    meta_descripcion = :meta,
                    header_btn_texto = :header_btn_texto,
                    header_btn_texto_en = :header_btn_texto_en,
                    header_btn_url = :header_btn_url,
                    testimonios_url = :testimonios_url,
                    tipo_cambio = :tipo_cambio,
                    ga4_id = :ga4_id"
        . $logo_sql_part
        . $favicon_sql_part .
        " WHERE id = :id";

    $stmt_upd = $db->prepare($query_upd);

    if ($stmt_upd->execute($params)) {
        // Redirección para limpiar caché y actualizar imágenes en el acto
        header("Location: configuracion.php?status=success");
        exit;
    } else {
        $mensaje_tipo = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $config['titulo_web']; ?> | Admin</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($config['favicon'])): ?>
        <link rel="shortcut icon" href="../assets/img/<?php echo $config['favicon']; ?>?v=<?php echo time(); ?>" type="image/x-icon">
    <?php endif; ?>
</head>

<body>
    <?php if ($mensaje_tipo == "success"): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'La configuración se guardó correctamente.',
                confirmButtonColor: '#15305D'
            });
        </script>
    <?php elseif ($mensaje_tipo == "error"): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo guardar la configuración en la base de datos.',
            });
        </script>
    <?php endif; ?>

    <div class="admin-wrapper" style="display: flex;">

        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content" style="padding: 30px; flex: 1; background: #f8f9fa;">
            <h2>Configuración General de la Web</h2>
            <p>Gestiona la identidad visual y el SEO de IntiPath Tours.</p>
            <hr>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="actualizar_config">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">

                    <section class="admin-contenedor" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #15305D;"><i class="fas fa-eye"></i> Identidad Visual</h3>
                        <br>

                        <div class="login-form-grupo" style="margin-bottom: 20px;">
                            <label style="font-weight: bold;"><i class="fas fa-image"></i> Logo Principal</label>
                            <input type="file" name="logo" accept="image/*" class="form-control">

                            <div style="margin-top: 10px; background: #f4f4f4; padding: 15px; text-align: center; border: 1px dashed #ccc; border-radius: 8px;">
                                <img src="../assets/img/<?php echo $config['logo'] ?? 'logo_intipath.png'; ?>?v=1.0"
                                    style="max-height: 80px; object-fit: contain;">
                                <p style="margin-top: 5px; font-size: 12px; color: #666;">Vista previa actual</p>
                            </div>
                        </div>
                        <!-- Aqui empieza el nuevo bloque para favicon -->
                        <div class="login-form-grupo">
                            <label style="font-weight: bold; display: block; margin-bottom: 5px;">
                                <i class="fas fa-certificate"></i> Favicon (Subir archivo .ico - Recomendado 48x48px)
                            </label>

                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                <input type="file" name="favicon" accept="image/x-icon, .ico" class="form-control" style="flex: 1;">

                                <a href="https://cloudconvert.com/png-to-ico" target="_blank" rel="noopener"
                                    style="background: #e74c3c; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                    <i class="fas fa-sync-alt"></i> Convertir a .ICO
                                </a>
                            </div>

                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 15px; background: #fdfdfd; padding: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
                                <?php
                                $img_favicon = (!empty($config) && isset($config['favicon'])) ? $config['favicon'] : 'favicon.ico';
                                ?>

                                <img src="../assets/img/<?php echo $img_favicon; ?>"
                                    style="width: 48px; height: 48px; border: 1px solid #ddd; object-fit: contain; border-radius: 4px;"
                                    alt="Favicon Actual">

                                <div style="display: flex; flex-direction: column;">
                                    <small><strong>Archivo actual:</strong> <?php echo $img_favicon; ?></small>
                                    <small style="color: #2c3e50;">
                                        <i class="fas fa-check-circle"></i> URL estable para Google (sin parámetros de tiempo).
                                    </small>
                                    <small style="color: #666; font-size: 11px; margin-top: 5px;">
                                        <i class="fas fa-external-link-alt"></i> Si tienes un PNG, usa el botón rojo para convertirlo antes de subirlo.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- Aqui es el final del nuevo bloque para favicon -->

                    </section>

                    <section class="admin-contenedor" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #15305D;"><i class="fas fa-search"></i> Posicionamiento (SEO)</h3>
                        <br>

                        <div class="login-form-grupo" style="margin-bottom: 20px;">
                            <label>Título de la Web</label>
                            <input type="text" name="titulo_web" value="<?php echo $config['titulo_web']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div class="login-form-grupo">
                            <label>Meta Descripción</label>
                            <textarea name="meta_descripcion" rows="5" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo $config['meta_descripcion']; ?></textarea>
                        </div>
                    </section>

                    <section class="admin-contenedor" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #15305D;"><i class="fas fa-rectangle-ad"></i> Botón del Header</h3>
                        <br>

                        <div class="login-form-grupo" style="margin-bottom: 20px;">
                            <label style="font-weight: bold;">Texto del botón (Español)</label>
                            <input type="text" name="header_btn_texto" value="<?php echo $config['header_btn_texto'] ?? 'Consultar'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div class="login-form-grupo" style="margin-bottom: 20px;">
                            <label style="font-weight: bold;">Texto del botón (Inglés)</label>
                            <input type="text" name="header_btn_texto_en" value="<?php echo $config['header_btn_texto_en'] ?? 'Inquire'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div class="login-form-grupo">
                            <label style="font-weight: bold;">URL del botón</label>
                            <input type="text" name="header_btn_url" value="<?php echo $config['header_btn_url'] ?? 'contacto.php'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </section>

                    <section class="admin-contenedor" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #15305D;"><i class="fas fa-star"></i> Enlace de Testimonios</h3>
                        <br>

                        <div class="login-form-grupo">
                            <label style="font-weight: bold;">URL a la que redirigen los botones "VER MÁS TESTIMONIOS"</label>
                            <input type="url" name="testimonios_url" value="<?php echo htmlspecialchars($config['testimonios_url'] ?? 'https://www.tripadvisor.com.pe/Attraction_Review-g294314-d34356631-Reviews-Inti_Path_Tours-Cusco_Cusco_Region.html'); ?>"
                                placeholder="https://www.tripadvisor.com.pe/..."
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: #888; display: block; margin-top: 6px;">
                                <i class="fas fa-info-circle"></i> Se abre al hacer clic en cualquier botón "VER MÁS TESTIMONIOS" / "VIEW MORE REVIEWS" de la web.
                            </small>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="../testimonios.php" target="_blank" rel="noopener"
                                style="background: #e8ac18; color: #15305D; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-external-link-alt"></i> Probar enlace
                            </a>
                            <a href="<?php echo htmlspecialchars($config['testimonios_url'] ?? 'https://www.tripadvisor.com.pe/Attraction_Review-g294314-d34356631-Reviews-Inti_Path_Tours-Cusco_Cusco_Region.html'); ?>" target="_blank" rel="noopener"
                                style="background: #15305D; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-globe"></i> Abrir destino actual
                            </a>
                        </div>
                    </section>

                    <section class="admin-contenedor" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #15305D;"><i class="fas fa-exchange-alt"></i> Tipo de Cambio</h3>
                        <br>

                        <div class="login-form-grupo">
                            <label style="font-weight: bold;">Tipo de cambio PEN → USD (S/ por US$)</label>
                            <input type="number" step="0.01" min="0" name="tipo_cambio" value="<?php echo htmlspecialchars($config['tipo_cambio'] ?? '3.75'); ?>"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: #888; display: block; margin-top: 6px;">
                                <i class="fas fa-info-circle"></i> Se usa para mostrar el equivalente en soles en la página de pago. Default: 3.75.
                            </small>
                        </div>

                        <div class="login-form-grupo" style="margin-top: 18px;">
                            <label style="font-weight: bold;"><i class="fab fa-google"></i> Google Analytics (GA4) ID</label>
                            <input type="text" name="ga4_id" value="<?php echo htmlspecialchars($config['ga4_id'] ?? ''); ?>"
                                placeholder="G-XXXXXXXXXX" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: #888; display: block; margin-top: 6px;">
                                <i class="fas fa-info-circle"></i> Se carga SOLO si el visitante acepta cookies analíticas en el banner. Déjalo vacío para no usar analíticas.
                            </small>
                        </div>
                    </section>

                    <div class="card-admin" style="margin-top: 30px; border: 1px solid #1a73e8; background: #f8fbff; padding: 20px; border-radius: 12px;">
                        <h3 style="color: #1a73e8; margin-top: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fab fa-google"></i> Monitor de Rastreo de Google
                        </h3>
                        <hr style="border: 0; border-top: 1px solid #d1e3fa; margin-bottom: 20px;">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                <p style="font-weight: bold; margin-bottom: 10px; color: #555;">Tu Versión Actual (Local)</p>
                                <img src="../assets/img/<?php echo $config['favicon']; ?>"
                                    style="width: 64px; height: 64px; padding: 5px; border-radius: 8px;">
                                <p style="font-size: 11px; color: #888; margin-top: 10px;">Archivo: <?php echo $config['favicon']; ?></p>
                            </div>

                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                <p style="font-weight: bold; margin-bottom: 10px; color: #555;">Versión Indexada (Google)</p>
                                <img src="https://www.google.com/s2/favicons?domain=www.intipathtours.com&sz=128&t=<?php echo time(); ?>"
                                    style="width: 64px; height: 64px; background: #eee; padding: 5px; border-radius: 8px;"
                                    onerror="this.src='../assets/img/no-image.png';">
                                <p style="font-size: 11px; color: #1a73e8; margin-top: 10px;">Lo que el mundo ve en el buscador</p>
                            </div>
                        </div>

                        <div style="margin-top: 20px; font-size: 13px; color: #666; background: #fff; padding: 10px; border-radius: 6px;">
                            <p><strong>¿Cómo saber si ya cambió?</strong></p>
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                <li>Si ambas imágenes son <b>azules</b>: ¡Google ya terminó de actualizarte!</li>
                                <li>Si la de la derecha sigue siendo <b>blanca</b>: Google aún no refresca su servidor global (tarda 24-48h).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-login" style="background: #15305D; color: #fff; padding: 15px 40px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>