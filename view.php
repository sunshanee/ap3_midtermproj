<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/modules.php';
require_login();

$m = $_GET['m'] ?? '';
$cfg = module_or_404($m);
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("select * from {$cfg['table']} where {$cfg['pk']} = :id");
$s->execute([':id' => $id]);
$row = $s->fetch();
if (!$row) { flash('That record was not found.', 'danger'); header("Location: list.php?m=$m"); exit; }
$opts = field_options($pdo, $cfg);

$page = $m; $title = ucfirst($cfg['one']) . ' details';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3"><?= esc(ucfirst($cfg['one'])) ?> details</h1>
<div class="card p-3 p-md-4">
  <dl class="row mb-0">
    <?php foreach ($cfg['fields'] as $f):
        $n = $f['name']; $v = $row[$n];
        if (($f['type'] ?? '') === 'select') {
            foreach ($opts[$n] as $o) { if ((string)$o['id'] === (string)$v) { $v = $o['label']; break; } }
        } ?>
      <dt class="col-sm-4"><?= esc($f['label']) ?></dt><dd class="col-sm-8"><?= esc($v) ?></dd>
    <?php endforeach; ?>
    <dt class="col-sm-4">Added</dt><dd class="col-sm-8"><?= esc(date('M j, Y g:i A', strtotime($row['created_at']))) ?></dd>
    <dt class="col-sm-4">Last updated</dt><dd class="col-sm-8"><?= esc(date('M j, Y g:i A', strtotime($row['updated_at']))) ?></dd>
  </dl>
  <div class="mt-4 d-flex gap-2">
    <a class="btn btn-primary" href="form.php?m=<?= esc($m) ?>&id=<?= $id ?>">Edit</a>
    <a class="btn btn-light" href="list.php?m=<?= esc($m) ?>">Back to list</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php';
