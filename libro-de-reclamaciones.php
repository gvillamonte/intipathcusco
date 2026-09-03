<?php
include 'includes/header.php';
require_once __DIR__ . '/includes/csrf_helper.php';
?>

<style>
    .lr-hero {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/img/Machu-Picchu.jpg');
        background-size: cover;
        background-position: center;
        height: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #fff;
        text-align: center;
        margin-top: 80px;
    }

    .lr-hero h1 {
        font-size: 2.5rem;
        margin: 0;
        text-transform: uppercase;
    }

    .lr-container {
        max-width: 900px;
        margin: -50px auto 50px;
        background: #fff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }

    .lr-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .lr-full {
        grid-column: span 2;
    }

    .lr-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
    }

    .lr-group label {
        font-size: 12px;
        font-weight: 700;
        color: #15305D;
        text-transform: uppercase;
    }

    .lr-group input,
    .lr-group select,
    .lr-group textarea {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: inherit;
    }

    .lr-btn {
        background: #f39c12;
        color: #fff;
        border: none;
        padding: 15px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
        font-size: 16px;
    }

    .lr-btn:hover {
        background: #e67e22;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .lr-form-grid {
            grid-template-columns: 1fr;
        }

        .lr-full {
            grid-column: span 1;
        }
    }
</style>

<section class="lr-hero">
    <h1>Libro de Reclamaciones</h1>
    <p>Estamos para escucharte y mejorar nuestro servicio.</p>
</section>

<div class="lr-container">

    <?php if (isset($_GET['res'])): ?>
        <?php if ($_GET['res'] == 'success'): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center; border: 1px solid #c3e6cb;">
                <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                <strong>¡Reclamación Registrada con éxito!</strong><br>
                Su número de seguimiento es: <span style="font-weight: 800; font-size: 1.2rem;"><?= htmlspecialchars($_GET['num']) ?></span><br>
                Le responderemos en un plazo máximo de 15 días hábiles.
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center; border: 1px solid #f5c6cb;">
                <strong>Hubo un error.</strong> Por favor, inténtelo de nuevo o contáctenos directamente por WhatsApp.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="procesar_reclamo.php" method="POST" onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.style.opacity='0.6'; return true;">
        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
        <!-- Honeypot anti-bots (invisible) -->
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px; opacity:0; height:0; width:0;" aria-hidden="true">
        <h3 style="color: #15305D; border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-bottom: 25px;">1. Identificación del Consumidor</h3>
        <div class="lr-form-grid">
            <div class="lr-group lr-full"><label>Nombre Completo</label><input type="text" name="nombre" required></div>
            <div class="lr-group"><label>Tipo Doc.</label>
                <select name="tipo_doc">
                    <option value="DNI">DNI</option>
                    <option value="Pasaporte">Pasaporte</option>
                    <option value="CE">C.E.</option>
                </select>
            </div>
            <div class="lr-group"><label>Número Doc.</label><input type="text" name="num_doc" required></div>
            <div class="lr-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="lr-group"><label>Teléfono</label><input type="tel" name="telefono"></div>
            <div class="lr-group lr-full"><label>Domicilio</label><input type="text" name="domicilio" required></div>
        </div>

        <h3 style="color: #15305D; border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-top: 30px; margin-bottom: 25px;">2. Detalle de la Reclamación</h3>
        <div class="lr-form-grid">
            <div class="lr-group"><label>Tipo de Bien</label>
                <select name="tipo_bien">
                    <option value="Servicio">Servicio (Tour/Trekking)</option>
                    <option value="Producto">Producto</option>
                </select>
            </div>
            <div class="lr-group"><label>Monto Reclamado (Opcional)</label><input type="number" name="monto" step="0.01"></div>
            <div class="lr-group lr-full"><label>Descripción del Tour/Servicio</label><input type="text" name="desc_bien" placeholder="Ej: Camino Inca 4 días"></div>
            <div class="lr-group lr-full"><label>Detalle del Reclamo o Queja</label><textarea name="detalle" rows="4" required></textarea></div>
            <div class="lr-group lr-full"><label>¿Qué es lo que solicita?</label><textarea name="pedido" rows="3" required></textarea></div>
        </div>

        <button type="submit" class="lr-btn">ENVIAR HOJA DE RECLAMACIÓN</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>