<?php
require_once __DIR__ . '/auth.php';
require_login();

$can_edit = is_admin();

/* ================= AJAX UPDATE ================= */
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    require_admin_for_write();
    check_csrf();
    $stmt = mysqli_prepare($conn, "UPDATE ip_allocations SET ip_address=?, cidr=?, vlan_id=?, vlan_name=?, purpose=?, description=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssisssi',
        $_POST['ip_address'], $_POST['cidr'], $_POST['vlan_id'], $_POST['vlan_name'],
        $_POST['purpose'], $_POST['description'], $_POST['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode($_POST);
    exit;
}

/* ================= AJAX DELETE ================= */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    require_admin_for_write();
    check_csrf();
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM ip_allocations WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success']);
    exit;
}

/* ================= SAVE IP ================= */
if (isset($_POST['save_ip'])) {
    require_admin_for_write();
    check_csrf();
    $ip   = trim($_POST['ip_address']);
    $cidr = trim($_POST['cidr']);
    $vid  = trim($_POST['vlan_id']);
    $vn   = trim($_POST['vlan_name']);
    $pur  = trim($_POST['purpose']);
    $desc = trim($_POST['description']);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $_SESSION['message'] = "Invalid IP format.";
    } else {
        $chk = mysqli_prepare($conn, "SELECT id FROM ip_allocations WHERE ip_address=? OR vlan_id=?");
        mysqli_stmt_bind_param($chk, 'si', $ip, $vid);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $_SESSION['message'] = "That IP address or VLAN ID already exists.";
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO ip_allocations (ip_address,cidr,vlan_id,vlan_name,purpose,description) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($ins, 'ssisss', $ip, $cidr, $vid, $vn, $pur, $desc);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
            $_SESSION['message'] = "IP saved successfully.";
        }
        mysqli_stmt_close($chk);
    }
    header("Location: ip_allocation.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM ip_allocations ORDER BY id ASC");

require_once __DIR__ . '/layout.php';
app_header('IP Allocation', 'ip');
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<?php if (!empty($_SESSION['message'])): ?>
  <div class="alert alert-info" style="border-radius:10px;"><?= e($_SESSION['message']) ?></div>
  <?php unset($_SESSION['message']); endif; ?>

<?php if (!$can_edit): ?>
  <div class="readonly-note">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0v3z"/></svg>
    Read-only view. Only administrators can add, edit or delete IP allocations.
  </div>
<?php endif; ?>

<?php if ($can_edit): ?>
<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addIPForm">+ Add IP</button>
</div>
<div class="collapse mb-4" id="addIPForm">
<form method="POST" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label>IP Address</label><input type="text" name="ip_address" class="form-control" required></div>
    <div class="col-md-2"><label>CIDR</label><input type="number" name="cidr" class="form-control" placeholder="24" required></div>
    <div class="col-md-3"><label>VLAN ID</label><input type="number" name="vlan_id" class="form-control" required></div>
    <div class="col-md-3"><label>VLAN Name</label><input type="text" name="vlan_name" class="form-control" required></div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6"><label>Usage Purpose</label><input type="text" name="purpose" class="form-control" placeholder="e.g. WIFI / Nurse Calling"></div>
    <div class="col-md-6"><label>Description</label><textarea name="description" class="form-control" rows="1"></textarea></div>
  </div>
  <div><button type="submit" name="save_ip" class="btn btn-success">Save IP</button></div>
</form>
</div>
<?php endif; ?>

<div class="table-wrap">
<table class="table align-middle">
<thead>
<tr><th>ID</th><th>IP</th><th>CIDR</th><th>VLAN</th><th>Name</th><th>Purpose</th><th>Description</th><?php if($can_edit):?><th class="text-end">Action</th><?php endif;?></tr>
</thead>
<tbody>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<tr id="row<?= (int)$row['id'] ?>">
  <td class="text-muted">#<?= (int)$row['id'] ?></td>
  <td><strong><?= e($row['ip_address']) ?></strong></td>
  <td><?= e($row['cidr']) ?></td>
  <td><?= e($row['vlan_id']) ?></td>
  <td><?= e($row['vlan_name']) ?></td>
  <td><?= e($row['purpose']) ?></td>
  <td><?= e($row['description']) ?></td>
  <?php if ($can_edit): ?>
  <td class="text-end">
    <button class="btn btn-warning btn-sm edit-btn" data-id="<?= (int)$row['id'] ?>">Edit</button>
    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= (int)$row['id'] ?>">Delete</button>
  </td>
  <?php endif; ?>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php if ($can_edit): ?>
<!-- EDIT MODAL -->
<div class="modal fade" id="editModal"><div class="modal-dialog"><div class="modal-content">
<form id="editForm">
  <div class="modal-header"><h5 class="modal-title">Edit IP</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" id="edit_id">
    <label>IP Address</label><input class="form-control mb-2" name="ip_address" id="edit_ip">
    <label>CIDR</label><input class="form-control mb-2" name="cidr" id="edit_cidr">
    <label>VLAN ID</label><input class="form-control mb-2" name="vlan_id" id="edit_vlan_id">
    <label>VLAN Name</label><input class="form-control mb-2" name="vlan_name" id="edit_vlan_name">
    <label>Purpose</label><input class="form-control mb-2" name="purpose" id="edit_purpose">
    <label>Description</label><textarea class="form-control" name="description" id="edit_description"></textarea>
  </div>
  <div class="modal-footer"><button class="btn btn-success">Update</button></div>
</form>
</div></div></div>

<script>
$(function(){
  $('.edit-btn').click(function(){
    var row=$(this).closest('tr');
    $('#edit_id').val($(this).data('id'));
    $('#edit_ip').val(row.find('td:eq(1)').text());
    $('#edit_cidr').val(row.find('td:eq(2)').text());
    $('#edit_vlan_id').val(row.find('td:eq(3)').text());
    $('#edit_vlan_name').val(row.find('td:eq(4)').text());
    $('#edit_purpose').val(row.find('td:eq(5)').text());
    $('#edit_description').val(row.find('td:eq(6)').text());
    new bootstrap.Modal(document.getElementById('editModal')).show();
  });
  $('#editForm').submit(function(e){
    e.preventDefault();
    $.post('', $(this).serialize()+'&action=update', function(res){
      var d=JSON.parse(res); var row=$('#row'+d.id);
      row.find('td:eq(1)').html('<strong>'+$('<div>').text(d.ip_address).html()+'</strong>');
      row.find('td:eq(2)').text(d.cidr);
      row.find('td:eq(3)').text(d.vlan_id);
      row.find('td:eq(4)').text(d.vlan_name);
      row.find('td:eq(5)').text(d.purpose);
      row.find('td:eq(6)').text(d.description);
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    });
  });
  $('.delete-btn').click(function(){
    if(!confirm('Delete this IP?'))return;
    var id=$(this).data('id');
    $.get('', {action:'delete', id:id, csrf:'<?= e(csrf_token()) ?>'}, function(){ $('#row'+id).remove(); });
  });
});
</script>
<?php endif; ?>

<?php app_footer(); ?>
