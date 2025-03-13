<?php
// Función auxiliar para decodificar el cuerpo del mensaje
function getDecodedBody($inbox, $emailNumber, $structure) {
    $body = '';
    $encoding = 0;
    // Si el correo no es multipart
    if (!isset($structure->parts)) {
        $body = imap_fetchbody($inbox, $emailNumber, 1);
        $encoding = $structure->encoding;
    } else {
        // Recorrer las partes para encontrar la parte de texto plano
        foreach ($structure->parts as $index => $part) {
            if (strtoupper($part->subtype) == 'PLAIN') {
                $body = imap_fetchbody($inbox, $emailNumber, $index + 1);
                $encoding = $part->encoding;
                break;
            }
        }
    }
    // Decodificar según el tipo de codificación
    switch ($encoding) {
        case 3: // BASE64
            $body = base64_decode($body);
            break;
        case 4: // QUOTED-PRINTABLE
            $body = quoted_printable_decode($body);
            break;
        default:
            break;
    }
    return $body;
}?>
