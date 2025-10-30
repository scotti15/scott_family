<?php
require_once '../config/db.php';
include '../../../includes/header.php';
include '../includes/header.php';
$rooms = $pdo->query("SELECT id,name FROM rooms ORDER BY name")->fetchAll();
?>

<div class="container mt-4">
  <h2>Manage Locations</h2>

  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>Location Name</th><th>Description</th><th>Room</th><th>Action</th></tr></thead>
    <tbody>
      <tr>
        <td><input id="location_name" class="form-control"></td>
        <td><input id="location_description" class="form-control"></td>
        <td>
          <select id="location_room_id" class="form-select">
            <option value="">Select room</option>
            <?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach;?>
          </select>
        </td>
        <td><button id="addLocationBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <h4 class="mt-4">Locations List</h4>
  <table id="locationsTable" class="table table-striped" style="width:100%">
    <thead class="table-dark"><tr><th>ID</th><th>Location</th><th>Description</th><th>Room</th><th>Actions</th></tr></thead><tbody></tbody>
  </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  const table = $('#locationsTable').DataTable({ ajax:'fetch_locations.php', columns:[{data:'id'},{data:'location_name'},{data:'description'},{data:'room_name'},{data:'actions',orderable:false,searchable:false}] });

  $('#addLocationBtn').click(function(){
    const payload = { location_name: $('#location_name').val().trim(), description: $('#location_description').val().trim(), room_id: $('#location_room_id').val() };
    if(!payload.location_name || !payload.room_id) return alert('Name and room required');
    $.post('insert_location.php', payload, function(resp){ if(resp.status==='success'){ table.ajax.reload(); $('#location_name,#location_description,#location_room_id').val(''); } else alert(resp.message); }, 'json');
  });

  $('#locationsTable').on('click','.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    const newName = prompt('Location name:', row.location_name);
    if(!newName) return;
    const newDesc = prompt('Description (leave empty to keep):', row.description || '');
    const newRoom = prompt('Room id (current: '+(row.room_id||'')+'):','');
    $.post('update_location.php',{ id: row.id, location_name:newName, description:newDesc, room_id: newRoom }, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
  });

  $('#locationsTable').on('click','.delete-btn', function(){
    if(!confirm('Delete location?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('delete_location.php',{id:row.id}, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
  });
});
</script>
