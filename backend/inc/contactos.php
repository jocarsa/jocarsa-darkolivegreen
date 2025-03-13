<?php
// CRUD y extracción de contactos basada en los emails almacenados
    $op = $_GET['op'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && $op == '') {
        // Extraer contactos de los correos: se consulta la tabla de emails
        $stmt = $db->prepare("SELECT sender FROM emails WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user['id']]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($emails as $row) {
            $sender = $row['sender'];
            $name = '';
            $emailAddr = '';
            if (preg_match('/(.*)<(.+)>/', $sender, $matches)) {
                $name = trim($matches[1], " \"'");
                $emailAddr = trim($matches[2]);
            } else {
                $emailAddr = trim($sender);
                $name = $emailAddr;
            }
            // Insertar solo si aún no existe para este usuario
            $stmt2 = $db->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = :user_id AND email = :email");
            $stmt2->execute([
                ':user_id' => $user['id'],
                ':email'   => $emailAddr
            ]);
            if ($stmt2->fetchColumn() == 0) {
                $stmt3 = $db->prepare("INSERT INTO contacts (user_id, name, email) VALUES (:user_id, :name, :email)");
                $stmt3->execute([
                    ':user_id' => $user['id'],
                    ':name'    => $name,
                    ':email'   => $emailAddr
                ]);
            }
        }
        // Devolver la lista de contactos para este usuario
        $stmt = $db->prepare("SELECT * FROM contacts WHERE user_id = :user_id ORDER BY name ASC");
        $stmt->execute([':user_id' => $user['id']]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($contacts);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Operaciones de CRUD vía POST
        if ($op == 'add') {
            $name = $_POST['name'] ?? '';
            $emailAddr = $_POST['email'] ?? '';
            if (!$name || !$emailAddr) {
                echo json_encode(['error' => 'Name and email required']);
                exit;
            }
            $stmt = $db->prepare("INSERT INTO contacts (user_id, name, email) VALUES (:user_id, :name, :email)");
            try {
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':name' => $name,
                    ':email' => $emailAddr
                ]);
                echo json_encode(['success' => 'Contact added']);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Error adding contact: ' . $e->getMessage()]);
            }
            exit;
        } elseif ($op == 'update') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $emailAddr = $_POST['email'] ?? '';
            if (!$id || !$name || !$emailAddr) {
                echo json_encode(['error' => 'ID, Name and email required']);
                exit;
            }
            $stmt = $db->prepare("UPDATE contacts SET name = :name, email = :email WHERE id = :id AND user_id = :user_id");
            try {
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $emailAddr,
                    ':id' => $id,
                    ':user_id' => $user['id']
                ]);
                echo json_encode(['success' => 'Contact updated']);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Error updating contact: ' . $e->getMessage()]);
            }
            exit;
        } elseif ($op == 'delete') {
            $id = $_POST['id'] ?? '';
            if (!$id) {
                echo json_encode(['error' => 'ID required']);
                exit;
            }
            $stmt = $db->prepare("DELETE FROM contacts WHERE id = :id AND user_id = :user_id");
            try {
                $stmt->execute([
                    ':id' => $id,
                    ':user_id' => $user['id']
                ]);
                echo json_encode(['success' => 'Contact deleted']);
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Error deleting contact: ' . $e->getMessage()]);
            }
            exit;
        } else {
            echo json_encode(['error' => 'Invalid operation']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'Unsupported request method']);
        exit;
    }
?>
