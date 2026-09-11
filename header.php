<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }


function _render_flash_once() {
    if (empty($_SESSION['flash'])) return;

    $f = $_SESSION['flash'];
    
    $msg  = is_array($f) ? ($f['msg'] ?? '')  : (string)$f;
    $type = is_array($f) ? ($f['type'] ?? 'info') : 'info';

    $class = 'mc-flash';
    if ($type === 'error')   $class .= ' error';
    if ($type === 'success') $class .= ' success';
    if ($type === 'warning') $class .= ' warning';

    echo '<div class="'. $class .'">'. htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') .'</div>';

    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MondyCinema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  
  <link rel="icon" href="/mondycinema/assets/img/favicon.png" type="image/png">

  
  <link rel="stylesheet" href="style.css">

  
  <script src="validation.js" defer></script>
</head>
<body>
<header class="mc-header">
  <nav class="mc-nav container">
    <a class="mc-logo" href="/mondycinema/index.php">🎬 MondyCinema</a>
    <ul class="mc-menu">
      <li><a href="/mondycinema/movie_list.php">Movies</a></li>
      <li><a href="/mondycinema/gallery.php">Gallery</a></li>
      <li><a href="/mondycinema/contact.php">Contact</a></li>

      <?php if (!empty($_SESSION['user_id'])): ?>
        <li><a href="/mondycinema/account.php">My Account</a></li>
        <li><a href="/mondycinema/my_bookings.php">My Bookings</a></li>
        <li><a href="/mondycinema/logout.php">Logout</a></li>
         <li><a href="/mondycinema/payment.php">Payment</a></li>
        
      <?php else: ?>
        <li><a href="/mondycinema/register.php">Register</a></li>
        <li><a href="/mondycinema/login.php">Login</a></li>
      <?php endif; ?>

      
      <li class="admin-link"><a href="/mondycinema/admin_login.php">Admin</a></li>
    </ul>
  </nav>
</header>

<main class="container">
  <?php _render_flash_once(); ?>
