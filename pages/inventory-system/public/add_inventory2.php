<?php
require_once '../config/db.php';
include '../includes/header.php';

// Fetch items & rooms for dropdowns
$items = $pdo->query("SELECT id, item_name FROM items ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
  <h2>Manage Inventory</h2>

  <!-- Add inventory row -->
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Item</th>
        <th>Room</th>
        <th>Location</th>
        <th>Quantity</th>
        <th>Entry Date</th>
        <th>Expiry Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <select id="item_id" class="form-select">
            <option value="">Select Item</option>
            <?php foreach($items as $i): ?>
              <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['item_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select id="room_id" class="form-select">
            <option value="">Select Room</option>
            <?php foreach($rooms as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select id="location_id" class="form-select">
            <option value="">Select Location</option>
          </select>
        </td>
        <td><input type="number" id="quantity" class="form-control" value="1"></td>
        <td><input type="datetime-local" id="entry_date" class="form-control"></td>
        <td><input type="datetime-local" id="expiry_date" class="form-control"></td>
        <td><button id="addInventoryBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <h4 class="mt-4">Inventory List</h4>
  <table id="inventoryTable" class="table table-striped" style="width:100%">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Item</th>
        <th>Room</th>
        <th>Location</th>
        <th>Quantity</th>
        <th>Entry Date</th>
        <th>Expiry Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Edit / Move Modal -->
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalTitle">Edit / Move Inventory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id">
        <div class="mb-2">
          <label>Quantity</label>
          <input type="number" id="edit_quantity" class="form-control">
        </div>
        <div class="mb-2">
          <label>Expiry Date</label>
          <input type="datetime-local" id="edit_expiry_date" class="form-control">
        </div>
        <div class="mb-2">
          <label>Room</label>
          <select id="edit_room_id" class="form-select">
            <option value="">Select Room</option>
            <?php foreach($rooms as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label>Location</label>
          <select id="edit_location_id" class="form-select">
            <option value="">Select Location</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="saveEditBtn" class="btn btn-success">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){
  const table = $('#inventoryTable').DataTable({
    ajax: { url: 'ajax_inventory.php', type: 'POST', data: { action: 'list' } },
    columns: [
      { data: 'id' },
      { data: 'item_name' },
      { data: 'room_name' },
      { data: 'location_name' },
      { data: 'quantity' },
      { data: 'entry_date' },
      { data: 'expiry_date' },
      { 
        data: null,
        orderable: false,
        searchable: false,
        render: function(_, __, row) {
          let icon = '';
          if (row.expiry_date) {
            const expiry = new Date(row.expiry_date.replace(' ', 'T'));
            const today = new Date();
            expiry.setHours(0,0,0,0);
            today.setHours(0,0,0,0);
            if ((expiry - today) / (1000*60*60*24) <= 0) {
              icon = ' <span title="Expired" style="color:red;">&#9888;</span>';
            }
          }
          return `<button class="btn btn-sm btn-warning edit-btn">Edit</button>
                  <button class="btn btn-sm btn-info move-btn">Move</button>
                  <button class="btn btn-sm btn-danger delete-btn">Delete</button>` + icon;
        }
      }
    ]
  });

  function loadLocations(roomId, locationSelect, selectedLocationId) {
    $(locationSelect).empty().append('<option value="">Select Location</option>');
    if(roomId){
      $.post('ajax_inventory.php', { action: 'get_locations', room_id: roomId }, function(data){
        if(data.success){
          data.locations.forEach(loc => {
            $(locationSelect).append(`<option value="${loc.id}">${loc.location_name}</option>`);
          });
          if(selectedLocationId) $(locationSelect).val(selectedLocationId);
        }
      }, 'json');
    }
  }

  $('#room_id').change(function(){
    loadLocations($(this).val(), '#location_id');
  });

  $('#edit_room_id').change(function(){
    loadLocations($(this).val(), '#edit_location_id');
  });

  $('#addInventoryBtn').click(function(){
    const payload = {
      action: 'add',
      item_id: $('#item_id').val(),
      room_id: $('#room_id').val(),
      location_id: $('#location_id').val(),
      quantity: $('#quantity').val(),
      entry_date: $('#entry_date').val(),
      expiry_date: $('#expiry_date').val()
    };
    if(!payload.item_id || !payload.room_id || !payload.quantity) return alert('Item, Room, and Quantity are required');
    $.post('ajax_inventory.php', payload, function(resp){
      if(resp.success){
        table.ajax.reload();
        $('#item_id, #room_id, #location_id').val('');
        $('#quantity').val(1);
        $('#entry_date, #expiry_date').val('');
      } else {
        alert(resp.message || 'Error adding inventory');
      }
    }, 'json');
  });

  $('#inventoryTable').on('click', '.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    $('#editModalTitle').text('Edit Inventory');
    $('#edit_id').val(row.id);
    $('#edit_quantity').val(row.quantity).prop('disabled', false);
    $('#edit_expiry_date').val(row.expiry_date ? row.expiry_date.replace(' ', 'T') : '').prop('disabled', false);
    $('#edit_room_id').val(row.room_id);
    loadLocations(row.room_id, '#edit_location_id', row.location_id);
    $('#saveEditBtn').data('mode', 'edit');
    $('#editInventoryModal').modal('show');
  });

  $('#inventoryTable').on('click', '.move-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    $('#editModalTitle').text('Move Inventory');
    $('#edit_id').val(row.id);
    $('#edit_quantity').val(row.quantity).prop('disabled', true);
    $('#edit_expiry_date').val(row.expiry_date ? row.expiry_date.replace(' ', 'T') : '').prop('disabled', true);
    $('#edit_room_id').val(row.room_id);
    loadLocations(row.room_id, '#edit_location_id', row.location_id);
    $('#saveEditBtn').data('mode', 'move');
    $('#editInventoryModal').modal('show');
  });

  $('#saveEditBtn').click(function(){
    const mode = $(this).data('mode') || 'edit';
    const payload = {
      action: mode,
      id: $('#edit_id').val(),
      room_id: $('#edit_room_id').val(),
      location_id: $('#edit_location_id').val()
    };
    if(mode === 'edit'){
      payload.quantity = $('#edit_quantity').val();
      payload.expiry_date = $('#edit_expiry_date').val();
    }
    $.post('ajax_inventory.php', payload, function(resp){
      if(resp.success){
        $('#editInventoryModal').modal('hide');
        table.ajax.reload();
      } else {
        alert(resp.message || 'Error updating inventory');
      }
    }, 'json');
  });

  $('#inventoryTable').on('click', '.delete-btn', function(){
    if(!confirm('Delete this inventory entry?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('ajax_inventory.php', { action: 'delete', id: row.id }, function(resp){
      if(resp.success) table.ajax.reload();
      else alert(resp.message);
    }, 'json');
  });
});
</script>
