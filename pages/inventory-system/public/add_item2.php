<?php
require_once '../config/db.php';
include '../includes/header.php';

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h2>Manage Items</h2>

  <!-- Add Item Form Row -->
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Category</th>
        <th>Cost</th>
        <th>Current Value</th>
        <th>Brand</th>
        <th>Serial #</th>
        <th>Purchase Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><input id="item_name" class="form-control" /></td>
        <td><input id="item_description" class="form-control" /></td>
        <td>
          <select id="item_category_id" class="form-select">
            <option value="">(none)</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input id="item_cost" type="number" step="0.01" class="form-control" /></td>
        <td><input id="item_currentvalue" type="number" step="0.01" class="form-control" /></td>
        <td><input id="item_brand" class="form-control" /></td>
        <td><input id="item_serialnumber" class="form-control" /></td>
        <td><input id="item_purchasedate" type="date" class="form-control" /></td>
        <td><button id="addItemBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <!-- Items Table -->
  <h4 class="mt-4">Items List</h4>
  <table id="itemsTable" class="table table-striped" style="width:100%">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Category</th>
        <th>Cost</th>
        <th>Current Value</th>
        <th>Brand</th>
        <th>Serial #</th>
        <th>Purchase Date</th>
        <th>Age</th> <!-- New column -->
        <th>Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="editItemForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Item</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_item_id" name="id">
        <div class="mb-2"><label>Name</label>
          <input id="edit_item_name" name="item_name" class="form-control" required>
        </div>
        <div class="mb-2"><label>Description</label>
          <input id="edit_item_description" name="description" class="form-control">
        </div>
        <div class="mb-2"><label>Category</label>
          <select id="edit_item_category_id" name="category_id" class="form-select">
            <option value="">(none)</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label>Cost</label>
          <input id="edit_item_cost" name="cost" type="number" step="0.01" class="form-control">
        </div>
        <div class="mb-2"><label>Current Value</label>
          <input id="edit_item_currentvalue" name="currentvalue" type="number" step="0.01" class="form-control">
        </div>
        <div class="mb-2"><label>Brand</label>
          <input id="edit_item_brand" name="brand" class="form-control">
        </div>
        <div class="mb-2"><label>Serial #</label>
          <input id="edit_item_serialnumber" name="serialnumber" class="form-control">
        </div>
        <div class="mb-2"><label>Purchase Date</label>
          <input id="edit_item_purchasedate" name="purchasedate" type="date" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- DataTables + jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
const table = $('#itemsTable').DataTable({
  ajax: 'ajax_item.php?action=list',
  columns: [
    { data: 'id' },
    { data: 'item_name' },
    { data: 'description' },
    { data: 'category_name' },
    { data: 'cost' },
    { data: 'currentvalue' },
    { data: 'brand' },
    { data: 'serialnumber' },
    { data: 'purchasedate' },
    {
      data: 'purchasedate',
      render: function(data) {
        if (!data || data === '0000-00-00') return ''; // blank if no date
        const purchaseDate = new Date(data);
        if (isNaN(purchaseDate.getTime())) return ''; // blank if invalid date
        const now = new Date();

        let years = now.getFullYear() - purchaseDate.getFullYear();
        let months = now.getMonth() - purchaseDate.getMonth();
        let days = now.getDate() - purchaseDate.getDate();

        if (days < 0) {
          months--;
          const prevMonth = new Date(now.getFullYear(), now.getMonth(), 0).getDate();
          days += prevMonth;
        }
        if (months < 0) {
          years--;
          months += 12;
        }

        return `${years} years, ${months} months, ${days} days`;
      }
    },
    { data: 'actions', orderable:false, searchable:false }
  ],
  order: [[1,'asc']]
});

  // Add item
  $('#addItemBtn').click(function(){
    const payload = {
      action: 'add',
      item_name: $('#item_name').val().trim(),
      description: $('#item_description').val().trim(),
      category_id: $('#item_category_id').val(),
      cost: $('#item_cost').val(),
      currentvalue: $('#item_currentvalue').val(),
      brand: $('#item_brand').val().trim(),
      serialnumber: $('#item_serialnumber').val().trim(),
      purchasedate: $('#item_purchasedate').val()
    };
    if(!payload.item_name){ return alert('Name required'); }
    $.post('ajax_item.php', payload, function(resp){
      if(resp.success){
        table.ajax.reload();
        $('#item_name,#item_description,#item_category_id,#item_cost,#item_currentvalue,#item_brand,#item_serialnumber,#item_purchasedate').val('');
      } else {
        alert(resp.message || 'Add failed');
      }
    }, 'json');
  });

  // Edit button
  $('#itemsTable').on('click', '.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    $('#edit_item_id').val(row.id);
    $('#edit_item_name').val(row.item_name);
    $('#edit_item_description').val(row.description);
    $('#edit_item_category_id').val(row.category_id);
    $('#edit_item_cost').val(row.cost);
    $('#edit_item_currentvalue').val(row.currentvalue);
    $('#edit_item_brand').val(row.brand);
    $('#edit_item_serialnumber').val(row.serialnumber);
    $('#edit_item_purchasedate').val(row.purchasedate);
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
  });

  // Save edit
  $('#editItemForm').submit(function(e){
    e.preventDefault();
    const data = $(this).serialize() + '&action=edit';
    $.post('ajax_item.php', data, function(resp){
      if(resp.success){
        table.ajax.reload();
        bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
      } else {
        alert(resp.message || 'Update failed');
      }
    }, 'json');
  });

  // Delete button
  $('#itemsTable').on('click', '.delete-btn', function(){
    if(!confirm('Delete this item?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('ajax_item.php', { action:'delete', id: row.id }, function(resp){
      if(resp.success) table.ajax.reload();
      else alert(resp.message || 'Delete failed');
    }, 'json');
  });
});
</script>
