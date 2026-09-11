<?php

require_once 'db.php';
require_once 'helpers.php';
session_start();
if (empty($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }
include 'admin_header.php';
?>




<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('admin-dash');
  });
</script>

<section class="grid-2">
  <div class="card">
    <div class="card-body">
      <h2>Welcome, <?php echo sanitize($_SESSION['admin_username']); ?></h2>
      <p>Use the links below to manage the site.</p>
      <p><a class="btn" href="manage_movies.php">Manage Movies</a></p>
      <p><a class="btn" href="admin_bookings.php">Manage Bookings</a></p>
      <p><a class="btn" href="manage_users.php">Manage Users</a></p>
      <p><a class="btn" href="admin_payments.php">Payments</a></p>
      <p><a class="btn" href="admin_messages.php">Messages</a></p>
      <p><a class="btn" href="index.php" target="_blank">View Site</a></p>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Quick Stats</h3>
      <?php
        $totU = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totM = (int)$conn->query("SELECT COUNT(*) FROM movies")->fetchColumn();
        $totB = (int)$conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
      ?>
      <p><strong>Users:</strong> <?php echo $totU; ?></p>
      <p><strong>Movies:</strong> <?php echo $totM; ?></p>
      <p><strong>Bookings:</strong> <?php echo $totB; ?></p>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>