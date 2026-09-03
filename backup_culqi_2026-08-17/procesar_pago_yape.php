<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/culqi.php';

use Culqi\Culqi;

$id_reserva = intval($_POST['id_reserva'] ?? 0);
if (!$id_reserva) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT r.*, t.titulo, t.titulo_en FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
$stmt->execute([$id_reserva]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reserva) { header("Location: index.php"); exit; }

$monto_cobrar = ($reserva['adelanto'] > 0) ? $reserva['adelanto'] : $reserva['monto_total'];
$monto_centimos = intval(round($monto_cobrar * 100));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$redirect_url = $protocol . '://' . $host . '/pago_exitoso.php?id_reserva=' . $id_reserva . '&codigo=' . $reserva['codigo'] . '&order_id=';

try {
    $culqi = new Culqi(array('api_key' => CULQI_SECRET_KEY));

    $order = $culqi->Orders->create([
        "amount" => $monto_centimos,
        "currency_code" => CULQI_MONEDA,
        "description" => 'Reserva #' . $reserva['codigo'] . ' - ' . $reserva['titulo'],
        "order_number" => 'RES-' . $reserva['codigo'],
        "client_details" => [
            "first_name" => $reserva['nombre'] ?? 'Cliente',
            "last_name" => $reserva['apellido'] ?? '',
            "email" => $reserva['email'],
            "phone_number" => $reserva['telefono'] ?? '999999999'
        ],
        "expiration_date" => time() + 60 * 30,
        "redirect_url" => $redirect_url,
        "metadata" => [
            "id_reserva" => $id_reserva,
            "codigo_reserva" => $reserva['codigo']
        ]
    ]);

    if (!is_object($order) || !isset($order->id)) {
        throw new Exception('Error al crear orden: ' . (is_string($order) ? $order : 'Error desconocido'));
    }

    $order_url = $order->order_url ?? '';

    if (empty($order_url)) {
        throw new Exception('No se pudo obtener la URL de pago de Culqi');
    }

    header("Location: " . $order_url);
    exit;

} catch (\Throwable $e) {
    $error_msg = $e->getMessage();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
    Swal.fire({ icon: 'error', title: 'Error al generar pago', text: '<?= addslashes($error_msg) ?>' })
    .then(() => { window.location.href = 'checkout_culqi.php?id_reserva=<?= $id_reserva ?>&metodo=yape'; });
    </script>
    </body>
    </html>
    <?php
    exit;
}
