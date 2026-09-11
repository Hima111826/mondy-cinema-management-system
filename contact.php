<?php
require_once 'db.php';
require_once 'helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId    = (int)($_SESSION['user_id']   ?? 0);
$userName  =        $_SESSION['user_name']  ?? '';
$userEmail =        $_SESSION['user_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = sanitize($_POST['name']    ?? ($userName  ?: 'Guest'));
  $email   = sanitize($_POST['email']   ?? ($userEmail ?: 'guest@mondycinema.local'));
  $subject = sanitize($_POST['subject'] ?? '');
  $message = sanitize($_POST['message'] ?? '');

  if (!$name || !$email || !$message) {
    $_SESSION['flash'] = "Please fill all required fields.";
    header("Location: contact.php"); exit;
  }

  $ins = $conn->prepare("
    INSERT INTO contact_messages
      (user_id, name, email, subject, message, is_read, is_admin, created_at)
    VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
  ");
  $ins->execute([$userId ?: null, $name, $email, $subject ?: null, $message]);

  $_SESSION['flash'] = "Message sent. We’ll get back to you soon.";
  header("Location: contact.php"); exit;
}

$messages = [];
if ($userId > 0) {
  $q = $conn->prepare("
    SELECT id, is_admin, subject, message, created_at
    FROM contact_messages
    WHERE user_id = ?
    ORDER BY created_at DESC
  ");
  $q->execute([$userId]);
  $messages = $q->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>
<h2>🎬 You must register here before you can connect with us.</h2>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<section class="form" style="max-width:680px">
  <h2>Contact Us</h2>
  <form method="post" autocomplete="off">
    <label>Your Name</label>
    <input name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
    <br>
     <br>
    <label>Your Email</label>
    <input name="email" type="email" value="<?php echo htmlspecialchars($userEmail); ?>" required>
     <br>
      <br>
    <label>Subject (optional)</label>
    <input name="subject" placeholder="Payment ID #123, Booking #45, etc.">
     <br>
      <br>
    <label>Message</label>
    <textarea name="message" rows="5" required></textarea>
     <br>
      <br>
    <button class="btn" type="submit">Send</button>
  </form>
</section>

<?php if ($userId > 0): ?>
<section class="list" style="max-width:900px">
  <h2>Inbox</h2>
  <table>
    <thead><tr><th>#</th><th>From</th><th>Subject</th><th>Preview</th><th>When</th></tr></thead>
    <tbody>
      <?php foreach ($messages as $m):
        $preview = mb_substr($m['message'] ?? '', 0, 80) . ((strlen($m['message'] ?? '')>80)?'…':'');
      ?>
      <tr>
        <td><?php echo (int)$m['id']; ?></td>
        <td><?php echo $m['is_admin'] ? 'Admin' : 'You'; ?></td>
        <td><?php echo htmlspecialchars($m['subject'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($preview ?: '—'); ?></td>
        <td><?php echo formatDateTime($m['created_at']); ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($messages)): ?>
        <tr><td colspan="5">No messages yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>