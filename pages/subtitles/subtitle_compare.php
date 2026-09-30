<?php

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$response = [
    'success' => false
];

$projectId = $_POST['project_id'] ?? '';
$languageId = $_POST['language_id'] ?? '';


// --------------------------------
// Validate input
// --------------------------------

if ($projectId === '' || $languageId === '') {
    $response['error'] = 'Project and language are required.';
    echo json_encode($response);
    exit;
}


// --------------------------------
// Verify language belongs to project
// --------------------------------

$stmt = $pdo->prepare(
    "SELECT language_id, language_name
     FROM subtitle_languages
     WHERE language_id = ?
       AND project_id = ?"
);

$stmt->execute([
    $languageId,
    $projectId
]);

$language = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$language) {
    $response['error'] = 'The selected language does not belong to this project.';
    echo json_encode($response);
    exit;
}


// --------------------------------
// Get subtitle entries
// --------------------------------

$stmt = $pdo->prepare(
    "SELECT
        entry_id,
        subtitle_number,
        start_time,
        end_time,
        text
     FROM subtitle_entries
     WHERE language_id = ?
     ORDER BY subtitle_number"
);

$stmt->execute([$languageId]);

$subtitles = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --------------------------------
// Return result
// --------------------------------

$response['success'] = true;
$response['language_id'] = $language['language_id'];
$response['language_name'] = $language['language_name'];
$response['subtitles'] = $subtitles;

echo json_encode($response);