<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('calendario');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) { die("Error de conexión."); }

// Obtenemos los tours activos para el selector
$query_tours = "SELECT id, titulo FROM tours WHERE estado = 'activo' ORDER BY titulo ASC";
$stmt_tours = $db->prepare($query_tours);
$stmt_tours->execute();
$lista_tours = $stmt_tours->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario IntiPath Tours</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        #calendar-admin { 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            margin-top: 20px; 
        }
        /* Estilo para que el sidebar no tape el contenido si usas flex */
        body { background-color: #f4f6f9; }
        .fc-daygrid-day:hover { background-color: #f1f3f5 !important; cursor: pointer; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 text-center">
                <h2 class="font-weight-bold">Gestión de Salidas - IntiPath Tours</h2>
                <p class="text-muted">Haz clic en un día para programar un tour o en un evento para eliminarlo</p>
            </div>
        </div>
        
        <div id='calendar-admin'></div>
    </div>
</div>

<div class="modal fade" id="modalCalendario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="procesar_calendario.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Programar Salida</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="fecha_salida" id="inputFecha">
                    <div class="form-group">
                        <label>Selecciona el Tour:</label>
                        <select name="id_tour" class="form-control" required>
                            <option value="">-- Seleccionar Tour --</option>
                            <?php foreach($lista_tours as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color en el Calendario:</label>
                        <input type="color" name="color_evento" class="form-control" value="#28a745">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_salida" class="btn btn-primary">Guardar Salida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET['res'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_GET['res'] == 'success'): ?>
                Swal.fire({
                    title: '¡Excelente!',
                    text: 'Se ha agregado el tour correctamente.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#f39c12', /* Naranja IntiPath */
                    timer: 3000,
                    timerProgressBar: true
                });
            /* --- AQUÍ AGREGAMOS LA ALERTA DE ELIMINADO --- */
            <?php elseif ($_GET['res'] == 'deleted' || $_GET['res'] == 'eliminado'): ?>
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'El tour ha sido borrado del calendario correctamente.',
                    icon: 'info',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#e74c3c', /* Rojo para dar contexto de borrado */
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php elseif ($_GET['res'] == 'error'): ?>
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un problema al procesar la solicitud. Intenta de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>

            // TRUCO PRO: Limpiar la URL para quitar el ?res=
            if (window.history.replaceState) {
                const urlLimpia = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: urlLimpia}, '', urlLimpia);
            }
        });
    </script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-admin');

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: { 
                left: 'prev,next today', 
                center: 'title', 
                right: 'dayGridMonth' 
            },
            events: 'obtener_eventos.php', 

            // Función para AGREGAR (Clic en día vacío)
            dateClick: function(info) {
                document.getElementById('inputFecha').value = info.dateStr;
                $('#modalCalendario').modal('show');
            },

            // Función para ELIMINAR (Clic en el tour programado)
            eventClick: function(info) {
                var nombreTour = info.event.title;
                
                // MEJORA: Reemplazamos el confirm() nativo por un SweetAlert
                Swal.fire({
                    title: '¿Eliminar salida?',
                    text: "Vas a borrar: " + nombreTour,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c', // Rojo para eliminar
                    cancelButtonColor: '#95a5a6',  // Gris para cancelar
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirige al procesador solo si hace clic en "Sí, eliminar"
                        window.location.href = "procesar_calendario.php?eliminar_id=" + info.event.id;
                    }
                });
            }
        });
        calendar.render();
    }
});
</script>
</body>
</html>