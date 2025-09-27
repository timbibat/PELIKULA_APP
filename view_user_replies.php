<?php
ob_start();
include_once 'fetch_gmail_replies.php';
ob_end_clean();

session_start();
require 'db.php';

// Only allow admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit;
}

// Mark reply as seen
function markReplyAsSeen($pdo, $reply_id) {
    $stmt = $pdo->prepare("UPDATE replies SET is_seen = 1 WHERE id = ?");
    $stmt->execute([$reply_id]);
}

// Handle admin reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parent_reply_id'], $_POST['admin_message'])) {
    $parent_reply_id = intval($_POST['parent_reply_id']);
    $admin_message = trim($_POST['admin_message']);
    $admin_email = 'admin@pelikulacinema.com'; // change this as needed

    // Fetch original reply to get user info (for booking_id)
    $stmt = $pdo->prepare("SELECT * FROM replies WHERE id=?");
    $stmt->execute([$parent_reply_id]);
    $parent_reply = $stmt->fetch();

    if ($admin_message && $parent_reply) {
        // Save the admin reply, relate it to the parent reply
        $stmt = $pdo->prepare("INSERT INTO replies (user_id, email, booking_id, message, parent_reply_id) VALUES (NULL, ?, ?, ?, ?)");
        $stmt->execute([$admin_email, $parent_reply['booking_id'], $admin_message, $parent_reply_id]);
        $admin_reply_id = $pdo->lastInsertId();

        // Send email notification to the original user using PHPMailer
        require_once 'send_email.php';
        $user_email = $parent_reply['email'];
        if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            sendAdminReplyEmail($user_email, $admin_message, $parent_reply['message'], $admin_reply_id);
        }

        header("Location: view_user_replies.php");
        exit;
    }
}

// Fetch all replies, including admin replies (threaded) and movie title
$stmt = $pdo->query("
    SELECT 
        r.id AS reply_id,
        COALESCE(u.email, r.email) AS user_email,
        r.booking_id,
        r.message,
        r.created_at,
        r.parent_reply_id,
        r.is_seen,
        b.movie_id,
        m.title AS movie_title
    FROM replies r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN bookings b ON r.booking_id = b.id
    LEFT JOIN tbl_movies m ON b.movie_id = m.id
    ORDER BY r.booking_id DESC, r.created_at ASC
");
$all_replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group replies by booking_id for dropdowns
$replies_by_booking = [];
foreach ($all_replies as $reply) {
    $replies_by_booking[$reply['booking_id']][] = $reply;
}

// Helper to check if a reply is new (not seen)
function isNewReply($reply) {
    return empty($reply['is_seen']) || $reply['is_seen'] == 0;
}

// Helper to recursively display threaded replies for a single booking
function displayRepliesThreaded($replies, $parent_id = 0, $level = 0) {
    global $pdo;
    // Group replies by parent
    $by_parent = [];
    foreach ($replies as $r) {
        $by_parent[$r['parent_reply_id'] ?? 0][] = $r;
    }
    // Recursive display
    if (isset($by_parent[$parent_id])) {
        foreach ($by_parent[$parent_id] as $reply) {
            $is_admin = (strtolower($reply['user_email']) === 'admin@pelikulacinema.com');
            $indent = $level * 40;
            $is_new = isNewReply($reply);
            if ($is_new) {
                markReplyAsSeen($pdo, $reply['reply_id']);
            }
            echo "<tr" . ($is_new ? " class='table-warning'" : "") . ">";
            echo "<td style='padding-left:{$indent}px'>";
            if ($is_admin) {
                echo "<span class='badge bg-secondary'>ADMIN</span> ";
            }
            echo htmlspecialchars($reply['user_email']);
            if ($is_new) {
                echo " <span class=\"badge bg-success ms-1\">New</span>";
            }
            echo "</td>";
            // Add Movie Title
            echo "<td>" . htmlspecialchars($reply['movie_title'] ?? '-') . "</td>";
            echo "<td class='message-cell'>" . nl2br(htmlspecialchars($reply['message'])) . "</td>";
            echo "<td>" . htmlspecialchars($reply['created_at']) . "</td>";
            echo "<td>";
            // Reply button for any user reply (not admin)
            if (!$is_admin) {
                ?>
                <button class="btn btn-sm btn-primary" onclick="showReplyForm(<?= $reply['reply_id'] ?>)">Reply</button>
                <form id="reply-form-<?= $reply['reply_id'] ?>" method="POST" style="display:none; margin-top:8px;">
                    <input type="hidden" name="parent_reply_id" value="<?= $reply['reply_id'] ?>">
                    <textarea name="admin_message" class="form-control mb-2" rows="2" placeholder="Type admin reply..." required></textarea>
                    <button type="submit" class="btn btn-success btn-sm">Send</button>
                </form>
                <?php
            }
            echo "</td>";
            echo "</tr>";
            displayRepliesThreaded($replies, $reply['reply_id'], $level + 1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Replies - Admin - Pelikula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-cell { max-width: 400px; word-break: break-word; }
        td { vertical-align: top; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
      <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Pelikula Admin</a>
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link btn btn-primary text-white px-3 mx-2" href="view_user_bookings.php">View User Bookings</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-info text-white px-3 mx-2" href="view_user_replies.php">View User Replies</a>
            </li>
            <li class="nav-item">
              <a class="nav-link btn btn-danger text-white px-3" href="logout.php">Logout</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container mt-3">
        <h2 class="mb-4">User Replies by Booking</h2>
        <?php if (empty($replies_by_booking)): ?>
            <div class="alert alert-info">No replies found.</div>
        <?php else: ?>
            <div class="accordion" id="bookingAccordion">
                <?php foreach ($replies_by_booking as $booking_id => $booking_replies): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $booking_id ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $booking_id ?>" aria-expanded="false" aria-controls="collapse<?= $booking_id ?>">
                            Booking ID: <?= htmlspecialchars($booking_id) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $booking_id ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $booking_id ?>" data-bs-parent="#bookingAccordion">
                        <div class="accordion-body px-0">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User Email</th>
                                        <th>Movie</th>
                                        <th>Message</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php displayRepliesThreaded($booking_replies); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showReplyForm(replyId) {
        document.getElementById('reply-form-' + replyId).style.display = 'block';
    }
    </script>
</body>
</html>