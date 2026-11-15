<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/modules.php';
require_login();
$pdo = db();

function q(PDO $pdo, string $sql, array $p = []): array { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function between(string $col, string $from, string $to, array &$p): string {
    $w = '';
    if ($from !== '') { $w .= " and $col >= :from"; $p[':from'] = $from; }
    if ($to !== '')   { $w .= " and $col <= :to";   $p[':to'] = $to; }
    return $w;
}
function sum_col(array $rows, int $i): float { return array_sum(array_map(fn($r) => (float)$r[$i], $rows)); }

$grade = ['label' => 'Quality grade', 'sql' => ref('quality_grade', 'quality_grade_id', 'grade_description')];
$harvestSql = "from harvest h join planting p on p.planting_id = h.planting_id join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id join quality_grade g on g.quality_grade_id = h.quality_grade_id join harvest_remark r on r.remark_id = h.remark_id where true";

$reports = [
    'farmers' => ['tab' => 'Farmers and farms', 'title' => 'Farmers and their farms', 'dates' => null,
        'filter' => ['label' => 'Address', 'sql' => 'select distinct address as id, address as label from farmer order by 1'],
        'run' => function ($pdo, $from, $to, $sel) {
            $p = []; $w = '';
            if ($sel !== '') { $w = 'where f.address = :sel'; $p[':sel'] = $sel; }
            $rows = q($pdo, "select f.farmer_id, f.first_name || ' ' || f.last_name as farmer, f.address, fm.farm_name, fm.location, fm.area_hectares, i.irrigation_description from farmer f left join farm fm on fm.farmer_id = f.farmer_id left join irrigation_type i on i.irrigation_type_id = fm.irrigation_type_id $w order by f.last_name, f.first_name, fm.farm_name", $p);
            $out = []; $ids = []; $farms = 0; $ha = 0;
            foreach ($rows as $x) {
                $ids[$x['farmer_id']] = 1;
                if ($x['farm_name'] !== null) { $farms++; $ha += $x['area_hectares']; }
                $out[] = [$x['farmer'], $x['address'], $x['farm_name'] ?? 'No farm yet', $x['location'], $x['area_hectares'], $x['irrigation_description']];
            }
            return ['head' => ['Farmer', 'Address', 'Farm', 'Location', 'Hectares', 'Irrigation'], 'types' => ['text', 'text', 'text', 'text', 'dec', 'text'],
                'rows' => $out, 'foot' => ['Total', count($ids) . ' farmers', $farms . ' farms', '', $ha, '']];
        }],
    'crops' => ['tab' => 'Crops per farm', 'title' => 'Crops planted per farm', 'dates' => 'Planting date',
        'filter' => ['label' => 'Farm', 'sql' => ref('farm', 'farm_id', 'farm_name')],
        'run' => function ($pdo, $from, $to, $sel) {
            $p = []; $w = between('p.planting_date', $from, $to, $p);
            if ($sel !== '') { $w .= ' and p.farm_id = :sel'; $p[':sel'] = (int)$sel; }
            $rows = q($pdo, "select f.farm_name, c.crop_name || ' (' || c.variety || ')' as crop, count(*) as n, sum(p.area_planted) as ha from planting p join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id where true $w group by f.farm_name, c.crop_name, c.variety order by f.farm_name, crop", $p);
            $out = array_map(fn($x) => [$x['farm_name'], $x['crop'], $x['n'], $x['ha']], $rows);
            return ['head' => ['Farm', 'Crop', 'Plantings', 'Area planted (ha)'], 'types' => ['text', 'text', 'int', 'dec'],
                'rows' => $out, 'foot' => ['Total', '', sum_col($out, 2), sum_col($out, 3)]];
        }],
    'status' => ['tab' => 'Plantings by status', 'title' => 'Planting records by status', 'dates' => 'Planting date',
        'filter' => ['label' => 'Status', 'sql' => ref('planting_status', 'status_id', 'status_description')],
        'run' => function ($pdo, $from, $to, $sel) {
            $p = []; $w = between('p.planting_date', $from, $to, $p);
            if ($sel !== '') { $w .= ' and p.status_id = :sel'; $p[':sel'] = (int)$sel; }
            $rows = q($pdo, "select s.status_description, f.farm_name, c.crop_name, p.planting_date, p.expected_harvest_date, p.area_planted from planting p join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id join planting_status s on s.status_id = p.status_id where true $w order by s.status_id, p.planting_date desc", $p);
            $out = []; $by = [];
            foreach ($rows as $x) { $by[$x['status_description']] = ($by[$x['status_description']] ?? 0) + 1; $out[] = array_values($x); }
            return ['cards' => array_map(null, array_keys($by), array_values($by)),
                'head' => ['Status', 'Farm', 'Crop', 'Planted', 'Expected harvest', 'Area (ha)'], 'types' => ['badge', 'text', 'text', 'text', 'text', 'dec'],
                'rows' => $out, 'foot' => ['Total', count($out) . ' records', '', '', '', sum_col($out, 5)]];
        }],
    'harvest' => ['tab' => 'Harvest records', 'title' => 'Harvest records', 'dates' => 'Harvest date', 'filter' => $grade,
        'run' => function ($pdo, $from, $to, $sel) use ($harvestSql) {
            $p = []; $w = between('h.harvest_date', $from, $to, $p);
            if ($sel !== '') { $w .= ' and h.quality_grade_id = :sel'; $p[':sel'] = (int)$sel; }
            $rows = q($pdo, "select h.harvest_date, f.farm_name, c.crop_name || ' (' || c.variety || ')' as crop, h.quantity_kg, h.selling_price_per_kg, h.quantity_kg * h.selling_price_per_kg as val, g.grade_description, r.remark_description $harvestSql $w order by h.harvest_date desc, h.harvest_id desc", $p);
            $out = array_map('array_values', $rows);
            return ['head' => ['Date', 'Farm', 'Crop', 'Quantity (kg)', 'Price/kg', 'Est. value', 'Grade', 'Remarks'], 'types' => ['text', 'text', 'text', 'dec', 'money', 'money', 'text', 'text'],
                'rows' => $out, 'foot' => ['Total', count($out) . ' records', '', sum_col($out, 3), '', sum_col($out, 5), '', '']];
        }],
    'totals' => ['tab' => 'Harvest totals and value', 'title' => 'Total harvested quantity and estimated value', 'dates' => 'Harvest date', 'filter' => $grade,
        'run' => function ($pdo, $from, $to, $sel) use ($harvestSql) {
            $p = []; $w = between('h.harvest_date', $from, $to, $p);
            if ($sel !== '') { $w .= ' and h.quality_grade_id = :sel'; $p[':sel'] = (int)$sel; }
            $rows = q($pdo, "select c.crop_name || ' (' || c.variety || ')' as crop, count(*) as n, sum(h.quantity_kg) as kg, sum(h.quantity_kg * h.selling_price_per_kg) as val $harvestSql $w group by c.crop_id, c.crop_name, c.variety order by val desc", $p);
            $out = array_map(fn($x) => [$x['crop'], $x['n'], $x['kg'], $x['kg'] > 0 ? $x['val'] / $x['kg'] : 0, $x['val']], $rows);
            $n = sum_col($out, 1); $kg = sum_col($out, 2); $val = sum_col($out, 4);
            return ['cards' => [['Total harvested', num($kg, 2) . ' kg'], ['Estimated value', peso($val)], ['Harvest records', num($n)]],
                'head' => ['Crop', 'Harvests', 'Quantity (kg)', 'Average price/kg', 'Estimated value'], 'types' => ['text', 'int', 'dec', 'money', 'money'],
                'rows' => $out, 'foot' => ['Total', $n, $kg, '', $val]];
        }],
];

$key = isset($reports[$_GET['r'] ?? '']) ? $_GET['r'] : 'farmers';
$rep = $reports[$key];
$valid = function (string $d): bool { if ($d === '') return true; $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; };
$from = trim($_GET['from'] ?? ''); $to = trim($_GET['to'] ?? ''); $sel = trim($_GET['sel'] ?? '');
if ($sel !== '' && !$rep['filter']) { $sel = ''; }
if ($key !== 'farmers' && $sel !== '' && !ctype_digit($sel)) { $sel = ''; }

$error = '';
if (!$rep['dates']) { $from = $to = ''; }
if (!$valid($from) || !$valid($to)) { $error = 'Enter valid dates.'; }
elseif ($from !== '' && $to !== '' && $from > $to) { $error = 'The start date must be on or before the end date.'; }
$res = $error ? null : $rep['run']($pdo, $from, $to, $sel);

// ---- CSV export (before any page output) ----
if ($res && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$key-report.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $res['head']);
    foreach ($res['rows'] as $r) { fputcsv($out, $r); }
    if ($res['rows']) { fputcsv($out, $res['foot']); }
    exit;
}

