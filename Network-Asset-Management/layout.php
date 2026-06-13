<?php
/* ============================================================
   SHARED LAYOUT  — clean, consistent app shell for every page.
   Usage:
     app_header('Page Title', 'active_key');   // top of page
       ... page content ...
     app_footer();                             // bottom of page
   ============================================================ */

function app_header($title, $active = '') {
    $username = e($_SESSION['username'] ?? 'User');
    $role     = e($_SESSION['role'] ?? 'USER');
    $initial  = strtoupper(substr($username, 0, 1));
    $admin    = is_admin();

    // Sidebar items: key => [label, href, svg path]
    $nav = [
        'dashboard' => ['Dashboard',        'dashboard.php',      'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        'assets'    => ['Asset Management',  'assets.php',         'M20 7h-4V5l-2-2h-4L8 5v2H4a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1zM10 5h4v2h-4V5z'],
        'report'    => ['Asset Report',      'assets_report.php',  'M5 3h11l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm8 1.5V9h4.5L13 4.5zM7 13h10v2H7v-2zm0 4h10v2H7v-2z'],
        'ip'        => ['IP Allocation',     'ip_allocation.php',  'M4 4h16v6H4V4zm0 10h16v6H4v-6zm3-7h2v2H7V7zm0 10h2v2H7v-2z'],
        'wishlist'  => ['Wishlist',          'wishlist.php',       'M12 21s-7.5-4.6-10-9.3C.4 8 2 4.5 5.3 4.5c2 0 3.4 1.2 4.2 2.4C10.3 5.7 11.7 4.5 13.7 4.5 17 4.5 18.6 8 16 11.7 21.5 16.4 12 21 12 21z'],
    ];
    if ($admin) {
        $nav['users'] = ['User Management', 'users.php', 'M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-5 0-9 2.5-9 6v2h18v-2c0-3.5-4-6-9-6z'];
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · NAMIAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef1f6; --surface:#ffffff; --ink:#1e293b; --muted:#64748b; --line:#e2e8f0;
  --side:#0f172a; --side-2:#1e293b; --side-ink:#cbd5e1; --side-ink-hi:#ffffff;
  --brand:#4f46e5; --brand-2:#6366f1; --brand-soft:#eef2ff;
  --ok:#16a34a; --warn:#d97706; --danger:#dc2626;
  --shadow:0 1px 2px rgba(15,23,42,.06), 0 8px 24px -12px rgba(15,23,42,.18);
  --radius:14px;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:'Public Sans',system-ui,sans-serif;font-size:14.5px;}
h1,h2,h3,h4,h5{font-family:'Sora',sans-serif;letter-spacing:-.01em;}

/* ---------- App shell ---------- */
.app{display:flex;min-height:100vh;}
.sidebar{
  width:248px;flex:0 0 248px;background:linear-gradient(180deg,var(--side),var(--side-2));
  color:var(--side-ink);position:fixed;top:0;bottom:0;left:0;display:flex;flex-direction:column;
  padding:18px 14px;z-index:1040;transition:transform .25s ease;
}
.brand{display:flex;align-items:center;gap:10px;padding:6px 10px 18px;}
.brand .logo{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--brand),var(--brand-2));
  display:grid;place-items:center;color:#fff;font-family:'Sora';font-weight:700;font-size:16px;}
.brand .name{color:#fff;font-family:'Sora';font-weight:700;font-size:18px;line-height:1;}
.brand .sub{color:#7c8aa5;font-size:11px;letter-spacing:.12em;text-transform:uppercase;}
.nav-link-c{display:flex;align-items:center;gap:12px;padding:11px 13px;margin:2px 0;border-radius:10px;
  color:var(--side-ink);text-decoration:none;font-weight:500;transition:.15s;}
.nav-link-c svg{width:19px;height:19px;flex:0 0 19px;opacity:.85;}
.nav-link-c:hover{background:rgba(255,255,255,.06);color:#fff;}
.nav-link-c.active{background:var(--brand);color:#fff;box-shadow:0 6px 16px -8px var(--brand);}
.nav-link-c.active svg{opacity:1;}
.side-foot{margin-top:auto;border-top:1px solid rgba(255,255,255,.08);padding-top:12px;}
.user-chip{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;}
.avatar{width:36px;height:36px;border-radius:50%;background:var(--brand-soft);color:var(--brand);
  display:grid;place-items:center;font-weight:700;font-family:'Sora';}
.user-chip .u{color:#fff;font-weight:600;font-size:13.5px;line-height:1.1;}
.user-chip .r{font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:#7c8aa5;}
.logout{display:flex;align-items:center;gap:9px;justify-content:center;margin-top:8px;padding:9px;
  border-radius:10px;color:#fca5a5;text-decoration:none;font-weight:600;font-size:13px;}
.logout:hover{background:rgba(248,113,113,.12);color:#fecaca;}

/* ---------- Main ---------- */
.main{margin-left:248px;flex:1;min-width:0;padding:30px 34px 60px;}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;}
.topbar h1{font-size:24px;margin:0;}
.topbar .crumb{color:var(--muted);font-size:12.5px;letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
.role-tag{font-size:11px;font-weight:700;letter-spacing:.08em;padding:5px 11px;border-radius:999px;}
.role-tag.admin{background:#dcfce7;color:#15803d;}
.role-tag.user{background:#e0e7ff;color:#4338ca;}
.readonly-note{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;
  padding:10px 14px;font-size:13px;margin-bottom:20px;display:flex;gap:8px;align-items:center;}

/* ---------- Cards / tables / forms ---------- */
.card{border:none;border-radius:var(--radius);box-shadow:var(--shadow);background:var(--surface);}
.card-header{background:transparent;border-bottom:1px solid var(--line);font-family:'Sora';font-weight:600;
  font-size:15px;padding:16px 20px;}
.card-body{padding:18px 20px;}
.table{margin:0;}
.table thead th{background:#f8fafc;color:#475569;font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;
  font-weight:700;border-bottom:1px solid var(--line);padding:13px 14px;white-space:nowrap;}
.table tbody td{padding:13px 14px;vertical-align:middle;border-color:#f1f5f9;}
.table tbody tr:hover{background:#f8fafc;}
.table-wrap{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
.form-control,.form-select{border-radius:10px;border-color:var(--line);padding:9px 12px;font-size:14px;}
.form-control:focus,.form-select:focus{border-color:var(--brand-2);box-shadow:0 0 0 3px rgba(99,102,241,.15);}
label{font-weight:600;font-size:12.5px;color:#475569;margin-bottom:5px;}
.btn{border-radius:10px;font-weight:600;font-size:13.5px;padding:9px 16px;}
.btn-primary{background:var(--brand);border-color:var(--brand);}
.btn-primary:hover{background:#4338ca;border-color:#4338ca;}
.btn-success{background:var(--ok);border-color:var(--ok);}
.btn-sm{padding:5px 11px;font-size:12.5px;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:18px;margin-bottom:24px;}
.stat{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 20px;}
.stat .k{font-size:12px;color:var(--muted);font-weight:600;letter-spacing:.04em;text-transform:uppercase;}
.stat .v{font-family:'Sora';font-weight:700;font-size:30px;margin-top:6px;line-height:1;}
.stat .ic{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;margin-bottom:12px;}
.badge-soft{font-weight:600;font-size:11.5px;padding:5px 10px;border-radius:999px;}
.menu-toggle{display:none;}
.backdrop{display:none;}

@media (max-width:992px){
  .sidebar{transform:translateX(-100%);} 
  .sidebar.open{transform:translateX(0);} 
  .main{margin-left:0;padding:20px 16px 50px;}
  .menu-toggle{display:inline-grid;place-items:center;width:42px;height:42px;border-radius:10px;border:1px solid var(--line);background:#fff;}
  .backdrop.show{display:block;position:fixed;inset:0;background:rgba(15,23,42,.4);z-index:1035;}
}
</style>
</head>
<body>
<div class="app">
  <div class="backdrop" id="backdrop" onclick="toggleNav(false)"></div>
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="logo">N</div>
      <div>
        <div class="name">NAMIAS</div>
        <div class="sub">Asset Manager</div>
      </div>
    </div>
    <nav class="flex-grow-1">
      <?php foreach ($nav as $key => $item): ?>
        <a class="nav-link-c <?= $active === $key ? 'active' : '' ?>" href="<?= e($item[1]) ?>">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?= $item[2] ?>"/></svg>
          <span><?= e($item[0]) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <div class="user-chip">
        <div class="avatar"><?= e($initial) ?></div>
        <div>
          <div class="u"><?= $username ?></div>
          <div class="r"><?= $role ?></div>
        </div>
      </div>
      <a href="logout.php" class="logout">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 17v-3H9v-4h7V7l5 5-5 5zM14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"/></svg>
        Sign out
      </a>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="menu-toggle" onclick="toggleNav(true)" aria-label="Menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>
        </button>
        <div>
          <div class="crumb">NAMIAS</div>
          <h1><?= e($title) ?></h1>
        </div>
      </div>
      <span class="role-tag <?= $admin ? 'admin' : 'user' ?>"><?= $admin ? 'Administrator' : 'Read-only user' ?></span>
    </div>
<?php
}

function app_footer() {
    ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleNav(open){
  document.getElementById('sidebar').classList.toggle('open', open);
  document.getElementById('backdrop').classList.toggle('show', open);
}
</script>
</body>
</html>
<?php
}
?>
