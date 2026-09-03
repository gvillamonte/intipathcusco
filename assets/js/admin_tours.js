/**
 * LÓGICA DE ADMINISTRACIÓN DE TOURS
 * Maneja alertas y confirmaciones con SweetAlert2
 * Funciona tanto en tours.php (lista) como en tour_editar.php (formulario)
 */


// ============================================================================
// 1. FUNCIONES COMUNES - CONFIRMACIONES Y ALERTAS
// ============================================================================

// Confirmar eliminación de tour
function confirmarEliminar(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer. Se eliminarán los datos y la imagen del tour.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar tour',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `procesar_tours.php?eliminar=${id}`;
        }
    });
}

// Copiar nombre al portapapeles
function copyName(n) {
    navigator.clipboard.writeText(n);
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Copiado: ' + n,
        showConfirmButton: false,
        timer: 1200
    });
}

// Marcar imagen fija para eliminar
function borrarFija(c, b) {
    document.getElementById('borrar_' + c).value = "1";
    var card = b.closest('.gallery-card') || b.closest('.card');
    if (card) card.style.opacity = '0.3';
}

// Eliminar imagen de galería itinerario
function eliminarGal(n, b) {
    Swal.fire({
        title: '¿Remover?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si'
    }).then((res) => {
        if (res.isConfirmed) {
            const inp = document.getElementById('fotos_para_eliminar');
            let vals = inp.value ? inp.value.split(',') : [];
            vals.push(n);
            inp.value = vals.join(',');
            b.closest('.col-6').classList.add('d-none');
        }
    });
}

// Borrar archivo (PDF o Mapa) con verificación de uso
function borrarArchivo(campo, tourId, btn) {
    var nombreArchivo = (campo === 'folleto_pdf')
        ? btn.closest('.pdf-preview-box').querySelector('.pdf-name').textContent
        : btn.closest('.map-preview-box').querySelector('img').src.split('/').pop();

    Swal.fire({
        title: '¿Quitar archivo?',
        text: 'Se eliminará el archivo físico y la referencia en la base de datos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar'
    }).then((res) => {
        if (res.isConfirmed) {
            var box = btn.closest('.pdf-preview-box') || btn.closest('.map-preview-box');
            if (box) {
                box.style.opacity = '0.3';
                box.style.border = '2px dashed #dc2626';
            }

            fetch('procesar_tours.php?usado_por_tours=1&archivo=' + encodeURIComponent(nombreArchivo) + '&campo=' + campo)
                .then(resp => resp.json())
                .then(data => {
                    if (data.usado) {
                        if (box) {
                            box.style.opacity = '';
                            box.style.border = '';
                        }
                        var nombres = (data.tours || []).map(function(t) { return t.titulo; });
                        Swal.fire({
                            title: 'No se puede eliminar',
                            html: 'Este archivo ya está siendo utilizado por otro tour o caminata y no se puede borrar.<br><br><strong>' + nombres.join(', ') + '</strong>',
                            icon: 'error',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc2626'
                        });
                        return false;
                    }
                    return fetch('procesar_tours.php?eliminar_archivo=' + encodeURIComponent(nombreArchivo) + '&tour_id=' + tourId + '&campo=' + campo);
                })
                .then((resp) => {
                    if (resp === false) return;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Archivo eliminado',
                        showConfirmButton: false,
                        timer: 1500
                    });
                })
                .catch((err) => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Error al eliminar',
                        showConfirmButton: false,
                        timer: 1500
                    });
                });
        }
    });
}

// Vista previa de PDF del servidor
function verPdfServidor(nombre) {
    document.getElementById('modalPdfFrame').src = '../assets/pdf/' + encodeURIComponent(nombre);
    document.getElementById('modalPdfTitle').textContent = nombre;
    var modal = new bootstrap.Modal(document.getElementById('modalPdfPreview'));
    modal.show();
}

