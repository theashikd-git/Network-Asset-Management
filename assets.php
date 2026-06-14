<?php
require_once __DIR__ . '/auth.php';
require_login();

$can_edit = is_admin();   // regular users are read-only

/* ================= AJAX UPDATE ================= */
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    require_admin_for_write();
    check_csrf();
    $stmt = mysqli_prepare($conn, "UPDATE assets SET product_name=?, category=?, quantity=?, department=?, place=?, warranty_start=?, warranty_end=?, note=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssisssssi',
        $_POST['product_name'], $_POST['category'], $_POST['quantity'], $_POST['department'],
        $_POST['place'], $_POST['warranty_start'], $_POST['warranty_end'], $_POST['note'], $_POST['id']);
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
    $stmt = mysqli_prepare($conn, "DELETE FROM assets WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success']);
    exit;
}

/* ================= ADD ASSET ================= */
if (isset($_POST['save_asset'])) {
    require_admin_for_write();
    check_csrf();
    $stmt = mysqli_prepare($conn, "INSERT INTO assets (product_name, category, quantity, department, place, warranty_start, warranty_end, note) VALUES (?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssisssss',
        $_POST['product_name'], $_POST['category'], $_POST['quantity'], $_POST['department'],
        $_POST['place'], $_POST['warranty_start'], $_POST['warranty_end'], $_POST['note']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: assets.php");
    exit;
}

/* ================= ADD CATEGORY / DEPARTMENT / PLACE ================= */
function add_lookup($conn, $table, $value) {
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO $table (name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, 's', $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
if (isset($_POST['save_category']))   { require_admin_for_write(); check_csrf(); add_lookup($conn,'categories',$_POST['new_category']);   header("Location: assets.php"); exit; }
if (isset($_POST['save_department'])) { require_admin_for_write(); check_csrf(); add_lookup($conn,'departments',$_POST['new_department']); header("Location: assets.php"); exit; }
if (isset($_POST['save_place']))      { require_admin_for_write(); check_csrf(); add_lookup($conn,'places',$_POST['new_place']);          header("Location: assets.php"); exit; }

$assets = mysqli_query($conn, "SELECT * FROM assets ORDER BY id ASC");

require_once __DIR__ . '/layout.php';
app_header('Asset Management', 'assets');
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<?php if (!$can_edit): ?>
  <div class="readonly-note">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm3 8H9V6a3 3 0 0 1 6 0v3z"/></svg>
    You are signed in as a read-only user. Only administrators can add, edit or delete assets.
  </div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <?php if ($can_edit): ?>
  <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addForm">+ Add Asset</button>
  <?php endif; ?>
</div>

<?php if ($can_edit): ?>
<div class="collapse mb-4" id="addForm">
<form method="POST" class="card p-4">
  <?= csrf_field() ?>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label>Product Name</label><input type="text" name="product_name" class="form-control" required></div>
    <div class="col-md-4"><label>Quantity</label><input type="number" name="quantity" class="form-control" required></div>
    <div class="col-md-4"><label>Warranty Start</label><input type="date" name="warranty_start" class="form-control"></div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label>Category</label>
      <div class="d-flex gap-2">
        <select name="category" class="form-select" required>
          <option value="">Select</option>
          <?php $c = mysqli_query($conn,"SELECT name FROM categories"); while($x=mysqli_fetch_assoc($c)) echo "<option>".e($x['name'])."</option>"; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#catBox">+</button>
      </div>
      <div class="collapse mt-2" id="catBox"><div class="d-flex gap-2">
        <input type="text" name="new_category" class="form-control" placeholder="New category">
        <button class="btn btn-success" name="save_category" formnovalidate>Save</button>
      </div></div>
    </div>
    <div class="col-md-4">
      <label>Department</label>
      <div class="d-flex gap-2">
        <select name="department" class="form-select" required>
          <option value="">Select</option>
          <?php $d = mysqli_query($conn,"SELECT name FROM departments"); while($x=mysqli_fetch_assoc($d)) echo "<option>".e($x['name'])."</option>"; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#depBox">+</button>
      </div>
      <div class="collapse mt-2" id="depBox"><div class="d-flex gap-2">
        <input type="text" name="new_department" class="form-control" placeholder="New department">
        <button class="btn btn-success" name="save_department" formnovalidate>Save</button>
      </div></div>
    </div>
    <div class="col-md-4">
      <label>Place</label>
      <div class="d-flex gap-2">
        <select name="place" class="form-select" required>
          <option value="">Select</option>
          <?php $p = mysqli_query($conn,"SELECT name FROM places"); while($x=mysqli_fetch_assoc($p)) echo "<option>".e($x['name'])."</option>"; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#placeBox">+</button>
      </div>
      <div class="collapse mt-2" id="placeBox"><div class="d-flex gap-2">
        <input type="text" name="new_place" class="form-control" placeholder="New place">
        <button class="btn btn-success" name="save_place" formnovalidate>Save</button>
      </div></div>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6"><label>Warranty End</label><input type="date" name="warranty_end" class="form-control"></div>
    <div class="col-md-6"><label>Note</label><textarea name="note" class="form-control" rows="1"></textarea></div>
  </div>
  <div><button class="btn btn-success" name="save_asset">Save Asset</button></div>
</form>
</div>
<?php endif; ?>

<div class="table-wrap">
<table class="table align-middle">
<thead>
<tr>
  <th>ID</th><th>Product</th><th>Category</th><th>Qty</th>
  <th>Department</th><th>Place</th><th>W.Start</th><th>W.End</th>
  <th>Note</th><?php if($can_edit):?><th class="text-end">Action</th><?php endif;?>
</tr>
</thead>
<tbody>
<?php while ($a = mysqli_fetch_assoc($assets)): ?>
<tr id="row<?= (int)$a['id'] ?>">
  <td class="text-muted">#<?= (int)$a['id'] ?></td>
  <td><strong><?= e($a['product_name']) ?></strong></td>
  <td><?= e($a['category']) ?></td>
  <td><?= (int)$a['quantity'] ?></td>
  <td><?= e($a['department']) ?></td>
  <td><?= e($a['place']) ?></td>
  <td><?= e($a['warranty_start']) ?></td>
  <td><?= e($a['warranty_end']) ?></td>
  <td><?= e($a['note']) ?></td>
  <?php if ($can_edit): ?>
  <td class="text-end">
    <button class="btn btn-warning btn-sm edit-btn" data-id="<?= (int)$a['id'] ?>">Edit</button>
    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= (int)$a['id'] ?>">Delete</button>
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
  <div class="modal-header"><h5 class="modal-title">Edit Asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" id="edit_id">
    <label>Product</label><input class="form-control mb-2" name="product_name" id="edit_product">
    <label>Category</label><input class="form-control mb-2" name="category" id="edit_category">
    <label>Quantity</label><input class="form-control mb-2" name="quantity" id="edit_qty">
    <label>Department</label><input class="form-control mb-2" name="department" id="edit_department">
    <label>Place</label><input class="form-control mb-2" name="place" id="edit_place">
    <label>Warranty Start</label><input type="date" class="form-control mb-2" name="warranty_start" id="edit_ws">
    <label>Warranty End</label><input type="date" class="form-control mb-2" name="warranty_end" id="edit_we">
    <label>Note</label><textarea class="form-control" name="note" id="edit_note"></textarea>
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
    $('#edit_category').val(row.find('td:eq(2)').text());
    $('#edit_qty').val(row.find('td:eq(3)').text());
    $('#edit_department').val(row.find('td:eq(4)').text());
    $('#edit_place').val(row.find('td:eq(5)').text());
    $('#edit_ws').val(row.find('td:eq(6)').text());
    $('#edit_we').val(row.find('td:eq(7)').text());
    $('#edit_note').val(row.find('td:eq(8)').text());
    new bootstrap.Modal(document.getElementById('editModal')).show();
  });
  $('#editForm').submit(function(e){
    e.preventDefault();
    $.post('', $(this).serialize()+'&action=update', function(res){
      var d=JSON.parse(res); var row=$('#row'+d.id);
      row.find('td:eq(1)').html('<strong>'+$('<div>').text(d.product_name).html()+'</strong>');
      row.find('td:eq(2)').text(d.category);
      row.find('td:eq(3)').text(d.quantity);
      row.find('td:eq(4)').text(d.department);
      row.find('td:eq(5)').text(d.place);
      row.find('td:eq(6)').text(d.warranty_start);
      row.find('td:eq(7)').text(d.warranty_end);
      row.find('td:eq(8)').text(d.note);
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    });
  });
  $('.delete-btn').click(function(){
    if(!confirm('Delete this asset?'))return;
    var id=$(this).data('id');
    $.get('', {action:'delete', id:id, csrf:'<?= e(csrf_token()) ?>'}, function(){ $('#row'+id).remove(); });
  });
});
</script>
<?php endif; ?>

<?php app_footer(); ?>
