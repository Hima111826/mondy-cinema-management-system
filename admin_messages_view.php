<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin_id'])) { $_SESSION['flash'] = "Please login as admin."; redirect("admin_login.php"); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { $_SESSION['flash'] = "Invalid message."; redirect("admin_messages.php"); }


$mr = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
$mr->execute([$id]);

$q = $conn->prepare("
  SELECT cm.*, u.full_name AS linked_user
  FROM contact_messages cm
  LEFT JOIN users u ON u.id = cm.user_id
  WHERE cm.id = ?
  LIMIT 1
");
$q->execute([$id]);
$m = $q->fetch(PDO::FETCH_ASSOC);
if (!$m) { $_SESSION['flash'] = "Message not found."; redirect("admin_messages.php"); }

include __DIR__ . '/admin_header.php';
?>
<section class="form" style="max-width:800px">
  <h2>Message #<?php echo (int)$m['id']; ?></h2>
  <div class="card">
    <div class="card-body">
      <p><strong>From:</strong> <?php echo htmlspecialchars($m['name']); ?> (<?php echo htmlspecialchars($m['email']); ?>)</p>
      <?php if (!empty($m['linked_user'])): ?>
        <p><strong>User:</strong> <?php echo htmlspecialchars($m['linked_user']); ?></p>
      <?php endif; ?>
      <p><strong>Subject:</strong> <?php echo htmlspecialchars($m['subject'] ?: '(no subject)'); ?></p>
      <p><strong>Received:</strong> <?php echo formatDateTime($m['created_at']); ?></p>
      <hr>
      <p style="white-space:pre-wrap;"><?php echo htmlspecialchars($m['message']); ?></p>
      <div style="margin-top:16px">
        <a class="btn" href="admin_messages.php">Back to Inbox</a>
        <form method="post" action="admin_messages.php" style="display:inline">
          <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
          <button class="btn secondary" name="delete" onclick="return confirm('Delete this message?')">Delete</button>
        </form>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/footer.php'; ?>
