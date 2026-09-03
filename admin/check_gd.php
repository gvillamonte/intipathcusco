<?php
// Verificar si la función existe
if (extension_loaded('gd') && function_exists('gd_info')) {
    echo "<h2 style='color: green;'>✅ ¡Éxito! La librería GD está activa.</h2>";
    echo "<pre>";
    print_r(gd_info());
    echo "</pre>";
} else {
    echo "<h2 style='color: red;'>❌ Error: La librería GD NO está activa.</h2>";
    echo "<p>Revisa que hayas quitado el ';' en el php.ini y reiniciado Apache en XAMPP.</p>";
}
?>