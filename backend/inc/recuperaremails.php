<?php
// recuperaremails.php

// Expected GET parameter 'folder' (default: INBOX)
$folder = $_GET['folder'] ?? 'INBOX';
// Determine if we should sync with IMAP (sync=1) or just read from DB (sync=0)
$sync = $_GET['sync'] ?? '0';

if ($sync === '1') {
    // --- Update the local trays table by syncing folders from the mail server ---
    $defaultFolder = "INBOX"; // Use INBOX as a base for connection
    $mailboxString = $user['imap_server'] . $defaultFolder;
    $imapStream = imap_open($mailboxString, $user['email'], $user['password']);
    if ($imapStream) {
         $mailboxes = imap_getmailboxes($imapStream, $user['imap_server'], "*");
         if ($mailboxes) {
              foreach ($mailboxes as $mailbox) {
                  // Remove the connection string from the mailbox name and clean it up.
                  $folderName = str_replace($user['imap_server'], "", $mailbox->name);
                  $folderName = trim($folderName, " {}");
                  // Check if this folder is already stored in the local trays table.
                  $stmt = $db->prepare("SELECT COUNT(*) FROM trays WHERE user_id = :user_id AND tray_name = :tray");
                  $stmt->execute([
                      ':user_id' => $user['id'],
                      ':tray'    => $folderName
                  ]);
                  if ($stmt->fetchColumn() == 0) {
                      // Insert new tray if not present.
                      $stmtInsert = $db->prepare("INSERT INTO trays (user_id, tray_name) VALUES (:user_id, :tray)");
                      $stmtInsert->execute([
                          ':user_id' => $user['id'],
                          ':tray'    => $folderName
                      ]);
                  }
              }
         }
         imap_close($imapStream);
    }
    
    // --- End tray synchronization ---
    
    // Proceed to sync emails from the mail server
    $mailboxString = $user['imap_server'] . $folder;
    $inbox = imap_open($mailboxString, $user['email'], $user['password']);

    if ($inbox) {
        $emailsOnServer = imap_search($inbox, 'ALL');
        if ($emailsOnServer) {
            // Sort descending so we process the newest first
            rsort($emailsOnServer);

            foreach ($emailsOnServer as $emailNumber) {
                $uid = imap_uid($inbox, $emailNumber);

                // Check if this mail already exists in our DB for the current user/folder
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM emails
                    WHERE user_id = :user_id
                      AND folder  = :folder
                      AND uid     = :uid
                ");
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':folder'  => $folder,
                    ':uid'     => $uid
                ]);
                $exists = $stmt->fetchColumn();

                if (!$exists) {
                    // Fetch the IMAP overview (subject, from, date, etc.)
                    $overview = imap_fetch_overview($inbox, $emailNumber, 0);
                    $emailOverview = $overview[0] ?? null;

                    // Handle subject decoding
                    $rawSubject = isset($emailOverview->subject) ? $emailOverview->subject : '(No Subject)';
                    $decodedSubject = '';
                    $subjectParts = imap_mime_header_decode($rawSubject);

                    foreach ($subjectParts as $part) {
                        $charset = $part->charset;
                        if (!$charset || in_array(strtolower($charset), ['default', 'unknown-8bit'])) {
                            $decodedSubject .= $part->text;
                        } else {
                            $decodedSubject .= @iconv($charset, 'UTF-8//TRANSLIT', $part->text);
                        }
                    }

                    // Get message structure for body decoding
                    $structure = imap_fetchstructure($inbox, $emailNumber);
                    // Use helper function to get decoded body (defined in funciones/decodificarcuerpo.php)
                    $decodedMessage = getDecodedBody($inbox, $emailNumber, $structure);

                    // Normalize the date string for proper ordering in SQLite
                    $rawDateStr = isset($emailOverview->date) ? $emailOverview->date : '';
                    $parsedDate = strtotime($rawDateStr);
                    if ($parsedDate === false) {
                        $parsedDate = time();
                    }
                    $normalizedDate = date('Y-m-d H:i:s', $parsedDate);

                    // Insert the new email into the DB
                    $stmt = $db->prepare("
                        INSERT OR IGNORE INTO emails
                        (user_id, folder, uid, sender, subject, date, body)
                        VALUES (:user_id, :folder, :uid, :sender, :subject, :date, :body)
                    ");
                    $stmt->execute([
                        ':user_id' => $user['id'],
                        ':folder'  => $folder,
                        ':uid'     => $uid,
                        ':sender'  => isset($emailOverview->from) ? $emailOverview->from : 'Unknown',
                        ':subject' => $decodedSubject,
                        ':date'    => $normalizedDate,
                        ':body'    => $decodedMessage
                    ]);
                }
            }
        }
        imap_close($inbox);
    }
}

// Finally, fetch all emails from the DB for this user & folder, ordered by date DESC.
$stmt = $db->prepare("
    SELECT * FROM emails
    WHERE user_id = :user_id
      AND folder  = :folder
    ORDER BY date DESC
");
$stmt->execute([
    ':user_id' => $user['id'],
    ':folder'  => $folder
]);
$storedEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($storedEmails);
?>

