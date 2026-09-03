<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/culqi.php';

$id_reserva = intval($_GET['id_reserva'] ?? 0);
$metodo = $_GET['metodo'] ?? 'tarjeta';
if (!$id_reserva) { header("Location: index.php"); exit; }

$db = (new Database())->getConnection();
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
$monto_centimos = $monto_cobrar * 100;
$titulo_tour = $reserva['titulo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $metodo === 'yape' ? 'Pago con Yape' : 'Pagar con Tarjeta' ?> - Reserva #<?= $reserva['codigo'] ?> | IntiPath Tours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://checkout.culqi.com/js/v4"></script>
    <style>
        :root {
            --ip-navy: #15305D;
            --ip-slate: #2C3E50;
            --ip-border: #E2E8F0;
            --ip-bg: #F8FAFC;
            --ip-yape: #8B1D8B;
            --ip-card: #15305D;
            --header-bg: <?= $metodo === 'yape' ? 'var(--ip-yape)' : 'var(--ip-navy)' ?>;
        }
        body { background: var(--ip-bg); font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; color: var(--ip-slate); }
        
        .card-pago { 
            max-width: 480px; 
            margin: 80px auto; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background: #fff;
            border: 1px solid var(--ip-border);
        }
        
        .card-header-pago { 
            background: var(--header-bg); 
            color: #fff; 
            padding: 32px 25px; 
            text-align: center; 
            border-bottom: 4px solid #c6d544;
        }
        .card-header-pago h3 { margin: 0; font-weight: 700; font-size: 1.25rem; text-transform: uppercase; letter-spacing: 1px; }
        .card-header-pago p { margin: 8px 0 0; font-size: 0.85rem; opacity: 0.8; font-weight: 500; }
        
        .card-body-pago { background: #fff; padding: 40px; }
        
        .tour-name { 
            text-align: center; 
            color: var(--ip-slate); 
            font-weight: 600; 
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .monto-display { 
            font-size: 42px; 
            font-weight: 800; 
            color: var(--ip-navy); 
            text-align: center; 
            margin-bottom: 5px;
            letter-spacing: -1px;
        }
        
        .payment-label {
            text-align: center;
            font-size: 0.85rem;
            color: #64748B;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .metodos-pago { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin-bottom: 30px; 
            padding-bottom: 25px;
            border-bottom: 1px solid var(--ip-border);
            color: #94A3B8;
        }
        .metodos-pago i { font-size: 24px; }

        .btn-culqi { 
            background: var(--header-bg); 
            color: #fff; 
            border: none; 
            padding: 18px; 
            border-radius: 6px; 
            font-weight: 700; 
            font-size: 1rem; 
            width: 100%; 
            cursor: pointer; 
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(<?= $metodo === 'yape' ? '139, 29, 139' : '21, 48, 93' ?>, 0.2);
        }
        .btn-culqi:hover { <?= $metodo === 'yape' ? 'background: #6a156a;' : 'background: #0F2447;' ?> transform: translateY(-1px); box-shadow: 0 6px 15px rgba(<?= $metodo === 'yape' ? '139, 29, 139' : '21, 48, 93' ?>, 0.3); }
        .btn-culqi:active { transform: translateY(0); }
        .btn-culqi:disabled { background: #94A3B8; cursor: not-allowed; transform: none; box-shadow: none; }

        .secure-badge { 
            text-align: center; 
            font-size: 0.75rem; 
            color: #64748B; 
            margin-top: 25px; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .secure-badge i { color: #10B981; font-size: 14px; }

        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        .back-link a { 
            color: #94A3B8; 
            font-size: 0.8rem; 
            text-decoration: none; 
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link a:hover { color: var(--ip-navy); }

        /* Loading Overlay Profesional */
        #loading-overlay { 
            display:none; 
            position:fixed; 
            inset:0; 
            background: rgba(15, 23, 42, 0.98);
            z-index:99999; 
            align-items:center; 
            justify-content:center; 
            flex-direction:column; 
            color: white;
            text-align: center;
        }
        #loading-overlay.active { display:flex; }
        .spinner-container { position: relative; width: 60px; height: 60px; margin-bottom: 25px; }
        .spinner-pago { 
            width: 60px; height: 60px; 
            border: 4px solid rgba(255,255,255,0.1); 
            border-top-color: #c6d544; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
        }
        .spinner-icon { 
            position: absolute; 
            top: 50%; left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 18px; 
            color: #fff; 
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; color: #fff; letter-spacing: 0.5px; }
        .loading-text { font-size: 0.9rem; color: #94A3B8; max-width: 280px; line-height: 1.5; }
    </style>
</head>
<body>
    <div id="loading-overlay">
        <div class="spinner-container">
            <div class="spinner-pago"></div>
            <i class="fas fa-lock spinner-icon"></i>
        </div>
        <div class="loading-title">PROCESANDO PAGO SEGURO</div>
        <div class="loading-text">Estamos estableciendo una conexión cifrada con la pasarela de pagos. Por favor, mantenga esta ventana abierta.</div>
    </div>
    <div class="container">
        <div class="card-pago">
            <div class="card-header-pago">
                <h3><i class="fas <?= $metodo === 'yape' ? 'fa-mobile-alt' : 'fa-credit-card' ?> me-2"></i> <?= $metodo === 'yape' ? 'Pago con Yape' : 'Resumen de Pago' ?></h3>
                <p>Reserva Segura #<?= $reserva['codigo'] ?></p>
            </div>
            <div class="card-body-pago">
                <div class="tour-name"><?= htmlspecialchars($titulo_tour) ?></div>
                <div class="monto-display">$<?= number_format($monto_cobrar, 2) ?></div>
                <div class="payment-label">
                    <?php if ($reserva['adelanto'] > 0): 
                        $porc = round(($reserva['adelanto'] / $reserva['monto_total']) * 100);
                    ?>
                        Monto por adelanto requerido (<?= $porc ?>%)
                    <?php else: ?>
                        Pago total del paquete turístico
                    <?php endif; ?>
                </div>

                <?php if ($metodo === 'yape'): ?>
                <div class="metodos-pago" style="color:var(--ip-yape);">
                    <i class="fas fa-mobile-alt fa-2x" title="Yape"></i>
                    <span style="font-weight:800;font-size:1.5rem;">Yape</span>
                </div>
                <div style="background:#f8f0ff;border-radius:12px;padding:15px;margin-bottom:20px;font-size:0.85rem;color:#6a156a;text-align:center;">
                    <i class="fas fa-info-circle me-2"></i> Se abrirá el QR de Yape. Escanea con tu app Yape para pagar.
                </div>
                <?php else: ?>
                <div class="metodos-pago">
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    <i class="fab fa-cc-amex" title="American Express"></i>
                    <i class="fab fa-cc-diners-club" title="Diners Club"></i>
                </div>
                <?php endif; ?>

                <?php if ($metodo === 'yape'): ?>
                <form method="post" action="procesar_pago_yape.php">
                    <input type="hidden" name="id_reserva" value="<?= $id_reserva ?>">
                    <button type="submit" class="btn-culqi">
                        <i class="fas fa-qrcode me-2"></i> Pagar con Yape
                    </button>
                </form>
                <?php else: ?>
                <button id="btnCulqiPay" class="btn-culqi" onclick="culqiOpen()">
                    <i class="fas fa-lock me-2"></i> Confirmar y Pagar
                </button>
                <?php endif; ?>

                <div class="secure-badge">
                    <i class="fas fa-shield-check"></i>
                    Transacción protegida mediante cifrado SSL de 256 bits
                </div>

                <div class="back-link">
                    <a href="seleccionar_pago.php?id_reserva=<?= $id_reserva ?>">
                        <i class="fas fa-arrow-left me-1"></i> Elegir otro método de pago
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        Culqi.publicKey = '<?= CULQI_PUBLIC_KEY ?>';

        function mostrarCarga() {
            document.getElementById('loading-overlay').classList.add('active');
            document.getElementById('btnCulqiPay').disabled = true;
        }

        function ocultarCarga() {
            document.getElementById('loading-overlay').classList.remove('active');
            document.getElementById('btnCulqiPay').disabled = false;
        }

        function culqiOpen() {
            mostrarCarga();
            Culqi.settings({
                title: "IntiPath Tours",
                currency: "<?= CULQI_MONEDA ?>",
                description: "Reserva #<?= $reserva['codigo'] ?>",
                amount: <?= $monto_centimos ?>,
                xculqi_metadata: {
                    id_reserva: <?= $id_reserva ?>
                }
            });
            Culqi.open();
        }

        function culqi() {
            if (Culqi.token) {
                var token = Culqi.token.id;
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'procesar_pago_culqi.php';
                form.innerHTML = '<input name="token" value="' + token + '">' +
                    '<input name="id_reserva" value="<?= $id_reserva ?>">' +
                    '<input name="monto" value="<?= $monto_centimos ?>">' +
                    '<input name="moneda" value="<?= CULQI_MONEDA ?>">';
                document.body.appendChild(form);
                form.submit();
            } else {
                ocultarCarga();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Pago',
                    text: Culqi.error ? Culqi.error.user_message : 'No se pudo procesar el pago. Por favor, intenta de nuevo.',
                    confirmButtonColor: '#15305D'
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('res') === 'error') {
                ocultarCarga();
            }
        });
    </script>
</body>
</html>
