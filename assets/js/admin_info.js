function confirmarEliminarInfo(id) {
    Swal.fire({
        title: '¿Eliminar tarjeta?',
        text: "Se borrará del menú principal.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#15305D',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'procesar_info.php?eliminar=' + id;
        }
    });
}

// Alertas de SweetAlert al cargar
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('res')) {
        Swal.fire({
            icon: urlParams.get('res'),
            title: urlParams.get('res') === 'success' ? '¡Éxito!' : 'Aviso',
            text: urlParams.get('msg'),
            showConfirmButton: false,
            timer: 2500
        });
        window.history.replaceState({}, document.title, "info_viaje.php");
    }
});