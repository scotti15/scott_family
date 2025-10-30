<?php
require_once '../config/db.php';
include '../../../includes/header.php';
include '../includes/header.php';

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
  <h2>Manage Items</h2>

  <!-- Add Item Form -->
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Name</th><th>Description</th><th>Category</th>
        <th>Cost</th><th>Current Value</th><th>Brand</th>
        <th>Serial Number</th><th>Purchase Date</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><input id="item_name" class="form-control"></td>
        <td><input id="description" class="form-control"></td>
        <td>
          <select id="category_id" class="form-select">
            <option value="">-- Select --</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input id="cost" type="number" step="0.01" class="form-control"></td>
        <td><input id="currentvalue" type="number" step="0.01" class="form-control"></td>
        <td><input id="brand" class="form-control"></td>
        <td><input id="serialnumber" class="form-control"></td>
        <td><input id="purchasedate" type="date" class="form-control"></td>
        <td><button id="addItemBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <!-- Items Table -->
  <table id="itemsTable" class="table table-striped table-bordered" style="width:100%">
    <thead class="table-dark">
      <tr>
        <th>ID</th><th>Name</th><th>Description</th><th>Category</th>
        <th>Cost</th><th>Current Value</th><th>Brand</th>
        <th>Serial Number</th><th>Purchase Date</th><th>Age</th>
        <th>Actions</th>
      </tr>
    </thead>
  </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id">
        <div class="mb-2">
          <label>Name</label>
          <input id="edit_item_name" class="form-control">
        </div>
        <div class="mb-2">
          <label>Description</label>
          <textarea id="edit_description" class="form-control"></textarea>
        </div>
        <div class="mb-2">
          <label>Category</label>
          <select id="edit_category_id" class="form-select">
            <option value="">-- Select --</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label>Cost</label>
          <input id="edit_cost" type="number" step="0.01" class="form-control">
        </div>
        <div class="mb-2">
          <label>Current Value</label>
          <input id="edit_currentvalue" type="number" step="0.01" class="form-control">
        </div>
        <div class="mb-2">
          <label>Brand</label>
          <input id="edit_brand" class="form-control">
        </div>
        <div class="mb-2">
          <label>Serial Number</label>
          <input id="edit_serialnumber" class="form-control">
        </div>
        <div class="mb-2">
          <label>Purchase Date</label>
          <input id="edit_purchasedate" type="date" class="form-control">
        </div>
        <div class="mb-2">
          <label>Age</label>
          <input id="edit_age" class="form-control" readonly>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="saveEditBtn" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function calculateAge(purchaseDate) {
  if (!purchaseDate) return '';
  const start = new Date(purchaseDate);
  if (isNaN(start)) return '';
  const now = new Date();
  let years = now.getFullYear() - start.getFullYear();
  let months = now.getMonth() - start.getMonth();
  let days = now.getDate() - start.getDate();

  if (days < 0) {
    months--;
    days += new Date(now.getFullYear(), now.getMonth(), 0).getDate();
  }
  if (months < 0) {
    years--;
    months += 12;
  }
  return `${years} years, ${months} months, ${days} days`;
}

$(function(){
  const table = $('#itemsTable').DataTable({
    ajax: 'ajax_item.php?action=list',
    columns: [
      {data:'id'},
      {data:'item_name'},
      {data:'description'},
      {data:'category_name'},
      {data:'cost'},
      {data:'currentvalue'},
      {data:'brand'},
      {data:'serialnumber'},
      {data:'purchasedate'},
      {data:null, render: row => calculateAge(row.purchasedate)},
      {data:'actions', orderable:false, searchable:false}
    ],
        columnDefs: [
        { targets: 10, width: '120px' } // make Actions column wider
    ],
    scrollX: true
  });

  $('#addItemBtn').click(function(){
    const data = {
      action: 'add',
      item_name: $('#item_name').val(),
      description: $('#description').val(),
      category_id: $('#category_id').val(),
      cost: $('#cost').val(),
      currentvalue: $('#currentvalue').val(),
      brand: $('#brand').val(),
      serialnumber: $('#serialnumber').val(),
      purchasedate: $('#purchasedate').val()
    };
    $.post('ajax_item.php', data, function(resp){
      if(resp.success){
        table.ajax.reload();
        $('input,select').val('');
      } else alert(resp.message);
    }, 'json');
  });

  $('#itemsTable').on('click','.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    $('#edit_id').val(row.id);
    $('#edit_item_name').val(row.item_name);
    $('#edit_description').val(row.description);
    $('#edit_category_id').val(row.category_id);
    $('#edit_cost').val(row.cost);
    $('#edit_currentvalue').val(row.currentvalue);
    $('#edit_brand').val(row.brand);
    $('#edit_serialnumber').val(row.serialnumber);
    $('#edit_purchasedate').val(row.purchasedate);
    $('#edit_age').val(calculateAge(row.purchasedate));
    $('#editModal').modal('show');
  });

  $('#edit_purchasedate').on('input', function(){
    $('#edit_age').val(calculateAge(this.value));
  });

  $('#saveEditBtn').click(function(){
    const data = {
      action: 'edit',
      id: $('#edit_id').val(),
      item_name: $('#edit_item_name').val(),
      description: $('#edit_description').val(),
      category_id: $('#edit_category_id').val(),
      cost: $('#edit_cost').val(),
      currentvalue: $('#edit_currentvalue').val(),
      brand: $('#edit_brand').val(),
      serialnumber: $('#edit_serialnumber').val(),
      purchasedate: $('#edit_purchasedate').val()
    };
    $.post('ajax_item.php', data, function(resp){
      if(resp.success){
        $('#editModal').modal('hide');
        table.ajax.reload();
      } else alert(resp.message);
    }, 'json');
  });

  $('#itemsTable').on('click','.delete-btn', function(){
    if(!confirm('Delete item?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('ajax_item.php', {action:'delete', id:row.id}, function(resp){
      if(resp.success) table.ajax.reload();
      else alert(resp.message);
    }, 'json');
  });
});
</script>
