<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'helpers.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { $_SESSION['flash'] = "Invalid booking."; header("Location: my_bookings.php"); exit; }


$chk = $conn->prepare("SELECT b.id, b.user_id, b.status FROM bookings b WHERE b.id = ?");
$chk->execute([$id]);
$bk = $chk->fetch(PDO::FETCH_ASSOC);

if (!$bk || $bk['user_id'] != $_SESSION['user_id']) {
    $_SESSION['flash'] = "Booking not found.";
    header("Location: my_bookings.php"); exit;
}

if (!in_array($bk['status'], ['pending','confirmed'])) {
    $_SESSION['flash'] = "This booking cannot be cancelled.";
    header("Location: my_bookings.php"); exit;
}


$conn->beginTransaction();
try {
    $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $upd->execute([$id]);

    
    $seatIds = $conn->prepare("SELECT seat_id FROM booking_seats WHERE booking_id = ?");
    $seatIds->execute([$id]);
    $free = $conn->prepare("UPDATE seats SET is_booked = 0 WHERE id = ?");
    while ($s = $seatIds->fetch(PDO::FETCH_ASSOC)) {
        $free->execute([$s['seat_id']]);
    }
    $conn->commit();
    $_SESSION['flash'] = "Booking cancelled.";
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['flash'] = "Failed to cancel booking.";
}

header("Location: my_bookings.php");
exit;
