<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user = $_SESSION['user'];
$action = $_GET['type'] ?? 'emails';

include "inc/conexionsqlite.php";

if ($action === 'folders') {
    include "inc/listarcarpetas.php";
    exit;
} elseif ($action === 'emails') {
    include "inc/recuperaremails.php";
    exit;
} elseif ($action === 'contacts') {
    include "inc/contactos.php";
} elseif ($action === 'calendar') {
    include "inc/calendario.php";
    exit;
} elseif ($action === 'settings') {
    include "inc/configuracion.php";
    exit;
} else {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

include "funciones/decodificarcuerpo.php";
?>

