<?php
// One definition per module. list.php, form.php, view.php and delete.php
// read these definitions, so every module behaves the same way.

function ref(string $table, string $id, string $label): string {
    return "select $id as id, $label as label from $table order by label";
}

function modules(): array {
    $today = date('Y-m-d');
    $fullName = "t.first_name || ' ' || t.last_name";

    return [
    'farmer' => [
        'title' => 'Farmers', 'one' => 'farmer', 'sub' => 'People who own or manage farms.',
        'table' => 'farmer', 'pk' => 'farmer_id',
        'from' => 'farmer t',
        'cols' => [
            ['h' => 'ID', 'sql' => 't.farmer_id'],
            ['h' => 'Name', 'sql' => $fullName],
            ['h' => 'Contact', 'sql' => 't.contact_number'],
            ['h' => 'Address', 'sql' => 't.address'],
            ['h' => 'Registered', 'sql' => 't.date_registered'],
        ],
        'search' => [$fullName, 't.contact_number', 't.address'],
        'filters' => [
            ['label' => 'addresses', 'col' => 't.address', 'sql' => 'select distinct address as id, address as label from farmer order by 1'],
        ],
        'fields' => [
            ['name' => 'first_name', 'label' => 'First name', 'required' => true, 'max' => 50],
            ['name' => 'last_name', 'label' => 'Last name', 'required' => true, 'max' => 50],
            ['name' => 'contact_number', 'label' => 'Contact number', 'required' => true, 'max' => 20,
             'pattern' => '/^[0-9+() -]{7,20}$/', 'msg' => 'Use 7 to 20 characters: numbers, +, -, spaces.'],
            ['name' => 'address', 'label' => 'Address', 'required' => true, 'max' => 150],
            ['name' => 'date_registered', 'label' => 'Date registered', 'type' => 'date', 'required' => true, 'default' => $today],
        ],
        'label' => fn($r) => $r['c1'],
        'check' => function (array $d, PDO $pdo): ?array {
            return $d['date_registered'] > date('Y-m-d') ? ['date_registered', 'Date cannot be in the future.'] : null;
        },
    ],

    'farm' => [
        'title' => 'Farms', 'one' => 'farm', 'sub' => 'Farms and their owners.',
        'table' => 'farm', 'pk' => 'farm_id',
        'from' => 'farm t join farmer fr on fr.farmer_id = t.farmer_id join soil_type s on s.soil_type_id = t.soil_type_id join irrigation_type i on i.irrigation_type_id = t.irrigation_type_id',
        'cols' => [
            ['h' => 'ID', 'sql' => 't.farm_id'],
            ['h' => 'Farm', 'sql' => 't.farm_name'],
            ['h' => 'Owner', 'sql' => "fr.first_name || ' ' || fr.last_name"],
            ['h' => 'Location', 'sql' => 't.location'],
            ['h' => 'Hectares', 'sql' => 't.area_hectares', 'type' => 'dec'],
            ['h' => 'Soil', 'sql' => 's.soil_description'],
            ['h' => 'Irrigation', 'sql' => 'i.irrigation_description'],
        ],
        'search' => ['t.farm_name', "fr.first_name || ' ' || fr.last_name", 't.location'],
        'filters' => [
            ['label' => 'irrigation types', 'col' => 't.irrigation_type_id', 'int' => true, 'sql' => ref('irrigation_type', 'irrigation_type_id', 'irrigation_description')],
            ['label' => 'soil types', 'col' => 't.soil_type_id', 'int' => true, 'sql' => ref('soil_type', 'soil_type_id', 'soil_description')],
        ],
        'fields' => [
            ['name' => 'farmer_id', 'label' => 'Owner', 'type' => 'select', 'required' => true, 'ref' => ref('farmer', 'farmer_id', "first_name || ' ' || last_name")],
            ['name' => 'farm_name', 'label' => 'Farm name', 'required' => true, 'max' => 100],
            ['name' => 'location', 'label' => 'Location', 'required' => true, 'max' => 150],
            ['name' => 'area_hectares', 'label' => 'Area (hectares)', 'type' => 'number', 'step' => '0.01', 'min' => 0.01, 'max' => 99999, 'required' => true],
            ['name' => 'soil_type_id', 'label' => 'Soil type', 'type' => 'select', 'required' => true, 'ref' => ref('soil_type', 'soil_type_id', 'soil_description')],
            ['name' => 'irrigation_type_id', 'label' => 'Irrigation type', 'type' => 'select', 'required' => true, 'ref' => ref('irrigation_type', 'irrigation_type_id', 'irrigation_description')],
        ],
        'label' => fn($r) => $r['c1'],
    ],

    'crop' => [
        'title' => 'Crops', 'one' => 'crop', 'sub' => 'Crop varieties and growing information.',
        'table' => 'crop', 'pk' => 'crop_id',
        'from' => 'crop t join crop_category cc on cc.category_id = t.category_id join season se on se.season_id = t.season_id',
        'cols' => [
            ['h' => 'ID', 'sql' => 't.crop_id'],
            ['h' => 'Crop', 'sql' => 't.crop_name'],
            ['h' => 'Variety', 'sql' => 't.variety'],
            ['h' => 'Category', 'sql' => 'cc.category_description'],
            ['h' => 'Growing days', 'sql' => 't.growing_period_days'],
            ['h' => 'Season', 'sql' => 'se.season_description'],
        ],
        'search' => ['t.crop_name', 't.variety'],
        'filters' => [
            ['label' => 'categories', 'col' => 't.category_id', 'int' => true, 'sql' => ref('crop_category', 'category_id', 'category_description')],
            ['label' => 'seasons', 'col' => 't.season_id', 'int' => true, 'sql' => ref('season', 'season_id', 'season_description')],
        ],
        'fields' => [
            ['name' => 'crop_name', 'label' => 'Crop name', 'required' => true, 'max' => 80],
            ['name' => 'variety', 'label' => 'Variety', 'required' => true, 'max' => 80],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'required' => true, 'ref' => ref('crop_category', 'category_id', 'category_description')],
            ['name' => 'growing_period_days', 'label' => 'Growing period (days)', 'type' => 'number', 'step' => '1', 'int' => true, 'min' => 1, 'max' => 1000, 'required' => true],
            ['name' => 'season_id', 'label' => 'Recommended season', 'type' => 'select', 'required' => true, 'ref' => ref('season', 'season_id', 'season_description')],
        ],
        'label' => fn($r) => $r['c1'] . ' (' . $r['c2'] . ')',
    ],

    'planting' => [
        'title' => 'Planting', 'one' => 'planting record', 'sub' => 'What was planted, where, and when.',
        'table' => 'planting', 'pk' => 'planting_id',
        'from' => 'planting t join farm f on f.farm_id = t.farm_id join crop c on c.crop_id = t.crop_id join planting_status ps on ps.status_id = t.status_id',
        'cols' => [
            ['h' => 'ID', 'sql' => 't.planting_id'],
            ['h' => 'Farm', 'sql' => 'f.farm_name'],
            ['h' => 'Crop', 'sql' => 'c.crop_name'],
            ['h' => 'Planted', 'sql' => 't.planting_date'],
            ['h' => 'Area (ha)', 'sql' => 't.area_planted', 'type' => 'dec'],
            ['h' => 'Seed qty', 'sql' => 't.seed_quantity', 'type' => 'dec'],
            ['h' => 'Expected harvest', 'sql' => 't.expected_harvest_date'],
            ['h' => 'Status', 'sql' => 'ps.status_description', 'type' => 'badge'],
        ],
        'search' => ['f.farm_name', 'c.crop_name', 'c.variety'],
        'filters' => [
            ['label' => 'statuses', 'col' => 't.status_id', 'int' => true, 'sql' => ref('planting_status', 'status_id', 'status_description')],
            ['label' => 'crops', 'col' => 't.crop_id', 'int' => true, 'sql' => ref('crop', 'crop_id', "crop_name || ' (' || variety || ')'")],
        ],
        'fields' => [
            ['name' => 'farm_id', 'label' => 'Farm', 'type' => 'select', 'required' => true, 'ref' => ref('farm', 'farm_id', 'farm_name')],
            ['name' => 'crop_id', 'label' => 'Crop', 'type' => 'select', 'required' => true, 'ref' => ref('crop', 'crop_id', "crop_name || ' (' || variety || ')'")],
            ['name' => 'planting_date', 'label' => 'Planting date', 'type' => 'date', 'required' => true, 'default' => $today],
            ['name' => 'expected_harvest_date', 'label' => 'Expected harvest date', 'type' => 'date', 'required' => true],
            ['name' => 'area_planted', 'label' => 'Area planted (ha)', 'type' => 'number', 'step' => '0.01', 'min' => 0.01, 'max' => 99999, 'required' => true],
            ['name' => 'seed_quantity', 'label' => 'Seed quantity', 'type' => 'number', 'step' => '0.01', 'min' => 0.01, 'max' => 99999, 'required' => true],
            ['name' => 'status_id', 'label' => 'Status', 'type' => 'select', 'required' => true, 'ref' => ref('planting_status', 'status_id', 'status_description')],
        ],
        'label' => fn($r) => 'Planting #' . $r['c0'],
        'check' => function (array $d, PDO $pdo): ?array {
            if ($d['expected_harvest_date'] < $d['planting_date']) {
                return ['expected_harvest_date', 'Must be on or after the planting date.'];
            }
            $s = $pdo->prepare('select area_hectares from farm where farm_id = :i');
            $s->execute([':i' => $d['farm_id']]);
            $area = $s->fetchColumn();
            if ($area !== false && $d['area_planted'] > $area) {
                return ['area_planted', "Cannot be more than the farm area ($area ha)."];
            }
            return null;
        },
    ],

    'harvest' => [
        'title' => 'Harvest', 'one' => 'harvest record', 'sub' => 'Harvested quantity, quality and selling price.',
        'table' => 'harvest', 'pk' => 'harvest_id',
        'from' => 'harvest t join planting p on p.planting_id = t.planting_id join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id join quality_grade g on g.quality_grade_id = t.quality_grade_id join harvest_remark r on r.remark_id = t.remark_id',
        'cols' => [
            ['h' => 'ID', 'sql' => 't.harvest_id'],
            ['h' => 'Planting', 'sql' => "'#' || t.planting_id || ' ' || f.farm_name || ' - ' || c.crop_name"],
            ['h' => 'Date', 'sql' => 't.harvest_date'],
            ['h' => 'Quantity (kg)', 'sql' => 't.quantity_kg', 'type' => 'dec'],
            ['h' => 'Price/kg', 'sql' => 't.selling_price_per_kg', 'type' => 'money'],
            ['h' => 'Est. value', 'sql' => 't.quantity_kg * t.selling_price_per_kg', 'type' => 'money'],
            ['h' => 'Grade', 'sql' => 'g.grade_description'],
            ['h' => 'Remarks', 'sql' => 'r.remark_description'],
        ],
        'search' => ['f.farm_name', 'c.crop_name', 'r.remark_description', 'g.grade_description'],
        'filters' => [
            ['label' => 'grades', 'col' => 't.quality_grade_id', 'int' => true, 'sql' => ref('quality_grade', 'quality_grade_id', 'grade_description')],
            ['label' => 'remarks', 'col' => 't.remark_id', 'int' => true, 'sql' => ref('harvest_remark', 'remark_id', 'remark_description')],
        ],
        'fields' => [
            ['name' => 'planting_id', 'label' => 'Planting record', 'type' => 'select', 'required' => true,
             'ref' => "select p.planting_id as id, '#' || p.planting_id || ' ' || f.farm_name || ' - ' || c.crop_name as label from planting p join farm f on f.farm_id = p.farm_id join crop c on c.crop_id = p.crop_id order by p.planting_id"],
            ['name' => 'harvest_date', 'label' => 'Harvest date', 'type' => 'date', 'required' => true, 'default' => $today],
            ['name' => 'quantity_kg', 'label' => 'Quantity (kg)', 'type' => 'number', 'step' => '0.01', 'min' => 0.01, 'max' => 99999999, 'required' => true],
            ['name' => 'selling_price_per_kg', 'label' => 'Selling price per kg', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'max' => 99999, 'required' => true],
            ['name' => 'quality_grade_id', 'label' => 'Quality grade', 'type' => 'select', 'required' => true, 'ref' => ref('quality_grade', 'quality_grade_id', 'grade_description')],
            ['name' => 'remark_id', 'label' => 'Remarks', 'type' => 'select', 'required' => true, 'ref' => ref('harvest_remark', 'remark_id', 'remark_description')],
        ],
        'label' => fn($r) => 'Harvest #' . $r['c0'],
        'check' => function (array $d, PDO $pdo): ?array {
            $s = $pdo->prepare('select planting_date from planting where planting_id = :i');
            $s->execute([':i' => $d['planting_id']]);
            $pd = $s->fetchColumn();
            if ($pd !== false && $d['harvest_date'] < $pd) {
                return ['harvest_date', "Cannot be before the planting date ($pd)."];
            }
            return null;
        },
    ],
    ];
}

