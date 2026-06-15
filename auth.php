<?php
require_once __DIR__ . '/auth.php';
require_login();

/* ============================================================
   REPORT SYSTEM
   Tab 1: Asset report — grouped by Category / Department / Place
          Filters: category, department, place, added date range
   Tab 2: Warranty expiry report
          Filters: department, expiry date range / expiring within
   Both: CSV export + print, with the same filters applied.
   ============================================================ */

$tab = ($_GET['tab'] ?? 'assets') === 'warranty' ? 'warranty' : 'assets';

/* ---------- Read filters ---------- */
$f_category   = trim($_GET['category']   ?? '');
$f_department = trim($_GET['department'] ?? '');
$f_place      = trim($_GET['place']      ?? '');
$f_from       = trim($_GET['from']       ?? '');   // date range start
$f_to         = trim($_GET['to']         ?? '');   // date range end
$f_group      = in_array($_GET['group'] ?? '', ['category','department','place']) ? $_GET['group'] : 'department';

/* Validate dates (YYYY-MM-DD) */
$valid_date = fn($d) => $d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
if (!$valid_date($f_from)) $f_from = '';
if (!$valid_date($f_to))   $f_to   = '';

/* ---------- Build WHERE with prepared params ---------- */
$where  = [];
$params = [];
$types  = '';

if ($f_category   !== '') { $where[] = "category = ?";   $params[] = $f_category;   $types .= 's'; }
if ($f_department !== '') { $where[] = "department = ?"; $params[] = $f_department; $types .= 's'; }
if ($f_place      !== '') { $where[] = "place = ?";      $params[] = $f_place;      $types .= 's'; }

