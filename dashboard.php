<?php
require_once __DIR__ . '/auth.php';
require_login();

/* ---------- Stats ---------- */
$stat_assets   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(quantity),0) q FROM assets"));
$stat_ips      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM ip_allocations"));
$stat_wish     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM wishlist WHERE status='Pending'"));
$stat_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"));

/* ---------- Panels ---------- */
$assets  = mysqli_query($conn, "SELECT product_name, department, SUM(quantity) total_qty FROM assets GROUP BY product_name, department ORDER BY product_name ASC LIMIT 6");
$ips     = mysqli_query($conn, "SELECT ip_address, cidr, vlan_id, vlan_name FROM ip_allocations ORDER BY id DESC LIMIT 6");
$recent  = mysqli_query($conn, "SELECT product_name, created_at FROM assets ORDER BY created_at DESC LIMIT 6");
$warranty= mysqli_query($conn, "SELECT product_name, warranty_end FROM assets WHERE warranty_end IS NOT NULL ORDER BY warranty_end ASC");
$wishlist= mysqli_query($conn, "SELECT product_name, brand, quantity, desired_department, status FROM wishlist ORDER BY id DESC LIMIT 6");

require_once __DIR__ . '/layout.php';
app_header('Dashboard', 'dashboard');
?>

<div class="stat-grid">
  <div class="stat">
    <div class="ic" style="background:#eef2ff;color:#4f46e5;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20 7h-4V5l-2-2h-4L8 5v2H4a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1z"/></svg></div>
    <div class="k">Total Assets</div>
    <div class="v"><?= (int)$stat_assets['c'] ?></div>
    <div class="text-muted small mt-1"><?= (int)$stat_assets['q'] ?> units in total</div>
  </div>
  <div class="stat">
    <div class="ic" style="background:#ecfeff;color:#0891b2;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v6H4V4zm0 10h16v6H4v-6z"/></svg></div>
    <div class="k">IP Allocations</div>
    <div class="v"><?= (int)$stat_ips['c'] ?></div>
    <div class="text-muted small mt-1">VLANs registered</div>
  </div>
  <div class="stat">
    <div class="ic" style="background:#fff7ed;color:#ea580c;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7.5-4.6-10-9.3C.4 8 2 4.5 5.3 4.5c2 0 3.4 1.2 4.2 2.4C10.3 5.7 11.7 4.5 13.7 4.5 17 4.5 18.6 8 16 11.7 21.5 16.4 12 21 12 21z"/></svg></div>
    <div class="k">Pending Wishlist</div>
    <div class="v"><?= (int)$stat_wish['c'] ?></div>
    <div class="text-muted small mt-1">awaiting approval</div>
  </div>
  <div class="stat">
    <div class="ic" style="background:#f0fdf4;color:#16a34a;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-5 0-9 2.5-9 6v2h18v-2c0-3.5-4-6-9-6z"/></svg></div>
    <div class="k">User Accounts</div>
    <div class="v"><?= (int)$stat_users['c'] ?></div>
    <div class="text-muted small mt-1"><?= is_admin() ? 'Manage in User Management' : 'Total registered' ?></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">Asset Overview</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <tbody>
          <?php while ($r = mysqli_fetch_assoc($assets)): ?>
            <tr>
              <td><strong><?= e($r['product_name']) ?></strong><div class="text-muted small"><?= e($r['department']) ?></div></td>
              <td class="text-end"><span class="badge-soft" style="background:#eef2ff;color:#4f46e5;">Qty <?= (int)$r['total_qty'] ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">Recent IP Allocations</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <tbody>
          <?php while ($r = mysqli_fetch_assoc($ips)): ?>
            <tr>
              <td><strong><?= e($r['ip_address']) ?>/<?= e($r['cidr']) ?></strong></td>
              <td class="text-end text-muted small">VLAN <?= e($r['vlan_id']) ?> · <?= e($r['vlan_name']) ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">Recently Added Assets</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <tbody>
          <?php while ($r = mysqli_fetch_assoc($recent)): ?>
            <tr>
              <td><strong><?= e($r['product_name']) ?></strong></td>
              <td class="text-end text-muted small"><?= e(date("d M Y", strtotime($r['created_at']))) ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header">Warranty Expiring Soon</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <tbody>
          <?php
          $today = new DateTime();
          $shown = 0;
          while (($r = mysqli_fetch_assoc($warranty)) && $shown < 6):
            $expiry = new DateTime($r['warranty_end']);
            if ($expiry < $today) continue;
            $i = $today->diff($expiry);
            $remaining = $i->m > 0 ? "{$i->m} mo {$i->d} d" : "{$i->d} d";
            $shown++; ?>
            <tr>
              <td><strong><?= e($r['product_name']) ?></strong></td>
              <td class="text-end"><span class="badge-soft" style="background:#fef2f2;color:#dc2626;"><?= e($remaining) ?> left</span></td>
            </tr>
          <?php endwhile; ?>
          <?php if ($shown === 0): ?><tr><td class="text-muted small">No upcoming warranty expirations.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">Latest Wishlist Requests</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Product</th><th>Brand</th><th>Qty</th><th>Department</th><th>Status</th></tr></thead>
          <tbody>
          <?php while ($r = mysqli_fetch_assoc($wishlist)): ?>
            <tr>
              <td><strong><?= e($r['product_name']) ?></strong></td>
              <td><?= e($r['brand']) ?></td>
              <td><?= (int)$r['quantity'] ?></td>
              <td><?= e($r['desired_department']) ?></td>
              <td><span class="badge-soft" style="background:#f1f5f9;color:#475569;"><?= e($r['status']) ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php app_footer(); ?>
