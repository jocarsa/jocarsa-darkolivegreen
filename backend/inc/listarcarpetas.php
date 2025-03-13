<?php
// listarcarpetas.php
//
// This script returns two things in JSON:
//   1) "trays" => array of folder (tray) names from the local DB
//   2) "usage" => mailbox quota usage data (if available from the IMAP server)

function getMailboxUsage($imapServer, $email, $password) {
    // Default structure if quota fails or is not supported
    $usageData = [
        'usedMB'     => 0,
        'limitMB'    => 0,
        'percentage' => 0
    ];

    // Attempt to open the INBOX to query quota
    $mailboxString = $imapServer . 'INBOX';
    $imap = @imap_open($mailboxString, $email, $password);
    if ($imap) {
        $quotaInfo = imap_get_quotaroot($imap, 'INBOX');
        if (is_array($quotaInfo) && isset($quotaInfo['STORAGE'])) {
            // 'usage' and 'limit' are typically in KB
            $usedKB  = $quotaInfo['STORAGE']['usage'];
            $limitKB = $quotaInfo['STORAGE']['limit'];

            $usedMB  = round($usedKB / 1024, 1);   // convert KB to MB
            $limitMB = round($limitKB / 1024, 1);  // convert KB to MB

            $percentage = 0;
            if ($limitMB > 0) {
                $percentage = round(($usedMB / $limitMB) * 100, 1);
            }

            $usageData = [
                'usedMB'     => $usedMB,
                'limitMB'    => $limitMB,
                'percentage' => $percentage
            ];
        }
        imap_close($imap);
    }

    return $usageData;
}

// Query the local SQLite database for the user's trays.
$stmt = $db->prepare("SELECT tray_name FROM trays WHERE user_id = :user_id ORDER BY tray_name ASC");
$stmt->execute([':user_id' => $user['id']]);
$trays = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Also get mailbox usage from the IMAP server
$usage = getMailboxUsage($user['imap_server'], $user['email'], $user['password']);

// Return both trays and usage in JSON
echo json_encode([
    'trays' => $trays,
    'usage' => $usage
]);
?>

