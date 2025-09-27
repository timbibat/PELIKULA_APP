<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

// Extract only the user's actual reply, removing quoted lines and admin headers
function extractUserReply($body) {
    $lines = preg_split('/\r\n|\r|\n/', $body);
    $reply = [];
    foreach ($lines as $line) {
        // Remove everything after (and including) the quoted line
        // Matches: On Fri, Sep 26, 2025 at 3:23 PM PELIKULA Admin <pelikulacinema@gmail.com> wrote:
        if (preg_match('/^On .+<.+@.+> wrote:$/i', trim($line))) break;
        if (preg_match('/^>/', trim($line))) break; // Remove quoted lines starting with ">"
        $reply[] = $line;
    }
    // Remove trailing blank lines
    while (count($reply) > 0 && trim(end($reply)) === '') {
        array_pop($reply);
    }
    return trim(implode("\n", $reply));
}

// Extract Booking ID from text
function extractBookingId($body) {
    if (preg_match('/Booking ID\s*:\s*(\d+)/i', $body, $matches)) {
        return $matches[1];
    }
    return null;
}

// Extract Parent Reply ID from text
function extractParentReplyId($body) {
    if (preg_match('/Replying to Reply ID: (\d+)/i', $body, $matches)) {
        return (int)$matches[1];
    }
    return null;
}

$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'pelikulacinema@gmail.com';
$password = 'ropmliocwcdxwspk';

$inbox = imap_open($hostname, $username, $password) or die('Cannot connect: ' . imap_last_error());
$emails = imap_search($inbox, 'UNSEEN');

if ($emails) {
    foreach ($emails as $email_number) {
        $header = imap_headerinfo($inbox, $email_number);
        $from = strtolower(trim($header->from[0]->mailbox . '@' . $header->from[0]->host));

        $plain_body = '';
        $html_body  = '';
        $structure = imap_fetchstructure($inbox, $email_number);

        if (!empty($structure->parts)) {
            foreach ($structure->parts as $partno => $part) {
                $body_part = imap_fetchbody($inbox, $email_number, $partno+1);
                if ($part->encoding == 3) $body_part = base64_decode($body_part);
                if ($part->encoding == 4) $body_part = quoted_printable_decode($body_part);

                if ($part->subtype == 'PLAIN') {
                    $plain_body .= $body_part;
                } elseif ($part->subtype == 'HTML') {
                    $html_body .= $body_part;
                }
            }
        } else {
            $plain_body = imap_fetchbody($inbox, $email_number, 1);
        }

        $user_reply = extractUserReply($plain_body ?: strip_tags($html_body));
        $booking_id = extractBookingId($plain_body);
        if (!$booking_id && $html_body) {
            $booking_id = extractBookingId(strip_tags($html_body));
        }

        // Extract parent reply ID
        $parent_reply_id = extractParentReplyId($plain_body ?: strip_tags($html_body));

        // If parent_reply_id exists but booking_id is missing, fetch it from parent
        if (!$booking_id && $parent_reply_id) {
            $stmt = $pdo->prepare("SELECT booking_id FROM replies WHERE id=?");
            $stmt->execute([$parent_reply_id]);
            $booking_id = $stmt->fetchColumn();
        }

        if ($user_reply) {
            // Try to match a registered user by email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE TRIM(LOWER(email)) = ?");
            $stmt->execute([$from]);
            $user = $stmt->fetch();

            // Prevent duplicates (same message, same user, same booking, same parent)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM replies WHERE message=? AND email=? AND booking_id=? AND IFNULL(parent_reply_id,0)=IFNULL(?,0)");
            $stmt->execute([$user_reply, $from, $booking_id, $parent_reply_id]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                if ($user) {
                    // Registered user
                    $stmt = $pdo->prepare("INSERT INTO replies (user_id, email, booking_id, message, parent_reply_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user['id'], $from, $booking_id, $user_reply, $parent_reply_id]);
                } else {
                    // Unregistered user
                    $stmt = $pdo->prepare("INSERT INTO replies (user_id, email, booking_id, message, parent_reply_id) VALUES (NULL, ?, ?, ?, ?)");
                    $stmt->execute([$from, $booking_id, $user_reply, $parent_reply_id]);
                }
            }
        }

        imap_setflag_full($inbox, $email_number, "\\Seen");
    }
}

imap_close($inbox);
?>