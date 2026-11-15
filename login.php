<?php
require __DIR__ . '/includes/auth.php';
if (current_user()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email === '' || $pass === '') {
        $error = 'Enter your email and password.';
    } else {
        try {
            $st = db()->prepare('select user_id, full_name, email, password_hash, role from app_users where lower(email) = lower(:e)');
            $st->execute([':e' => $email]);
            $u = $st->fetch();
            if ($u && password_verify($pass, $u['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = ['id' => $u['user_id'], 'name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role']];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Wrong email or password. Check them and try again.';
        } catch (Throwable $e) {
            $error = 'Could not log in right now. Try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in | AgriTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="row g-0">
  <div class="col-lg-6"><div class="auth-art">
    <div class="brand px-0"><i class="bi bi-flower1"></i> AgriTrack</div>
    <h1>Every farm, planting and harvest in one place.</h1>
    <p class="mt-3 mb-0 text-white-50">Agriculture Information System for Cebu farmers.</p>
  </div></div>
  <div class="col-lg-6 d-flex align-items-center justify-content-center p-4" style="min-height:70vh">
    <form method="post" class="w-100" style="max-width:380px" novalidate>
      <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
      <h2 class="mb-1">Log in</h2>
      <p class="text-muted mb-4">Use the account given to you by an administrator.</p>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= esc($error) ?></div><?php endif; ?>
      <label class="form-label" for="em">Email</label>
      <input id="em" name="email" type="email" class="form-control mb-3" autocomplete="username" value="<?= esc($_POST['email'] ?? '') ?>" required>
      <label class="form-label" for="pw">Password</label>
      <input id="pw" name="password" type="password" class="form-control mb-4" autocomplete="current-password" required>
      <button class="btn btn-primary w-100 py-2">Log in</button>
    </form>
  </div>
</div>
</body>
</html>