// Seleccionar PDF del servidor
function usarPdfServidor(nombre, el) {
    document.getElementById('pdf_input_manual').value = nombre;
    document.querySelectorAll('.pdf-server-item').forEach(function(i) { i.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('pdf_selected_label').innerHTML = 'PDF seleccionado: <strong>' + nombre + '</strong>';
}

// Eliminar PDF del servidor
function eliminarPdfServidor(nombre, tourId) {
    Swal.fire({
        title: '¿Eliminar PDF del servidor?',
        text: 'Se borrará el archivo físico de assets/pdf/. No se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        confirmButtonColor: '#dc2626'
    }).then((res) => {
        if (!res.isConfirmed) return;
        fetch('procesar_tours.php?usado_por_tours=1&archivo=' + encodeURIComponent(nombre) + '&campo=folleto_pdf')
            .then(resp => resp.json())
            .then(data => {
                if (data.usado) {
                    var nombres = (data.tours || []).map(function(t) { return t.titulo; });
                    Swal.fire({
                        title: 'No se puede eliminar',
                        html: 'Este PDF ya está siendo utilizado por otro tour o caminata y no se puede borrar.<br><br><strong>' + nombres.join(', ') + '</strong>',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc2626'
                    });
                    return;
                }
                var url = 'procesar_tours.php?eliminar_pdf_servidor=' + encodeURIComponent(nombre);
                if (tourId) url += '&tour_id=' + tourId;
                window.location.href = url;
            })
            .catch(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Error al verificar el archivo',
                    showConfirmButton: false,
                    timer: 1500
                });
            });
    });
}

// Preview de mapa al subir
function previewMapa(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById('mapa-preview-new');
            if (prev) {
                prev.style.display = 'block';
                prev.querySelector('img').src = e.target.result;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// ============================================================================
// 2. INICIALIZACIÓN AL CARGAR LA PÁGINA
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    // Manejar parámetros de respuesta (?res=success&msg=...)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('res')) {
        const resultado = urlParams.get('res');
        const mensaje = urlParams.get('msg');

        let tituloAlerta = resultado === 'success' ? '¡Excelente!' : 'Hubo un error';
        if (resultado === 'success') {
            const msgLower = mensaje.toLowerCase();
            if (msgLower.includes('publicado')) {
                tituloAlerta = '¡Tour Publicado!';
            } else if (msgLower.includes('actualizado') || msgLower.includes('editado')) {
                tituloAlerta = '¡Cambios Guardados!';
            } else if (msgLower.includes('eliminado')) {
                tituloAlerta = '¡Registro Borrado!';
            }
        }

        Swal.fire({
            icon: resultado === 'success' ? 'success' : 'error',
            title: tituloAlerta,
            text: mensaje,
            showConfirmButton: true,
            confirmButtonColor: '#15305D',
            timer: 5000
        });

        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
    }

    // Previsualización de imagen antes de subir (solo en formulario)
    const inputImagen = document.querySelector('input[name="imagen"]');
    if (inputImagen) {
        inputImagen.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                console.log("Imagen seleccionada: " + this.files[0].name);
            }
        });
    }

    // ========================================================================
    // 3. FILTROS PARA LA VISTA GRID (tours.php)
    // ========================================================================
    const grid = document.getElementById('toursGrid');
    if (grid) {
        const cards = grid.querySelectorAll('.tour-card');
        const filters = {
            search: document.getElementById('filterSearch'),
            categoria: document.getElementById('filterCategoria'),
            tipo: document.getElementById('filterTipo'),
            estado: document.getElementById('filterEstado'),
            enmenu: document.getElementById('filterEnMenu'),
            moneda: document.getElementById('filterMoneda')
        };

        function applyFilters() {
            const searchVal = filters.search.value.toLowerCase().trim();
            const catVal = filters.categoria.value;
            const tipoVal = filters.tipo.value;
            const estadoVal = filters.estado.value;
            const enmenuVal = filters.enmenu.value;
            const monedaVal = filters.moneda.value;

            let visibleCount = 0;

            cards.forEach(card => {
                const titulo = card.querySelector('.card-title').textContent.toLowerCase();
                const cat = card.dataset.categoria;
                const tipo = card.dataset.tipo;
                const estado = card.dataset.estado;
                const enmenu = card.dataset.enmenu;
                const moneda = card.dataset.moneda;

                let show = true;

                if (searchVal && !titulo.includes(searchVal)) show = false;
                if (catVal && cat !== catVal) show = false;
                if (tipoVal && tipo !== tipoVal) show = false;
                if (estadoVal && estado !== estadoVal) show = false;
                if (enmenuVal !== '' && enmenu !== enmenuVal) show = false;
                if (monedaVal && moneda !== monedaVal) show = false;

                if (show) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Mostrar/ocultar estado vacío
            let emptyState = grid.querySelector('.empty-state');
            if (visibleCount === 0 && cards.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.style.gridColumn = '1 / -1';
                    emptyState.innerHTML = '<i class="fas fa-filter"></i><h4>Sin resultados</h4><p>No hay tours que coincidan con los filtros seleccionados</p>';
                    grid.appendChild(emptyState);
                }
            } else if (emptyState) {
                emptyState.remove();
            }
        }

        // Event listeners
        let debounceTimer;
        filters.search.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilters, 300);
        });
        ['categoria', 'tipo', 'estado', 'enmenu', 'moneda'].forEach(key => {
            if (filters[key]) {
                filters[key].addEventListener('change', applyFilters);
            }
        });
    }

    // ========================================================================
    // 4. PREVIEW DE IMÁGENES EN GALERÍA ITINERARIO (tour_editar.php)
    // ========================================================================
    const inputItinerario = document.querySelector('input[name="fotos_itinerario[]"]');
    if (inputItinerario) {
        inputItinerario.addEventListener('change', function() {
            generarMiniaturas(this);
        });
    }

    // Preview miniaturas de galería itinerario
    window.generarMiniaturas = function(input) {
        const contenedor = document.getElementById('preview-itinerario');
        const contador = document.getElementById('contador-fotos');
        const placeholder = document.getElementById('placeholder-text');

        if (!contenedor) return;

        contenedor.innerHTML = '';

        if (input.files && input.files.length > 0) {
            if (contador) {
                contador.innerText = `${input.files.length} fotos seleccionadas`;
                contador.classList.replace('bg-secondary', 'bg-primary');
            }
            if (placeholder) placeholder.remove();

            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = "position-relative";
                    div.innerHTML = `
                        <img src="${e.target.result}" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;" 
                             class="img-thumbnail" 
                             title="${file.name}">
                    `;
                    contenedor.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        } else {
            if (contador) {
                contador.innerText = "0 fotos";
                contador.classList.replace('bg-primary', 'bg-secondary');
            }
        }
    };

    // Mostrar nombres de galería (función legacy)
    window.mostrarNombresGaleria = function(input) {
        const preview = document.getElementById('preview-nombres-galeria');
        if (!preview) return;
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'col-md-3 text-center mb-2';
                    div.innerHTML = `
                        <div class="p-2 border bg-warning bg-opacity-10 rounded shadow-sm">
                            <img src="${e.target.result}" class="img-fluid rounded mb-1" style="height: 80px; object-fit: cover;">
                            <code class="d-block small text-danger" style="user-select: all;">${file.name}</code>
                            <small class="text-muted" style="font-size:0.6rem;">(Nuevo - Pendiente guardar)</small>
                        </div>
                    `;
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    };

    // Selector de PDF (legacy)
    const pdfSelector = document.getElementById('pdf_selector');
    if (pdfSelector) {
        pdfSelector.addEventListener('change', function() {
            let fullPath = this.value;
            if (fullPath) {
                let startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                let filename = fullPath.substring(startIndex);
                if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                    filename = filename.substring(1);
                }
                let nombreLimpio = filename.replace(/\s+/g, '_').toLowerCase();
                const nombreInput = document.getElementById('nombre_archivo_generado');
                if (nombreInput) nombreInput.value = nombreLimpio;
            }
        });
    }
});

