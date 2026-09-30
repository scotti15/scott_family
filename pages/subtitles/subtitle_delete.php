<?php

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$entryId = $_POST['entry_id'] ?? '';

$response = ['success' => false];

if ($entryId === '') {
    $response['error'] = 'Subtitle entry ID is required.';
    echo json_encode($response);
    exit;
}

try {

    $pdo->beginTransaction();

    // Find the language before deleting the entry.
    $stmt = $pdo->prepare(
        "SELECT language_id
         FROM subtitle_entries
         WHERE entry_id = ?"
    );

    $stmt->execute([$entryId]);

    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        $pdo->rollBack();

        $response['error'] = 'Subtitle entry not found.';
        echo json_encode($response);
        exit;
    }

    $languageId = $entry['language_id'];

    // Delete the entry.
    $stmt = $pdo->prepare(
        "DELETE FROM subtitle_entries
         WHERE entry_id = ?"
    );

    $stmt->execute([$entryId]);

    if ($stmt->rowCount() !== 1) {
        $pdo->rollBack();

        $response['error'] = 'The subtitle could not be deleted.';
        echo json_encode($response);
        exit;
    }

    // Rebuild the subtitle sequence for this language.
    $stmt = $pdo->prepare(
        "SELECT entry_id
         FROM subtitle_entries
         WHERE language_id = ?
         ORDER BY subtitle_number, entry_id"
    );

    $stmt->execute([$languageId]);

    $entries = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $updateStmt = $pdo->prepare(
        "UPDATE subtitle_entries
         SET subtitle_number = ?
         WHERE entry_id = ?"
    );

    $subtitleNumber = 1;

    foreach ($entries as $remainingEntryId) {
        $updateStmt->execute([
            $subtitleNumber,
            $remainingEntryId
        ]);

        $subtitleNumber++;
    }

    $pdo->commit();

    $response['success'] = true;
    $response['deleted'] = 1;
    $response['renumbered'] = count($entries);

    echo json_encode($response);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response['error'] = 'The subtitle could not be deleted.';
    echo json_encode($response);
}