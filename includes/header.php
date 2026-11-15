<?php
// Expects $page (active menu key) and $title before it is included.
$user = current_user();
$nav = [
    ['dashboard', 'dashboard.php',        'Dashboard', 'bi-speedometer2'],
    ['farmer',    'list.php?m=farmer',    'Farmers',   'bi-people'],
    ['farm',      'list.php?m=farm',      'Farms',     'bi-geo-alt'],
    ['crop',      'list.php?m=crop',      'Crops',     'bi-flower2'],
    ['planting',  'list.php?m=planting',  'Planting',  'bi-tree'],
    ['harvest',   'list.php?m=harvest',   'Harvest',   'bi-basket'],
    ['reports',   'reports.php',          'Reports',   'bi-bar-chart-line'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($title ?? 'AgriTrack') ?> | AgriTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<aside class="side" id="side">
  <div class="brand"><i class="bi bi-flower1"></i> AgriTrack</div>
  <nav>
    <?php foreach ($nav as [$key, $href, $label, $icon]): ?>
      <a href="<?= $href ?>" class="<?= ($page ?? '') === $key ? 'on' : '' ?>"><i class="bi <?= $icon ?>"></i><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</aside>
<div class="wrap">
  <header class="top">
    <button class="btn btn-light d-lg-none" id="burger" aria-label="Menu"><i class="bi bi-list"></i></button>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="small text-muted d-none d-sm-inline"><?= esc($user['email']) ?></span>
      <span class="badge role-<?= esc($user['role']) ?>"><?= is_admin() ? 'Administrator' : 'Staff' ?></span>
      <a class="btn btn-outline-secondary btn-sm" href="logout.php">Log out</a>
    </div>
  </header>
  <main class="p-3 p-lg-4">
  <?php if (!empty($_SESSION['flash'])): [$fm, $ft] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= esc($ft) ?> alert-dismissible fade show" role="alert">
      <?= esc($fm) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
