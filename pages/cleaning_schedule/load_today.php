<?php
require_once "../../config/db.php";

$today = date('Y-m-d');
$weekday = (int)date('N');
$dayOfMonth = (int)date('j');
$weekOfMonth = (int)ceil($dayOfMonth / 7);

// Quarter logic (needed for quarterly tasks)
$month = (int)date('n');
$quarterStartMonth = floor(($month - 1) / 3) * 3 + 1;
$startOfQuarter = new DateTime(date('Y') . '-' . $quarterStartMonth . '-01');
$todayDate = new DateTime();
$daysIntoQuarter = $startOfQuarter->diff($todayDate)->days;
$weekOfQuarter = (int)floor($daysIntoQuarter / 7) + 1;

// Determine frequency parameter from GET (default daily)
$frequency = $_GET['frequency'] ?? 'daily';

// Build WHERE clause and parameters safely
$where = "l.cleanable = 1 AND l.active = 1";
$params = [];

switch ($frequency) {
    case 'daily':
        $where .= " AND l.frequency_id = 1";
        break;

        case 'weekly':
            $where .= " AND l.frequency_id = 2 AND l.schedule_weekday = ?";
            $params[] = $weekday;
            break;
        
        case 'monthly':
            $where .= " AND l.frequency_id = 3 
                        AND l.schedule_weekday = ?
                        AND l.schedule_nth = ?";
            $params[] = $weekday;
            $params[] = $weekOfMonth;
            break;
        
        case 'quarterly':
            $where .= " AND l.frequency_id = 4
                        AND l.schedule_weekday = ?
                        AND l.schedule_nth = ?";
            $params[] = $weekday;
            $params[] = $weekOfQuarter;
            break;

    default:
        $where .= " AND l.frequency_id = 1"; // fallback to daily
        break;
}

// Main query
$sql = "SELECT 
        l.location_id AS task_id,
        CONCAT(
            COALESCE(p.name, l.name),
            CASE WHEN p.name IS NULL THEN '' ELSE ' → ' END,
            CASE WHEN p.name IS NULL THEN '' ELSE l.name END
        ) AS location_path,
        CASE 
            WHEN cl.cleaned_date IS NULL THEN 0 
            ELSE 1 
        END AS completed_today
    FROM locations l
    LEFT JOIN locations p 
        ON l.parent_id = p.location_id
    LEFT JOIN cleaning_log cl 
        ON cl.task_id = l.location_id 
        AND cl.cleaned_date = CURDATE()
    WHERE $where
    ORDER BY location_path";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));