<?php
require_once '../config/db.php';
include '../../../includes/header.php';
include '../includes/header.php';
?>

<div class="container mt-4">
  <h2>Manage Categories</h2>

  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>Category Name</th><th>Action</th></tr></thead>
    <tbody>
      <tr>
        <td><input id="category_name" class="form-control"></td>
        <td><button id="addCategoryBtn" class="btn btn-primary">Add</button></td>
      </tr>
    </tbody>
  </table>

  <h4 class="mt-4">Categories List</h4>
  <table id="categoriesTable" class="table table-striped" style="width:100%">
    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead><tbody></tbody>
  </table>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(function(){
  const table = $('#categoriesTable').DataTable({ ajax:'fetch_categories.php', columns:[{data:'id'},{data:'name'},{data:'actions',orderable:false,searchable:false}] });

  $('#addCategoryBtn').click(function(){
    const name = $('#category_name').val().trim(); if(!name) return alert('Name required');
    $.post('insert_category.php',{name}, function(resp){ if(resp.status==='success'){ table.ajax.reload(); $('#category_name').val(''); } else alert(resp.message); }, 'json');
  });

  $('#categoriesTable').on('click','.edit-btn', function(){
    const row = table.row($(this).closest('tr')).data();
    const newName = prompt('Edit category name', row.name);
    if(newName && newName !== row.name){
      $.post('update_category.php',{id:row.id,name:newName}, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
    }
  });

  $('#categoriesTable').on('click','.delete-btn', function(){
    if(!confirm('Delete category?')) return;
    const row = table.row($(this).closest('tr')).data();
    $.post('delete_category.php',{id:row.id}, function(resp){ if(resp.status==='success') table.ajax.reload(); else alert(resp.message); }, 'json');
  });
});
</script>
