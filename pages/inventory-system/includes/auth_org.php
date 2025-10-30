<?php
// includes/auth.php
// depends on ../config/db.php being included where needed (or include it here)

if (session_status() === PHP_SESSION_NONE) {
    // secure session cookie params — adjust domain/path as needed
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',         // set if needed
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'     // or 'Strict' for tighter security
    ]);
    session_start();
}

// Call this early on pages to harden session after login
function secure_session_regenerate() {
    // regenerate session id to prevent fixation
    if (!isset($_SESSION['__regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['__regenerated'] = time();
    }
}

// simple function to fetch current user row (or null)
function current_user(PDO $pdo) {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// require login — redirect to login page if not authenticated
function require_login(PDO $pdo, $redirect = '/login.php') {
    if (empty($_SESSION['user_id'])) {
        // store intended URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect);
        exit;
    }
    secure_session_regenerate();
}

// login helper — returns array(success=>bool, message=>string)
function attempt_login(PDO $pdo, $username_or_email, $password, $max_failed = 5, $lock_minutes = 15) {
    $username_or_email = trim($username_or_email);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username_or_email, $username_or_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return ['success'=>false,'message'=>'Invalid credentials'];

    // check account lock
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        return ['success'=>false,'message'=>'Account locked. Try again later.'];
    }

    if (password_verify($password, $user['password_hash'])) {
        // success: reset failed attempts, update last_login, set session
        $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        session_regenerate_id(true); // prevent fixation
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        return ['success'=>true];
    } else {
        // increment failed attempts
        $failed = $user['failed_attempts'] + 1;
        $locked_until = null;
        if ($failed >= $max_failed) {
            $locked_until = date('Y-m-d H:i:s', time() + ($lock_minutes * 60));
        }
        $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->execute([$failed, $locked_until, $user['id']]);

        return ['success'=>false, 'message'=>'Invalid credentials'];
    }
}

// logout helper
function do_logout() {
    // Unset session and destroy cookie
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// CSRF token helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    $t = csrf_token();
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($t).'">';
}
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Quick guard for AJAX endpoints: ensure logged in and valid CSRF
function require_ajax_auth(PDO $pdo) {
    header('Content-Type: application/json; charset=utf-8');
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        exit;
    }
    // For POST requests, validate CSRF token if provided (recommended)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$token || !validate_csrf($token)) {
            echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
            exit;
        }
    }
}
