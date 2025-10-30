<?php
require_once '../config/db.php';

$action = $_POST['action'] ?? '';
if($action === 'add'){
    $name = trim($_POST['item_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? null; // stores category id (categories.id) or empty
    $cost = $_POST['cost'] ?? null;

    if(!$name){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

    // If category passed as id, store id or store name? Your items.category column previously stored varchar.
    // We'll store category as category id (int) if categories table exists; but your schema stored category varchar.
    // To remain compatible with schema (items.category varchar), accept category id and convert to name.
    $category_name = '';
    if($category){
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category]);
        $cat = $stmt->fetch();
        if($cat) $category_name = $cat['name'];
    }

    $stmt = $pdo->prepare("INSERT INTO items (item_name, description, category, cost) VALUES (?, ?, ?, ?)");
    $ok = $stmt->execute([$name, $desc, $category_name ? $category_name : null, $cost ? $cost : null]);

    if($ok){
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'item_name'=>$name,'description'=>$desc,'category_name'=>$category_name,'cost'=>$cost]);
    } else {
        echo json_encode(['success'=>false,'message'=>'DB insert failed']);
    }
    exit;
}

if($action === 'get'){
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if($item) echo json_encode(['success'=>true,'item'=>$item]);
    else echo json_encode(['success'=>false]);
    exit;
}

if($action === 'edit'){
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['item_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? null;
    $cost = $_POST['cost'] ?? null;

    // convert category id to name if provided
    $category_name = '';
    if($category){
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category]);
        $cat = $stmt->fetch();
        if($cat) $category_name = $cat['name'];
    }

    $stmt = $pdo->prepare("UPDATE items SET item_name = ?, description = ?, category = ?, cost = ? WHERE id = ?");
    $ok = $stmt->execute([$name, $desc, $category_name ? $category_name : null, $cost ? $cost : null, $id]);
    if($ok){
        echo json_encode(['success'=>true,'id'=>$id,'item_name'=>$name,'description'=>$desc,'category_name'=>$category_name,'cost'=>$cost]);
    } else echo json_encode(['success'=>false,'message'=>'Update failed']);
    exit;
}

if($action === 'delete'){
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['success'=> (bool)$ok]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
