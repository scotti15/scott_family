<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';


switch ($action) {

    case 'all':
        $stmt = $pdo->query("
            SELECT location_id, name, parent_id, display_order, active
            FROM locations
            ORDER BY display_order, name
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['data' => $rows]);
        break;

    // --- List locations for DataTable ---
    case 'list':

        $stmt = $pdo->query("
            SELECT 
                l.location_id, 
                l.name, 
                l.parent_id,
                (SELECT name FROM locations p WHERE p.location_id = l.parent_id) AS parent_name,
                l.display_order, 
                l.active,
                f.frequency_name,
                l.frequency_id,
                l.schedule_weekday,
                l.schedule_nth
            FROM locations l
            LEFT JOIN cleaning_frequency f 
                ON l.frequency_id = f.frequency_id
            ORDER BY l.display_order, l.name;
        ");
    
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // ===== Lookup tables =====
    
        $weekdayNames = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
            4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
        ];
    
        // Supports up to 13 for quarterly
        $nthNames = [];
        for ($i = 1; $i <= 13; $i++) {
            $suffix = "th";
            if ($i % 10 == 1 && $i != 11) $suffix = "st";
            elseif ($i % 10 == 2 && $i != 12) $suffix = "nd";
            elseif ($i % 10 == 3 && $i != 13) $suffix = "rd";
            $nthNames[$i] = $i . $suffix;
        }
    
        // ===== Build display string =====
    
        foreach ($rows as &$r) {
    
            if (!empty($r['schedule_weekday'])) {
    
                $day = $weekdayNames[$r['schedule_weekday']] ?? '';
    
                // Weekly → weekday only
                if ($r['frequency_id'] == 2) {
                    $r['schedule'] = $day;
    
                // Monthly / Quarterly → nth + weekday
                } elseif (!empty($r['schedule_nth'])) {
                    $nth = $nthNames[$r['schedule_nth']] ?? '';
                    $r['schedule'] = trim("$nth $day");
                } else {
                    $r['schedule'] = $day;
                }
    
            } else {
                $r['schedule'] = '';
            }
        }
    
        echo json_encode(['data' => $rows]);
        break;
    // --- Add new location ---
// --- Add new location ---
case 'add':

    // --- Basic fields ---
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;

    // --- New cleaning fields ---
    $cleanable = isset($_POST['cleanable']) && $_POST['cleanable'] == '1' ? 1 : 0;
    $frequency_id = !empty($_POST['frequency_id']) ? (int)$_POST['frequency_id'] : null;
    $schedule = $_POST['schedule'] ?? null;

// --- Parse schedule into weekday & nth ---
$schedule_weekday = null;
$schedule_nth = null;

if ($schedule) {

    $days = [
        'Sunday'=>7,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,
        'Thursday'=>4,'Friday'=>5,'Saturday'=>6
    ];

    // ===== Case 1: Weekly (e.g. "Tuesday") =====
    if (isset($days[$schedule])) {
        $schedule_weekday = $days[$schedule];
        $schedule_nth = null;
    }

    // ===== Case 2: Monthly / Quarterly (e.g. "Tuesday03") =====
    else {
        $matches = [];
        preg_match('/^(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)(\d{1,2})$/', $schedule, $matches);

        if (isset($matches[1], $matches[2]) && isset($days[$matches[1]])) {
            $schedule_weekday = $days[$matches[1]];
            $schedule_nth = (int)$matches[2];
        }
    }
}

    // --- Validation ---
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Name required']);
        break;
    }

    // --- Insert into database ---
    $stmt = $pdo->prepare("
        INSERT INTO locations
        (name, parent_id, display_order, active, cleanable, frequency_id, schedule_weekday, schedule_nth)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $ok = $stmt->execute([
        $name,
        $parent_id,
        $display_order,
        $active,
        $cleanable,
        $frequency_id,
        $schedule_weekday,
        $schedule_nth
    ]);

    echo json_encode([
        'success' => (bool)$ok,
        'debug' => $_POST
    ]);
    break;
    case 'edit':
        $location_id = (int)($_POST['location_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $display_order = (int)($_POST['display_order'] ?? 0);
        $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;
    
        // --- Cleaning fields ---
        $cleanable = isset($_POST['cleanable']) && $_POST['cleanable'] == '1' ? 1 : 0;
        $frequency_id = !empty($_POST['frequency_id']) ? (int)$_POST['frequency_id'] : null;
        $schedule = $_POST['schedule'] ?? null;
    
        // --- Parse schedule (SAME AS ADD) ---
        $schedule_weekday = null;
        $schedule_nth = null;
    
        if ($schedule) {
            $days = [
                'Sunday'=>7,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,
                'Thursday'=>4,'Friday'=>5,'Saturday'=>6
            ];
    
            // Weekly
            if (isset($days[$schedule])) {
                $schedule_weekday = $days[$schedule];
                $schedule_nth = null;
            }
            // Monthly / Quarterly
            else {
                $matches = [];
                preg_match('/^(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)(\d{1,2})$/', $schedule, $matches);
    
                if (isset($matches[1], $matches[2]) && isset($days[$matches[1]])) {
                    $schedule_weekday = $days[$matches[1]];
                    $schedule_nth = (int)$matches[2];
                }
            }
        }
    
        if (!$location_id || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            break;
        }
    
        $stmt = $pdo->prepare("
            UPDATE locations
            SET
              name = ?,
              parent_id = ?,
              display_order = ?,
              active = ?,
              cleanable = ?,
              frequency_id = ?,
              schedule_weekday = ?,
              schedule_nth = ?
            WHERE location_id = ?
        ");
    
        $ok = $stmt->execute([
            $name,
            $parent_id,
            $display_order,
            $active,
            $cleanable,
            $frequency_id,
            $schedule_weekday,
            $schedule_nth,
            $location_id
        ]);
    
        echo json_encode(['success' => (bool)$ok]);
        break;

    // --- Delete a location ---
    case 'delete':
        $location_id = (int)$_POST['id'];
    
        $pdo->beginTransaction();
        try {
            // 1. Delete all cleaning log entries for tasks in this location
            $stmt = $pdo->prepare("
                DELETE cl 
                FROM cleaning_log cl
                INNER JOIN cleaning_tasks ct ON cl.task_id = ct.task_id
                WHERE ct.location_id = ?
            ");
            $stmt->execute([$location_id]);
    
            // 2. Delete all cleaning tasks for this location
            $stmt = $pdo->prepare("DELETE FROM cleaning_tasks WHERE location_id = ?");
            $stmt->execute([$location_id]);
    
            // 3. Delete the location itself
            $stmt = $pdo->prepare("DELETE FROM locations WHERE location_id = ?");
            $stmt->execute([$location_id]);
    
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => "Failed to delete location: " . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

ob_end_flush();