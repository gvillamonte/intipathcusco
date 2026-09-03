document.addEventListener('DOMContentLoaded', function () {
    console.log("Detalle de Tour Cargado");

    // Animación suave de entrada
    const sidebar = document.querySelector('.ip-details-sidebar');
    const summary = document.querySelector('.ip-details-summary');

    if (sidebar && summary) {
        sidebar.style.opacity = '0';
        summary.style.opacity = '0';

        setTimeout(() => {
            sidebar.style.transition = 'opacity 0.8s ease';
            summary.style.transition = 'opacity 0.8s ease';
            sidebar.style.opacity = '1';
            summary.style.opacity = '1';
        }, 100);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const videoTrigger = document.querySelector('.ip-video-trigger');
    const videoModal = new bootstrap.Modal(document.getElementById('videoTourModal'));
    const videoIframe = document.getElementById('videoIframe');

    if (videoTrigger) {
        videoTrigger.addEventListener('click', function () {
            let rawUrl = this.getAttribute('data-video');
            let videoId = "";

            // Lógica para extraer el ID de YouTube si es un link normal o corto
            if (rawUrl.includes('v=')) {
                videoId = rawUrl.split('v=')[1].split('&')[0];
            } else if (rawUrl.includes('youtu.be/')) {
                videoId = rawUrl.split('youtu.be/')[1];
            }

            if (videoId) {
                videoIframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                videoModal.show();
            } else {
                alert("URL de video no válida");
            }
        });
    }

    // Limpiar el video al cerrar el modal para que pare el sonido
    document.getElementById('videoTourModal').addEventListener('hidden.bs.modal', function () {
        videoIframe.src = "";
    });
});


/* ==========================================================================
    INTIPATH TOURS - Lógica de Reserva (Pestaña Resumen)
   ========================================================================== */

function cambiarPax(cambio) {
    // 1. Elementos del DOM
    const input = document.getElementById('paxReserva');
    const displayTotal = document.getElementById('totalReserva');

    // Validación de seguridad
    if (!input || !displayTotal) return;

    // 2. Obtener precio base del atributo data-precio que pusimos en el HTML
    const precioUnitario = parseFloat(displayTotal.getAttribute('data-precio'));

    // 3. Calcular nueva cantidad
    let cantidadActual = parseInt(input.value);
    cantidadActual += cambio;

    // Mínimo 1 pasajero
    if (cantidadActual < 1) {
        cantidadActual = 1;
    }

    // 4. Actualizar el input
    input.value = cantidadActual;

    // 5. Calcular y formatear total
    const totalCalculado = precioUnitario * cantidadActual;

    const formatoMoneda = totalCalculado.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // 6. Mostrar en el HTML
    displayTotal.innerText = 'US$' + formatoMoneda;
}


/**
 * Inicialización de Fancybox v5 para Intipath Tours
 * Soporta Imágenes y PDFs en ventana emergente profesional
 */
document.addEventListener("DOMContentLoaded", function () {
    if (typeof Fancybox !== 'undefined') {
        Fancybox.bind("[data-fancybox]", {
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: [],
                    right: ["iterateZoom", "slideshow", "fullScreen", "download", "thumbs", "close"],
                },
            },
            Images: {
                Panzoom: { maxScale: 3 },
            },
            Html: {
                iframe: { preload: true },
            },
            l10n: {
                CLOSE: "Cerrar",
                NEXT: "Siguiente",
                PREV: "Anterior",
                MODAL: "Puedes cerrar con la tecla ESC",
                ERROR: "Error al cargar el contenido",
                IMAGE_ERROR: "No se pudo cargar la imagen",
                DOWNLOAD: "Descargar",
            }
        });
    }
});

function irAConsulta() {
    const tabBtn = document.querySelector('[data-bs-target="#consultar-det"]');
    if (tabBtn) {
        tabBtn.click();
        document.getElementById('consultar-det').scrollIntoView({ behavior: 'smooth' });
    }
}

