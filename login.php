<?php
session_start();
require 'db.php';
require 'helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['flash'] = "Welcome back, " . htmlspecialchars($user['full_name']) . " 🎉";
        redirect("account.php");
    } else {
        $error = "⚠️ Invalid email or password";
    }
}
?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<?php include 'header.php'; ?>

<section class="form" style="max-width:500px">
  <h2>Login</h2>

  <?php if (!empty($error)): ?>
    <div class="mc-flash error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
    <br>
    <br>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <br>
    <br>
    <button type="submit" class="btn">Login</button>
    <a href="register.php" class="btn ghost">Create Account</a>
  </form>
</section>

<?php include 'footer.php'; ?>

