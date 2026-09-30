<?php

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false];

$data = json_decode(file_get_contents('php://input'), true);

$entryIds   = $data['entryIds'] ?? [];
$target     = $data['target'] ?? '';
$adjustment = $data['adjustment'] ?? null;


// ==========================================
// Validate input
// ==========================================

if (
    !is_array($entryIds) ||
    empty($entryIds) ||
    !in_array($target, ['start', 'end', 'both'], true) ||
    !is_numeric($adjustment)
) {
    $response['error'] = 'Invalid timing adjustment data.';
    echo json_encode($response);
    exit;
}


// Make sure all IDs are integers
$entryIds = array_map('intval', $entryIds);

$adjustment = (int) $adjustment;


// ==========================================
// Convert subtitle timestamp to milliseconds
// ==========================================

function timeToMilliseconds($time)
{
    $parts = explode(':', $time);

    if (count($parts) !== 3) {
        return false;
    }

    $hours = (int) $parts[0];
    $minutes = (int) $parts[1];

    $secondParts = explode(',', $parts[2]);

    if (count($secondParts) !== 2) {
        return false;
    }

    $seconds = (int) $secondParts[0];
    $milliseconds = (int) $secondParts[1];

    return
        ($hours * 3600000) +
        ($minutes * 60000) +
        ($seconds * 1000) +
        $milliseconds;
}


// ==========================================
// Convert milliseconds back to SRT timestamp
// ==========================================

function millisecondsToTime($milliseconds)
{
    if ($milliseconds < 0) {
        return false;
    }

    $hours = intdiv($milliseconds, 3600000);
    $milliseconds %= 3600000;

    $minutes = intdiv($milliseconds, 60000);
    $milliseconds %= 60000;

    $seconds = intdiv($milliseconds, 1000);
    $milliseconds %= 1000;

    return sprintf(
        '%02d:%02d:%02d,%03d',
        $hours,
        $minutes,
        $seconds,
        $milliseconds
    );
}


// ==========================================
// Get selected entries
// ==========================================

$placeholders = implode(',', array_fill(0, count($entryIds), '?'));

$stmt = $pdo->prepare(
    "SELECT entry_id, start_time, end_time
     FROM subtitle_entries
     WHERE entry_id IN ($placeholders)"
);

$stmt->execute($entryIds);

$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Make sure every requested ID was found

if (count($entries) !== count($entryIds)) {
    $response['error'] = 'One or more subtitle entries could not be found.';
    echo json_encode($response);
    exit;
}


// ==========================================
// Calculate new times
// ==========================================

$updates = [];

foreach ($entries as $entry) {

    $newStart = $entry['start_time'];
    $newEnd   = $entry['end_time'];

    if ($target === 'start' || $target === 'both') {

        $startMilliseconds = timeToMilliseconds($entry['start_time']);

        if ($startMilliseconds === false) {
            $response['error'] = 'Invalid start timestamp.';
            echo json_encode($response);
            exit;
        }

        $newStart = millisecondsToTime(
            $startMilliseconds + $adjustment
        );

        if ($newStart === false) {
            $response['error'] = 'Timing adjustment would create a negative timestamp.';
            echo json_encode($response);
            exit;
        }
    }


    if ($target === 'end' || $target === 'both') {

        $endMilliseconds = timeToMilliseconds($entry['end_time']);

        if ($endMilliseconds === false) {
            $response['error'] = 'Invalid end timestamp.';
            echo json_encode($response);
            exit;
        }

        $newEnd = millisecondsToTime(
            $endMilliseconds + $adjustment
        );

        if ($newEnd === false) {
            $response['error'] = 'Timing adjustment would create a negative timestamp.';
            echo json_encode($response);
            exit;
        }
    }


    // Make sure Start does not become equal to or later than End

    $newStartMilliseconds = timeToMilliseconds($newStart);
    $newEndMilliseconds   = timeToMilliseconds($newEnd);

    if ($newStartMilliseconds >= $newEndMilliseconds) {
        $response['error'] =
            'Timing adjustment would make the start time equal to or later than the end time.';
        echo json_encode($response);
        exit;
    }


    $updates[] = [
        'entry_id'   => $entry['entry_id'],
        'start_time' => $newStart,
        'end_time'   => $newEnd
    ];
}


// ==========================================
// Update database
// ==========================================

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE subtitle_entries
         SET start_time = ?, end_time = ?
         WHERE entry_id = ?"
    );

    foreach ($updates as $update) {

        $stmt->execute([
            $update['start_time'],
            $update['end_time'],
            $update['entry_id']
        ]);
    }

    $pdo->commit();

    $response['success'] = true;
    $response['updated'] = count($updates);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response['error'] = 'Database update failed.';
}

echo json_encode($response);