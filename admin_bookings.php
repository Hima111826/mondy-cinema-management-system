<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require 'db.php';
require 'helpers.php';
requireAdmin(); 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action     = $_POST['action'] === 'confirm' ? 'confirm' : 'reject';
    $status     = ($action === 'confirm') ? 'confirmed' : 'rejected';

   
    $upd = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $upd->execute([$status, $booking_id]);

    
    $u = $conn->prepare("
        SELECT u.email, u.full_name
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        WHERE b.id = ?
        LIMIT 1
    ");
    $u->execute([$booking_id]);
    if ($user = $u->fetch(PDO::FETCH_ASSOC)) {
        $subject = "Booking {$status} - MondyCinema";
        $body    = "<p>Dear ".sanitize($user['full_name']).",</p>
                    <p>Your booking #{$booking_id} has been <strong>{$status}</strong>.</p>
                    <p>Thank you for choosing MondyCinema!</p>";
        
       
    }

    setFlash("Booking #{$booking_id} updated to {$status}.", 'success');
    redirect('admin_bookings.php');
}


$q = $conn->query("
    SELECT
        b.*,
        u.full_name,
        m.title,
        s.date_time,
        (SELECT GROUP_CONCAT(se.seat_no ORDER BY se.seat_no SEPARATOR ', ')
           FROM booking_seats bs
           JOIN seats se ON se.id = bs.seat_id
          WHERE bs.booking_id = b.id) AS seat_list
    FROM bookings b
    JOIN users u     ON u.id = b.user_id
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m    ON m.id = s.movie_id
    ORDER BY b.created_at DESC
");
$bookings = $q->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';

?>
<script>
 
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>
<section class="list">
  <h2>Manage Bookings</h2>
  <?php flashMessage(); ?>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Movie</th>
        <th>Showtime</th>
        <th>Seats</th>
        <th>Status</th>
        <th>Total</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?php echo (int)$b['id']; ?></td>
          <td><?php echo sanitize($b['full_name']); ?></td>
          <td><?php echo sanitize($b['title']); ?></td>
          <td><?php echo formatDateTime($b['date_time']); ?></td>
          <td><?php echo sanitize($b['seat_list'] ?? '-'); ?></td>
          <td><strong><?php echo sanitize($b['status']); ?></strong></td>
          <td>Rs. <?php echo number_format((float)$b['total_price'], 2); ?></td>
          <td>
            <?php if ($b['status'] === 'pending'): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                <button class="btn" type="submit" name="action" value="confirm">Confirm</button>
                <button class="btn ghost" type="submit" name="action" value="reject"
                        onclick="return confirm('Reject this booking?');">Reject</button>
              </form>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="8">No bookings yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  
</section>
<form method="post" action="admin_dashboard.php" style="margin-top:10px">
        <button class="btn" type="submit">Go Back</button>
<?php include 'footer.php'; ?>
