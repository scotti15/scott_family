<?php

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$entryId = $_POST['entry_id'] ?? '';
$text = $_POST['text'] ?? '';

$response = ['success' => false];

if ($entryId === '') {
    $response['error'] = 'Subtitle entry ID is required.';
    echo json_encode($response);
    exit;
}

$stmt = $pdo->prepare(
    "UPDATE subtitle_entries
     SET text = ?
     WHERE entry_id = ?"
);

$stmt->execute([$text, $entryId]);

$response['success'] = true;

$response['updated'] = $stmt->rowCount();

echo json_encode($response);