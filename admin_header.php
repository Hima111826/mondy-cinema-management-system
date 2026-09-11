<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MondyCinema — Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  
  <link rel="icon" href="/mondycinema/assets/img/favicon.png" type="image/png">

  
  <link rel="stylesheet" href="style.css">
  
</head>
<body>
<header class="mc-header admin-bar">
  <nav class="mc-nav container" style="gap:1rem">
    <a class="mc-logo" href="/mondycinema/admin_dashboard.php">🛠️ Admin</a>
  

    <div class="admin-right">
      <span class="admin-badge">
        <?php if (!empty($_SESSION['admin_username'])): ?>
          <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
        <?php endif; ?>
      </span>
      <a class="btn ghost" href="/mondycinema/index.php" title="Open public site">View Site</a>
      <a class="btn" href="/mondycinema/admin_logout.php">Logout</a>
    </div>
  </nav>
</header>

<main class="container">
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="mc-flash">
      
    </div>
  <?php endif; ?>
