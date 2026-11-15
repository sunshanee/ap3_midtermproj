<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/modules.php';
require_login();

$m = $_GET['m'] ?? '';
$cfg = module_or_404($m);
$pdo = db();

// ---- build the query: search, filters, sorting, pagination ----
$selects = ['t.' . $cfg['pk'] . ' as _id'];
foreach ($cfg['cols'] as $i => $c) { $selects[] = $c['sql'] . " as c$i"; }

$where = []; $params = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $parts = [];
    foreach ($cfg['search'] as $i => $expr) { $parts[] = "($expr)::text ilike :q$i"; $params[":q$i"] = "%$q%"; }
    $where[] = '(' . implode(' or ', $parts) . ')';
}
$filterOpts = [];
foreach ($cfg['filters'] as $i => $fl) {
    $filterOpts[$i] = $pdo->query($fl['sql'])->fetchAll();
    $v = trim($_GET["f$i"] ?? '');
    if ($v !== '' && (empty($fl['int']) || ctype_digit($v))) { $where[] = $fl['col'] . " = :f$i"; $params[":f$i"] = $v; }
}
$w = $where ? 'where ' . implode(' and ', $where) : '';

$sort = isset($_GET['sort']) && ctype_digit($_GET['sort']) && (int)$_GET['sort'] < count($cfg['cols']) ? (int)$_GET['sort'] : null;
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$order = $sort !== null ? "order by c$sort $dir, _id" : 'order by _id asc';

$per = 10;
$cnt = $pdo->prepare("select count(*) from {$cfg['from']} $w");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$pg = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$st = $pdo->prepare('select ' . implode(', ', $selects) . " from {$cfg['from']} $w $order limit $per offset " . (($pg - 1) * $per));
$st->execute($params);
$rows = $st->fetchAll();

$url = fn(array $over) => 'list.php?' . http_build_query(array_merge($_GET, ['m' => $m], $over));

$page = $m; $title = $cfg['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div><h1 class="h3 mb-0"><?= esc($cfg['title']) ?></h1><div class="text-muted small"><?= esc($cfg['sub']) ?></div></div>
  <a class="btn btn-primary ms-auto" href="form.php?m=<?= esc($m) ?>"><i class="bi bi-plus-lg me-1"></i>Add <?= esc($cfg['one']) ?></a>
</div>

<form class="card p-3 mb-3" method="get">
  <input type="hidden" name="m" value="<?= esc($m) ?>">
  <div class="row g-2">
    <div class="col-md-4"><input name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Search <?= esc(strtolower($cfg['title'])) ?>" aria-label="Search"></div>
    <?php foreach ($cfg['filters'] as $i => $fl): ?>
      <div class="col-6 col-md-3"><select name="f<?= $i ?>" class="form-select" aria-label="Filter by <?= esc($fl['label']) ?>">
        <option value="">All <?= esc($fl['label']) ?></option>
        <?php foreach ($filterOpts[$i] as $o): ?><option value="<?= esc($o['id']) ?>" <?= ((string)($_GET["f$i"] ?? '') === (string)$o['id']) ? 'selected' : '' ?>><?= esc($o['label']) ?></option><?php endforeach; ?>
      </select></div>
    <?php endforeach; ?>
    <div class="col-auto"><button class="btn btn-primary">Search</button> <a class="btn btn-light" href="list.php?m=<?= esc($m) ?>">Reset</a></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr>
      <?php foreach ($cfg['cols'] as $i => $c):
          $nextDir = ($sort === $i && $dir === 'asc') ? 'desc' : 'asc'; ?>
        <th><a class="text-decoration-none text-reset" href="<?= esc($url(['sort' => $i, 'dir' => $nextDir, 'page' => 1])) ?>"><?= esc($c['h']) ?><?php if ($sort === $i): ?><i class="bi bi-caret-<?= $dir === 'asc' ? 'up' : 'down' ?>-fill ms-1"></i><?php endif; ?></a></th>
      <?php endforeach; ?>
      <th class="text-end">Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <?php foreach ($cfg['cols'] as $i => $c): ?><td><?= fmt_cell($r["c$i"], $c['type'] ?? 'text') ?></td><?php endforeach; ?>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" title="View" href="view.php?m=<?= esc($m) ?>&id=<?= (int)$r['_id'] ?>"><i class="bi bi-eye"></i></a>
          <a class="btn btn-sm btn-outline-primary" title="Edit" href="form.php?m=<?= esc($m) ?>&id=<?= (int)$r['_id'] ?>"><i class="bi bi-pencil"></i></a>
          <?php if (is_admin()): ?><button type="button" class="btn btn-sm btn-outline-danger js-del" title="Delete" data-id="<?= (int)$r['_id'] ?>" data-name="<?= esc($cfg['label']($r)) ?>"><i class="bi bi-trash"></i></button><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if (!$rows): ?>
      <tr><td colspan="<?= count($cfg['cols']) + 1 ?>" class="text-center text-muted py-5">No records found. Change your search or filters, or add a new <?= esc($cfg['one']) ?>.</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top">
    <span class="text-muted small"><?= $total ? 'Showing ' . (($pg - 1) * $per + 1) . ' to ' . (($pg - 1) * $per + count($rows)) . ' of ' . $total : '0 records' ?></span>
    <div class="btn-group">
      <a class="btn btn-sm btn-outline-secondary <?= $pg <= 1 ? 'disabled' : '' ?>" href="<?= esc($url(['page' => $pg - 1])) ?>">Previous</a>
      <span class="btn btn-sm btn-light disabled">Page <?= $pg ?> of <?= $pages ?></span>
      <a class="btn btn-sm btn-outline-secondary <?= $pg >= $pages ? 'disabled' : '' ?>" href="<?= esc($url(['page' => $pg + 1])) ?>">Next</a>
    </div>
  </div>
</div>

<?php if (is_admin()): ?>
<div class="modal fade" id="delModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="delete.php">
  <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
  <input type="hidden" name="m" value="<?= esc($m) ?>">
  <input type="hidden" name="id" id="delId">
  <div class="modal-header"><h5 class="modal-title">Delete <?= esc($cfg['one']) ?>?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"><b id="delName"></b> will be permanently deleted. This cannot be undone.</div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Delete</button></div>
</form></div></div>
<?php
$extra_js = <<<'HTML'
<script>
document.querySelectorAll('.js-del').forEach(b => b.onclick = () => {
  document.getElementById('delId').value = b.dataset.id;
  document.getElementById('delName').textContent = b.dataset.name;
  new bootstrap.Modal(document.getElementById('delModal')).show();
});
</script>
HTML;
endif;
include __DIR__ . '/includes/footer.php';
