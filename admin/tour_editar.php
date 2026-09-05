<?php

/**
 * tour_editar.php
 * Formulario de crear/editar tour - IntiPath Tours
 * Reescritura UX completa.
 */

require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('tours');

require_once '../config/database.php';
require_once '../includes/tipo_cambio_helper.php';
$database = new Database();
$db = $database->getConnection();
$tipo_cambio_actual = obtenerTipoCambio($db);

$edit_mode = false;
$tour_id = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;

$datos = [
    'id'                        => '',
    'titulo'                    => '',
    'titulo_en'                 => '',
    'precio'                    => 0.00,
    'precio_soles'              => null,
    'duracion'                  => '',
    'duracion_en'               => '',
    'descripcion_corta'         => '',
    'descripcion_corta_en'      => '',
    'titulo_corto'              => '',
    'titulo_corto_en'           => '',
    'itinerario'                => '',
    'itinerario_en'             => '',
    'moneda'                    => 'USD',
    'mostrar_precio'            => 1,
    'es_recomendado'            => 0,
    'en_oferta'                 => 0,
    'tipo_precio'               => 'persona',
    'incluye'                   => '',
    'incluye_en'                => '',
    'no_incluye'                => '',
    'no_incluye_en'             => '',
    'id_categoria'              => '',
    'destacados'                => '',
    'destacados_en'             => '',
    'itinerario_resumen'        => '',
    'itinerario_resumen_en'     => '',
    'lista_equipaje'            => '',
    'lista_equipaje_en'         => '',
    'precio_grupal'             => 0.00,
    'porcentaje_adelanto'       => 30,
    'precio_nino'               => NULL,
    'max_personas'              => 0,
    'pago_adelantado'           => 0.00,
    'saldo_cusco'               => 0.00,
    'extras_texto'              => '',
    'extras_texto_en'           => '',
    'video_url'                 => '',
    'altitud_max'               => '',
    'dificultad'                => 'Moderado',
    'ubicacion_texto'           => '',
    'distancia_caminata'        => '',
    'comidas_info'              => '',
    'comidas_info_en'           => '',
    'folleto_pdf'               => '',
    'mapa_imagen'               => '',
    'info_importante_detallada' => '',
    'info_importante_detallada_en' => '',
    'img_galeria1'              => '',
    'img_galeria2'              => '',
    'img_galeria3'              => '',
    'img_galeria4'              => '',
    'antes_de_viajar'           => '',
    'antes_de_viajar_en'        => '',
    'aclimatacion_texto'        => '',
    'aclimatacion_texto_en'     => '',
    'galeria_itinerario'        => '',
    'en_menu'                   => 0,
    'parent_id'                 => '',
];

$bd_error = '';
$categorias = [];
$tours_principales = [];
$tarjetas_confianza = [];
$pdfs_disponibles = glob("../assets/pdf/*.pdf") ?: [];

