<?php
/**
 * procesar_pago_culqi.php
 * Procesa el cargo con la API de Culqi (tarjeta y Yape).
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/culqi.php';

use Culqi\Culqi;

// Evitar acceso directo sin POST
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_reserva'])) {
    header("Location: index.php");
    exit;
}

$id_reserva = intval($_POST['id_reserva']);
$monto_centimos = intval($_POST['monto'] ?? 0);
$moneda = CULQI_MONEDA;

// Puede venir un token (tarjeta) o un charge_id (Yape desde checkout)
$token = $_POST['token'] ?? null;
$charge_id_input = $_POST['charge_id'] ?? null;

$db = (new Database())->getConnection();

// Obtener datos de la reserva
$stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
$stmt->execute([$id_reserva]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    header("Location: index.php?res=error");
    exit;
}

// 1. MOSTRAR PANTALLA DE CARGA INMEDIATA (Para evitar pantalla en blanco)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesando Pago | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #15305D; color: white; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; overflow: hidden; }
        .proc-container { text-align: center; max-width: 400px; padding: 20px; }
        .spinner-pago { width: 80px; height: 80px; border: 6px solid rgba(255,255,255,0.1); border-top-color: #c6d544; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 30px; position: relative; }
        .spinner-pago i { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 24px; color: white; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { font-weight: 800; font-size: 1.8rem; margin-bottom: 10px; }
        p { color: rgba(255,255,255,0.7); font-size: 1.1rem; line-height: 1.5; }
        .step-msg { font-size: 0.85rem; color: #c6d544; margin-top: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="proc-container">
        <div class="spinner-pago">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h2>Verificando Pago</h2>
        <p>Estamos confirmando tu transacción con la entidad bancaria. Por favor espera un momento...</p>
        <div class="step-msg" id="msg-pago">Conectando con la pasarela...</div>
    </div>
    <?php
    // Forzamos la salida del HTML para que el cliente lo vea mientras PHP sigue trabajando
    flush();
    ob_flush();

    // 2. PROCESAR EL PAGO CON CULQI
    try {
        $culqi = new Culqi(array('api_key' => CULQI_SECRET_KEY));

        if ($charge_id_input) {
            // Yape: el cargo ya fue creado por Culqi Checkout, solo obtenemos sus datos
            $charge = $culqi->Charges->get($charge_id_input);
            if (!is_object($charge) || !isset($charge->id)) {
                throw new Exception('Error al verificar el pago Yape');
            }
        } else {
            // Tarjeta: creamos el cargo con el token
            $charge = $culqi->Charges->create([
                'amount' => $monto_centimos,
                'currency_code' => $moneda,
                'email' => $reserva['email'],
                'source_id' => $token,
                'metadata' => [
                    'id_reserva' => $id_reserva,
                    'codigo_reserva' => $reserva['codigo']
                ]
            ]);

            if (!is_object($charge) || !isset($charge->id)) {
                throw new Exception('Error al crear cargo: ' . (is_string($charge) ? $charge : 'Error desconocido'));
            }
        }

        $charge_id = $charge->id;
        $monto_pagado = $charge->amount / 100;
        $metodo_pago = is_object($charge->source ?? null) ? ($charge->source->type ?? 'culqi_tarjeta') : 'culqi_tarjeta';

        // Guardar pago en DB
        $stmt_p = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, culqi_charge_id, estado, fecha_pago) VALUES (?, ?, ?, ?, ?, 'pagado', NOW())");
        $stmt_p->execute([$id_reserva, $monto_pagado, $moneda, $metodo_pago, $charge_id]);

        // Actualizar estado de reserva
        $stmt_t = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE id_reserva = ? AND estado = 'pagado'");
        $stmt_t->execute([$id_reserva]);
        $total_pagado = $stmt_t->fetchColumn();

        $nuevo_estado = ($total_pagado >= $reserva['monto_total']) ? 'pagado' : 'parcial';

        $stmt_up = $db->prepare("UPDATE reservas SET estado = ?, metodo_pago = ?, updated_at = NOW() WHERE id = ?");
        $stmt_up->execute([$nuevo_estado, $metodo_pago, $id_reserva]);

        // Redirección por JS al Éxito
        echo "<script>
            document.getElementById('msg-pago').textContent = '¡Pago Confirmado! Redirigiendo...';
            setTimeout(function() {
                window.location.href = 'pago_exitoso.php?id_reserva=$id_reserva&codigo=" . $reserva['codigo'] . "';
            }, 500);
        </script>";

    } catch (\Throwable $e) {
        // En caso de error, volvemos al checkout con el mensaje
        $err = addslashes($e->getMessage());
        echo "<script>
            document.getElementById('msg-pago').textContent = 'Error detectado. Volviendo...';
            window.location.href = 'checkout_culqi.php?id_reserva=$id_reserva&res=error&msg=" . urlencode($err) . "';
        </script>";
    }
    ?>
</body>
</html>
