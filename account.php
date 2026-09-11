<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
requireLogin();

include __DIR__ . '/header.php';

$userId = $_SESSION['user_id'];


$u = $conn->prepare("SELECT id, full_name, email, phone, created_at FROM users WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch(PDO::FETCH_ASSOC);


$b = $conn->prepare("
  SELECT b.id, b.status, b.total_price, b.created_at,
         s.date_time, s.screen, m.title
  FROM bookings b
  JOIN showtimes s ON s.id = b.showtime_id
  JOIN movies m ON m.id = s.movie_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
  LIMIT 5
");
$b->execute([$userId]);
$bookings = $b->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
 
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<section class="grid-2">
  <div class="card">
    <div class="card-body">
      <h2>My Profile</h2>
      <p><strong>Name:</strong> <?php echo sanitize($user['full_name']); ?></p>
      <p><strong>Email:</strong> <?php echo sanitize($user['email']); ?></p>
      <p><strong>Phone:</strong> <?php echo sanitize($user['phone'] ?? ''); ?></p>
      <p><strong>Member since:</strong> <?php echo date("d M Y", strtotime($user['created_at'])); ?></p>
      <a class="btn" href="edit_profile.php">Edit Profile</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h2>Quick Links</h2>
      <p><a class="btn" href="movie_list.php">Browse Movies</a></p>
      <p><a class="btn secondary" href="my_bookings.php">My Bookings</a></p>
      <p><a class="btn ghost" href="logout.php">Logout</a></p>
    </div>
  </div>
</section>

<section class="list" style="margin-top:16px">
  <h2>Recent Bookings</h2>
  <table>
    <thead><tr><th>#</th><th>Movie</th><th>Showtime</th><th>Screen</th><th>Status</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach($bookings as $row): ?>
        <tr>
          <td><?php echo (int)$row['id']; ?></td>
          <td><?php echo sanitize($row['title']); ?></td>
          <td><?php echo formatDateTime($row['date_time']); ?></td>
          <td><?php echo sanitize($row['screen']); ?></td>
          <td><?php echo sanitize($row['status']); ?></td>
          <td>Rs. <?php echo number_format($row['total_price'],2); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="6">No recent bookings.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/footer.php'; ?>
