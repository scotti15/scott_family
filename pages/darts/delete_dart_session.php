<?php
session_start();
require_once '../../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sessionId = (int)($_POST['session_id'] ?? 0);

    if ($sessionId > 0) {

        try {

            $pdo->beginTransaction();

            // Delete throws
            $stmt = $pdo->prepare("
                DELETE d
                FROM dart_throws d
                JOIN dart_turns t ON d.turn_id = t.turn_id
                JOIN dart_games g ON t.game_id = g.game_id
                WHERE g.play_session_id = ?
            ");
            $stmt->execute([$sessionId]);

            // Delete turns
            $stmt = $pdo->prepare("
                DELETE t
                FROM dart_turns t
                JOIN dart_games g ON t.game_id = g.game_id
                WHERE g.play_session_id = ?
            ");
            $stmt->execute([$sessionId]);

            // Delete games
            $stmt = $pdo->prepare("
                DELETE FROM dart_games
                WHERE play_session_id = ?
            ");
            $stmt->execute([$sessionId]);

            // Delete session
            $stmt = $pdo->prepare("
                DELETE FROM dart_sessions
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);

            $pdo->commit();

            $message = "Session $sessionId deleted.";

        } catch (Exception $e) {

            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }

    }
}

?>

<form method="post">

<h2>Delete Dart Session</h2>

<label>
Session ID:
<input type="number" name="session_id" required>
</label>

<button type="submit">
Delete
</button>

</form>

<p><?= htmlspecialchars($message) ?></p>