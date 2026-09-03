/**
 * IntiPath Tours - Main JavaScript (Versión Final Sidebar Admin)
 */

document.addEventListener("DOMContentLoaded", function () {

    /* 1. NAVEGACIÓN WEB (Menú Móvil de Tours) */
    // Solo se ejecuta si existen los elementos del menú móvil
    const btnOpen = document.getElementById('ipBtnOpen');
    const overlay = document.getElementById('ipOverlay');
    if (btnOpen && overlay) {
        btnOpen.onclick = (e) => { e.preventDefault(); overlay.classList.add('active'); document.body.style.overflow = 'hidden'; };
        const btnClose = document.getElementById('ipBtnClose');
        if (btnClose) btnClose.onclick = () => { overlay.classList.remove('active'); document.body.style.overflow = 'auto'; };
    }

    /* 2. ACORDEÓN DE TOURS (Móvil) */
    const mAccordionHeaders = document.querySelectorAll('.ip-m-accordion-header');
    mAccordionHeaders.forEach(header => {
        header.onclick = function(e) {
            if (window.innerWidth <= 991) {
                const parent = this.parentElement;
                if (!parent.classList.contains('active')) {
                    e.preventDefault();
                    document.querySelectorAll('.ip-has-mega').forEach(li => li.classList.remove('active'));
                    parent.classList.add('active');
                }
            }
        };
    });

    /* ============================================================
       3. SIDEBAR ÚNICO PARA ADMINISTRADOR (Contenido, etc.)
       ============================================================ */
    // Buscamos los enlaces del sidebar que tienen la flechita
    const sidebarItems = document.querySelectorAll('.sidebar-wrapper .nav-link, .sidebar-wrapper .sidebar-link');

    sidebarItems.forEach(item => {
        item.onclick = function(e) {
            // Buscamos el submenú (UL) dentro del mismo elemento padre (LI)
            const parentLi = this.closest('li');
            const submenu = parentLi.querySelector('ul');
            const arrow = this.querySelector('.fa-chevron-down');

            if (submenu) {
                e.preventDefault();
                
                // Verificamos si ya está abierto
                const isOpened = submenu.style.display === "block";

                // Opcional: Cerrar otros menús abiertos en el sidebar para mantener el orden
                const allSubmenus = document.querySelectorAll('.sidebar-wrapper ul ul');
                allSubmenus.forEach(s => { if(s !== submenu) s.style.display = "none"; });
                document.querySelectorAll('.sidebar-wrapper .nav-link').forEach(l => l.classList.remove('active'));

                // Abrir o cerrar el actual
                if (isOpened) {
                    submenu.style.display = "none";
                    this.classList.remove('active');
                    if(arrow) arrow.style.transform = "rotate(0deg)";
                } else {
                    submenu.style.display = "block";
                    this.classList.add('active');
                    if(arrow) arrow.style.transform = "rotate(180deg)";
                }
            }
        };
    });

    /* ============================================================
       4. OTRAS FUNCIONES (FAQ, Formulario AJAX, etc.)
       ============================================================ */
    
    // FAQ Acordeón
    const faqButtons = document.querySelectorAll('.faq-ip-question');
    faqButtons.forEach(button => {
        button.onclick = function() {
            const item = this.parentElement;
            const isOpen = item.classList.contains('active');
            document.querySelectorAll('.faq-ip-item').forEach(other => {
                other.classList.remove('active');
                const ans = other.querySelector('.faq-ip-answer');
                if(ans) ans.style.maxHeight = null;
            });
            if (!isOpen) {
                item.classList.add('active');
                const answer = item.querySelector('.faq-ip-answer');
                if(answer) answer.style.maxHeight = answer.scrollHeight + "px";
            }
        };
    });

    // Formulario AJAX
    const formCont = document.getElementById('formContacto');
    if (formCont) {
        formCont.onsubmit = function(e) {
            e.preventDefault();
            const btn = this.querySelector('.ip-submit-btn');
            const alertBox = document.getElementById('ip-mensaje-ajax');
            const fData = new FormData(this);
            fData.append('ajax', 'true');
            if(btn) { btn.disabled = true; btn.innerHTML = 'Enviando...'; }
            fetch('admin/enviar_consulta.php', { method: 'POST', body: fData })
            .then(res => res.text())
            .then(data => {
                if (data.includes("success")) {
                    if(alertBox) { alertBox.style.display = 'flex'; alertBox.innerHTML = '¡Enviado!'; alertBox.className = 'ip-alert-success-ajax'; }
                    this.reset();
                }
            })
            .finally(() => { if(btn) { btn.disabled = false; btn.innerHTML = 'Enviar'; } });
        };
    }
});
/* ============================================================
   SIDEBAR ADMINISTRADOR (Función toggleAcordeon)
   ============================================================ */
