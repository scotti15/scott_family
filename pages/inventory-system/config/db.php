
<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/scott_family');
}

$host = 'localhost';
$user = "root";  // change if needed
$pass = "";      // change if needed
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/*
|--------------------------------------------------------------------------
| Inventory database (house) — EXISTING
|--------------------------------------------------------------------------
*/
$db_house = 'house';
$dsn_house = "mysql:host=$host;dbname=$db_house;charset=$charset";

try {
    $pdo = new PDO($dsn_house, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException(
        "House DB connection failed: " . $e->getMessage(),
        (int)$e->getCode()
    );
}

/*
|--------------------------------------------------------------------------
| Main site database (scott_family) — NEW
|--------------------------------------------------------------------------
*/
$db_scott_family = 'scott_family';
$dsn_scott_family = "mysql:host=$host;dbname=$db_scott_family;charset=$charset";

try {
    $pdo_scott_family = new PDO($dsn_scott_family, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException(
        "Scott_family DB connection failed: " . $e->getMessage(),
        (int)$e->getCode()
    );
}
