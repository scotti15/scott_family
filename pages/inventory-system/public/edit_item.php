<?php
require_once '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) die("Invalid ID");

$item = $pdo->prepare("SELECT * FROM items WHERE id=?");
$item->execute([$id]);
$item = $item->fetch(PDO::FETCH_ASSOC);
if(!$item) die("Item not found");

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<form id="editItemForm">
    <input type="hidden" name="id" value="<?=$id?>">
    <div class="mb-3">
        <label>Item Name</label>
        <input name="item_name" class="form-control" value="<?=htmlspecialchars($item['item_name'])?>" required>
    </div>
    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"><?=htmlspecialchars($item['description'])?></textarea>
    </div>
    <div class="mb-3">
        <label>Category</label>
        <select name="category_id" class="form-control">
            <option value="">Select Category</option>
            <?php foreach($cats as $c): ?>
                <option value="<?=$c['id']?>" <?=$c['id']==$item['category_id']?'selected':''?>><?=$c['name']?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Cost</label>
        <input type="number" step="0.01" name="cost" class="form-control" value="<?=$item['cost']?>">
    </div>
    <div class="mb-3">
        <label>Current Value</label>
        <input type="number" step="0.01" name="currentvalue" class="form-control" value="<?=$item['currentvalue']?>">
    </div>
    <div class="mb-3">
        <label>Brand</label>
        <input name="brand" class="form-control" value="<?=htmlspecialchars($item['brand'])?>">
    </div>
    <div class="mb-3">
        <label>Serial Number</label>
        <input name="serialnumber" class="form-control" value="<?=htmlspecialchars($item['serialnumber'])?>">
    </div>
    <div class="mb-3">
        <label>Purchase Date</label>
        <input type="date" name="purchasedate" class="form-control" value="<?=$item['purchasedate']?>">
    </div>
    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
    </div>
</form>

<script>
$('#editItemForm').submit(function(e){
    e.preventDefault();
    $.post('ajax_item.php?action=update', $(this).serialize(), function(resp){
        if(resp.success){
            $('#editModal').modal('hide');
            $('#itemsTable').DataTable().ajax.reload();
        } else {
            alert(resp.message || 'Update failed');
        }
    }, 'json');
});
</script>
