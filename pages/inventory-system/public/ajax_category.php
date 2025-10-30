<?php
require_once '../config/db.php';

$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? null;
$name   = trim($_POST['name'] ?? '');

switch($action){
    case 'add':
        if(!$name){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
        $check = $pdo->prepare("SELECT id FROM categories WHERE name=?");
        $check->execute([$name]);
        if($check->fetch()){ echo json_encode(['success'=>false,'message'=>'Category exists']); exit; }
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'name'=>$name]);
        break;

    case 'edit':
        if(!$id || !$name){ echo json_encode(['success'=>false]); exit; }
        $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$name,$id]);
        echo json_encode(['success'=>true]);
        break;

    case 'delete':
        if(!$id){ echo json_encode(['success'=>false]); exit; }
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
}
