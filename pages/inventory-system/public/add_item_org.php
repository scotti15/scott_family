<?php
require_once '../config/db.php';
include '../../../includes/header.php';
include '../includes/header.php';

// categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>

<div class="container mt-4">
  <h2>Manage Items</h2>

  <table class="table table-bordered">
    <thead class="table-dark">
      <tr><th>Item Name</th><th>Description</th><th>Category</th><th>Cost</th><th>Action</th></tr>
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
        <td><button id="addItemBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <h4 class="mt-4">Items List</h4>
  <table id="itemsTable" class="table table-striped" style="width:100%">
    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Description</th><th>Category</th><th>Cost</th><th>Actions</th></tr></thead>
    <tbody></tbody>
  </table>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editItemForm" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Item</h5></div>
      <div class="modal-body">
        <input type="hidden" id="edit_item_id" name="id">
        <div class="mb-2"><label>Name</label><input id="edit_item_name" name="item_name" class="form-control" required></div>
        <div class="mb-2"><label>Description</label><input id="edit_item_description" name="description" class="form-control"></div>
        <div class="mb-2">
          <label>Category</label>
          <select id="edit_item_category_id" name="category_id" class="form-select">
            <option value="">(none)</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label>Cost</label><input id="edit_item_cost" name="cost" type="number" step="0.01" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- DataTables and AJAX logic -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  const table = $('#itemsTable').DataTable({
    ajax: 'fetch_items.php',
    columns: [
      { data: 'id' },
      { data: 'item_name' },
      { data: 'description' },
      { data: 'category_name' },
      { data: 'cost' },
      { data: 'actions', orderable:false, searchable:false }
    ],
    order: [[1,'asc']]
  });

  $('#addItemBtn').click(function(){
    const payload = {
      item_name: $('#item_name').val().trim(),
      description: $('#item_description').val().trim(),
      category_id: $('#item_category_id').val(),
      cost: $('#item_cost').val()
    };
    if(!payload.item_name){ return alert('Name required'); }
    $.post('insert_item.php', payload, function(resp){
      if(resp.status === 'success'){ table.ajax.reload(); $('#item_name,#item_description,#item_category_id,#item_cost').val(''); }
      else alert(resp.message || 'Add failed');
    }, 'json');
  });

  $('#itemsTable').on('click', '.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    $('#edit_item_id').val(row.id);
    $('#edit_item_name').val(row.item_name);
    $('#edit_item_description').val(row.description);
    $('#edit_item_category_id').val(row.category_id);
    $('#edit_item_cost').val(row.cost);
    new bootstrap.Modal(document.getElementById('editItemModal')).show();
  });

  $('#editItemForm').submit(function(e){
    e.preventDefault();
    const data = $(this).serialize() + '&action=update';
    $.post('update_item.php', data, function(resp){
      if(resp.status === 'success'){ table.ajax.reload(); bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide(); }
      else alert(resp.message || 'Update failed');
    }, 'json');
  });

  $('#itemsTable').on('click', '.delete-btn', function(){
    if(!confirm('Delete this item?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('delete_item.php', { id: row.id }, function(resp){
      if(resp.status === 'success') table.ajax.reload();
      else alert(resp.message || 'Delete failed');
    }, 'json');
  });
});
</script>
