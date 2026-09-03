# AGENTS.md - PTO.INTI (IntiPath Tours)

PHP 8.2 plain-PHP project (Laravel-free). No tests, no lint step, no CI. Composer deps are vendor-committed.

## Architecture

- **Entry points**: root-level `*.php` files (`index.php`, `tours.php`, `detalle_tour.php`, etc.) and `admin/*.php` for the admin panel.
- **No autoloader**: each file does its own `require_once '../config/database.php'`. Include paths are relative to the calling file's location.
- **Auth**: `includes/auth_helper.php` provides `requierePermiso('permiso')` — called at the top of every admin page. Session timeout = 600s (`iniciarSesionAdmin`). Admin pages live in `admin/`.
- **DB**: MySQL. `config/database.php` → `Database` class, PDO, charset utf8mb4, host=localhost, db=intipath, user=root, pass=(empty). All queries go through stored procedures: `CALL sp_...`.
- **i18n**: `config/lang.php` → `$translations['es']` array. Bilingual DB fields use `_en` suffix (`titulo` / `titulo_en`, `incluye` / `incluye_en`, etc.).
- **Services**: `src/Services/EmailService.php` (PHPMailer) and `WhatsappService.php` (Guzzle). Both require `vendor/autoload.php` at the top.
- **PDF generation**: `dompdf/dompdf` in vendor. See `includes/pdf_helper.php` and `includes/generar_pdf_reserva.php`.
- **Payments**: `config/culqi.php` + `checkout_culqi.php` for Culqi gateway.

## Key commands

```bash
# Syntax check (no linter or test runner exists)
php -l admin/tours.php

# DB (Laragon MySQL)
# host: localhost, db: intipath, user: root, pass: (empty)

# Composer autoloader is at vendor/autoload.php — include it if using PHPMailer/Guzzle/dompdf classes
```

## Conventions

- Files mix PHP/HTML freely; use `<?=` for inline echo.
- Stored-procedure call pattern in admin pages:
  ```php
  $stmt = $db->prepare("CALL sp_obtener_tour_editar(?)");
  $stmt->execute([$_GET['editar']]);
  $res = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();
  ```
- `admin/tours.php` uses a 5-tab interface: General → Itinerario → Inclusiones → Precios → Multimedia & PDF.
- The admin sidebar (`includes/sidebar.php`) is always present on admin pages.
- `requierePermiso('tours')` is the first real call in `admin/tours.php` after including auth helper.

## Gotchas

- No README — all context lives in code comments (verbose, Spanish).
- `EmailService` and `WhatsappService` have **placeholder credentials** — replace before production.
- `Database::getConnection()` echoes errors on failure (does not throw).
- `vendor/` is committed — `composer install` is not required, but packages may be stale.
- DB field `precio_nino` is NULLABLE; empty means 70% of adult price.