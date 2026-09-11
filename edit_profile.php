<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    
    $dup = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
    $dup->execute([$email, $userId]);
    if ($dup->fetch()) {
        $_SESSION['flash'] = "Email is already in use by another account.";
    } else {
        $up = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $up->execute([$name, $email, $phone, $userId]);

        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['flash'] = "Profile updated successfully.";
        header("Location: account.php");
        exit;
    }
}


$u = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>
<section class="form">
  <h2>Edit Profile</h2>
  <form method="post">
    <div class="row">
      <div class="col">
        <label>Full Name</label>
        <input type="text" name="full_name" required value="<?php echo sanitize($user['full_name']); ?>">
      </div>
      <br>
      <br>
      <div class="col">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo sanitize($user['email']); ?>">
      </div>
      <br>
      <br>
      <div class="col">
      <label>Phone</label>
      <input type="text" name="phone" value="<?php echo sanitize($user['phone'] ?? ''); ?>" onkeypress="return onlyNumbers(event)">
      </div>
    </div>
    <button class="btn" type="submit">Save Changes</button>
    <a class="btn ghost" href="account.php">Cancel</a>
  </form>
</section>
<?php include __DIR__ . '/footer.php'; ?>
