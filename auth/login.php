<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $identifier = trim($_POST['identifier'] ?? ''); // username or email
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        flash('error', 'Both fields are required.');
    } else {
        // Fetch user including role
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = :i OR email = :i LIMIT 1");
        $stmt->execute([':i' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set all session keys for navbar
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            flash('success', 'Welcome back, ' . $user['username'] . '!');
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        } else {
            flash('error', 'Invalid credentials.');
        }
    }
}
?>
<div class="container mt-4">
  <h1>Log In</h1>
  <?php render_flashes(); ?>
  <form method="post" action="">
    <?php csrf_field(); ?>
    <div class="mb-3">
      <label class="form-label">Username or Email</label>
      <input class="form-control" name="identifier" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <button class="btn btn-primary">Log In</button>
    <a class="btn btn-link" href="<?= htmlspecialchars(BASE_URL.'auth/forgot_password.php') ?>">Forgot password?</a>
    <a class="btn btn-link" href="<?= htmlspecialchars(BASE_URL.'auth/signup.php') ?>">Sign up</a>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
