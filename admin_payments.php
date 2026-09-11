<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'db.php';
require_once 'helpers.php';
require_once 'auth.php';
requireAdmin(); 


$status  = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed = ['paid','pending','failed'];
$where   = '';
$params  = [];

if ($status && in_array($status, $allowed, true)) {
    $where = "WHERE p.status = ?";
    $params[] = $status;
}

$sql = "
SELECT
  p.id,
  p.booking_id,
  p.amount,
  p.method,
  p.status,
  p.paid_at,
  b.qty,
  b.status AS booking_status,
  u.full_name,
  u.email,
  s.date_time,
  m.title
FROM payments p
JOIN bookings b  ON b.id = p.booking_id
JOIN users u     ON u.id = b.user_id
JOIN showtimes s ON s.id = b.showtime_id
JOIN movies m    ON m.id = s.movie_id
{$where}
ORDER BY COALESCE(p.paid_at, p.id) DESC, p.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<script>
 
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>
<section class="list">
  <h2>Payments<?php echo $status ? ' — ' . htmlspecialchars(strtoupper($status)) : ''; ?></h2>

  <div style="margin:10px 0;">
    <a class="btn ghost" href="admin_payments.php">All</a>
    <a class="btn ghost" href="admin_payments.php?status=paid">Paid</a>
    <a class="btn ghost" href="admin_payments.php?status=pending">Pending</a>
    <a class="btn ghost" href="admin_payments.php?status=failed">Failed</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Booking</th>
        <th>User</th>
        <th>Movie</th>
        <th>Showtime</th>
        <th>Qty</th>
        <th>Amount</th>
        <th>Method</th>
        <th>Pay Status</th>
        <th>Paid At</th>
        <th>Booking Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="11">No payments yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td>#<?php echo (int)$r['booking_id']; ?></td>
          <td><?php echo sanitize($r['full_name']); ?><br><small><?php echo sanitize($r['email']); ?></small></td>
          <td><?php echo sanitize($r['title']); ?></td>
          <td><?php echo formatDateTime($r['date_time']); ?></td>
          <td><?php echo (int)$r['qty']; ?></td>
          <td>Rs. <?php echo number_format((float)$r['amount'], 2); ?></td>
          <td><?php echo strtoupper(sanitize($r['method'])); ?></td>
          <td>
            <?php if ($r['status']==='paid'): ?>
              <span class="badge" style="background:#1f9d55">PAID</span>
            <?php elseif ($r['status']==='pending'): ?>
              <span class="badge" style="background:#b7791f">PENDING</span>
            <?php else: ?>
              <span class="badge" style="background:#c53030">FAILED</span>
            <?php endif; ?>
          </td>
          <td><?php echo $r['paid_at'] ? formatDateTime($r['paid_at']) : '—'; ?></td>
          <td><?php echo sanitize($r['booking_status']); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
<form method="post" action="admin_dashboard.php" style="margin-top:10px">
        <button class="btn" type="submit">Go Back</button>
<?php include 'footer.php'; ?>