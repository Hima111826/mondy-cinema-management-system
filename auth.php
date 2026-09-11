<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/helpers.php';


if (!isLoggedIn()) {
    $_SESSION['flash'] = "⚠️ Please login first to continue.";
    redirect("login.php");
}
