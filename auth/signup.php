<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        flash('error', 'All fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Invalid email address.');
    } elseif ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = :u OR email = :e");
            $stmt->execute([':u' => $username, ':e' => $email]);
            if ($stmt->fetch()) {
                flash('error', 'Username or email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)");
                $ins->execute([':u' => $username, ':e' => $email, ':p' => $hash]);
                flash('success', 'Account created. You can log in now.');
                header('Location: '.BASE_URL.'auth/login.php');
                exit;
            }
        } catch (Exception $e) {
            flash('error', 'Signup failed.');
        }
    }
}
?>
<div class="container mt-4">
  <h1>Sign Up</h1>
  <?php render_flashes(); ?>
  <form method="post" action="">
    <?php csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input class="form-control" name="username" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm Password</label>
      <input class="form-control" type="password" name="confirm" required>
    </div>
    <button class="btn btn-primary">Create Account</button>
    <a class="btn btn-link" href="<?= htmlspecialchars(BASE_URL.'auth/login.php') ?>">Log in</a>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
