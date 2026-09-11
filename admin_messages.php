<?php

session_start();
require_once 'db.php';
require_once 'helpers.php';
require_once 'auth.php';
requireAdmin();


function adminAlreadyConfirmedMessage(PDO $conn, int $bookingId): bool {
  $q = $conn->prepare("
    SELECT COUNT(*) FROM contact_messages
    WHERE is_admin = 1 AND booking_id = ? AND subject LIKE 'Payment Confirmation%'
  ");
  $q->execute([$bookingId]);
  return (int)$q->fetchColumn() > 0;
}

function extractBookingIdFromSubject(?string $subject): int {
  if (!$subject) return 0;
  if (preg_match('/booking\s*#\s*(\d+)/i', $subject, $m)) return (int)$m[1];
  return 0;
}

$replyTo = (int)($_GET['reply_to'] ?? 0);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id     = (int)($_POST['id'] ?? 0);

  if ($action === 'delete' && $id) {
    $conn->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = "Message #{$id} deleted.";
    header("Location: admin_messages.php"); exit;
  }

  if ($action === 'mark_read' && $id) {
    $conn->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = "Message #{$id} marked as read.";
    header("Location: admin_messages.php"); exit;
  }

  if ($action === 'confirm_payment') {
    $msgId     = (int)($_POST['id'] ?? 0);
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $userId    = (int)($_POST['user_id'] ?? 0);

    if ($bookingId > 0 && $userId > 0) {
      if (!adminAlreadyConfirmedMessage($conn, $bookingId)) {
        $subject = "Payment Confirmation for Booking #{$bookingId}";
        $body    = "We have confirmed your payment. Your booking #{$bookingId} is now paid. Enjoy the movie!";

        $ins = $conn->prepare("
          INSERT INTO contact_messages
            (user_id, name, email, subject, message, is_read, is_admin, booking_id, created_at)
          VALUES (?, 'Admin', 'noreply@mondycinema.local', ?, ?, 0, 1, ?, NOW())
        ");
        $ins->execute([$userId, $subject, $body, $bookingId]);

        if ($msgId) {
          $conn->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$msgId]);
        }

        $_SESSION['flash'] = "Confirmation sent to user for booking #{$bookingId}.";
      } else {
        $_SESSION['flash'] = "This booking already has a confirmation message.";
      }
    } else {
      $_SESSION['flash'] = "Invalid confirmation request.";
    }
    header("Location: admin_messages.php"); exit;
  }

  if ($action === 'send_reply') {
    $origId  = (int)($_POST['id'] ?? 0);
    $userId  = (int)($_POST['user_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if ($origId && $userId && $body !== '') {
      if ($subject === '') $subject = 'Re: your message';

      $ins = $conn->prepare("
        INSERT INTO contact_messages
          (user_id, name, email, subject, message, is_read, is_admin, created_at)
        VALUES (?, 'Admin', 'noreply@mondycinema.local', ?, ?, 0, 1, NOW())
      ");
      $ins->execute([$userId, $subject, $body]);

      $conn->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$origId]);

      $_SESSION['flash'] = "Reply sent to user.";
    } else {
      $_SESSION['flash'] = "Reply failed. Please enter a message.";
    }
    header("Location: admin_messages.php"); exit;
  }
}


$q = $conn->query("
  SELECT id, user_id, name, email, subject, message, is_read, is_admin, booking_id, payment_id, created_at
  FROM contact_messages
  ORDER BY created_at DESC
");
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>
<section class="list">
  <h2>Inbox</h2>
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="mc-flash"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>#</th><th>From</th><th>Email</th><th>Subject</th><th>Preview</th><th>When</th><th>Status</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r):
      $preview  = mb_substr($r['message'] ?? '', 0, 80) . ((strlen($r['message'] ?? '') > 80) ? '…' : '');
      $isAdmin  = (int)$r['is_admin'] === 1;
      $subject  = $r['subject'] ?? '';
      $bookingId = (int)($r['booking_id'] ?? 0);
      if ($bookingId <= 0) $bookingId = extractBookingIdFromSubject($subject);

      $isAdminPaymentConfirmation = ($isAdmin && stripos($subject, 'Payment Confirmation') === 0);
      $showConfirmForUserPayment  = (!$isAdmin && $bookingId > 0 && !adminAlreadyConfirmedMessage($conn, $bookingId));
      $isNormalUserMsg            = (!$isAdmin && $bookingId === 0);
    ?>
      <tr id="row-<?php echo (int)$r['id']; ?>">
        <td><?php echo (int)$r['id']; ?></td>
        <td><?php echo $isAdmin ? '<span class="badge">Admin</span> Admin' : htmlspecialchars($r['name']); ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars($subject ?: '—'); ?></td>
        <td><?php echo htmlspecialchars($preview ?: '—'); ?></td>
        <td><?php echo formatDateTime($r['created_at']); ?></td>
        <td><?php echo $r['is_read'] ? 'read' : 'unread'; ?></td>
        <td>
          <?php if ($showConfirmForUserPayment): ?>
            
            <form method="post" style="display:inline;margin-right:6px">
              <input type="hidden" name="action" value="confirm_payment">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
              <input type="hidden" name="user_id" value="<?php echo (int)$r['user_id']; ?>">
              <button class="btn">Confirm Payment</button>
            </form>
            <form method="post" style="display:inline">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn ghost" name="action" value="mark_read">Mark Read</button>
              <button class="btn ghost" name="action" value="delete" onclick="return confirm('Delete this message?')">Delete</button>
            </form>

          <?php elseif ($isAdmin): ?>
            
            <form method="post" style="display:inline">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn ghost" name="action" value="delete" onclick="return confirm('Delete this message?')">Delete</button>
            </form>

          <?php elseif ($isNormalUserMsg): ?>
            
            <a class="btn" href="admin_messages.php?reply_to=<?php echo (int)$r['id']; ?>#row-<?php echo (int)$r['id']; ?>">Reply</a>
            <form method="post" style="display:inline;margin-left:6px">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn ghost" name="action" value="delete" onclick="return confirm('Delete this message?')">Delete</button>
            </form>

          <?php else: ?>
            
            <form method="post" style="display:inline">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn ghost" name="action" value="mark_read">Mark Read</button>
              <button class="btn ghost" name="action" value="delete" onclick="return confirm('Delete this message?')">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>

      <?php if ($isNormalUserMsg && $replyTo === (int)$r['id']): ?>
        
        <tr>
          <td colspan="8">
            <form method="post" autocomplete="off" style="display:grid; gap:8px; max-width:720px">
              <input type="hidden" name="action" value="send_reply">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <input type="hidden" name="user_id" value="<?php echo (int)$r['user_id']; ?>">

              <label>To</label>
              <input value="<?php echo htmlspecialchars($r['name'] . ' <' . $r['email'] . '>'); ?>" readonly>

              <label>Subject</label>
              <input name="subject" value="<?php echo htmlspecialchars('Re: ' . ($subject ?: 'your message')); ?>">

              <label>Message</label>
              <textarea name="body" rows="4" required placeholder="Type your reply…"></textarea>

              <div>
                <button class="btn" type="submit">Send Reply</button>
                <a class="btn ghost" href="admin_messages.php#row-<?php echo (int)$r['id']; ?>">Cancel</a>
              </div>
            </form>
          </td>
        </tr>
      <?php endif; ?>

    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
      <tr><td colspan="8">No messages.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>
<form method="post" action="admin_dashboard.php" style="margin-top:10px">
        <button class="btn" type="submit">Go Back</button>
<?php include 'footer.php'; ?>