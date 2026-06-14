<?php
require_once __DIR__ . '/auth.php';
require_admin();                       // <-- whole page is admin-only

$msg = '';
$msg_type = 'success';

/* ---------- CREATE USER ---------- */
if (isset($_POST['create_user'])) {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = ($_POST['role'] ?? 'USER') === 'ADMIN' ? 'ADMIN' : 'USER';

    if ($username === '' || strlen($password) < 4) {
        $msg = "Username is required and password must be at least 4 characters.";
        $msg_type = 'danger';
    } else {
        $chk = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($chk, 's', $username);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $msg = "That username already exists.";
            $msg_type = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'sss', $username, $hash, $role);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
            $msg = "User \"$username\" created successfully.";
        }
        mysqli_stmt_close($chk);
    }
}

/* ---------- RESET PASSWORD ---------- */
if (isset($_POST['reset_password'])) {
    check_csrf();
    $uid = (int)($_POST['user_id'] ?? 0);
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 4) {
        $msg = "New password must be at least 4 characters.";
        $msg_type = 'danger';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Password has been reset.";
    }
}

/* ---------- CHANGE ROLE ---------- */
if (isset($_POST['change_role'])) {
    check_csrf();
    $uid  = (int)($_POST['user_id'] ?? 0);
    $role = ($_POST['role'] ?? 'USER') === 'ADMIN' ? 'ADMIN' : 'USER';
    // Prevent removing the last admin / demoting yourself accidentally.
    if ($uid === (int)$_SESSION['user_id'] && $role !== 'ADMIN') {
        $msg = "You cannot remove your own admin role.";
        $msg_type = 'danger';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $role, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Role updated.";
    }
}

/* ---------- DELETE USER ---------- */
if (isset($_POST['delete_user'])) {
    check_csrf();
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === (int)$_SESSION['user_id']) {
        $msg = "You cannot delete your own account.";
        $msg_type = 'danger';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $msg = "User deleted.";
    }
}

$users = mysqli_query($conn, "SELECT user_id, username, role FROM users ORDER BY role ASC, username ASC");

require_once __DIR__ . '/layout.php';
app_header('User Management', 'users');
?>

<?php if ($msg): ?>
<div class="alert alert-<?= e($msg_type) ?>" style="border-radius:10px;"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Create user -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Create new user</div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Password</label>
            <input type="text" name="password" class="form-control" placeholder="min 4 characters" required>
          </div>
          <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-select">
              <option value="USER">User — read only</option>
              <option value="ADMIN">Admin — full control</option>
            </select>
          </div>
          <button name="create_user" class="btn btn-primary w-100">Create user</button>
        </form>
      </div>
    </div>
  </div>

  <!-- User list -->
  <div class="col-lg-8">
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr><th>ID</th><th>Username</th><th>Role</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($users)):
              $is_me = ((int)$u['user_id'] === (int)$_SESSION['user_id']); ?>
          <tr>
            <td class="text-muted">#<?= e($u['user_id']) ?></td>
            <td>
              <strong><?= e($u['username']) ?></strong>
              <?php if ($is_me): ?><span class="badge-soft" style="background:#e0e7ff;color:#4338ca;">you</span><?php endif; ?>
            </td>
            <td>
              <span class="badge-soft" style="<?= $u['role']==='ADMIN' ? 'background:#dcfce7;color:#15803d;' : 'background:#f1f5f9;color:#475569;' ?>">
                <?= e($u['role']) ?>
              </span>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-warning"
                      data-bs-toggle="modal" data-bs-target="#resetModal"
                      data-id="<?= e($u['user_id']) ?>" data-name="<?= e($u['username']) ?>">Reset password</button>

              <button class="btn btn-sm btn-outline-secondary"
                      data-bs-toggle="modal" data-bs-target="#roleModal"
                      data-id="<?= e($u['user_id']) ?>" data-name="<?= e($u['username']) ?>" data-role="<?= e($u['role']) ?>">Role</button>

              <?php if (!$is_me): ?>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete user \'<?= e($u['username']) ?>\'?');">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= e($u['user_id']) ?>">
                <button name="delete_user" class="btn btn-sm btn-danger">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reset password modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Reset password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="user_id" id="reset_uid">
        <p class="text-muted small mb-3">Setting a new password for <strong id="reset_name"></strong>.</p>
        <label>New password</label>
        <input type="text" name="new_password" class="form-control" placeholder="min 4 characters" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button name="reset_password" class="btn btn-primary">Reset password</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Change role modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Change role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="user_id" id="role_uid">
        <p class="text-muted small mb-3">Updating role for <strong id="role_name"></strong>.</p>
        <select name="role" id="role_select" class="form-select">
          <option value="USER">User — read only</option>
          <option value="ADMIN">Admin — full control</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button name="change_role" class="btn btn-primary">Save role</button>
      </div>
    </form>
  </div></div>
</div>

<script>
const resetModal = document.getElementById('resetModal');
resetModal.addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  resetModal.querySelector('#reset_uid').value = b.dataset.id;
  resetModal.querySelector('#reset_name').textContent = b.dataset.name;
});
const roleModal = document.getElementById('roleModal');
roleModal.addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  roleModal.querySelector('#role_uid').value = b.dataset.id;
  roleModal.querySelector('#role_name').textContent = b.dataset.name;
  roleModal.querySelector('#role_select').value = b.dataset.role;
});
</script>

<?php app_footer(); ?>
