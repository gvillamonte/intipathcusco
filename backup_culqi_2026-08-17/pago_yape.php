<?php
require_once __DIR__ . '/config/database.php';

$id_reserva = intval($_GET['id_reserva'] ?? 0);
if (!$id_reserva) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();

// Cargar config de pagos desde BD
$stmt_c = $db->query("SELECT clave, valor FROM config_pagos");
$cfgs = [];
while ($r = $stmt_c->fetch(PDO::FETCH_ASSOC)) $cfgs[$r['clave']] = $r['valor'];
$yape_numero = $cfgs['yape_numero'] ?? '999 888 777';
$yape_titular = $cfgs['yape_titular'] ?? 'IntiPath Tours Peru S.A.C.';
$yape_qr = $cfgs['yape_qr'] ?? 'assets/img/yape_qr.png';

// Procesar confirmacion manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_yape'])) {
    $stmt_r = $db->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt_r->execute([$id_reserva]);
    $reserva = $stmt_r->fetch(PDO::FETCH_ASSOC);
    if ($reserva) {
        $monto = ($reserva['adelanto'] > 0) ? $reserva['adelanto'] : $reserva['monto_total'];
        $stmt_p = $db->prepare("INSERT INTO pagos (id_reserva, monto, moneda, metodo, estado, fecha_pago) VALUES (?, ?, 'USD', 'yape', 'pendiente', NOW())");
        $stmt_p->execute([$id_reserva, $monto]);
        $stmt_up = $db->prepare("UPDATE reservas SET updated_at = NOW() WHERE id = ?");
        $stmt_up->execute([$id_reserva]);
        header("Location: pago_exitoso.php?id_reserva=$id_reserva&codigo=" . $reserva['codigo'] . "&yape=1");
        exit;
    }
}

$stmt = $db->prepare("SELECT r.*, t.titulo, t.titulo_en FROM reservas r LEFT JOIN tours t ON r.id_tour = t.id WHERE r.id = ?");
$stmt->execute([$id_reserva]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reserva) { header("Location: index.php"); exit; }

// Restricción: Si ya está pagado, redirigir al éxito
if ($reserva['estado'] === 'pagado') {
    header("Location: pago_exitoso.php?id_reserva=$id_reserva&codigo=" . $reserva['codigo']);
    exit;
}

