<?php
try {
    // Conexión a SQLite
    $db = new PDO('sqlite:../../databases/darkolivegreen.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
