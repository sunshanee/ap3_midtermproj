<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/modules.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
csrf_check();

$m = $_POST['m'] ?? '';
$cfg = module_or_404($m);
$id = (int)($_POST['id'] ?? 0);

try {
    $s = db()->prepare("delete from {$cfg['table']} where {$cfg['pk']} = :id");
    $s->execute([':id' => $id]);
    flash($s->rowCount() ? ucfirst($cfg['one']) . ' deleted.' : 'That record was already gone.', $s->rowCount() ? 'success' : 'warning');
} catch (PDOException $e) {
    flash(db_error_message($e, 'delete'), 'danger');
}
header("Location: list.php?m=$m");
exit;