// Copiar nombre PDF (legacy)
function copiarNombrePDF() {
    let copyText = document.getElementById("nombre_archivo_generado");
    if (copyText) {
        if (copyText.value === "" || copyText.value === "Aqui aparecera el nombre...") {
            alert("Primero selecciona un archivo PDF");
            return;
        }
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Copiado: " + copyText.value);
    }
}


// ============================================================================
// 5. TOUR EDITAR - FUNCIONALIDADES DE FORMULARIO MEJORADO
// ============================================================================

// --- 5a. Custom Tabs con indicadores de completitud ---
function initCustomTabs() {
    const tabs = document.querySelectorAll('.nav-tab-item');
    if (tabs.length === 0) return;

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const target = this.dataset.target;

            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');

            document.querySelectorAll('.tab-pane-form').forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            var targetPane = document.querySelector(target);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

function updateTabChecks() {
    var tabFields = {
        'tab-gen': ['input[name="titulo"]', 'select[name="id_categoria"]'],
        'tab-iti': ['textarea[name="itinerario_resumen"]', 'textarea[name="itinerario"]'],
        'tab-inc': ['textarea[name="incluye"]', 'textarea[name="no_incluye"]'],
        'tab-pre': ['input[name="precio"]'],
        'tab-mul': []
    };

    Object.keys(tabFields).forEach(function(tabId) {
        var tabBtn = document.querySelector('.nav-tab-item[data-target="#' + tabId + '"]');
        if (!tabBtn) return;
        var check = tabBtn.querySelector('.tab-check');
        if (!check) return;

        var fields = tabFields[tabId];
        if (fields.length === 0) {
            check.className = 'tab-check';
            return;
        }

        var filled = 0;
        fields.forEach(function(sel) {
            var el = document.querySelector(sel);
            if (el && el.value && el.value.trim() !== '') filled++;
        });

        check.className = 'tab-check';
        if (filled === fields.length) {
            check.classList.add('complete');
            check.innerHTML = '<i class="fas fa-check"></i>';
        } else if (filled > 0) {
            check.classList.add('partial');
            check.innerHTML = '<i class="fas fa-minus"></i>';
        } else {
            check.innerHTML = '';
        }
    });
}

// --- 5b. Cover Image Preview ---
function initCoverPreview() {
    var inputImagen = document.querySelector('input[name="imagen"]');
    if (!inputImagen) return;

    inputImagen.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var card = document.getElementById('coverUploadCard');
                if (!card) return;
                var img = card.querySelector('img');
                var empty = card.querySelector('.cover-empty');
                var overlay = card.querySelector('.cover-overlay');

                if (empty) empty.style.display = 'none';
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                if (overlay) {
                    overlay.innerHTML = '<i class="fas fa-camera"></i><span>Cambiar imagen</span>';
                }
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// --- 5c. Sticky Save + Loading State ---
function initStickySave() {
    var form = document.getElementById('tourForm');
    if (!form) return;

    form.addEventListener('submit', function() {
        var btn = document.getElementById('btnSave');
        if (!btn) return;

        btn.classList.add('loading');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';

        setTimeout(function() {
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="fas fa-save"></i> Guardando cambios';
        }, 15000);
    });
}

// --- 5d. Auto-Resize Textareas ---
function initAutoResize() {
    document.querySelectorAll('textarea.form-control').forEach(function(ta) {
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 400) + 'px';
        });
    });
}

