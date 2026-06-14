<?php
require_once __DIR__ . '/auth.php';

// Make sure a default admin exists on a fresh install.
seed_default_admin($conn);

// Already logged in? Go to dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if (isset($_POST['login'])) {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · NAMIAS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--brand:#4f46e5;--brand-2:#6366f1;--ink:#1e293b;--muted:#64748b;--line:#e2e8f0;}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Public Sans',system-ui,sans-serif;color:var(--ink);
  background:radial-gradient(1200px 600px at 80% -10%,#312e81 0,transparent 60%),
             radial-gradient(900px 500px at -10% 110%,#1e1b4b 0,transparent 55%),#0f172a;
  display:grid;place-items:center;padding:20px;}
.login-card{width:100%;max-width:400px;background:#fff;border-radius:18px;padding:34px 32px;
  box-shadow:0 30px 60px -20px rgba(0,0,0,.5);}
.logo{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--brand),var(--brand-2));
  display:grid;place-items:center;color:#fff;font-family:'Sora';font-weight:700;font-size:24px;margin:0 auto 16px;}
h3{font-family:'Sora';font-weight:700;text-align:center;margin:0 0 4px;}
.sub{text-align:center;color:var(--muted);font-size:13.5px;margin-bottom:24px;}
label{font-weight:600;font-size:12.5px;color:#475569;margin-bottom:6px;}
.form-control{border-radius:10px;border-color:var(--line);padding:11px 13px;}
.form-control:focus{border-color:var(--brand-2);box-shadow:0 0 0 3px rgba(99,102,241,.15);}
.btn-login{background:var(--brand);border:none;border-radius:10px;font-weight:600;padding:11px;width:100%;
  color:#fff;margin-top:6px;}
.btn-login:hover{background:#4338ca;}
.hint{text-align:center;color:#94a3b8;font-size:11.5px;margin-top:18px;}
.alert{border-radius:10px;font-size:13.5px;padding:10px 14px;}
</style>
</head>
<body>
<div class="login-card">
  <div class="logo">N</div>
  <h3>Welcome back</h3>
  <div class="sub">Sign in to the Network Asset Manager</div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label>Username</label>
      <input type="text" name="username" class="form-control" autofocus required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" name="login" class="btn-login">Sign in</button>
  </form>
</div>
</body>
</html>
