function abrirModalEditar(id, tourId, nombre) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tour_id').value = tourId; // Aquí asigna el tour correcto
    document.getElementById('edit_nombre').value = nombre;
    
    const myModal = new bootstrap.Modal(document.getElementById('modalEditar'));
    myModal.show();
}

// Función SweetAlert para eliminar
function confirmarEliminar(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'tipos_tours.php?eliminar=' + id;
        }
    })
}

// Alerta SweetAlert para resultados (res y msg en la URL)
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('res')) {
    Swal.fire({
        icon: urlParams.get('res'),
        title: urlParams.get('msg'),
        timer: 2000,
        showConfirmButton: false
    });
    // Limpia la URL
    window.history.replaceState({}, document.title, "tipos_tours.php");
}
/* ================================================================
   JS GESTIÓN DE CAMINATAS - INTIPATH TOURS
   ================================================================ */

/**
 * Función para confirmar la desvinculación de un tour hijo
 * @param {number} id - ID del tour que se va a desvincular
 */
function confirmarDesvincular(id) {
    Swal.fire({
        title: '¿Desvincular caminata?',
        text: "El tour dejará de aparecer en el menú desplegable del tour principal, pero NO se borrará de la base de datos.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e67e22', // Naranja IntiPath
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desvincular',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirigimos al mismo PHP con el parámetro eliminar
            window.location.href = 'tipos_tours.php?eliminar=' + id;
        }
    });
}

/**
 * Manejo de alertas automáticas al cargar la página
 * Detecta los parámetros 'res' y 'msg' en la URL enviados por PHP
 */
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('res')) {
        const tipoRes = urlParams.get('res'); // 'success' o 'error'
        const mensaje = urlParams.get('msg');

        Swal.fire({
            title: tipoRes === 'success' ? '¡Excelente!' : 'Atención',
            text: decodeURIComponent(mensaje.replace(/\+/g, ' ')),
            icon: tipoRes,
            confirmButtonColor: '#15305D', // Azul IntiPath
            timer: 3000,
            timerProgressBar: true
        });

        // Limpiar la URL para que no repita la alerta al refrescar
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

/* ================================================================
   FIN DEL SCRIPT
   ================================================================ */