function toggleAcordeon(id, elemento, event) {
    // Detener propagación para evitar que se cierre al hacer clic en enlaces
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    // 1. Buscamos el submenú por su ID (ej: grupo-contenido)
    const submenu = document.getElementById(id);
    const flecha = elemento.querySelector('.acordeon-flecha');

    if (submenu) {
        // Verificamos si está abierto revisando el display o la clase
        const estaAbierto = submenu.classList.contains('abierto') || submenu.style.display === "block";

        if (estaAbierto) {
            // CERRAR
            submenu.style.display = "none";
            submenu.classList.remove('abierto');
            if (flecha) flecha.classList.remove('rotada');
        } else {
            // ABRIR
            // Opcional: Cerrar otros acordeones primero para que se vea limpio
            document.querySelectorAll('.acordeon-contenido').forEach(ul => {
                ul.style.display = "none";
                ul.classList.remove('abierto');
            });
            document.querySelectorAll('.acordeon-flecha').forEach(f => f.classList.remove('rotada'));

            // Abrir el actual
            submenu.style.display = "block";
            submenu.classList.add('abierto');
            if (flecha) flecha.classList.add('rotada');

            // Desplazar el sidebar (scroll propio) para que el submódulo abierto quede visible
            setTimeout(function () {
                const sb = document.querySelector('.sidebar');
                if (!sb) return;
                const sRect = sb.getBoundingClientRect();
                const mRect = submenu.getBoundingClientRect();
                if (mRect.bottom > sRect.bottom) {
                    sb.scrollTop += (mRect.bottom - sRect.bottom) + 12;
                } else if (mRect.top < sRect.top) {
                    sb.scrollTop += (mRect.top - sRect.top) - 12;
                }
            }, 80);
        }
    }
}

// Para que la página sepa qué menú dejar abierto al cargar
document.addEventListener("DOMContentLoaded", function() {
    // Buscamos el que tenga la clase 'abierto' por PHP y le damos el estilo block
    const menuActivo = document.querySelector('.acordeon-contenido.abierto');
    if (menuActivo) {
        menuActivo.style.display = "block";
        // Desplazar el sidebar hasta el grupo abierto (si quedó debajo del borde visible)
        const sb = document.querySelector('.sidebar');
        if (sb) {
            const sRect = sb.getBoundingClientRect();
            const mRect = menuActivo.getBoundingClientRect();
            if (mRect.bottom > sRect.bottom) {
                sb.scrollTop += (mRect.bottom - sRect.bottom) + 12;
            } else if (mRect.top < sRect.top) {
                sb.scrollTop += (mRect.top - sRect.top) - 12;
            }
        }
    }
});



/* 5. CONTROL MEGA MENÚ (PC) - Actualizado para incluir NOSOTROS */
document.addEventListener("DOMContentLoaded", function () {
    const megaMenuItems = document.querySelectorAll('.ip-has-mega');

    megaMenuItems.forEach(item => {
        let timer;
        // AQUÍ ESTÁ EL CAMBIO: Buscamos .ip-mega-panel (Tours) O .ip-mega-menu (Nosotros)
        const panel = item.querySelector('.ip-mega-panel, .ip-mega-menu');

        if (panel) {
            const openPanel = () => {
                clearTimeout(timer);
                panel.style.display = 'block';
                panel.style.opacity = '1';
                panel.style.visibility = 'visible';
            };

            const closePanel = () => {
                timer = setTimeout(() => {
                    panel.style.display = 'none';
                    panel.style.opacity = '0';
                    panel.style.visibility = 'hidden';
                }, 250);
            };

            item.addEventListener('mouseenter', openPanel);
            item.addEventListener('mouseleave', closePanel);
            panel.addEventListener('mouseenter', openPanel);
            panel.addEventListener('mouseleave', closePanel);
        }
    });
});



//licencias js

// Inicialización de Fancybox para Licencias
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Fancybox !== "undefined") {
        Fancybox.bind("[data-fancybox='gallery-licencias']", {
            // Animación suave de apertura
            showClass: "f-fadeIn",
            hideClass: "f-fadeOut",
            // Mostrar título en la parte inferior
            caption: function (fancybox, slide) {
                return slide.caption || "";
            }
        });
    }
});



/* 6. LAZY LOAD DE FONDOS (data-bg-lazy) - Carga imagenes de fondo al acercarse al scroll */
document.addEventListener("DOMContentLoaded", function () {
    const bgLazyEls = document.querySelectorAll('[data-bg-lazy]');
    if (bgLazyEls.length === 0) return;

    const aplicar = function (el) {
        const url = el.getAttribute('data-bg-lazy');
        if (url) {
            el.style.backgroundImage = "url('" + url + "')";
        }
        el.removeAttribute('data-bg-lazy');
    };

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    aplicar(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '300px 0px' });
        bgLazyEls.forEach(function (el) { io.observe(el); });
    } else {
        bgLazyEls.forEach(aplicar);
    }
});


/* 7. TOGGLE DE SONIDO DEL SLIDER (cookie + auto-activar en primera interacción) */
(function () {
    var btn = document.getElementById('ipSndToggle');
    if (!btn) return;

    var iconOn  = btn.querySelector('.fa-volume-up');
    var iconOff = btn.querySelector('.fa-volume-mute');

    function setCookie(n, v) { document.cookie = n + '=' + v + ';path=/;max-age=' + (86400 * 365); }
    function getCookie(n) { var c = document.cookie.split(';').find(function (x) { return x.trim().indexOf(n + '=') === 0; }); return c ? c.split('=')[1] : null; }

    var soundOn = getCookie('ip_sound') === '1';

    function updateIcon() {
        if (iconOn)  iconOn.style.display  = soundOn ? '' : 'none';
        if (iconOff) iconOff.style.display = soundOn ? 'none' : '';
    }
    updateIcon();

    function applyToVideo() {
        var vid = document.querySelector('.main-s-slide.is-active video');
        if (!vid) return;
        vid.muted = !soundOn;
        if (soundOn) { vid.volume = 1; vid.play().catch(function () {}); }
    }

    btn.onclick = function () {
        soundOn = !soundOn;
        setCookie('ip_sound', soundOn ? '1' : '0');
        applyToVideo();
        updateIcon();
    };

    if (soundOn) {
        var activate = function () {
            applyToVideo();
            document.removeEventListener('click', activate);
        };
        document.addEventListener('click', activate, { once: true });
    }
})();