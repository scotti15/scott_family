<?php
require_once '../config/db.php';

$action = $_REQUEST['action'] ?? '';

if($action === 'list'){
    $stmt = $pdo->query("SELECT i.*, c.name AS category_name 
                         FROM items i 
                         LEFT JOIN categories c ON i.category_id = c.id 
                         ORDER BY i.id DESC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($items as &$row){
        $row['actions'] = '<button class="btn btn-sm btn-warning edit-btn">Edit</button>
                           <button class="btn btn-sm btn-danger delete-btn">Delete</button>';
    }
    echo json_encode(['data'=>$items]);
    exit;
}

if($action === 'add'){
    $sql = "INSERT INTO items (item_name, description, category_id, cost, currentvalue, brand, serialnumber, purchasedate)
            VALUES (?,?,?,?,?,?,?,?)";
    $ok = $pdo->prepare($sql)->execute([
        $_POST['item_name'], $_POST['description'], $_POST['category_id'] ?: null,
        $_POST['cost'] ?: null, $_POST['currentvalue'] ?: null,
        $_POST['brand'], $_POST['serialnumber'], $_POST['purchasedate'] ?: null
    ]);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'':'Insert failed']);
    exit;
}

if($action === 'edit'){
    $sql = "UPDATE items SET item_name=?, description=?, category_id=?, cost=?, currentvalue=?, brand=?, serialnumber=?, purchasedate=? WHERE id=?";
    $ok = $pdo->prepare($sql)->execute([
        $_POST['item_name'], $_POST['description'], $_POST['category_id'] ?: null,
        $_POST['cost'] ?: null, $_POST['currentvalue'] ?: null,
        $_POST['brand'], $_POST['serialnumber'], $_POST['purchasedate'] ?: null,
        $_POST['id']
    ]);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'':'Update failed']);
    exit;
}

if($action === 'delete'){
    $ok = $pdo->prepare("DELETE FROM items WHERE id=?")->execute([$_POST['id']]);
    echo json_encode(['success'=>$ok]);
    exit;
}
if($action === 'update'){
    $stmt = $pdo->prepare("UPDATE items SET item_name=?, description=?, category_id=?, cost=?, currentvalue=?, brand=?, serialnumber=?, purchasedate=? WHERE id=?");
    $ok = $stmt->execute([
        $_POST['item_name'],
        $_POST['description'],
        $_POST['category_id'] ?: null,
        $_POST['cost'] ?: null,
        $_POST['currentvalue'] ?: null,
        $_POST['brand'],
        $_POST['serialnumber'],
        $_POST['purchasedate'] ?: null,
        $_POST['id']
    ]);
    echo json_encode(['success'=>$ok, 'message'=>$ok?'':'Update failed']);
    exit;
}


echo json_encode(['success'=>false,'message'=>'Invalid action']);
