<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
$_SESSION['flash'] = "Logged out.";
header("Location: /mondycinema/admin_login.php");
exit;
