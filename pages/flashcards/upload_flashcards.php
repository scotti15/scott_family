<?php
// ------------------------
// SESSION
// ------------------------
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', 'scotti.42web.io'); // adjust for your environment
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------
// DB
// ------------------------
require_once __DIR__ . '/../../config/db.php';
include __DIR__ . '/../../includes/header.php';


if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo "<p style='color:red;'>Database connection not found (expected \$pdo).</p>";
    exit;
}

// ------------------------
// AUTH
// ------------------------
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    echo "<p style='color:red;'>You must be logged in to upload flashcards.</p>";
    exit;
}

// ------------------------
// HANDLE LIST DELETION
// ------------------------
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_list_id'])) {
    $deleteId = (int)$_POST['delete_list_id'];

    // verify ownership
    $check = $pdo->prepare("SELECT id FROM flashcard_lists WHERE id = ? AND user_id = ?");
    $check->execute([$deleteId, $userId]);
    if ($check->fetch()) {
        // delete flashcards
        $pdo->prepare("DELETE FROM flashcards WHERE list_id = ? AND user_id = ?")
            ->execute([$deleteId, $userId]);
        // delete list
        $pdo->prepare("DELETE FROM flashcard_lists WHERE id = ? AND user_id = ?")
            ->execute([$deleteId, $userId]);

        $message = "<p style='color:lightgreen;'>✅ List deleted successfully.</p>";
    } else {
        $message = "<p style='color:red;'>List not found or not allowed.</p>";
    }
}

// ------------------------
// FETCH EXISTING LISTS
// ------------------------
try {
    $listsStmt = $pdo->prepare("
        SELECT l.id, l.name, l.description, COUNT(f.id) AS card_count
        FROM flashcard_lists l
        LEFT JOIN flashcards f ON l.id = f.list_id
        WHERE l.user_id = ?
        GROUP BY l.id
        ORDER BY l.id DESC
    ");
    $listsStmt->execute([$userId]);
    $lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lists = [];
}

// ------------------------
// HANDLE CSV UPLOAD
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $listName = trim($_POST['list_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileTmp = $_FILES['csv_file']['tmp_name'];

    if (!$listName) {
        $message = "<p style='color:red;'>Please enter a list name.</p>";
    } elseif (!is_uploaded_file($fileTmp)) {
        $message = "<p style='color:red;'>No valid file uploaded.</p>";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM flashcard_lists WHERE user_id = ? AND name = ?");
            $check->execute([$userId, $listName]);
            if ($check->fetch()) {
                $message = "<p style='color:red;'>A list named '" . htmlspecialchars($listName) . "' already exists. Choose another name.</p>";
            } else {
                $pdo->beginTransaction();
                $ins = $pdo->prepare("INSERT INTO flashcard_lists (user_id, name, description) VALUES (?, ?, ?)");
                $ins->execute([$userId, $listName, $description]);
                $listId = (int)$pdo->lastInsertId();

                $insertCard = $pdo->prepare("INSERT INTO flashcards (user_id, list_id, question, answer, created_at) VALUES (?, ?, ?, ?, NOW())");

                $fh = fopen($fileTmp, 'r');
                $rowCount = 0;
                while (($row = fgetcsv($fh)) !== false && $rowCount < 50) {
                    if (count($row) < 2) continue;
                    $q = trim($row[0]);
                    $a = trim($row[1]);
                    if ($q === '' || $a === '') continue;
                    $insertCard->execute([$userId, $listId, $q, $a]);
                    $rowCount++;
                }
                if (is_resource($fh)) fclose($fh);

                $pdo->commit();
                $message = "<p style='color:lightgreen;'>✅ Uploaded $rowCount cards into list '<strong>" . htmlspecialchars($listName) . "</strong>'.</p>";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<p style='color:red;'>Error importing CSV: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // refresh lists after upload
    $listsStmt->execute([$userId]);
    $lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Upload Flashcards</title>
  <style>
    body {
    font-family: Arial, sans-serif;
    background:#0b6623;
    color:#fff;
    margin:0;
}

/* New wrapper for the 2 columns */
.page-container {
    margin: 24px;
    display:flex;
    gap:20px;
}

/* existing styles remain the same */
.upload { flex:1; background:#1c7430; padding:18px; border-radius:8px; }
.lists { width:360px; background:#145a2e; padding:18px; border-radius:8px; overflow:auto; max-height:80vh; }

    .upload { flex:1; background:#1c7430; padding:18px; border-radius:8px; }
    .lists { width:360px; background:#145a2e; padding:18px; border-radius:8px; overflow:auto; max-height:80vh; }
    input, textarea, button { width:100%; padding:8px; margin:8px 0; border-radius:6px; border:none; }
    button { background:#ffc107; color:#000; cursor:pointer; font-weight:600; }
    .list-item { background:#1b5e32; padding:10px; margin-bottom:10px; border-radius:6px; }
    .list-item h4 { margin:0;color:#ffd54f; display:inline-block; }
    .list-item small { color:#ddd; }
    .list-item form { display:inline-block; margin-left:8px; }
    .list-item form button { background:#e53935; color:#fff; font-weight:500; padding:4px 8px; cursor:pointer; border-radius:4px; border:none; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../../includes/navbar.php'; ?>

  <div class="page-container">
      <div class="upload">
        <h2>Upload Flashcard List</h2>
        <?= $message ?>
        <form method="post" enctype="multipart/form-data">
          <label>List name (required)</label>
          <input type="text" name="list_name" required maxlength="255" />

          <label>Description (optional)</label>
          <textarea name="description" rows="2"></textarea>

          <label>CSV file (Question,Answer) — max 50 rows</label>
          <input type="file" name="csv_file" accept=".csv" required />

          <button type="submit">Upload</button>
        </form>
      </div>

      <div class="lists">
        <h3>Your Lists</h3>
        <?php if (empty($lists)): ?>
          <p><em>No lists found.</em></p>
        <?php else: ?>
          <?php foreach ($lists as $l): ?>
            <div class="list-item">
              <h4><?= htmlspecialchars($l['name']) ?></h4>
              <small><?= htmlspecialchars($l['description'] ?: 'No description') ?></small><br>
              <small>🗂️ <?= (int)$l['card_count'] ?> cards</small>

              <form method="post" onsubmit="return confirm('Delete this list and all its cards?');">
                <input type="hidden" name="delete_list_id" value="<?= (int)$l['id'] ?>" />
                <button type="submit">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
  </div>
</body>

</html>