/* Función del Widget de Reserva */

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar-widget-lateral');

    if (calendarEl) {
        var idTour = (typeof tourIdGlobal !== 'undefined') ? tourIdGlobal : 0;

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: 'auto',

            // ==========================================
            // PON ESTAS LÍNEAS AQUÍ PARA MOSTRAR TODO
            // ==========================================
            validRange: null,            // <-- 1. Evita que bloquee los días pasados
            showNonCurrentDates: true,   // <-- 2. Muestra los días grises del mes anterior
            fixedWeekCount: false,       // <-- 3. Ajusta las semanas sin deformar
            // ==========================================

            headerToolbar: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },

            eventDidMount: function (info) {
                var cell = info.el.closest('.fc-daygrid-day');
                if (cell) {
                    cell.classList.add('dia-disponible'); // Marca la celda para el CSS

                    if (typeof tippy !== 'undefined') {
                        tippy(info.el, {
                            content: `<div style="text-align:center; padding:5px;">
                                <strong style="color:#E8AC18;">${info.event.title}</strong><br>
                                <span style="color:#2ecc71;">${info.event.extendedProps.precio}</span>
                              </div>`,
                            allowHTML: true,
                            theme: 'translucent',
                            trigger: 'mouseenter click',
                            appendTo: document.body,
                            interactive: true
                        });
                    }
                }
            },
            events: 'obtener_eventos_cliente.php?id=' + idTour,
            // (Conserva tus funciones eventClick y dateClick aquí)


            eventClick: function (info) {
                info.jsEvent.preventDefault();
                if (info.event.extendedProps.tour_id) {
                    window.location.href = 'detalle_tour.php?id=' + info.event.extendedProps.tour_id;
                }
            },

            dateClick: function (info) {
                if (info.dayEl.classList.contains('dia-disponible')) {
                    document.querySelectorAll('.dia-seleccionado').forEach(function (el) {
                        el.classList.remove('dia-seleccionado');
                    });
                    info.dayEl.classList.add('dia-seleccionado');

                    var inputFecha = document.getElementById('fechaReservaSeleccionada');
                    if (inputFecha) inputFecha.value = info.dateStr;

                    var btn = document.getElementById('btnReservarAhora');
                    if (btn) {
                        btn.disabled = false;
                        btn.style.backgroundColor = '#f39c12';
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                    }

                    var msg = document.getElementById('msgAvisoFecha');
                    if (msg) msg.style.display = 'none';
                }
            }
        });

        calendar.render();
    }
});
//Función para botones de pasajeros (+ / -)
function cambiarPax(cambio) {
    let input = document.getElementById('paxReserva');
    let display = document.getElementById('totalReservaDisplay');
    let precioBase = (typeof precioBaseGlobal !== 'undefined') ? precioBaseGlobal : 0;

    if (input && display) {
        let nuevoValor = parseInt(input.value) + cambio;
        if (nuevoValor >= 1) {
            input.value = nuevoValor;
            let total = precioBase * nuevoValor;
            display.innerText = 'US$' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }
    }
}
/* ==========================================================================
    Lógica de Calendario con HOVER (Blindada)
   ========================================================================== */
// document.addEventListener('DOMContentLoaded', function () {
//     var calendarEl = document.getElementById('calendar-widget-lateral');

//     if (calendarEl) {
//         // ID del tour desde el PHP
//         var idTour = (typeof tourIdGlobal !== 'undefined') ? tourIdGlobal : 0;

//         var calendar = new FullCalendar.Calendar(calendarEl, {
//             initialView: 'dayGridMonth',
//             locale: 'es',
//             height: 'auto',
//             headerToolbar: {
//                 left: 'prev',
//                 center: 'title',
//                 right: 'next'
//             },
//             // Ruta relativa para que funcione en Local y Servidor
//             events: 'obtener_eventos_cliente.php?id=' + idTour,

//             // HOVER: Mostrar globo con imagen y resumen
//             eventDidMount: function (info) {
//                 var cell = info.el.closest('.fc-daygrid-day');
//                 if (cell && !cell.classList.contains('fc-day-past')) {
//                     cell.classList.add('dia-disponible');
//                 }

//                 if (typeof tippy !== 'undefined') {
//                     // DESTRUIR INSTANCIA PREVIA para evitar duplicados en el mismo elemento
//                     if (info.el._tippy) info.el._tippy.destroy();

