<?php
session_start();
require 'db.php';
require 'helpers.php';


$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['full_name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone']);
    $pass = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    if (!$email) {
        $error = "Invalid email address!";
    } elseif ($pass !== $cpass) {
        $error = "Passwords do not match!";
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, phone) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hash, $phone]);

           
           

            $_SESSION['flash'] = "🎉 Registration successful. Please login.";
            redirect("login.php");
        } catch (PDOException $e) {
            $error = "⚠️ Email already exists. Try another one.";
        }
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
  <h2>Create Account</h2>
  
  <?php if (!empty($error)): ?>
    <div class="mc-flash error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="register.php" onsubmit="return validateRegisterForm();">
    <label for="full_name">Full Name</label>
    <input type="text" id="full_name" name="full_name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
    <br>
    <br>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
    <br>
    <br>
    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" required value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
    <br>
    <br>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <br>
    <br>
    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required>
    <br>
    <br>
    <button type="submit" class="btn">Register</button>
    <a href="login.php" class="btn ghost">Already have an account?</a>
  </form>
</section>

<script>
function validateRegisterForm() {
  const pass = document.getElementById("password").value;
  const cpass = document.getElementById("confirm_password").value;
  if (pass !== cpass) {
    alert("Passwords do not match!");
    return false;
  }
  return true;
}
</script>

<?php include 'footer.php'; ?>
