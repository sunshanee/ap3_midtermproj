<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/modules.php';
require_login();

$m = $_GET['m'] ?? '';
$cfg = module_or_404($m);
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$opts = field_options($pdo, $cfg);

$row = [];
if ($editing) {
    $s = $pdo->prepare("select * from {$cfg['table']} where {$cfg['pk']} = :id");
    $s->execute([':id' => $id]);
    $row = $s->fetch();
    if (!$row) { flash('That record was not found.', 'danger'); header("Location: list.php?m=$m"); exit; }
} else {
    foreach ($cfg['fields'] as $f) { $row[$f['name']] = $f['default'] ?? ''; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$data, $errors] = validate_input($pdo, $cfg, $_POST, $opts);
    $row = array_merge($row, $_POST);
    if (!$errors) {
        try {
            $names = array_keys($data);
            if ($editing) {
                $set = implode(', ', array_map(fn($n) => "$n = :$n", $names));
                $sql = "update {$cfg['table']} set $set where {$cfg['pk']} = :_id";
                $data['_id'] = $id;
            } else {
                $sql = "insert into {$cfg['table']} (" . implode(', ', $names) . ') values (' . implode(', ', array_map(fn($n) => ":$n", $names)) . ')';
            }
            $pdo->prepare($sql)->execute($data);
            flash(ucfirst($cfg['one']) . ($editing ? ' updated' : ' added') . ' successfully.');
            header("Location: list.php?m=$m");
            exit;
        } catch (PDOException $e) {
            flash(db_error_message($e), 'danger');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$page = $m; $title = ($editing ? 'Edit ' : 'Add ') . $cfg['one'];
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3"><?= esc(ucfirst($title)) ?></h1>
<form method="post" class="card p-3 p-md-4" novalidate>
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <?php if ($errors): ?><div class="alert alert-danger">Please fix the highlighted fields.</div><?php endif; ?>
  <div class="row g-3">
  <?php foreach ($cfg['fields'] as $f):
      $n = $f['name']; $type = $f['type'] ?? 'text'; $val = $row[$n] ?? ''; $bad = isset($errors[$n]) ? 'is-invalid' : ''; ?>
    <div class="col-md-6">
      <label class="form-label" for="<?= $n ?>"><?= esc($f['label']) ?><?= !empty($f['required']) ? ' <span class="text-danger">*</span>' : '' ?></label>
      <?php if ($type === 'select'): ?>
        <select class="form-select <?= $bad ?>" id="<?= $n ?>" name="<?= $n ?>">
          <option value="">Select...</option>
          <?php foreach ($opts[$n] as $o): ?><option value="<?= esc($o['id']) ?>" <?= (string)$val === (string)$o['id'] ? 'selected' : '' ?>><?= esc($o['label']) ?></option><?php endforeach; ?>
        </select>
      <?php else: ?>
        <input class="form-control <?= $bad ?>" id="<?= $n ?>" name="<?= $n ?>" type="<?= $type ?>" value="<?= esc($val) ?>"<?= isset($f['step']) ? ' step="' . $f['step'] . '"' : '' ?>>
      <?php endif; ?>
      <?php if ($bad): ?><div class="invalid-feedback"><?= esc($errors[$n]) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Add ' . esc($cfg['one']) ?></button>
    <a class="btn btn-light" href="list.php?m=<?= esc($m) ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/includes/footer.php';