$monto_cobrar = ($reserva['adelanto'] > 0) ? $reserva['adelanto'] : $reserva['monto_total'];
$qr_exists = file_exists(__DIR__ . '/' . $yape_qr);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago con Yape - Reserva #<?= $reserva['codigo'] ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card-yape { max-width: 500px; margin: 40px auto; }
        .card-header-yape { background: #8B1D8B; color: #fff; padding: 25px; text-align: center; border-radius: 20px 20px 0 0; }
        .card-header-yape h3 { margin: 0; font-weight: 800; }
        .card-body-yape { background: #fff; padding: 30px; border-radius: 0 0 20px 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; }
        .qr-container { background: #fff; border: 3px dashed #ddd; border-radius: 16px; padding: 20px; margin: 20px auto; max-width: 220px; }
        .qr-container img { max-width: 100%; border-radius: 8px; }
        .qr-placeholder { width: 180px; height: 180px; background: #f8f0ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
        .qr-placeholder span { font-size: 60px; }
        .numero-yape { font-size: 28px; font-weight: 800; color: #8B1D8B; letter-spacing: 2px; margin: 10px 0; }
        .label-dato { font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .monto-display { font-size: 36px; font-weight: 800; color: #15305D; margin: 15px 0; }
        .instrucciones { background: #f8f9fa; border-radius: 12px; padding: 15px; text-align: left; font-size: 14px; color: #555; margin: 20px 0; }
        .instrucciones li { margin-bottom: 8px; }
        .btn-yape { background: #8B1D8B; color: #fff; border: none; padding: 15px 30px; border-radius: 12px; font-weight: 700; font-size: 18px; width: 100%; cursor: pointer; transition: 0.3s; }
        .btn-yape:hover { background: #6a156a; color: #fff; }
        .btn-volver { color: #888; font-size: 13px; text-decoration: none; display: inline-block; margin-top: 15px; }
        .btn-volver:hover { color: #555; }

        /* Estilos de carga profesional */
        #loading-overlay-yape { 
            display:none; 
            position:fixed; 
            inset:0; 
            background:rgba(0,0,0,0.75); 
            z-index:99999; 
            align-items:center; 
            justify-content:center; 
            flex-direction:column; 
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }
        #loading-overlay-yape.active { display:flex; }
        .spinner-yape { 
            width: 50px; 
            height: 50px; 
            border: 5px solid rgba(255, 255, 255, 0.3); 
            border-radius: 50%; 
            border-top-color: #8B1D8B; 
            animation: spin-yape 1s ease-in-out infinite; 
            margin-bottom: 20px; 
        }
        @keyframes spin-yape { to { transform: rotate(360deg); } }
        .loading-text-yape { font-size: 1.2rem; font-weight: 600; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div id="loading-overlay-yape">
        <div class="spinner-yape"></div>
        <div class="loading-text-yape">Verificando pago...</div>
        <div class="mt-2 text-white-50 small">Por favor no cierres esta ventana</div>
    </div>
    <div class="container">
        <div class="card-yape">
            <div class="card-header-yape">
                <h3>Pago con Yape</h3>
                <p style="margin:5px 0 0;opacity:0.8;">Reserva #<?= $reserva['codigo'] ?></p>
            </div>
            <div class="card-body-yape">
                <p style="color:#666;"><?= htmlspecialchars($reserva['titulo'] ?? '') ?></p>

                <div class="monto-display">$<?= number_format($monto_cobrar, 2) ?></div>

                <div class="qr-container">
                    <?php if ($qr_exists): ?>
                        <img src="<?= htmlspecialchars($yape_qr) ?>" alt="QR Yape">
                    <?php else: ?>
                        <div class="qr-placeholder"><span>📱</span></div>
                        <p style="font-size:11px;color:#999;margin-top:8px;">Escanea con Yape</p>
                    <?php endif; ?>
                </div>

                <p class="label-dato">Número Yape</p>
                <div class="numero-yape"><?= htmlspecialchars($yape_numero) ?></div>
                <p class="label-dato" style="margin-top:5px;">Titular: <?= htmlspecialchars($yape_titular) ?></p>

                <div class="instrucciones">
                    <strong>Instrucciones:</strong>
                    <ol style="padding-left:20px;margin:10px 0 0;">
                        <li>Abre la app <strong>Yape</strong> en tu celular</li>
                        <li>Selecciona <strong>"Pagar"</strong> e ingresa el número: <strong><?= htmlspecialchars($yape_numero) ?></strong></li>
                        <li>Ingresa el monto exacto: <strong>$<?= number_format($monto_cobrar, 2) ?></strong></li>
                        <li>Confirma el pago en tu app</li>
                        <li>Luego haz clic en <strong>"Ya pagué"</strong> abajo</li>
                    </ol>
                </div>

                <form method="post" id="form-yape">
                    <input type="hidden" name="confirmar_yape" value="1">
                    <button type="button" class="btn-yape" onclick="confirmarPagoYape()">
                        ✓ Ya pagué
                    </button>
                </form>

                <p style="font-size:12px;color:#999;margin-top:15px;">
                    Tu pago quedará como <strong>"pendiente de verificación"</strong>.
                    Nuestro equipo lo confirmará en breve.
                </p>

                <a href="seleccionar_pago.php?id_reserva=<?= $id_reserva ?>" class="btn-volver">&larr; Elegir otro método</a>
            </div>
        </div>
    </div>

    <script>
    function confirmarPagoYape() {
        Swal.fire({
            title: '¿Confirmas el pago?',
            text: "Asegúrate de haber realizado la transferencia por $<?= number_format($monto_cobrar, 2) ?> a través de Yape.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#8B1D8B',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, ya pagué',
            cancelButtonText: 'Aún no',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('loading-overlay-yape').classList.add('active');
                document.getElementById('form-yape').submit();
            }
        });
    }
    </script>
</body>
</html>