try {
    if ($tour_id > 0) {
        $edit_mode = true;
        $stmt = $db->prepare("CALL sp_obtener_tour_editar(?)");
        $stmt->execute([$tour_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $datos = array_merge($datos, $res);
        }
        $stmt->closeCursor();
    }

    $stmt_cat = $db->query("CALL sp_obtener_categorias_tours()");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    $stmt_cat->closeCursor();

    $stmt_tp = $db->query("CALL sp_obtener_tours_principales_admin()");
    $tours_principales = $stmt_tp->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tp->closeCursor();

    $stmt_tc = $db->query("CALL sp_obtener_confianza_admin()");
    $tarjetas_confianza = $stmt_tc->fetchAll(PDO::FETCH_ASSOC);
    $stmt_tc->closeCursor();

} catch (PDOException $e) {
    $bd_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Editar Tour' : 'Nuevo Tour' ?> | Admin IntiPath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_tours.css">
</head>
<body>
    <div class="admin-wrapper d-flex">
        <?php include '../includes/sidebar.php'; ?>

        <?php if (!empty($bd_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="position:fixed; top:15px; right:15px; z-index:9999; max-width:480px;">
                <strong><i class="fas fa-database"></i> Error de base de datos</strong>
                <div class="small mt-1"><?= htmlspecialchars($bd_error) ?></div>
                <small class="d-block mt-2 text-muted">Probablemente falta un stored procedure. Ejecute <code>admin/actualizar_bd.php</code> para sincronizarla.</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <main class="tou-main-content p-4 w-100">

            <!-- HEADER -->
            <div class="edit-form-header">
                <div class="header-left">
                    <div class="tou-breadcrumb">
                        <a href="tours.php"><i class="fas fa-arrow-left me-1"></i> Tours</a>
                        <span class="sep"><i class="fas fa-chevron-right"></i></span>
                        <span><?= $edit_mode ? 'Editar' : 'Nuevo Tour' ?></span>
                    </div>
                    <h2><?= $edit_mode ? htmlspecialchars($datos['titulo']) : 'Nuevo Tour' ?></h2>
                    <p><?= $edit_mode ? 'Edite la informacion del tour' : 'Complete los datos para publicar su tour' ?></p>
                </div>
                <a href="tours.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Volver</a>
            </div>

            <form action="procesar_tours.php" method="POST" enctype="multipart/form-data" id="tourForm">
                <input type="hidden" name="id_tour" value="<?= $datos['id'] ?>">

                <!-- TABS -->
                <div class="custom-nav-tabs" role="tablist">
                    <button type="button" class="nav-tab-item active" data-target="#tab-gen" role="tab">
                        <i class="fas fa-info-circle"></i>
                        <span class="tab-text">General</span>
                        <span class="tab-check" id="check-gen"></span>
                    </button>
                    <button type="button" class="nav-tab-item" data-target="#tab-iti" role="tab">
                        <i class="fas fa-list-ol"></i>
                        <span class="tab-text">Itinerario</span>
                        <span class="tab-check" id="check-iti"></span>
                    </button>
                    <button type="button" class="nav-tab-item" data-target="#tab-inc" role="tab">
                        <i class="fas fa-check-double"></i>
                        <span class="tab-text">Inclusiones</span>
                        <span class="tab-check" id="check-inc"></span>
                    </button>
                    <button type="button" class="nav-tab-item" data-target="#tab-pre" role="tab">
                        <i class="fas fa-tags"></i>
                        <span class="tab-text">Precios</span>
                        <span class="tab-check" id="check-pre"></span>
                    </button>
                    <button type="button" class="nav-tab-item" data-target="#tab-mul" role="tab">
                        <i class="fas fa-photo-video"></i>
                        <span class="tab-text">Multimedia</span>
                        <span class="tab-check" id="check-mul"></span>
                    </button>
                </div>

                <div class="tou-card p-4">

                    <!-- ====== TAB 1: GENERAL ====== -->
                    <div class="tab-pane-form show active" id="tab-gen" role="tabpanel">
                        <div class="row g-4">
                            <!-- Titulo + Categoria -->
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Titulo del Tour (ES) *</label>
                                <input type="text" name="titulo" class="form-control mb-3" value="<?= htmlspecialchars($datos['titulo']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Categoria *</label>
                                <select name="id_categoria" class="form-select" required>
                                    <option value="">-- Elija Categoria --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($datos['id_categoria'] == $cat['id']) ? 'selected' : '' ?>><?= mb_strtoupper($cat['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Bloque ES -->
                            <div class="col-md-6">
                                <div class="lang-section es-block">
                                    <div class="lang-badge"><i class="fas fa-flag"></i> Espanol</div>
                                    <div class="form-group">
                                        <label class="field-label">Titulo Corto (Menu)</label>
                                        <input type="text" name="titulo_corto" class="form-control" value="<?= htmlspecialchars($datos['titulo_corto']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Duracion</label>
                                        <input type="text" name="duracion" class="form-control" value="<?= htmlspecialchars($datos['duracion']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Destacados</label>
                                        <textarea name="destacados" class="form-control" rows="3"><?= htmlspecialchars($datos['destacados']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Resumen Corto</label>
                                        <textarea name="descripcion_corta" class="form-control" rows="3"><?= htmlspecialchars($datos['descripcion_corta']) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Bloque EN -->
                            <div class="col-md-6">
                                <div class="lang-section en-block">
                                    <div class="lang-badge"><i class="fas fa-flag"></i> English</div>
                                    <div class="form-group">
                                        <label class="field-label">Short Title</label>
                                        <input type="text" name="titulo_corto_en" class="form-control" value="<?= htmlspecialchars($datos['titulo_corto_en']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Duration</label>
                                        <input type="text" name="duracion_en" class="form-control" value="<?= htmlspecialchars($datos['duracion_en']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Highlights</label>
                                        <textarea name="destacados_en" class="form-control" rows="3"><?= htmlspecialchars($datos['destacados_en']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Short Description</label>
                                        <textarea name="descripcion_corta_en" class="form-control" rows="3"><?= htmlspecialchars($datos['descripcion_corta_en']) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Cover Image -->
                            <div class="col-12">
                                <div class="form-section-title"><i class="fas fa-camera"></i> Foto Portada Principal</div>
                                <div id="coverUploadCard" class="cover-upload-card">
                                    <?php if ($datos['id']): ?>
                                        <?php $cover_path = '../assets/img/tours/' . htmlspecialchars($datos['imagen_principal'] ?: 'placeholder.jpg'); ?>
                                        <img src="<?= $cover_path ?>" alt="Portada" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="cover-empty" style="display:none;">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Subir imagen de portada</span>
                                        </div>
                                        <div class="cover-overlay">
                                            <i class="fas fa-camera"></i>
                                            <span>Cambiar imagen</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="cover-empty">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Subir imagen de portada</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="imagen" id="inputImagenCover" class="d-none" accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <!-- Vincular Tour -->
                            <div class="col-12">
                                <div class="form-section-title"><i class="fas fa-link"></i> Vincular a Tour Principal</div>
                                <div class="form-helper">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Solo si este tour es una sub-tour (caminata). Deje vacio si es tour principal independiente.</span>
                                </div>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- Tour Principal Independiente --</option>
                                    <?php
                                    try {
                                        $stmt_p = $db->prepare("CALL sp_obtener_tours_padre(?)");
                                        $stmt_p->execute([$tour_id]);
                                        while ($rp = $stmt_p->fetch()) {
                                            echo "<option value='{$rp['id']}' " . ($datos['parent_id'] == $rp['id'] ? 'selected' : '') . ">" . mb_strtoupper($rp['titulo']) . "</option>";
                                        }
                                        $stmt_p->closeCursor();
                                    } catch (PDOException $e) {
                                        echo "<option value=''>-- Error al cargar tours padre --</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ====== TAB 2: ITINERARIO ====== -->
                    <div class="tab-pane-form" id="tab-iti" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="lang-section es-block">
                                    <div class="lang-badge"><i class="fas fa-flag"></i> Espanol</div>
                                    <div class="form-group">
                                        <label class="field-label"><i class="fas fa-list-ol me-1"></i> Itinerario Resumen</label>
                                        <div class="form-helper">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Formato: DIA 01: Cusco * Descripcion breve del dia</span>
                                        </div>
                                        <textarea name="itinerario_resumen" class="form-control" rows="6"><?= htmlspecialchars($datos['itinerario_resumen']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label"><i class="fas fa-list-ol me-1"></i> Itinerario Completo Detallado</label>
                                        <textarea name="itinerario" class="form-control" rows="10"><?= htmlspecialchars($datos['itinerario']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="lang-section en-block">
                                    <div class="lang-badge"><i class="fas fa-flag"></i> English</div>
                                    <div class="form-group">
                                        <label class="field-label"><i class="fas fa-list-ol me-1"></i> Itinerary Summary</label>
                                        <div class="form-helper">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Format: DAY 01: Cusco * Short description of the day</span>
                                        </div>
                                        <textarea name="itinerario_resumen_en" class="form-control" rows="6"><?= htmlspecialchars($datos['itinerario_resumen_en']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label"><i class="fas fa-list-ol me-1"></i> Full Detailed Itinerary</label>
                                        <textarea name="itinerario_en" class="form-control" rows="10"><?= htmlspecialchars($datos['itinerario_en']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ====== TAB 3: INCLUSIONES ====== -->
                    <div class="tab-pane-form" id="tab-inc" role="tabpanel">
                        <div class="row g-4">
                            <!-- Bloque ES -->
                            <div class="col-md-6">
                                <div class="inclusion-card card-incluye">
                                    <div class="card-header"><i class="fas fa-check-circle"></i> Que Incluye?</div>
                                    <textarea name="incluye" class="form-control" rows="5" placeholder="Transporte, guia, comidas, entradas..."><?= htmlspecialchars($datos['incluye']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-no-incluye">
                                    <div class="card-header"><i class="fas fa-times-circle"></i> No Incluye</div>
                                    <textarea name="no_incluye" class="form-control" rows="5" placeholder="Bebidas, propinas, seguro..."><?= htmlspecialchars($datos['no_incluye']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-info">
                                    <div class="card-header"><i class="fas fa-info-circle"></i> Informacion Importante</div>
                                    <textarea name="info_importante_detallada" class="form-control" rows="5" placeholder="Recomendaciones, clima, altitud..."><?= htmlspecialchars($datos['info_importante_detallada']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-equipaje">
                                    <div class="card-header"><i class="fas fa-suitcase-rolling"></i> Lista de Equipaje</div>
                                    <textarea name="lista_equipaje" class="form-control" rows="5" placeholder="Bastones, bloqueador, zapatillas..."><?= htmlspecialchars($datos['lista_equipaje']) ?></textarea>
                                </div>
                            </div>
                            <!-- Bloque EN -->
                            <div class="col-md-6">
                                <div class="inclusion-card card-incluye">
                                    <div class="card-header"><i class="fas fa-check-circle"></i> What is Included?</div>
                                    <textarea name="incluye_en" class="form-control" rows="5" placeholder="Transport, guide, meals, entries..."><?= htmlspecialchars($datos['incluye_en']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-no-incluye">
                                    <div class="card-header"><i class="fas fa-times-circle"></i> Not Included</div>
                                    <textarea name="no_incluye_en" class="form-control" rows="5" placeholder="Drinks, tips, insurance..."><?= htmlspecialchars($datos['no_incluye_en']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-info">
                                    <div class="card-header"><i class="fas fa-info-circle"></i> Important Info</div>
                                    <textarea name="info_importante_detallada_en" class="form-control" rows="5" placeholder="Recommendations, weather, altitude..."><?= htmlspecialchars($datos['info_importante_detallada_en']) ?></textarea>
                                </div>
                                <div class="inclusion-card card-equipaje">
                                    <div class="card-header"><i class="fas fa-suitcase-rolling"></i> Packing List</div>
                                    <textarea name="lista_equipaje_en" class="form-control" rows="5" placeholder="Poles, sunscreen, sneakers..."><?= htmlspecialchars($datos['lista_equipaje_en']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ====== TAB 4: PRECIOS ====== -->
                    <div class="tab-pane-form" id="tab-pre" role="tabpanel">
                        <div class="row g-4">
                            <!-- Precios Principales -->
                            <div class="col-12">
                                <div class="price-group">
                                    <div class="group-title"><i class="fas fa-dollar-sign"></i> Precios Principales</div>
                                    <div class="price-field-row">
                                        <div class="form-group">
                                            <label class="field-label">Precio Adulto</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" name="precio" class="form-control" value="<?= $datos['precio'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">Precio Nino</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" name="precio_nino" class="form-control" value="<?= $datos['precio_nino'] ?>" placeholder="70% del adulto">
                                            </div>
                                            <small class="text-muted">Vacio = 70% del precio adulto</small>
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">Moneda</label>
                                            <select name="moneda" class="form-select">
                                                <option value="USD" <?= $datos['moneda'] == 'USD' ? 'selected' : '' ?>>Dolares (USD)</option>
                                                <option value="PEN" <?= $datos['moneda'] == 'PEN' ? 'selected' : '' ?>>Soles (PEN)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">% Adelanto</label>
                                            <div class="input-group">
                                                <input type="number" min="0" max="100" name="porcentaje_adelanto" class="form-control" value="<?= $datos['porcentaje_adelanto'] ?>">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">Max. Personas</label>
                                            <input type="number" min="0" max="999" name="max_personas" class="form-control" value="<?= (int)$datos['max_personas'] ?>" placeholder="0 = sin limite">
                                            <small class="text-muted">0 = sin limite</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Precio Soles -->
                            <div class="col-12">
                                <div class="price-group">
                                    <div class="group-title"><i class="fas fa-coins"></i> Precio en Soles (PEN) — Se muestra en español</div>
                                    <div class="form-helper">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Se calcula automáticamente con el tipo de cambio. Desmarque la casilla para editar manualmente.</span>
                                    </div>
                                    <div class="price-field-row">
                                        <div class="form-group">
                                            <label class="field-label">Precio Soles (S/)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">S/</span>
                                                <input type="number" step="0.01" name="precio_soles" id="precioSolesInput" class="form-control" value="<?= $datos['precio_soles'] ?? '' ?>" placeholder="Se calcula automáticamente" readonly>
                                            </div>
                                            <div class="soles-row">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" name="auto_soles" id="autoSolesCheck" value="1" checked style="border-radius:3px;">
                                                    <label class="form-check-label fw-bold" for="autoSolesCheck" id="autoSolesLabel">Automático</label>
                                                </div>
                                                <small class="text-muted mb-0" id="tipoCambioRef"></small>
                                                <div class="soles-preview">
                                                    S/ <span id="previewSoles">—</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-12">
                                <div class="checkbox-row">
                                    <label class="mb-0"><input type="checkbox" name="mostrar_precio" value="1" <?= $datos['mostrar_precio'] == 1 ? 'checked' : '' ?>> Publicar Precio</label>
                                    <label class="text-warning mb-0 fw-bold"><input type="checkbox" name="es_recomendado" value="1" <?= $datos['es_recomendado'] == 1 ? 'checked' : '' ?>> Recomendado</label>
                                    <label class="text-danger mb-0 fw-bold"><input type="checkbox" name="en_oferta" value="1" <?= $datos['en_oferta'] == 1 ? 'checked' : '' ?>> Oferta</label>
                                    <label class="text-primary mb-0 fw-bold"><input type="checkbox" name="en_menu" value="1" <?= $datos['en_menu'] == 1 ? 'checked' : '' ?>> En Menu</label>
                                </div>
                            </div>

                            <!-- Info Alimentacion ES -->
                            <div class="col-md-6">
                                <div class="price-group">
                                    <div class="group-title"><i class="fas fa-utensils"></i> Info Alimentacion (ES)</div>
                                    <div class="form-group">
                                        <label class="field-label">Comidas</label>
                                        <input type="text" name="comidas_info" class="form-control" value="<?= htmlspecialchars($datos['comidas_info']) ?>" placeholder="Desayuno, almuerzo, cena...">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Extras Texto</label>
                                        <textarea name="extras_texto" class="form-control" rows="3" placeholder="Servicios adicionales..."><?= htmlspecialchars($datos['extras_texto']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Aclimatacion y Ofertas</label>
                                        <textarea name="aclimatacion_texto" class="form-control" rows="3" placeholder="Detalles de aclimatacion..."><?= htmlspecialchars($datos['aclimatacion_texto']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Antes de Viajar</label>
                                        <textarea name="antes_de_viajar" class="form-control" rows="4" placeholder="Recomendaciones generales..."><?= htmlspecialchars($datos['antes_de_viajar']) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Alimentacion EN -->
                            <div class="col-md-6">
                                <div class="price-group">
                                    <div class="group-title"><i class="fas fa-utensils"></i> Meals Information (EN)</div>
                                    <div class="form-group">
                                        <label class="field-label">Meals</label>
                                        <input type="text" name="comidas_info_en" class="form-control" value="<?= htmlspecialchars($datos['comidas_info_en']) ?>" placeholder="Breakfast, lunch, dinner...">
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Extra Services Text</label>
                                        <textarea name="extras_texto_en" class="form-control" rows="3" placeholder="Additional services..."><?= htmlspecialchars($datos['extras_texto_en']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Aclimatation & Offers</label>
                                        <textarea name="aclimatacion_texto_en" class="form-control" rows="3" placeholder="Aclimatation details..."><?= htmlspecialchars($datos['aclimatacion_texto_en']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="field-label">Before You Travel</label>
                                        <textarea name="antes_de_viajar_en" class="form-control" rows="4" placeholder="General recommendations..."><?= htmlspecialchars($datos['antes_de_viajar_en']) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Altitud, Dificultad, YouTube -->
                            <div class="col-12">
                                <div class="price-group">
                                    <div class="group-title"><i class="fas fa-mountain"></i> Datos Tecnicos</div>
                                    <div class="price-field-row">
                                        <div class="form-group">
                                            <label class="field-label">Altitud Maxima</label>
                                            <input type="text" name="altitud_max" class="form-control" value="<?= htmlspecialchars($datos['altitud_max']) ?>" placeholder="Ej: 4,200 m.s.n.m.">
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">Dificultad</label>
                                            <select name="dificultad" class="form-select">
                                                <option value="Facil" <?= $datos['dificultad'] === 'Facil' ? 'selected' : '' ?>>Facil</option>
                                                <option value="Moderado" <?= $datos['dificultad'] === 'Moderado' ? 'selected' : '' ?>>Moderado</option>
                                                <option value="Desafiante" <?= $datos['dificultad'] === 'Desafiante' ? 'selected' : '' ?>>Desafiante</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="field-label">YouTube URL / ID</label>
                                            <input type="text" name="video_url" class="form-control" value="<?= htmlspecialchars($datos['video_url']) ?>" placeholder="URL o ID del video">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ====== TAB 5: MULTIMEDIA ====== -->
                    <div class="tab-pane-form" id="tab-mul" role="tabpanel">
                        <div class="row g-4">
                            <!-- Folleto PDF -->
                            <div class="col-md-6">
                                <div class="media-section">
                                    <h6 class="text-danger"><i class="fas fa-file-pdf"></i> Folleto PDF</h6>
                                    <?php if ($datos['folleto_pdf']): ?>
                                        <div class="pdf-preview-box mb-3">
                                            <div class="pdf-thumbnail">
                                                <embed src="../assets/pdf/<?= htmlspecialchars($datos['folleto_pdf']) ?>" type="application/pdf">
                                            </div>
                                            <div class="pdf-name"><?= htmlspecialchars($datos['folleto_pdf']) ?></div>
                                            <a href="../assets/pdf/<?= htmlspecialchars($datos['folleto_pdf']) ?>" target="_blank" class="pdf-link"><i class="fas fa-external-link-alt me-1"></i>Abrir PDF</a>
                                            <button type="button" class="btn-delete-media" onclick="borrarArchivo('folleto_pdf', '<?= $datos['id'] ?>', this)"><i class="fas fa-trash"></i> Eliminar</button>
                                        </div>
                                        <input type="hidden" name="borrar_folleto_pdf" id="borrar_folleto_pdf" value="0">
                                    <?php endif; ?>
                                    <div class="upload-zone-v2">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <div class="uz-label"><?= $datos['folleto_pdf'] ? 'Reemplazar PDF actual' : 'Subir nuevo PDF' ?></div>
                                        <div class="uz-hint">Formatos: PDF</div>
                                        <input type="file" name="nuevo_pdf" accept=".pdf">
                                    </div>
                                    <div class="mt-3">
                                        <label class="small fw-bold"><i class="fas fa-folder-open me-1"></i> O elegir del servidor:</label>
                                        <div class="pdf-server-list">
                                            <?php foreach ($pdfs_disponibles as $p): $n = basename($p); ?>
                                                <div class="pdf-server-item <?= ($datos['folleto_pdf'] === $n) ? 'selected' : '' ?>" onclick="usarPdfServidor('<?= htmlspecialchars($n) ?>', this)">
                                                    <i class="fas fa-file-pdf"></i>
                                                    <span class="pdf-server-name" title="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></span>
                                                    <span class="pdf-server-actions">
                                                        <button type="button" class="btn-pdf-preview" title="Vista previa" onclick="event.stopPropagation(); verPdfServidor('<?= htmlspecialchars($n) ?>')"><i class="fas fa-eye"></i></button>
                                                        <button type="button" class="btn-pdf-delete" title="Eliminar del servidor" onclick="event.stopPropagation(); eliminarPdfServidor('<?= htmlspecialchars($n) ?>', '<?= $datos['id'] ?>')"><i class="fas fa-trash"></i></button>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="small text-muted mt-1" id="pdf_selected_label">PDF seleccionado: <strong><?= htmlspecialchars($datos['folleto_pdf'] ?: 'ninguno') ?></strong></div>
                                        <input type="hidden" name="folleto_pdf" id="pdf_input_manual" value="<?= $datos['folleto_pdf'] ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Mapa -->
                            <div class="col-md-6">
                                <div class="media-section">
                                    <h6 class="text-success"><i class="fas fa-map-marked-alt"></i> Mapa del Tour</h6>
                                    <?php if ($datos['mapa_imagen']): ?>
                                        <div class="map-preview-box mb-3">
                                            <img src="../assets/img/mapas/<?= $datos['mapa_imagen'] ?>" alt="Mapa">
                                            <div class="map-overlay">
                                                <button type="button" class="btn-delete-media" onclick="borrarArchivo('mapa_imagen', '<?= $datos['id'] ?>', this)"><i class="fas fa-trash"></i> Quitar Mapa</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="borrar_mapa_imagen" id="borrar_mapa_imagen" value="0">
                                    <?php endif; ?>
                                    <div class="upload-zone-v2">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <div class="uz-label"><?= $datos['mapa_imagen'] ? 'Reemplazar mapa actual' : 'Subir mapa del tour' ?></div>
                                        <div class="uz-hint">Formatos: JPG, PNG, WEBP</div>
                                        <input type="file" name="mapa_imagen" accept=".jpg,.jpeg,.png,.webp" onchange="previewMapa(this)">
                                    </div>
                                    <?php if (!$datos['mapa_imagen']): ?>
                                        <div id="mapa-preview-new" class="mt-2" style="display:none;"><img src="" class="img-fluid rounded border" style="max-height:120px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Galeria Fija 4 Imagenes -->
                            <div class="col-12">
                                <div class="media-section">
                                    <h6><i class="fas fa-images text-primary"></i> Galeria Principal (4 Fotos)</h6>
                                    <div class="form-helper mb-3">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Las imagenes se guardan automaticamente en formato WEBP para mayor rendimiento. Se aceptan JPG y PNG.</span>
                                    </div>
                                    <div class="row g-3">
                                        <?php for ($i = 1; $i <= 4; $i++): $img = $datos['img_galeria' . $i]; ?>
                                            <div class="col-md-3 col-6">
                                                <div class="gallery-card">
                                                    <?php if ($img): ?>
                                                        <img src="../assets/img/tours/<?= $img ?>" alt="Galeria <?= $i ?>">
                                                    <?php else: ?>
                                                        <div class="gallery-empty"><i class="fas fa-plus opacity-25"></i></div>
                                                    <?php endif; ?>
                                                    <input type="file" name="img_galeria<?= $i ?>" class="form-control form-control-sm mb-1" accept=".jpg,.jpeg,.png,.webp" style="font-size:0.75rem;">
                                                    <input type="hidden" name="borrar_img_galeria<?= $i ?>" id="borrar_img_galeria<?= $i ?>" value="0">
                                                    <?php if ($img): ?>
                                                        <button type="button" class="btn-delete-media" style="width:100%;" onclick="borrarFija('img_galeria<?= $i ?>', this)"><i class="fas fa-trash"></i> Quitar</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria Itinerario -->
                            <div class="col-12">
                                <div class="media-section">
                                    <h6><i class="fas fa-photo-video text-warning"></i> Galeria Global de Itinerario</h6>
                                    <div class="mb-3">
                                        <label class="small fw-bold">Subir nuevas imagenes:</label>
                                        <input type="file" name="fotos_itinerario[]" multiple class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                                        <small class="text-muted">Se convierten automaticamente a WEBP</small>
                                    </div>
                                    <div id="preview-itinerario" class="d-flex flex-wrap gap-2 mb-2"></div>
                                    <span id="contador-fotos" class="badge bg-secondary">0 fotos</span>
                                    <input type="hidden" name="fotos_para_eliminar" id="fotos_para_eliminar" value="">
                                    <div class="row g-2 mt-2" id="grid-gal">
                                        <?php if ($datos['galeria_itinerario']):
                                            $gal_arr = explode(",", $datos['galeria_itinerario']);
                                            foreach ($gal_arr as $file): $file = trim($file);
                                                if (!$file) continue; ?>
                                                <div class="col-6 col-md-2 col-lg-1 text-center">
                                                    <div class="position-relative">
                                                        <img src="../assets/img/tours/<?= $file ?>" class="rounded w-100 shadow-sm" style="height: 55px; object-fit:cover;">
                                                        <button type="button" class="btn btn-danger btn-xs position-absolute top-0 end-0" onclick="eliminarGal('<?= $file ?>', this)">x</button>
                                                    </div>
                                                    <code class="d-block badge bg-light text-dark copy-badge mt-1" onclick="copyName('<?= $file ?>')"><?= substr($file, 0, 15) ?>...</code>
                                                </div>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- STICKY SAVE BAR -->
                <div class="sticky-save-bar">
                    <button type="submit" name="guardar_tour" id="btnSave" class="btn-save">
                        <i class="fas fa-save"></i>
                        <?= $edit_mode ? "Guardar Cambios" : "Publicar Tour" ?>
                    </button>
                    <span class="save-hint">Los cambios se guardan de inmediato</span>
                </div>
            </form>

            <!-- Vista Previa: Confianza -->
            <?php if (!empty($tarjetas_confianza)): ?>
                <div class="collapsible-section">
                    <div class="collapsible-header" id="toggleConfianza">
                        <div class="ch-left">
                            <i class="fas fa-handshake"></i> Vista Previa: Confianza (Por que elegirnos?)
                        </div>
                        <div class="ch-right">
                            <span class="badge bg-primary"><?= count($tarjetas_confianza) ?> tarjetas</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapsible-body" id="bodyConfianza">
                        <div class="row g-4 mt-2 mb-4">
                            <?php foreach ($tarjetas_confianza as $tc): ?>
                                <div class="col-md-4">
                                    <div class="card-conf shadow-sm">
                                        <img src="../assets/img/iconos/<?= $tc['imagen'] ?>" alt="Confianza">
                                        <div class="p-3">
                                            <h6 class="fw-bold mb-2"><?= htmlspecialchars($tc['titulo']) ?></h6>
                                            <p class="text-muted small mb-3"><?= substr($tc['descripcion'], 0, 80) ?>...</p>
                                            <a href="porque_nosotros_mant.php?editar=<?= $tc['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4">Editar</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal Vista Previa PDF -->
            <div class="modal fade" id="modalPdfPreview" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPdfTitle">Vista Previa PDF</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe id="modalPdfFrame" src="" style="width:100%;height:75vh;border:none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin_tours.js"></script>
    <script>
    (function() {
        var tipoCambio = <?= (float)$tipo_cambio_actual ?>;
        var precioInput = document.querySelector('input[name="precio"]');
        var monedaSelect = document.querySelector('select[name="moneda"]');
        var solesInput = document.getElementById('precioSolesInput');
        var autoCheck = document.getElementById('autoSolesCheck');
        var preview = document.getElementById('previewSoles');
        var ref = document.getElementById('tipoCambioRef');

        function mostrarTipoCambio() {
            if (ref) ref.textContent = 'TC: 1 USD = (S/ ' + tipoCambio.toFixed(4) + ')';
        }

        function fetchTipoCambio() {
            fetch('api/tipo_cambio.php')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.tipo_cambio && d.tipo_cambio > 0) {
                        tipoCambio = d.tipo_cambio;
                        mostrarTipoCambio();
                        calcSoles();
                    }
                })
                .catch(function() {});
        }

        function getMoneda() {
            if (!monedaSelect) return 'USD';
            return monedaSelect.value || 'USD';
        }

        function calcSoles() {
            var usd = parseFloat(precioInput.value) || 0;
            var isAuto = autoCheck && autoCheck.checked;
            var moneda = getMoneda();
            var label = document.getElementById('autoSolesLabel');
            if (label) label.textContent = isAuto ? 'Automático' : 'Manual';
            if (isAuto) {
                solesInput.removeAttribute('readonly');
                var soles = (moneda === 'PEN') ? usd.toFixed(2) : (usd * tipoCambio).toFixed(2);
                solesInput.value = usd > 0 ? soles : '0.00';
                solesInput.setAttribute('readonly', 'readonly');
            } else {
                solesInput.value = '0.00';
                solesInput.removeAttribute('readonly');
                solesInput.focus();
            }
            if (solesInput.value) {
                if (preview) preview.textContent = parseFloat(solesInput.value).toLocaleString('es-PE', {minimumFractionDigits: 2});
            }
        }

        mostrarTipoCambio();
        fetchTipoCambio();
        setInterval(fetchTipoCambio, 60000);

        if (precioInput) precioInput.addEventListener('input', calcSoles);
        if (monedaSelect) monedaSelect.addEventListener('change', calcSoles);
        if (autoCheck) autoCheck.addEventListener('change', calcSoles);
        if (solesInput) {
            solesInput.addEventListener('input', function() {
                if (preview) preview.textContent = parseFloat(this.value || 0).toLocaleString('es-PE', {minimumFractionDigits: 2});
            });
        }
        calcSoles();
    })();
    </script>
</body>
</html>