function module_or_404(string $m): array {
    $all = modules();
    if (!isset($all[$m])) {
        http_response_code(404);
        exit('Page not found.');
    }
    return $all[$m];
}

function field_options(PDO $pdo, array $cfg): array {
    $opts = [];
    foreach ($cfg['fields'] as $f) {
        if (($f['type'] ?? '') === 'select') {
            $opts[$f['name']] = $pdo->query($f['ref'])->fetchAll();
        }
    }
    return $opts;
}

function fmt_cell($v, string $type = 'text'): string {
    if ($v === null || $v === '') { return ''; }
    return match ($type) {
        'int'   => esc(num($v)),
        'dec'   => esc(num($v, 2)),
        'money' => esc(peso($v)),
        'badge' => badge((string)$v),
        default => esc($v),
    };
}

// Checks every field. Returns [clean values, errors by field name].
function validate_input(PDO $pdo, array $cfg, array $post, array $opts): array {
    $data = []; $err = [];
    foreach ($cfg['fields'] as $f) {
        $n = $f['name'];
        $type = $f['type'] ?? 'text';
        $v = trim((string)($post[$n] ?? ''));
        if ($v === '') {
            if (!empty($f['required'])) { $err[$n] = $f['label'] . ' is required.'; }
            $data[$n] = null;
            continue;
        }
        if ($type === 'number') {
            if (!is_numeric($v)) { $err[$n] = 'Enter a valid number.'; continue; }
            $x = $v + 0;
            if (!empty($f['int']) && floor($x) != $x) { $err[$n] = 'Enter a whole number.'; continue; }
            if (isset($f['min']) && $x < $f['min']) { $err[$n] = 'Must be at least ' . $f['min'] . '.'; continue; }
            if (isset($f['max']) && $x > $f['max']) { $err[$n] = 'Must be ' . $f['max'] . ' or less.'; continue; }
            $data[$n] = $x;
        } elseif ($type === 'date') {
            $d = DateTime::createFromFormat('Y-m-d', $v);
            if (!$d || $d->format('Y-m-d') !== $v) { $err[$n] = 'Enter a valid date.'; continue; }
            $data[$n] = $v;
        } elseif ($type === 'select') {
            $ids = array_map('strval', array_column($opts[$n], 'id'));
            if (!in_array($v, $ids, true)) { $err[$n] = 'Choose a valid option from the list.'; continue; }
            $data[$n] = (int)$v;
        } else {
            if (isset($f['max']) && (function_exists('mb_strlen') ? mb_strlen($v) : strlen($v)) > $f['max']) { $err[$n] = 'Use ' . $f['max'] . ' characters or fewer.'; continue; }
            if (!empty($f['pattern']) && !preg_match($f['pattern'], $v)) { $err[$n] = $f['msg']; continue; }
            $data[$n] = $v;
        }
    }
    if (!$err && isset($cfg['check'])) {
        $e = $cfg['check']($data, $pdo);
        if ($e) { $err[$e[0]] = $e[1]; }
    }
    return [$data, $err];
}

// Friendly message for database errors. Never shows technical details.
function db_error_message(PDOException $e, string $op = 'save'): string {
    return match ($e->getCode()) {
        '23505' => 'That value already exists. Use a different one.',
        '23503' => $op === 'delete'
            ? 'Cannot delete: other records depend on this one. Delete or change those first.'
            : 'The selected related record does not exist.',
        '23514' => 'A number is out of range or dates are not in order.',
        default => 'Something went wrong. Please check your entries and try again.',
    };
}
