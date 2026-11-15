<?php
require __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$count = fn(string $t): int => (int)$pdo->query("select count(*) from $t")->fetchColumn();
$stats = [
    ['list.php?m=farmer',   'Farmers',          $count('farmer'),   'bi-people'],
    ['list.php?m=farm',     'Farms',            $count('farm'),     'bi-geo-alt'],
    ['list.php?m=crop',     'Crops',            $count('crop'),     'bi-flower2'],
    ['list.php?m=planting', 'Planting records', $count('planting'), 'bi-tree'],
    ['list.php?m=harvest',  'Harvest records',  $count('harvest'),  'bi-basket'],
];
$tot = $pdo->query('select coalesce(sum(quantity_kg),0) kg, coalesce(sum(quantity_kg*selling_price_per_kg),0) val from harvest')->fetch();
$status = $pdo->query('select s.status_description d, count(p.planting_id) n from planting_status s left join planting p on p.status_id = s.status_id group by s.status_id, s.status_description order by s.status_id')->fetchAll();
$rp = $pdo->query('select p.planting_date, f.farm_name, c.crop_name, s.status_description from planting p join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id join planting_status s on s.status_id = p.status_id order by p.planting_date desc, p.planting_id desc limit 5')->fetchAll();
$rh = $pdo->query('select h.harvest_date, h.quantity_kg, f.farm_name, c.crop_name, g.grade_description from harvest h join planting p on p.planting_id = h.planting_id join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id join quality_grade g on g.quality_grade_id = h.quality_grade_id order by h.harvest_date desc, h.harvest_id desc limit 5')->fetchAll();

$page = 'dashboard'; $title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-0">Dashboard</h1><div class="text-muted small mb-3">Overview of farms, plantings and harvests.</div>

<div class="row g-3 mb-3">
<?php foreach ($stats as [$href, $label, $n, $icon]): ?>
  <div class="col-6 col-md-4 col-xl"><a class="stat" href="<?= $href ?>"><i class="bi <?= $icon ?>"></i><div><b><?= num($n) ?></b><span><?= $label ?></span></div></a></div>
<?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-4"><div class="hero">
    <div class="small text-white-50 mb-2">Total harvested</div>
    <div class="big"><?= num($tot['kg']) ?><small>kg</small></div>
    <div class="mt-4 small text-white-50">Estimated value</div><div class="h4 mb-0"><?= peso($tot['val']) ?></div>
    <div class="mt-3 small text-white-50">From <?= num($stats[4][2]) ?> harvest records</div>
  </div></div>
  <div class="col-lg-8"><div class="card p-3 h-100"><h2 class="h6">Plantings by status</h2>
    <?php if (array_sum(array_column($status, 'n')) > 0): ?>
      <div style="height:230px"><canvas id="ch"></canvas></div>
    <?php else: ?><div class="text-muted py-5 text-center">No planting records yet.</div><?php endif; ?>
  </div></div>
</div>

<div class="row g-3">
  <div class="col-xl-6"><div class="card"><div class="p-3 pb-0"><h2 class="h6">Recent plantings</h2></div>
    <div class="table-responsive"><table class="table mb-0 align-middle">
      <thead><tr><th>Farm</th><th>Crop</th><th>Planted</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($rp as $r): ?>
        <tr><td><?= esc($r['farm_name']) ?></td><td><?= esc($r['crop_name']) ?></td><td><?= esc($r['planting_date']) ?></td><td><?= badge($r['status_description']) ?></td></tr>
      <?php endforeach; if (!$rp): ?><tr><td colspan="4" class="text-center text-muted py-4">Nothing recorded yet.</td></tr><?php endif; ?>
      </tbody></table></div></div></div>
  <div class="col-xl-6"><div class="card"><div class="p-3 pb-0"><h2 class="h6">Recent harvests</h2></div>
    <div class="table-responsive"><table class="table mb-0 align-middle">
      <thead><tr><th>Farm</th><th>Crop</th><th>Date</th><th>Kg</th><th>Grade</th></tr></thead><tbody>
      <?php foreach ($rh as $r): ?>
        <tr><td><?= esc($r['farm_name']) ?></td><td><?= esc($r['crop_name']) ?></td><td><?= esc($r['harvest_date']) ?></td><td><?= num($r['quantity_kg']) ?></td><td><?= esc($r['grade_description']) ?></td></tr>
      <?php endforeach; if (!$rh): ?><tr><td colspan="5" class="text-center text-muted py-4">Nothing recorded yet.</td></tr><?php endif; ?>
      </tbody></table></div></div></div>
</div>
<?php
$labels = json_encode(array_column($status, 'd'));
$values = json_encode(array_map('intval', array_column($status, 'n')));
$extra_js = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const el = document.getElementById('ch');
if (el) new Chart(el, { type: 'doughnut',
  data: { labels: $labels, datasets: [{ data: $values, backgroundColor: ['#C98A1B', '#2E8B57', '#5B8DB8', '#8F6BB3'], borderWidth: 0 }] },
  options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right' } } } });
</script>
HTML;
include __DIR__ . '/includes/footer.php';
