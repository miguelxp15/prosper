<?php
include_once(str_repeat("../", 3).'202-config.php');
include_once(str_repeat("../", 3).'202-config/connect2.php');

header('Content-Type: application/json');

echo json_encode(array('version' => $version));

?>