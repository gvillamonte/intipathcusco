<?php
$c = file_get_contents('C:\laragon\www\intipathcusco\admin\tipos_tours.php');
echo json_encode(substr($c, -200));