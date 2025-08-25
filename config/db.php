<?php
$host = "localhost";
$dbname = "scott_family";
$user = "root";  // change if needed
$pass = "";      // change if needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Detect BASE_URL (web path to your project root) ---
if (!defined('BASE_URL')) {
    // Filesystem paths normalized
    $docRoot    = rtrim(str_replace('\\','/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $projectRoot= rtrim(str_replace('\\','/', realpath(dirname(__DIR__))), '/'); // parent of /config = project root

    if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
        $relative = substr($projectRoot, strlen($docRoot)); // e.g. "" or "/mysite"
        $base = $relative === '' ? '/' : '/' . trim($relative, '/') . '/';
    } else {
        // Fallback if detection fails
        $base = '/';
    }
    define('BASE_URL', $base); // e.g. "/" or "/mysite/"
}
// --- App settings ---
if (!defined('APP_NAME')) define('APP_NAME', 'MySite');
if (!defined('EMAIL_FROM')) define('EMAIL_FROM', 'no-reply@example.test');

// Dev mode: show reset links on screen instead of sending email
if (!defined('DEV_MODE')) define('DEV_MODE', true);

// --- Flash helper ---
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}
function render_flashes() {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert '.$cls.' mt-3" role="alert">'.htmlspecialchars($f['message']).'</div>';
    }
    unset($_SESSION['flash']);
}

// --- CSRF (simple) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token']).'">';
}
function csrf_check() {
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(400);
        die('Invalid CSRF token');
    }
}

// --- Send email (very basic; replace with PHPMailer in prod) ---
function send_email($to, $subject, $body) {
    if (DEV_MODE) {
        // In dev, just return true and let the page show the link
        return true;
    }
    $headers = "From: ".EMAIL_FROM."\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $body, $headers);
}

?>
