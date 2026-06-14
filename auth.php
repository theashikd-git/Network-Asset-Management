<?php
/* ============================================================
   AUTH & SECURITY HELPERS
   Include this at the very top of every protected page.
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* ---------- HTML escaping (use on ALL output) ---------- */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ---------- CSRF protection ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf() {
    $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$token)) {
        http_response_code(403);
        die('Invalid or expired request token. Please go back and try again.');
    }
}

/* ---------- Role helpers ---------- */
function current_role() {
    return $_SESSION['role'] ?? null;
}

function is_admin() {
    return current_role() === 'ADMIN';
}

/* Every protected page must be viewable only by logged-in users. */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/* Pages / actions only an admin may reach. */
function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied. This area is for administrators only.');
    }
}

/* Guard a write action (add / edit / delete). Regular users are read-only. */
function require_admin_for_write() {
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Read-only: only an administrator can modify data.']);
        exit;
    }
}

/* ============================================================
   FIRST-RUN BOOTSTRAP
   If no users exist yet, create the default admin account.
   Default login -> username: admin   password: admin123
   (Change the password from the Users page after first login.)
   ============================================================ */
function seed_default_admin($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        if ((int)$row['c'] === 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'ADMIN')");
            $u = 'admin';
            mysqli_stmt_bind_param($stmt, 'ss', $u, $hash);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
