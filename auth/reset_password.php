<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

$token = $_GET['token'] ?? '';
$valid = false;
$userId = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = :t LIMIT 1");
    $stmt->execute([':t' => $token]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['reset_token_expires'] && new DateTime() < new DateTime($row['reset_token_expires'])) {
            $valid = true;
            $userId = (int)$row['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $userId = (int)($_POST['user_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    if ($password === '' || $password !== $confirm) {
        flash('error', 'Passwords must match and not be empty.');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = :p, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
        $upd->execute([':p' => $hash, ':id' => $userId]);
        flash('success', 'Password reset. You can log in now.');
        header('Location: '.BASE_URL.'auth/login.php');
        exit;
    }
}
?>
<div class="container mt-4">
  <h1>Reset Password</h1>
  <?php render_flashes(); ?>

  <?php if (!$valid && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <div class="alert alert-danger">This reset link is invalid or has expired.</div>
  <?php elseif ($valid || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <form method="post" action="">
      <?php csrf_field(); ?>
      <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input class="form-control" type="password" name="confirm" required>
      </div>
      <button class="btn btn-primary">Set New Password</button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
