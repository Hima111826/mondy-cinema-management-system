<?php


if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once 'db.php';
require_once 'helpers.php';
 


if (!empty($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $conn->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);           // prevent session fixation
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please provide username and password.';
    }
}

include 'header.php';
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bgadmin');
  });
</script>
<section class="form" style="max-width:480px">
  <h2>Admin Login</h2>
  <?php if ($error): ?>
    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <form method="post" autocomplete="off">
    <label>Username (email)</label>
    <input name="username" type="email" required />
    <br>
    <br>
    <label>Password</label>
    <input name="password" type="password" required />
    <br>
    <br>
    <button class="btn btn-primary" type="submit">Login</button>
  </form>
</section>
<?php include 'footer.php'; ?>