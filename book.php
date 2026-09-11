<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';
require 'helpers.php';


if (!isset($_SESSION['user_id'])) {
    redirect("login.php");
    exit;
}

$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;
if ($showtime_id <= 0) die("Invalid showtime.");


$stmt = $conn->prepare("SELECT s.*, m.title 
                        FROM showtimes s 
                        JOIN movies m ON s.movie_id=m.id 
                        WHERE s.id=?");
$stmt->execute([$showtime_id]);
$showtime = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$showtime) die("Showtime not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_count = intval($_POST['seat_count'] ?? 0);

    if ($seat_count <= 0) {
        $error = "Please select at least 1 seat.";
    } else {
        $total_price = $seat_count * $showtime['ticket_price'];

        $conn->beginTransaction();
        try {
            
            $stmt = $conn->prepare("INSERT INTO bookings 
                (user_id, showtime_id, qty, total_price, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())");

            $stmt->execute([
                $_SESSION['user_id'],
                $showtime_id,
                $seat_count,
                $total_price
            ]);

            $booking_id = $conn->lastInsertId();

       

            $conn->commit();
            $_SESSION['success'] = "Booking submitted successfully. Total Rs. " . number_format($total_price, 2);
            redirect("my_bookings.php");
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error booking seats: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<section class="form">
  <h2>Book Movie: <?php echo sanitize($showtime['title']); ?></h2>
  <p><strong>Showtime:</strong> <?php echo formatDateTime($showtime['date_time']); ?></p>
  <p><strong>Price per seat:</strong> Rs. <?php echo number_format($showtime['ticket_price'], 2); ?></p>

  <?php if (!empty($error)): ?>
    <div class="alert error"><?php echo sanitize($error); ?></div>
  <?php endif; ?>

  <form method="post">
    <label for="seat_count">Select number of seats:</label>
    <input type="number" id="seat_count" name="seat_count" 
           min="1" max="<?php echo (int)$showtime['total_seats']; ?>" 
           value="1" required>
    <button class="btn btn-primary" type="submit">Confirm Booking</button>
  </form>
</section>

<?php include 'footer.php'; ?>
