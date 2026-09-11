<?php


function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


function sanitize($data) {
    if (is_array($data)) {
       
        return htmlspecialchars(implode(', ', $data), ENT_QUOTES, 'UTF-8');
    }
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}


function isLoggedIn(): bool
{
    ensureSessionStarted();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    ensureSessionStarted();
    return !empty($_SESSION['admin_id']);
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function formatDateTime(?string $datetime, string $format = 'D, d M Y h:i A'): string
{
    if (!$datetime) return '-';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Throwable $e) {
        return '-';
    }
}

function requireLogin(): void
{
    ensureSessionStarted();
    if (!isLoggedIn()) {
        setFlash("⚠️ Please login first to continue.", 'error');
        redirect("login.php");
    }
}

function requireAdmin(): void
{
    ensureSessionStarted();
    if (!isAdmin()) {
        setFlash("⚠️ Admin login required.", 'error');
        redirect("admin_login.php");
    }
}



function setFlash(string $message, string $type = 'info'): void
{
    ensureSessionStarted();
    $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
}


function flashMessage(): void
{
    ensureSessionStarted();
    if (empty($_SESSION['flash'])) return;

    $f = $_SESSION['flash'];
    
    $msg  = is_array($f) ? ($f['msg'] ?? '') : (string)$f;
    $type = is_array($f) ? ($f['type'] ?? 'info') : 'info';

   
    $class = 'alert';
    if ($type === 'error')   $class .= ' error';
    if ($type === 'success') $class .= ' success';
    if ($type === 'warning') $class .= ' warning';

    echo '<div class="'. $class .'">'. sanitize($msg) .'</div>';

    unset($_SESSION['flash']);
}
