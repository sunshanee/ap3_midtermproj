<?php
// Shared helpers: database, session, login check, roles, messages.
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function esc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function peso($n): string { return '₱' . number_format((float)$n, 2); }
function num($n, int $d = 0): string { return number_format((float)$n, $d); }
function badge(string $s): string {
    $c = $s === 'Harvested' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis';
    return '<span class="badge ' . $c . '">' . esc($s) . '</span>';
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function flash(string $msg, string $type = 'success'): void { $_SESSION['flash'] = [$msg, $type]; }

function require_login(): void {
    if (!current_user()) { header('Location: login.php'); exit; }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) { flash('You do not have permission to do that.', 'danger'); header('Location: dashboard.php'); exit; }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('This form expired. Go back, refresh the page, and try again.');
    }
}
