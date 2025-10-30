<?php
require_once '../config/db.php';

$action = $_REQUEST['action'] ?? '';

if($action === 'list'){
    $stmt = $pdo->query("
        SELECT i.*, c.name AS category_name
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.id
        ORDER BY i.item_name
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($items as &$item){
        $item['actions'] = '<button class="btn btn-sm btn-warning edit-btn">Edit</button> 
                            <button class="btn btn-sm btn-danger delete-btn">Delete</button>';
    }

    echo json_encode(['data'=>$items]);
    exit;
}

if($action === 'add'){
    $name = trim($_POST['item_name'] ?? '');
    if(!$name) { echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

    $stmt = $pdo->prepare("
        INSERT INTO items (item_name, description, category_id, cost, currentvalue, brand, serialnumber, purchasedate)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([
        $name,
        $_POST['description'] ?? null,
        $_POST['category_id'] ?: null,
        $_POST['cost'] ?: null,
        $_POST['currentvalue'] ?: null,
        $_POST['brand'] ?? '',
        $_POST['serialnumber'] ?? '',
        $_POST['purchasedate'] ?: null
    ]);

    echo json_encode(['success'=>$ok]);
    exit;
}

if($action === 'edit'){
    $id = (int)($_POST['id'] ?? 0);
    if(!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }

    $stmt = $pdo->prepare("
        UPDATE items
        SET item_name=?, description=?, category_id=?, cost=?, currentvalue=?, brand=?, serialnumber=?, purchasedate=?
        WHERE id=?
    ");
    $ok = $stmt->execute([
        $_POST['item_name'] ?? '',
        $_POST['description'] ?? null,
        $_POST['category_id'] ?: null,
        $_POST['cost'] ?: null,
        $_POST['currentvalue'] ?: null,
        $_POST['brand'] ?? '',
        $_POST['serialnumber'] ?? '',
        $_POST['purchasedate'] ?: null,
        $id
    ]);

    echo json_encode(['success'=>$ok]);
    exit;
}

if($action === 'delete'){
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['success'=>$ok]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
