<?php
require 'db.php';

$movie_id = intval($_GET['movie_id'] ?? 0);
$showdate = $_GET['showdate'] ?? date('Y-m-d');
$showtime = $_GET['showtime'] ?? '';

if (!$movie_id || !$showdate || !$showtime) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT seat_code FROM seats WHERE movie_id=? AND showdate=? AND showtime=? AND status='reserved'");
$stmt->execute([$movie_id, $showdate, $showtime]);
$reserved = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($reserved);
?>