<?php
require_once __DIR__ . '/auth.php';
require_login();

$can_edit = is_admin();

/* ================= AJAX UPDATE ================= */
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    require_admin_for_write();
    check_csrf();
    $stmt = mysqli_prepare($conn, "UPDATE wishlist SET product_name=?, brand=?, quantity=?, desired_department=?, details=?, status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssisssi',
        $_POST['product_name'], $_POST['brand'], $_POST['quantity'], $_POST['desired_department'],
        $_POST['details'], $_POST['status'], $_POST['id']);
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
    $stmt = mysqli_prepare($conn, "DELETE FROM wishlist WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success']);
    exit;
}

/* ================= SAVE ITEM ================= */
if (isset($_POST['save_item'])) {
    require_admin_for_write();
    check_csrf();
    $stmt = mysqli_prepare($conn, "INSERT INTO wishlist (product_name,brand,quantity,desired_department,details,status) VALUES (?,?,?,?,?,'Pending')");
    mysqli_stmt_bind_param($stmt, 'ssiss',
        $_POST['product_name'], $_POST['brand'], $_POST['quantity'], $_POST['desired_department'], $_POST['details']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: wishlist.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM wishlist ORDER BY id ASC");

require_once __DIR__ . '/layout.php';
app_header('Wishlist', 'wishlist');
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<?php if (!$can_edit): ?>
  <div class="readonly-note">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0v3z"/></svg>
    Read-only view. Only administrators can add, edit or delete wishlist items.
  </div>
<?php endif; ?>

<?php if ($can_edit): ?>
<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addForm">+ Add Item</button>
</div>
<div class="collapse mb-4" id="addForm">
<form method="POST" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label>Product Name</label><input type="text" name="product_name" class="form-control" required></div>
    <div class="col-md-6"><label>Brand</label><input type="text" name="brand" class="form-control"></div>
    <div class="col-md-6"><label>Quantity</label><input type="number" name="quantity" class="form-control" required></div>
    <div class="col-md-6"><label>Department</label><input type="text" name="desired_department" class="form-control" required></div>
    <div class="col-12"><label>Details</label><textarea name="details" class="form-control" rows="1"></textarea></div>
  </div>
  <div class="mt-3"><button class="btn btn-success" name="save_item">Save</button></div>
</form>
</div>
<?php endif; ?>

<div class="table-wrap">
<table class="table align-middle">
<thead>
<tr><th>ID</th><th>Product</th><th>Brand</th><th>Qty</th><th>Department</th><th>Details</th><th>Status</th><?php if($can_edit):?><th class="text-end">Action</th><?php endif;?></tr>
</thead>
<tbody>
<?php while ($row = mysqli_fetch_assoc($result)):
  $sc = ['Pending'=>'background:#fef9c3;color:#854d0e;','Approved'=>'background:#dcfce7;color:#15803d;','Ordered'=>'background:#dbeafe;color:#1e40af;','Rejected'=>'background:#fee2e2;color:#b91c1c;'][$row['status']] ?? 'background:#f1f5f9;color:#475569;'; ?>
<tr id="row<?= (int)$row['id'] ?>">
  <td class="text-muted">#<?= (int)$row['id'] ?></td>
  <td><strong><?= e($row['product_name']) ?></strong></td>
  <td><?= e($row['brand']) ?></td>
  <td><?= (int)$row['quantity'] ?></td>
  <td><?= e($row['desired_department']) ?></td>
  <td><?= e($row['details']) ?></td>
  <td><span class="badge-soft status-cell" style="<?= $sc ?>"><?= e($row['status']) ?></span></td>
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
  <div class="modal-header"><h5 class="modal-title">Edit Wishlist Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" id="edit_id">
    <label>Product</label><input class="form-control mb-2" name="product_name" id="edit_product">
    <label>Brand</label><input class="form-control mb-2" name="brand" id="edit_brand">
    <label>Quantity</label><input class="form-control mb-2" name="quantity" id="edit_qty">
    <label>Department</label><input class="form-control mb-2" name="desired_department" id="edit_dep">
    <label>Details</label><textarea class="form-control mb-2" name="details" id="edit_details"></textarea>
    <label>Status</label>
    <select class="form-select" name="status" id="edit_status">
      <option>Pending</option><option>Approved</option><option>Ordered</option><option>Rejected</option>
    </select>
  </div>
  <div class="modal-footer"><button class="btn btn-success">Update</button></div>
</form>
</div></div></div>

<script>
$(function(){
  $('.edit-btn').click(function(){
    var row=$(this).closest('tr');
    $('#edit_id').val($(this).data('id'));
    $('#edit_product').val(row.find('td:eq(1)').text());
    $('#edit_brand').val(row.find('td:eq(2)').text());
    $('#edit_qty').val(row.find('td:eq(3)').text());
    $('#edit_dep').val(row.find('td:eq(4)').text());
    $('#edit_details').val(row.find('td:eq(5)').text());
    $('#edit_status').val(row.find('td:eq(6)').text().trim());
    new bootstrap.Modal(document.getElementById('editModal')).show();
  });
  $('#editForm').submit(function(e){
    e.preventDefault();
    $.post('', $(this).serialize()+'&action=update', function(res){
      var d=JSON.parse(res); var row=$('#row'+d.id);
      row.find('td:eq(1)').html('<strong>'+$('<div>').text(d.product_name).html()+'</strong>');
      row.find('td:eq(2)').text(d.brand);
      row.find('td:eq(3)').text(d.quantity);
      row.find('td:eq(4)').text(d.desired_department);
      row.find('td:eq(5)').text(d.details);
      row.find('td:eq(6)').text(d.status);
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    });
  });
  $('.delete-btn').click(function(){
    if(!confirm('Delete this item?'))return;
    var id=$(this).data('id');
    $.get('', {action:'delete', id:id, csrf:'<?= e(csrf_token()) ?>'}, function(){ $('#row'+id).remove(); });
  });
});
</script>
<?php endif; ?>

<?php app_footer(); ?>
