<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once 'db.php';
require_once 'helpers.php';
require_once 'auth.php';
requireLogin();

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;


$stmt = $conn->prepare("
  SELECT
    b.id,
    m.title,
    s.date_time,
    b.qty,            -- number of seats
    b.status,
    b.total_price
  FROM bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN movies   m ON s.movie_id   = m.id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<section class="list">
  <h2>My Bookings</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Movie</th>
        <th>Showtime</th>
        <th>Seats</th>
        <th>Status</th>
        <th>Total</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($bookings): ?>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?php echo (int)$b['id']; ?></td>
            <td><?php echo sanitize($b['title']); ?></td>
            <td><?php echo formatDateTime($b['date_time']); ?></td>
            <td><?php echo (int)$b['qty']; ?></td>
            <td><?php echo sanitize($b['status']); ?></td>
            <td>Rs. <?php echo number_format((float)$b['total_price'], 2); ?></td>
            <td>
              <?php if (in_array($b['status'], ['pending','confirmed'], true)): ?>
                <a class="btn ghost"
                   href="cancel_booking.php?id=<?php echo (int)$b['id']; ?>"
                   onclick="return confirm('Cancel this booking?')">Cancel</a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
          <p> </p>
        <?php endforeach; ?> 
      <?php else: ?>
        <tr><td colspan="7">No bookings yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<?php include 'footer.php'; ?>
