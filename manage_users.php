<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
session_start();
if (empty($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user_id'])) {
        $id = (int)$_POST['delete_user_id'];
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = "User deleted.";
        header("Location: manage_users.php"); exit;
    }
    if (isset($_POST['update_user_id'])) {
        $id = (int)$_POST['update_user_id'];
        $name = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $id]);
        $_SESSION['flash'] = "User updated.";
        header("Location: manage_users.php"); exit;
    }
}

$users = $conn->query("SELECT id, full_name, email, phone, created_at FROM users ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/admin_header.php';
?>
<script>
 
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>
<section>
  <h2>Manage Users</h2>
  <div class="list">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <form method="post">
            <td><input type="text" name="full_name" value="<?php echo sanitize($u['full_name']); ?>"></td>
            <td><?php echo sanitize($u['email']); ?></td>
            <td><input name="phone" value="<?php echo sanitize($u['phone']); ?>"></td>
            <td><?php echo date("d M Y", strtotime($u['created_at'])); ?></td>
            <td>
              <input type="hidden" name="update_user_id" value="<?php echo (int)$u['id']; ?>">
              <button class="btn" type="submit">Save</button>
          </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?');">
                <input type="hidden" name="delete_user_id" value="<?php echo (int)$u['id']; ?>">
                <button class="btn btn-danger" type="submit">Delete</button>
              </form>
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<form method="post" action="admin_dashboard.php" style="margin-top:10px">
        <button class="btn" type="submit">Go Back</button>

<?php include __DIR__ . '/footer.php'; ?>
