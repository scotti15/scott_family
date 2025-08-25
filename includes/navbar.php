<?php
require_once __DIR__ . '/../config/db.php';

// Make sure session is started safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch menu items
$stmt = $pdo->query("SELECT * FROM menu_items ORDER BY sort_order ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build tree
$menu_tree = [];
foreach ($items as $item) {
    $menu_tree[$item['parent_id']][] = $item;
}

function renderMenu($parent_id, $menu_tree, $isSubmenu = false) {
    if (!isset($menu_tree[$parent_id])) return;

    foreach ($menu_tree[$parent_id] as $item) {
        $hasChildren = isset($menu_tree[$item['id']]);
        $link = $item['link'] ? BASE_URL . ltrim($item['link'], '/') : '#';

        if ($hasChildren) {
            // Dropdown parent
            echo '<li class="' . ($isSubmenu ? 'dropdown-submenu' : 'nav-item dropdown') . '">';
            echo '<a class="' . ($isSubmenu ? 'dropdown-item dropdown-toggle' : 'nav-link dropdown-toggle') . '" href="' . htmlspecialchars($link) . '" data-bs-toggle="dropdown" aria-expanded="false">'
                . htmlspecialchars($item['title']) . '</a>';
            echo '<ul class="dropdown-menu">';
            renderMenu($item['id'], $menu_tree, true);
            echo '</ul>';
            echo '</li>';
        } else {
            // Regular link
            echo '<li>';
            echo '<a class="dropdown-item" href="' . htmlspecialchars($link) . '">' 
                . htmlspecialchars($item['title']) . '</a>';
            echo '</li>';
        }
    }
}
?>

<!-- Navbar HTML -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">My Site</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
                aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <?php renderMenu(null, $menu_tree); ?>

                <!-- Show admin-only link -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>

                <!-- Login / Logout links -->
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text">
                            Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>auth/register.php">Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Multi-level dropdown CSS -->
<style>
/* Position submenu */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu > .dropdown-menu {
    top: 0;
    left: 100%;
    margin-top: -0.125rem;
}
</style>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Enable multi-level dropdown -->
<script>
document.addEventListener("DOMContentLoaded", function(){
    // Enable hover for desktop
    if (window.innerWidth > 992) {
        document.querySelectorAll('.navbar .dropdown').forEach(function(dropdown){
            dropdown.addEventListener('mouseenter', function(){
                const menu = dropdown.querySelector('.dropdown-menu');
                if(menu){ new bootstrap.Dropdown(dropdown.querySelector('[data-bs-toggle="dropdown"]')).show(); }
            });
            dropdown.addEventListener('mouseleave', function(){
                const menu = dropdown.querySelector('.dropdown-menu');
                if(menu){ new bootstrap.Dropdown(dropdown.querySelector('[data-bs-toggle="dropdown"]')).hide(); }
            });
        });
    }
});
</script>
