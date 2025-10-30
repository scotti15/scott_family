<?php
require_once '../config/db.php';
include '../../../includes/header.php';
include '../includes/header.php';
?>

<div class="container mt-4">
  <h2>Manage Rooms</h2>

  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>Room Name</th><th>Action</th></tr></thead>
    <tbody>
      <tr>
        <td><input id="room_name" class="form-control"></td>
        <td><button id="addRoomBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <h4 class="mt-4">Rooms List</h4>
  <table id="roomsTable" class="table table-striped" style="width:100%">
    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead><tbody></tbody>
  </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  const table = $('#roomsTable').DataTable({ ajax:'fetch_rooms.php', columns:[{data:'id'},{data:'name'},{data:'actions',orderable:false,searchable:false}] });

  $('#addRoomBtn').click(function(){
    const name = $('#room_name').val().trim();
    if(!name) return alert('Name required');
    $.post('insert_room.php',{name}, function(resp){
      if(resp.status === 'success'){ table.ajax.reload(); $('#room_name').val(''); } else alert(resp.message);
    }, 'json');
  });

  $('#roomsTable').on('click','.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    const newName = prompt('Edit room name', row.name);
    if(newName && newName !== row.name){
      $.post('update_room.php',{id:row.id,name:newName}, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
    }
  });

  $('#roomsTable').on('click','.delete-btn', function(){
    if(!confirm('Delete room?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('delete_room.php',{id:row.id}, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
  });
});
</script>