//                     tippy(info.el, {
//                         content: `
//                 <a href="detalle_tour.php?id=${info.event.extendedProps.tour_id}" style="text-decoration:none; color:inherit; display:block;">
//                     <div style="width:200px; text-align:center; padding:8px; cursor:pointer;">
//                         <img src="assets/img/tours/${info.event.extendedProps.imagen}" 
//                              style="width:100%; border-radius:6px; margin-bottom:8px; border:1px solid #444; height:110px; object-fit:cover;">
//                         <strong style="color:#E8AC18; font-size:12px; display:block; line-height:1.2; margin-bottom:5px;">${info.event.title}</strong>
//                         <p style="font-size:10px; color:#ddd; margin:5px 0; line-height:1.3; text-align:justify;">
//                             ${info.event.extendedProps.resumen}
//                         </p>
//                         <div style="margin:8px 0;">
//                             <span style="font-weight:bold; color:#2ecc71; font-size:14px;">USD ${info.event.extendedProps.precio}</span>
//                         </div>
//                         <div style="font-size:10px; color:#f39c12; font-weight:bold; border-top:1px solid #444; padding-top:5px;">
//                             CLIC PARA VER DETALLES →
//                         </div>
//                     </div>
//                 </a>`,
//                         allowHTML: true,
//                         theme: 'translucent',
//                         interactive: true,
//                         placement: 'right', // CAMBIO CLAVE: 'right' o 'left' evita que tape los tours de arriba/abajo
//                         offset: [0, 20],    // Separación lateral para no tapar el calendario

//                         // LÓGICA DE LIMPIEZA:
//                         onShow(instance) {
//                             // Cierra todos los demás Tippys abiertos en la página antes de mostrar este
//                             document.querySelectorAll('[data-tippy-root]').forEach(t => {
//                                 t._tippy?.hide();
//                             });
//                         },

//                         delay: [50, 0],      // Aparece rápido, desaparece INSTANTÁNEO
//                         duration: [200, 0],  // Transición suave al entrar, ninguna al salir
//                         animation: 'fade',
//                         appendTo: document.body,
//                         boundary: 'viewport'
//                     });
//                 }
//             },

//             // CLIC: Redirigir al tour seleccionado
//             eventClick: function (info) {
//                 info.jsEvent.preventDefault();
//                 if (info.event.extendedProps.tour_id) {
//                     window.location.href = 'detalle_tour.php?id=' + info.event.extendedProps.tour_id;
//                 }
//             },

//             // SELECCIÓN: Para reservar el tour actual
//             dateClick: function (info) {
//                 if (info.dayEl.classList.contains('dia-disponible')) {
//                     document.querySelectorAll('.dia-seleccionado').forEach(function (el) {
//                         el.classList.remove('dia-seleccionado');
//                     });
//                     info.dayEl.classList.add('dia-seleccionado');

//                     var inputFecha = document.getElementById('fechaReservaSeleccionada');
//                     if (inputFecha) inputFecha.value = info.dateStr;

//                     var btn = document.getElementById('btnReservarAhora');
//                     if (btn) {
//                         btn.disabled = false;
//                         btn.removeAttribute('disabled');
//                         btn.style.backgroundColor = '#f39c12';
//                         btn.style.borderColor = '#f39c12';
//                         btn.style.color = '#ffffff';
//                         btn.style.opacity = '1';
//                         btn.style.cursor = 'pointer';
//                         btn.classList.remove('btn-secondary');
//                         btn.classList.add('btn-warning');
//                     }

//                     var msg = document.getElementById('msgAvisoFecha');
//                     if (msg) msg.style.display = 'none';
//                 }
//             },
//             validRange: { start: new Date().toISOString().split('T')[0] }
//         });

//         calendar.render();
//     }
// });

// Función para botones de pasajeros (+ / -)
function cambiarPax(cambio) {
    let input = document.getElementById('paxReserva');
    let display = document.getElementById('totalReservaDisplay');
    let precioBase = (typeof precioBaseGlobal !== 'undefined') ? precioBaseGlobal : 0;

    if (input && display) {
        let nuevoValor = parseInt(input.value) + cambio;
        if (nuevoValor >= 1) {
            input.value = nuevoValor;
            let total = precioBase * nuevoValor;
            display.innerText = 'US$' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }
    }
}