if ($tab === 'assets') {
    if ($f_from !== '') { $where[] = "DATE(created_at) >= ?"; $params[] = $f_from; $types .= 's'; }
    if ($f_to   !== '') { $where[] = "DATE(created_at) <= ?"; $params[] = $f_to;   $types .= 's'; }
} else { // warranty tab filters on warranty_end
    $where[] = "warranty_end IS NOT NULL";
    if ($f_from !== '') { $where[] = "warranty_end >= ?"; $params[] = $f_from; $types .= 's'; }
    if ($f_to   !== '') { $where[] = "warranty_end <= ?"; $params[] = $f_to;   $types .= 's'; }
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------- Query ---------- */
if ($tab === 'assets') {
    $order_col = $f_group; // category|department|place — whitelisted above
    $sql = "SELECT product_name, category, department, place, quantity, DATE(created_at) AS added
            FROM assets $where_sql
            ORDER BY $order_col ASC, product_name ASC";
} else {
    $sql = "SELECT product_name, category, department, place, quantity, warranty_start, warranty_end
            FROM assets $where_sql
            ORDER BY warranty_end ASC";
}

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* ---------- Warranty status helper ---------- */
function warranty_status($end) {
    $today  = new DateTime('today');
    $expiry = new DateTime($end);
    if ($expiry < $today) {
        return ['Expired', 'background:#fee2e2;color:#b91c1c;', null];
    }
    $days = (int)$today->diff($expiry)->days;
    if ($days <= 30)  return ["{$days} days left", 'background:#fee2e2;color:#b91c1c;', $days];
    if ($days <= 90)  return ["{$days} days left", 'background:#fef9c3;color:#854d0e;', $days];
    return ["{$days} days left", 'background:#dcfce7;color:#15803d;', $days];
}

/* ---------- CSV EXPORT (same filters) ---------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $fname = $tab === 'assets' ? 'asset_report' : 'warranty_report';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$fname}_" . date('Y-m-d') . ".csv");
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly

    if ($tab === 'assets') {
        fputcsv($out, ['Product', 'Category', 'Department', 'Place', 'Quantity', 'Added']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['product_name'], $r['category'], $r['department'], $r['place'], $r['quantity'], $r['added']]);
        }
    } else {
        fputcsv($out, ['Product', 'Category', 'Department', 'Place', 'Quantity', 'Warranty Start', 'Warranty End', 'Status']);
        foreach ($rows as $r) {
            [$label] = warranty_status($r['warranty_end']);
            fputcsv($out, [$r['product_name'], $r['category'], $r['department'], $r['place'], $r['quantity'], $r['warranty_start'], $r['warranty_end'], $label]);
        }
    }
    fclose($out);
    exit;
}

/* ---------- Dropdown options ---------- */
$opt_categories  = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT category FROM assets ORDER BY category"), MYSQLI_ASSOC);
$opt_departments = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT department FROM assets ORDER BY department"), MYSQLI_ASSOC);
$opt_places      = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT place FROM assets ORDER BY place"), MYSQLI_ASSOC);

/* ---------- Group rows (assets tab) ---------- */
$grouped = [];
$grand_total = 0;
if ($tab === 'assets') {
    foreach ($rows as $r) {
        $grouped[$r[$f_group]][] = $r;
        $grand_total += (int)$r['quantity'];
    }
}

/* Build a query string for export link that keeps current filters */
$qs = http_build_query(array_filter([
    'tab' => $tab, 'group' => $tab === 'assets' ? $f_group : null,
    'category' => $f_category, 'department' => $f_department, 'place' => $f_place,
    'from' => $f_from, 'to' => $f_to,
], fn($v) => $v !== null && $v !== ''));

/* ---------- Chart datasets (respect current tab + filters) ---------- */
$chart = ['type' => $tab];
if ($tab === 'assets') {
    $group_totals = [];
    foreach ($grouped as $g => $items) {
        $group_totals[$g] = array_sum(array_map(fn($i) => (int)$i['quantity'], $items));
    }
    arsort($group_totals);
    $cat_totals = [];
    foreach ($rows as $r) {
        $cat_totals[$r['category']] = ($cat_totals[$r['category']] ?? 0) + (int)$r['quantity'];
    }
    arsort($cat_totals);
    $chart['group_label']  = ucfirst($f_group);
    $chart['group_labels'] = array_keys($group_totals);
    $chart['group_values'] = array_values($group_totals);
    $chart['cat_labels']   = array_keys($cat_totals);
    $chart['cat_values']   = array_values($cat_totals);
    $chart['summary'] = ['items' => count($rows), 'qty' => $grand_total, 'groups' => count($grouped)];
} else {
    $buckets = ['Expired' => 0, 'Under 30 days' => 0, 'Under 90 days' => 0, 'Healthy' => 0];
    foreach ($rows as $r) {
        [, , $days] = warranty_status($r['warranty_end']);
        if ($days === null)  $buckets['Expired']++;
        elseif ($days <= 30) $buckets['Under 30 days']++;
        elseif ($days <= 90) $buckets['Under 90 days']++;
        else                 $buckets['Healthy']++;
    }
    $chart['w_labels'] = array_keys($buckets);
    $chart['w_values'] = array_values($buckets);
    $chart['summary']  = ['items' => count($rows), 'expired' => $buckets['Expired'],
                          'soon' => $buckets['Under 30 days'] + $buckets['Under 90 days']];
}

require_once __DIR__ . '/layout.php';
app_header('Reports', 'report');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.report-tabs{display:flex;gap:8px;margin-bottom:20px;}
.report-tabs a{padding:9px 18px;border-radius:10px;font-weight:600;font-size:13.5px;text-decoration:none;
  color:#475569;background:#fff;border:1px solid var(--line);}
.report-tabs a.active{background:var(--brand);border-color:var(--brand);color:#fff;}
.filter-bar{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);padding:16px 20px;margin-bottom:22px;}
.group-head td{background:#eef2ff !important;color:#3730a3;font-family:'Sora';font-weight:700;font-size:13px;
  letter-spacing:.03em;}
.group-sub td{background:#f8fafc !important;font-weight:600;color:#475569;}
@media print {
  .sidebar,.topbar,.report-tabs,.filter-bar,.no-print,.backdrop{display:none !important;}
  .main{margin:0 !important;padding:0 !important;}
  body{background:#fff;}
  .table-wrap{box-shadow:none;border:1px solid #ccc;border-radius:0;}
  .print-title{display:block !important;}
}
.print-title{display:none;font-family:'Sora';margin-bottom:14px;}
</style>

<!-- Tabs -->
<div class="report-tabs">
  <a href="?tab=assets" class="<?= $tab==='assets' ? 'active' : '' ?>">Asset Report</a>
  <a href="?tab=warranty" class="<?= $tab==='warranty' ? 'active' : '' ?>">Warranty Expiry</a>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <div class="row g-3 align-items-end">
    <?php if ($tab === 'assets'): ?>
    <div class="col-md-2">
      <label>Group by</label>
      <select name="group" class="form-select">
        <option value="department" <?= $f_group==='department'?'selected':'' ?>>Department</option>
        <option value="category"   <?= $f_group==='category'?'selected':''   ?>>Category</option>
        <option value="place"      <?= $f_group==='place'?'selected':''      ?>>Place</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-2">
      <label>Category</label>
      <select name="category" class="form-select">
        <option value="">All</option>
        <?php foreach ($opt_categories as $o): ?>
        <option <?= $f_category===$o['category']?'selected':'' ?>><?= e($o['category']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label>Department</label>
      <select name="department" class="form-select">
        <option value="">All</option>
        <?php foreach ($opt_departments as $o): ?>
        <option <?= $f_department===$o['department']?'selected':'' ?>><?= e($o['department']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label>Place</label>
      <select name="place" class="form-select">
        <option value="">All</option>
        <?php foreach ($opt_places as $o): ?>
        <option <?= $f_place===$o['place']?'selected':'' ?>><?= e($o['place']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label><?= $tab==='assets' ? 'Added from' : 'Expiry from' ?></label>
      <input type="date" name="from" class="form-control" value="<?= e($f_from) ?>">
    </div>
    <div class="col-md-2">
      <label><?= $tab==='assets' ? 'Added to' : 'Expiry to' ?></label>
      <input type="date" name="to" class="form-control" value="<?= e($f_to) ?>">
    </div>
    <div class="col-md-12 d-flex gap-2 justify-content-end">
      <a href="?tab=<?= e($tab) ?>" class="btn btn-light">Reset</a>
      <button class="btn btn-primary">Apply Filters</button>
      <a href="?<?= e($qs) ?>&export=csv" class="btn btn-success">Export CSV</a>
      <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
    </div>
  </div>
</form>

<h4 class="print-title">
  <?= $tab==='assets' ? 'Asset Report (grouped by '.e(ucfirst($f_group)).')' : 'Warranty Expiry Report' ?>
  — <?= e(date('d M Y')) ?>
</h4>

<?php if (!empty($rows)): ?>
<!-- =============== VISUAL SUMMARY =============== -->
<div class="stat-grid">
  <?php if ($tab === 'assets'): ?>
    <div class="stat"><div class="k">Matching Items</div><div class="v"><?= (int)$chart['summary']['items'] ?></div></div>
    <div class="stat"><div class="k">Total Quantity</div><div class="v"><?= (int)$chart['summary']['qty'] ?></div></div>
    <div class="stat"><div class="k"><?= e($chart['group_label']) ?>s</div><div class="v"><?= (int)$chart['summary']['groups'] ?></div></div>
  <?php else: ?>
    <div class="stat"><div class="k">Items with Warranty</div><div class="v"><?= (int)$chart['summary']['items'] ?></div></div>
    <div class="stat"><div class="k">Expired</div><div class="v" style="color:#dc2626;"><?= (int)$chart['summary']['expired'] ?></div></div>
    <div class="stat"><div class="k">Expiring Soon (≤90d)</div><div class="v" style="color:#d97706;"><?= (int)$chart['summary']['soon'] ?></div></div>
  <?php endif; ?>
</div>

<div class="row g-4 mb-4">
  <?php if ($tab === 'assets'): ?>
    <div class="col-lg-7"><div class="card h-100">
      <div class="card-header">Quantity by <?= e($chart['group_label']) ?></div>
      <div class="card-body"><canvas id="chartA" height="150"></canvas></div>
    </div></div>
    <div class="col-lg-5"><div class="card h-100">
      <div class="card-header">Quantity by Category</div>
      <div class="card-body"><canvas id="chartB" height="150"></canvas></div>
    </div></div>
  <?php else: ?>
    <div class="col-12"><div class="card">
      <div class="card-header">Warranty Status Breakdown</div>
      <div class="card-body"><canvas id="chartA" height="90"></canvas></div>
    </div></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
  <div class="card"><div class="card-body text-center text-muted py-5">
    No records match the selected filters.
  </div></div>

<?php elseif ($tab === 'assets'): ?>
  <!-- =============== ASSET REPORT (GROUPED) =============== -->
  <div class="table-wrap">
  <table class="table align-middle">
    <thead>
      <tr><th>Product</th><th>Category</th><th>Department</th><th>Place</th><th class="text-end">Qty</th><th>Added</th></tr>
    </thead>
    <tbody>
    <?php foreach ($grouped as $group_name => $items):
          $sub = array_sum(array_map(fn($i) => (int)$i['quantity'], $items)); ?>
      <tr class="group-head">
        <td colspan="6"><?= e(ucfirst($f_group)) ?>: <?= e($group_name) ?> &nbsp;·&nbsp; <?= count($items) ?> item(s)</td>
      </tr>
      <?php foreach ($items as $r): ?>
      <tr>
        <td><strong><?= e($r['product_name']) ?></strong></td>
        <td><?= e($r['category']) ?></td>
        <td><?= e($r['department']) ?></td>
        <td><?= e($r['place']) ?></td>
        <td class="text-end"><?= (int)$r['quantity'] ?></td>
        <td class="text-muted"><?= e($r['added']) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="group-sub">
        <td colspan="4" class="text-end">Subtotal — <?= e($group_name) ?></td>
        <td class="text-end"><?= $sub ?></td>
        <td></td>
      </tr>
    <?php endforeach; ?>
      <tr class="group-head">
        <td colspan="4" class="text-end">GRAND TOTAL</td>
        <td class="text-end"><?= $grand_total ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>
  </div>

<?php else: ?>
  <!-- =============== WARRANTY EXPIRY REPORT =============== -->
  <div class="table-wrap">
  <table class="table align-middle">
    <thead>
      <tr><th>Product</th><th>Category</th><th>Department</th><th>Place</th><th class="text-end">Qty</th>
          <th>W. Start</th><th>W. End</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r):
          [$label, $style] = warranty_status($r['warranty_end']); ?>
      <tr>
        <td><strong><?= e($r['product_name']) ?></strong></td>
        <td><?= e($r['category']) ?></td>
        <td><?= e($r['department']) ?></td>
        <td><?= e($r['place']) ?></td>
        <td class="text-end"><?= (int)$r['quantity'] ?></td>
        <td class="text-muted"><?= e($r['warranty_start']) ?></td>
        <td><?= e($r['warranty_end']) ?></td>
        <td><span class="badge-soft" style="<?= $style ?>"><?= e($label) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<?php if (!empty($rows)): ?>
<script>
const CHART = <?= json_encode($chart) ?>;
const PALETTE = ['#4f46e5','#0891b2','#16a34a','#d97706','#db2777','#7c3aed','#0d9488','#e11d48','#2563eb','#65a30d'];

Chart.defaults.font.family = "'Public Sans', sans-serif";
Chart.defaults.color = '#64748b';

if (CHART.type === 'assets') {
  new Chart(document.getElementById('chartA'), {
    type: 'bar',
    data: { labels: CHART.group_labels,
      datasets: [{ label: 'Quantity', data: CHART.group_values, backgroundColor: '#4f46e5', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
  new Chart(document.getElementById('chartB'), {
    type: 'doughnut',
    data: { labels: CHART.cat_labels,
      datasets: [{ data: CHART.cat_values, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
  });
} else {
  new Chart(document.getElementById('chartA'), {
    type: 'bar',
    data: { labels: CHART.w_labels,
      datasets: [{ label: 'Items', data: CHART.w_values,
        backgroundColor: ['#dc2626','#ea580c','#d97706','#16a34a'], borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
}
</script>
<?php endif; ?>

<?php app_footer(); ?>
