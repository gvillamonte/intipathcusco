/**
 * LÓGICA DE CONTROL - ADMIN NOSOTROS
 * IntiPath Tours
 */

document.addEventListener("DOMContentLoaded", function() {
    
    const formNosotros = document.querySelector('form');

    if (formNosotros) {
        formNosotros.addEventListener('submit', function(e) {
            e.preventDefault(); // Detenemos el envío automático

            Swal.fire({
                title: '¿Guardar todos los cambios?',
                text: "Se actualizará el contenido, imágenes y valores de la sección Nosotros.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#15305D', // Azul Institucional
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar todo',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Feedback visual de carga
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Actualizando base de datos e imágenes.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Enviamos el formulario físicamente
                    formNosotros.submit();
                }
            });
        });
    }

    // Detectar éxito al recargar (si pasas ?status=success en la URL)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: 'La configuración se guardó correctamente.',
            timer: 3000,
            showConfirmButton: false
        });
    }
});