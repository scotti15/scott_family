<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

$devResetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Do not reveal whether the email exists
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
            $upd = $pdo->prepare("UPDATE users SET reset_token = :t, reset_token_expires = :x WHERE id = :id");
            $upd->execute([':t' => $token, ':x' => $expires, ':id' => $user['id']]);

            $resetUrl = BASE_URL.'auth/reset_password.php?token='.$token;
            $sent = send_email($email, APP_NAME.' password reset', "Reset your password: ".$resetUrl);

            if (DEV_MODE) {
                $devResetLink = $resetUrl; // show it on page in dev
            }
        }
        flash('success', 'If that email is registered, a reset link has been sent.');
    }
}
?>
<div class="container mt-4">
  <h1>Forgot Password</h1>
  <?php render_flashes(); ?>
  <form method="post" action="">
    <?php csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Your Email</label>
      <input class="form-control" type="email" name="email" required>
    </div>
    <button class="btn btn-primary">Send Reset Link</button>
    <a class="btn btn-link" href="<?= htmlspecialchars(BASE_URL.'auth/login.php') ?>">Back to login</a>
  </form>

  <?php if (DEV_MODE && $devResetLink): ?>
    <div class="alert alert-info mt-3">
      <strong>DEV MODE:</strong> Reset link: 
      <a href="<?= htmlspecialchars($devResetLink) ?>"><?= htmlspecialchars($devResetLink) ?></a>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
