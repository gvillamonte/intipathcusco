/**
 * admin_confianza.js
 * Funciones para la gestión de bloques de confianza
 */

function confirmarEliminar(id) {
    Swal.fire({
        title: '¿Eliminar bloque?',
        text: "Esta acción no se puede deshacer y afectará a todos los tours.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#15305d', // Azul Corporativo
        cancelButtonColor: '#dc3545', // Rojo
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'porque_nosotros_mant.php?eliminar=' + id;
        }
    });
}

// Detectar alertas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    
    // 1. Si venimos de una ELIMINACIÓN
    if (params.get('res') === 'deleted') {
        Swal.fire({
            title: '¡Eliminado!',
            text: 'El bloque ha sido removido correctamente.',
            icon: 'success',
            confirmButtonColor: '#15305d'
        });
        // LIMPIAR URL: Quita el "?res=deleted" para que no vuelva a salir al guardar
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // 2. Opcional: Si quieres que también avise al guardar (puedes añadir esto en tu PHP)
    // En tu PHP al guardar podrías redirigir a: porque_nosotros_mant.php?res=updated
    if (params.get('res') === 'updated') {
        Swal.fire({
            title: '¡Guardado!',
            text: 'Todos los cambios se aplicaron con éxito.',
            icon: 'success',
            confirmButtonColor: '#15305d'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});