// --- 5e. Collapsible Sections ---
function initCollapsibles() {
    document.querySelectorAll('.collapsible-header').forEach(function(header) {
        header.addEventListener('click', function() {
            this.classList.toggle('open');
            var body = this.nextElementSibling;
            if (body) {
                body.classList.toggle('open');
            }
        });
    });
}

// --- 5f. Preview de galeria itinerario mejorado ---
function initItinerarioPreview() {
    var inputItinerario = document.querySelector('input[name="fotos_itinerario[]"]');
    if (!inputItinerario) return;

    inputItinerario.addEventListener('change', function() {
        var contenedor = document.getElementById('preview-itinerario');
        var contador = document.getElementById('contador-fotos');
        if (!contenedor) return;

        contenedor.innerHTML = '';

        if (this.files && this.files.length > 0) {
            if (contador) {
                contador.textContent = this.files.length + ' fotos seleccionadas';
            }

            Array.from(this.files).forEach(function(file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var div = document.createElement('div');
                    div.className = 'position-relative';
                    div.innerHTML = '<img src="' + e.target.result + '" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">';
                    contenedor.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
    });
}

// --- 5g. Inicializacion para tour_editar.php ---
document.addEventListener('DOMContentLoaded', function() {
    var tourForm = document.getElementById('tourForm');
    if (!tourForm) return;

    initCustomTabs();
    initCoverPreview();
    initStickySave();
    initAutoResize();
    initCollapsibles();
    initItinerarioPreview();
    updateTabChecks();

    document.querySelectorAll('#tourForm input, #tourForm textarea, #tourForm select').forEach(function(el) {
        el.addEventListener('change', updateTabChecks);
        el.addEventListener('input', updateTabChecks);
    });

    document.querySelectorAll('.upload-zone-v2').forEach(function(zone) {
        zone.addEventListener('click', function() {
            var input = this.querySelector('input[type="file"]');
            if (input) input.click();
        });
    });

    document.querySelectorAll('.cover-upload-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var input = document.getElementById('inputImagenCover');
            if (input) input.click();
        });
    });
});