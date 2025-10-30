<?php
// register.php
require_once '../config/db.php';
require_once '../includes/auth.php';

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple CSRF check
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (!$username) $errors[] = 'Username required';
        if (!$email) $errors[] = 'Valid email required';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
        if ($password !== $password2) $errors[] = 'Passwords do not match';

        if (empty($errors)) {
            // check uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already in use';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $ok = $stmt->execute([$username, $email, $hash]);
                if ($ok) {
                    $success = true;
                } else $errors[] = 'Registration failed';
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container mt-4">
  <h2>Register</h2>
  <?php if ($success): ?>
    <div class="alert alert-success">Account created. <a href="login.php">Log in</a></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
  <?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="mb-2"><label>Username</label><input name="username" class="form-control" required></div>
    <div class="mb-2"><label>Email</label><input name="email" type="email" class="form-control" required></div>
    <div class="mb-2"><label>Password</label><input name="password" type="password" class="form-control" required></div>
    <div class="mb-2"><label>Confirm Password</label><input name="password2" type="password" class="form-control" required></div>
    <button class="btn btn-primary">Register</button>
  </form>
</div>
