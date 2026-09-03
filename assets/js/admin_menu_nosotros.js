
    // Esperamos a que el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Si existe el parámetro status y es success
        if (urlParams.get('status') === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado!',
                text: 'Los cambios se actualizaron correctamente.',
                confirmButtonColor: '#E8AC18',
                timer: 2000, // Se cierra solo en 2 segundos
                timerProgressBar: true
            }).then(() => {
                // ESTO LIMPIA LA URL: Quita el ?status=success para que al presionar F5 no salga de nuevo
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }

        // Si hay un error
        if (urlParams.get('status') === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un problema al guardar los cambios.',
                confirmButtonColor: '#15305D'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    });


/**
 * ARCHIVO: admin_menu_mision.php
 * DESCRIPCIÓN: Panel para gestionar Misión, Visión y Políticas de IntiPath Tours.
 */





