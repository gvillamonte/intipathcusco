<?php
require_once __DIR__ . '/../includes/auth_helper.php';
requierePermiso('info_viaje');

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// LÓGICA ELIMINAR
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $db->prepare("SELECT imagen FROM info_viaje WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if($res) { @unlink("../assets/img/info/" . $res['imagen']); }

    $db->prepare("DELETE FROM info_viaje WHERE id = ?")->execute([$id]);
    header("Location: info_viaje.php?res=success&msg=Tarjeta+eliminada");
    exit();
}

// LÓGICA GUARDAR / EDITAR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_info'])) {
    $id_info = $_POST['id_info'];
    $tour_id = $_POST['tour_id'];
    $titulo = $_POST['titulo'];
    $enlace = $_POST['enlace'];

    // VALIDACIÓN: Máximo 3 imágenes por tour (Solo si es nuevo registro)
    if (empty($id_info)) {
        $check = $db->prepare("SELECT COUNT(*) as total FROM info_viaje WHERE tour_id = ?");
        $check->execute([$tour_id]);
        $total = $check->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total >= 3) {
            header("Location: info_viaje.php?res=error&msg=Límite+alcanzado:+Un+tour+solo+puede+tener+3+tarjetas.");
            exit();
        }
    }

    $img_sql = "";
    $params_img = [];

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_img = "info_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], "../assets/img/info/" . $nombre_img)) {
            $img_sql = ", imagen = :img";
            $params_img = [':img' => $nombre_img];
        }
    }

    if (!empty($id_info)) {
        $sql = "UPDATE info_viaje SET tour_id=:tid, titulo=:t, enlace=:e $img_sql WHERE id=:id";
        $stmt = $db->prepare($sql);
        $params = [':tid'=>$tour_id, ':t'=>$titulo, ':e'=>$enlace, ':id'=>$id_info];
        $stmt->execute(array_merge($params, $params_img));
        $msg = "Tarjeta actualizada";
    } else {
        $img_db = $params_img[':img'] ?? 'default.jpg';
        $sql = "INSERT INTO info_viaje (tour_id, titulo, imagen, enlace) VALUES (?, ?, ?, ?)";
        $db->prepare($sql)->execute([$tour_id, $titulo, $img_db, $enlace]);
        $msg = "Tarjeta publicada";
    }

    header("Location: info_viaje.php?res=success&msg=" . urlencode($msg));
    exit();
}