$filterOpts = $rep['filter'] ? $pdo->query($rep['filter']['sql'])->fetchAll() : [];
$right = fn($t) => in_array($t, ['int', 'dec', 'money'], true) ? 'text-end' : '';
$self = fn(array $o) => 'reports.php?' . http_build_query(array_merge($_GET, $o));

$page = 'reports'; $title = 'Reports';
include __DIR__ . '/includes/header.php';
?>
<div class="no-print">
  <h1 class="h3 mb-0">Reports</h1><div class="text-muted small mb-3">Choose a report, set the filters, then generate it.</div>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($reports as $k => $r): ?><a class="btn btn-sm btn-outline-primary <?= $k === $key ? 'active' : '' ?>" href="reports.php?r=<?= $k ?>"><?= esc($r['tab']) ?></a><?php endforeach; ?>
  </div>
  <form class="card p-3 mb-3" method="get">
    <input type="hidden" name="r" value="<?= esc($key) ?>">
    <div class="row g-2 align-items-end">
      <?php if ($rep['dates']): ?>
        <div class="col-6 col-md-3"><label class="form-label small mb-1" for="from"><?= esc($rep['dates']) ?> from</label><input type="date" id="from" name="from" value="<?= esc($from) ?>" class="form-control"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1" for="to"><?= esc($rep['dates']) ?> to</label><input type="date" id="to" name="to" value="<?= esc($to) ?>" class="form-control"></div>
      <?php endif; ?>
      <?php if ($rep['filter']): ?>
        <div class="col-md-3"><label class="form-label small mb-1" for="sel"><?= esc($rep['filter']['label']) ?></label>
          <select id="sel" name="sel" class="form-select"><option value="">All</option>
            <?php foreach ($filterOpts as $o): ?><option value="<?= esc($o['id']) ?>" <?= $sel === (string)$o['id'] ? 'selected' : '' ?>><?= esc($o['label']) ?></option><?php endforeach; ?>
          </select></div>
      <?php endif; ?>
      <div class="col-auto"><button class="btn btn-primary">Generate report</button></div>
      <div class="col-auto ms-md-auto">
        <a class="btn btn-outline-secondary <?= $res ? '' : 'disabled' ?>" href="<?= esc($self(['export' => 'csv'])) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </form>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= esc($error) ?></div>
