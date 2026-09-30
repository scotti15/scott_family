<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/subtitle_functions.php';

session_start();

$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$projectId = $_POST['project_id'] ?? '';
$projectTitle = trim($_POST['project_title'] ?? '');
$languageCode = $_POST['language_code'] ?? '';

$languageNames = [
    'en' => 'English',
    'fr' => 'French',
    'de' => 'German',
    'es' => 'Spanish'
];

$languageName = $languageNames[$languageCode] ?? 'Unknown';

$fileName = $_FILES['subtitle_file']['name'] ?? '';


// --------------------------------
// Read and parse uploaded file
// --------------------------------

$parsedEntries = [];

if (
    isset($_FILES['subtitle_file']) &&
    $_FILES['subtitle_file']['error'] === UPLOAD_ERR_OK
) {

    $parsedEntries = parseSrtFile(
        $_FILES['subtitle_file']['tmp_name']
    );
}


// --------------------------------
// Save everything in one transaction
// --------------------------------

try {

    $pdo->beginTransaction();


    // --------------------------------
    // 1. Create or select project
    // --------------------------------

    if ($projectId === 'new') {

        if ($projectTitle === '') {
            throw new Exception(
                'A project title is required for a new project.'
            );
        }

        $stmt = $pdo->prepare(
            "INSERT INTO subtitle_projects (name)
             VALUES (?)"
        );

        $stmt->execute([$projectTitle]);

        $projectId = $pdo->lastInsertId();

    } else {

        $stmt = $pdo->prepare(
            "SELECT project_id
             FROM subtitle_projects
             WHERE project_id = ?"
        );

        $stmt->execute([$projectId]);

        if (!$stmt->fetch()) {
            throw new Exception(
                'The selected project does not exist.'
            );
        }

        // Get the existing project's name
        $stmt = $pdo->prepare(
            "SELECT name
             FROM subtitle_projects
             WHERE project_id = ?"
        );

        $stmt->execute([$projectId]);

        $projectTitle = $stmt->fetchColumn();
    }


    // --------------------------------
    // 2. Create language
    // --------------------------------

    $stmt = $pdo->prepare(
        "INSERT INTO subtitle_languages
            (project_id, language_code, language_name)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $projectId,
        $languageCode,
        $languageName
    ]);

    $languageId = $pdo->lastInsertId();


    // --------------------------------
// 3. Insert subtitle entries
// --------------------------------

$stmt = $pdo->prepare(
    "INSERT INTO subtitle_entries
        (
            language_id,
            subtitle_number,
            timestamp_original,
            start_time,
            end_time,
            text
        )
     VALUES (?, ?, ?, ?, ?, ?)"
);

$subtitleNumber = 1;

foreach ($parsedEntries as $entry) {

    $stmt->execute([
        $languageId,
        $subtitleNumber,
        $entry['timestamp_original'],
        $entry['start_time'],
        $entry['end_time'],
        $entry['text']
    ]);

    $subtitleNumber++;
}


    // --------------------------------
    // Everything succeeded
    // --------------------------------

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (
        $e instanceof PDOException &&
        $e->getCode() === '23000'
    ) {

        $uploadError =
            'This language has already been loaded into this project.';

    } else {

        $uploadError =
            'Upload failed: ' . $e->getMessage();
    }
}


// --------------------------------
// Save result or error for redirect
// --------------------------------

if ($uploadError === null) {

    $_SESSION['uploadResult'] = [
        'project_id' => $projectId,
        'project_title' => $projectTitle,
        'language_name' => $languageName,
        'file_name' => $fileName,
        'entry_count' => count($parsedEntries)
    ];

} else {

    $_SESSION['uploadError'] = $uploadError;
}


// --------------------------------
// Redirect back to main page
// --------------------------------

header('Location: index.php');
exit;