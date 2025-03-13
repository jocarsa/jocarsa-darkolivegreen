<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to      = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body    = $_POST['message'] ?? '';

    // Get user data from session
    $user = $_SESSION['user'];
    // SMTP parameters from the user record
    $smtp_server = $user['smtp_server'];
    $port        = 465;
    $username    = $user['email'];
    $password    = $user['password'];
    $from        = $user['email'];

    $result = sendSMTPMail($smtp_server, $port, $username, $password, $from, $to, $subject, $body);
    if ($result === true) {
         // After successful sending, save the email in the local database under the "Sent" folder.
         include "backend/inc/conexionsqlite.php";
         try {
             // Generate a unique identifier for the sent email
             $uid = uniqid();
             $stmt = $db->prepare("INSERT INTO emails (user_id, folder, uid, sender, subject, date, body) VALUES (:user_id, 'Sent', :uid, :sender, :subject, :date, :body)");
             $stmt->execute([
                 ':user_id' => $user['id'],
                 ':uid'     => $uid,
                 ':sender'  => $from,
                 ':subject' => $subject,
                 ':date'    => date('Y-m-d H:i:s'),
                 ':body'    => $body
             ]);
         } catch (PDOException $e) {
             // Log the error if needed, but do not block the success response
         }
         echo json_encode(['success' => 'Email sent successfully']);
    } else {
         echo json_encode(['error' => $result]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

/**
 * Function to send email via SMTP using an SSL socket.
 *
 * @param string $smtp_server SMTP server domain.
 * @param int    $port        Port number (465 for SSL).
 * @param string $username    SMTP username.
 * @param string $password    SMTP password.
 * @param string $from        From email address.
 * @param string $to          Recipient email address.
 * @param string $subject     Email subject.
 * @param string $body        Email body (HTML format).
 *
 * @return true|string Returns true on success or an error message string on failure.
 */
function sendSMTPMail($smtp_server, $port, $username, $password, $from, $to, $subject, $body) {
    $errno = 0;
    $errstr = '';
    $remote_socket = "ssl://{$smtp_server}:{$port}";
    $socket = stream_socket_client($remote_socket, $errno, $errstr, 30);
    if (!$socket) {
         return "Connection failed: $errno - $errstr";
    }
    
    // Helper function to read the response from the SMTP server
    function getResponse($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }
    
    // Read the initial response (expecting 220)
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "220") {
         fclose($socket);
         return "Unexpected response on connection: $response";
    }
    
    // Send EHLO command
    fwrite($socket, "EHLO localhost\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "250") {
         fclose($socket);
         return "EHLO failed: $response";
    }
    
    // Start authentication with AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "334") {
         fclose($socket);
         return "AUTH LOGIN not accepted: $response";
    }
    
    // Send the username (base64 encoded)
    fwrite($socket, base64_encode($username) . "\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "334") {
         fclose($socket);
         return "Username not accepted: $response";
    }
    
    // Send the password (base64 encoded)
    fwrite($socket, base64_encode($password) . "\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "235") {
         fclose($socket);
         return "Authentication failed: $response";
    }
    
    // MAIL FROM command
    fwrite($socket, "MAIL FROM:<$from>\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "250") {
         fclose($socket);
         return "MAIL FROM failed: $response";
    }
    
    // RCPT TO command
    fwrite($socket, "RCPT TO:<$to>\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "250") {
         fclose($socket);
         return "RCPT TO failed: $response";
    }
    
    // DATA command to start sending email content
    fwrite($socket, "DATA\r\n");
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "354") {
         fclose($socket);
         return "DATA command failed: $response";
    }
    
    // Prepare email headers and body
    $headers = "From: <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "\r\n";
    
    // Send headers and body; end with a single period on a new line
    $data = $headers . $body . "\r\n.\r\n";
    fwrite($socket, $data);
    $response = getResponse($socket);
    if (substr($response, 0, 3) != "250") {
         fclose($socket);
         return "Sending data failed: $response";
    }
    
    // QUIT command to close the SMTP session
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}
?>