<?php else: ?>
  <div class="mb-3"><h2 class="h5 mb-0"><?= esc($rep['title']) ?></h2>
    <div class="text-muted small"><?= ($from !== '' || $to !== '') ? 'Period: ' . esc($from ?: 'beginning') . ' to ' . esc($to ?: 'today') . '.' : 'All dates.' ?> Generated <?= esc(date('M j, Y g:i A')) ?>.</div></div>
  <?php if (!empty($res['cards'])): ?>
    <div class="row g-3 mb-3"><?php foreach ($res['cards'] as [$l, $v]): ?>
      <div class="col-6 col-md-4"><div class="card p-3"><div class="text-muted small"><?= esc($l) ?></div><div class="h4 mb-0"><?= esc($v) ?></div></div></div>
    <?php endforeach; ?></div>
  <?php endif; ?>
  <div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><?php foreach ($res['head'] as $i => $h): ?><th class="<?= $right($res['types'][$i]) ?>"><?= esc($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($res['rows'] as $r): ?>
      <tr><?php foreach ($r as $i => $v): ?><td class="<?= $right($res['types'][$i]) ?>"><?= fmt_cell($v, $res['types'][$i]) ?></td><?php endforeach; ?></tr>
    <?php endforeach; if (!$res['rows']): ?>
      <tr><td colspan="<?= count($res['head']) ?>" class="text-center text-muted py-5">No records match these filters.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($res['rows']): ?>
      <tfoot><tr class="fw-bold"><?php foreach ($res['foot'] as $i => $v): ?><td class="<?= $right($res['types'][$i]) ?>"><?= is_numeric($v) && $v !== '' && $res['types'][$i] === 'text' ? esc($v) : fmt_cell($v, $res['types'][$i] === 'badge' ? 'text' : $res['types'][$i]) ?></td><?php endforeach; ?></tr></tfoot>
    <?php endif; ?>
  </table></div></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php';
