<?php
$pagina_actual = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($pagina_actual === 'detalle_tour.php' && isset($t) && isset($t['id'])):
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js" defer></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js' defer></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js' defer></script>
<script src="https://unpkg.com/@popperjs/core@2" defer></script>
<script src="https://unpkg.com/tippy.js@6" defer></script>

<script>
var tourIdGlobal = <?= (int)$t['id'] ?>;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-widget-lateral');
    if (!calendarEl) return;
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        height: 'auto',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        displayEventTime: false,
        dayMaxEvents: false,
        events: 'obtener_eventos_cliente.php?id=' + tourIdGlobal,
        eventDidMount: function(info) {
            var cell = info.el.closest('.fc-daygrid-day');
            if (!cell) return;
            cell.classList.add('dia-disponible');
            if (typeof tippy !== 'undefined') {
                tippy(cell, {
                    content: '<a href="detalle_tour.php?id=' + info.event.extendedProps.tour_id + '" style="text-decoration:none; color:inherit; display:block;">' +
                        '<div style="width:200px; text-align:center; padding:8px; cursor:pointer;">' +
                        '<img src="assets/img/tours/' + info.event.extendedProps.imagen + '" style="width:100%; border-radius:6px; margin-bottom:8px; border:1px solid #444; height:110px; object-fit:cover;">' +
                        '<strong style="color:#E8AC18; font-size:12px; display:block; line-height:1.2; margin-bottom:5px;">' + info.event.title + '</strong>' +
                        '<p style="font-size:10px; color:#ddd; margin:5px 0; line-height:1.3; text-align:justify;">' + info.event.extendedProps.resumen + '</p>' +
                        '<div style="margin:8px 0;"><span style="font-weight:bold; color:#0f9b9e; font-size:14px;">USD ' + info.event.extendedProps.precio + '</span></div>' +
                        '<div style="font-size:10px; color:#c6d544; font-weight:bold; border-top:1px solid #444; padding-top:5px;">CLIC PARA VER DETALLES →</div></div></a>',
                    allowHTML: true, theme: 'translucent', trigger: 'mouseenter', appendTo: document.body, interactive: true
                });
            }
        },
        dateClick: function(info) {
            document.querySelectorAll('.dia-seleccionado').forEach(function(el) { el.classList.remove('dia-seleccionado'); });
            info.dayEl.classList.add('dia-seleccionado');
            var inputFecha = document.getElementById('fechaReservaSeleccionada');
            if (inputFecha) inputFecha.value = info.dateStr;
            var partes = info.dateStr.split('-');
            var fechaObj = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            var opciones = { year: 'numeric', month: 'long', day: 'numeric' };
            var fechaFormateada = fechaObj.toLocaleDateString('es-ES', opciones);
            var confirmDiv = document.getElementById('fechaConfirmacion');
            if (confirmDiv) {
                confirmDiv.innerHTML = '✅ ' + ('<?= ($idioma ?? 'es') == 'en' ? "Selected" : "Has seleccionado" ?>') + ': ' + fechaFormateada;
                confirmDiv.style.display = 'block';
            }
            var btn = document.getElementById('btnReservarAhora');
            if (btn) {
                btn.disabled = false;
                btn.style.backgroundColor = '#c6d544';
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            var msg = document.getElementById('msgAvisoFecha');
            if (msg) msg.style.display = 'none';
        }
    });
    calendar.render();
});

function agregarBloque(containerId, tipo) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var template = container.querySelector('.pasajero-template');
    if (!template) return;
    var idx = container.querySelectorAll('.pasajero-block:not(.pasajero-template)').length;
    var clone = template.cloneNode(true);
    clone.className = 'pasajero-block row g-2 mt-1 align-items-end';
    clone.style.display = '';
    clone.removeAttribute('id');
    clone.querySelectorAll('[name]').forEach(function(el) {
        var name = el.getAttribute('name');
        if (name) el.setAttribute('name', name.replace('INDEX', idx));
    });
    if (tipo) {
        var sel = clone.querySelector('select[name*="[tipo]"]');
        if (sel) sel.value = tipo;
    }
    template.insertAdjacentElement('beforebegin', clone);
    actualizarConteo(containerId);
}

function eliminarBloque(btn) {
    var block = btn.closest('.pasajero-block');
    if (!block || block.classList.contains('pasajero-template')) return;
    var container = block.closest('[id^="pasajeros-container"]');
    if (container && container.querySelectorAll('.pasajero-block:not(.pasajero-template)').length <= 1) return;
    block.remove();
    if (container) actualizarConteo(container.id);
}

function actualizarConteo(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var blocks = container.querySelectorAll('.pasajero-block:not(.pasajero-template)');
    var a = 0, n = 0;
    blocks.forEach(function(b) {
        var sel = b.querySelector('select[name*="[tipo]"]');
        if (sel && sel.value === 'adulto') a++; else n++;
    });
    var form = container.closest('form');
    if (form) {
        var ai = form.querySelector('input[name="adultos"]');
        var ni = form.querySelector('input[name="ninos"]');
        if (ai) ai.value = a;
        if (ni) ni.value = n;
    }
    if (typeof actualizarResumenPago === 'function') {
        actualizarResumenPago();
    }
}
</script>
<?php endif; ?>
