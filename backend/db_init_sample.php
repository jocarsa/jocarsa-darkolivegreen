<?php
// db_init.php

try {
    $db = new PDO('sqlite:../../databases/darkolivegreen.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table for users
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT UNIQUE,
        password TEXT,
        imap_server TEXT,
        smtp_server TEXT
    )");

    // Create table for emails
    $db->exec("CREATE TABLE IF NOT EXISTS emails (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        folder TEXT,
        uid TEXT,
        sender TEXT,
        subject TEXT,
        date TEXT,
        body TEXT,
        UNIQUE(user_id, folder, uid)
    )");

    // Create table for contacts
    $db->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        email TEXT,
        UNIQUE(user_id, email)
    )");

    // Create table for trays (mail folders)
    $db->exec("CREATE TABLE IF NOT EXISTS trays (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        tray_name TEXT,
        UNIQUE(user_id, tray_name)
    )");

    // Insert initial user if not exists.
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => '']);
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        // Note: In production, passwords should be hashed.
        $stmt = $db->prepare("INSERT INTO users (name, email, password, imap_server, smtp_server) 
                              VALUES (:name, :email, :password, :imap, :smtp)");
        $stmt->execute([
            ':name'     => '',
            ':email'    => '',
            ':password' => '', 
            ':imap'     => '{:993/imap/ssl}', 
            ':smtp'     => ''
        ]);
    }

    // Insert default trays for the user (if not already present)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => 'info@dibujant.es']);
    $user_id = $stmt->fetchColumn();
    if ($user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM trays WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $countTrays = $stmt->fetchColumn();
        if ($countTrays == 0) {
            $stmtInsert = $db->prepare("INSERT INTO trays (user_id, tray_name) VALUES (:user_id, :tray)");
            $stmtInsert->execute([':user_id' => $user_id, ':tray' => 'INBOX']);
            // Optionally, add additional default trays (e.g., Sent)
            $stmtInsert->execute([':user_id' => $user_id, ':tray' => 'Sent']);
        }
    }

    echo "Database initialized.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

