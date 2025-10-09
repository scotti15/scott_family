<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            echo '<li class="' . ($isSubmenu ? 'dropdown-submenu' : 'nav-item dropdown') . '">';
            echo '<a class="' . ($isSubmenu ? 'dropdown-item dropdown-toggle' : 'nav-link dropdown-toggle') . '" href="' . htmlspecialchars($link) . '" data-bs-toggle="dropdown" aria-expanded="false">'
                . htmlspecialchars($item['title']) . '</a>';
            echo '<ul class="dropdown-menu">';
            renderMenu($item['id'], $menu_tree, true);
            echo '</ul>';
            echo '</li>';
        } else {
            echo '<li>';
            echo '<a class="dropdown-item" href="' . htmlspecialchars($link) . '">' 
                . htmlspecialchars($item['title']) . '</a>';
            echo '</li>';
        }
    }
}
?>

<!-- Navbar HTML -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="<?php echo BASE_URL; ?>">My Site</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
                aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <?php renderMenu(null, $menu_tree); ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text text-white me-2">
                            Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>auth/signup.php">Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Styles -->
<style>
/* Navbar background */
.navbar {
    background: linear-gradient(90deg, #4e73df, #1cc88a);
    padding: 0.75rem 1rem;
}

/* Top-level links */
.navbar .nav-link {
    color: white !important;
    font-weight: 500;
    padding: 0.75rem 1rem;
    transition: background 0.3s, color 0.3s;
}

.navbar .nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
}

/* Dropdown menu styling */
.navbar .dropdown-menu {
    background: #ffffff;
    border: none;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    border-radius: 0.5rem;
    padding: 0.5rem 0;
    min-width: 220px;
    display: none;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.25s ease;
}

.navbar .dropdown-menu.show {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

/* Dropdown items */
.navbar .dropdown-item {
    padding: 0.5rem 1rem;
    transition: background 0.3s, color 0.3s;
}
.navbar .dropdown-item:hover {
    background: #f8f9fc;
    color: #4e73df;
}

/* Submenu */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu > .dropdown-menu {
    top: 0;
    left: 100%;
    margin-top: 0;
    margin-left: -1px; /* overlap border */
}

/* Submenu indicator */
.dropdown-submenu > a::after {
    content: "›";
    float: right;
    margin-left: 0.5rem;
    font-weight: bold;
}

/* Optional mega menu */
.navbar .dropdown-menu.mega {
    width: 100%;
    left: 0;
    right: 0;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Dropdown hover logic with delay -->
<script>
document.addEventListener("DOMContentLoaded", function(){
    if (window.innerWidth > 992) {
        // Top-level dropdowns
        document.querySelectorAll('.navbar .dropdown').forEach(function(dropdown){
            let timeout;
            dropdown.addEventListener('mouseenter', function(){
                clearTimeout(timeout);
                const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) bootstrap.Dropdown.getOrCreateInstance(toggle).show();
            });
            dropdown.addEventListener('mouseleave', function(){
                const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) {
                    timeout = setTimeout(() => {
                        bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                    }, 200);
                }
            });
        });

        // Submenus
        document.querySelectorAll('.dropdown-submenu').forEach(function(submenu){
            let timeout;
            submenu.addEventListener('mouseenter', function(){
                clearTimeout(timeout);
                const menu = submenu.querySelector('.dropdown-menu');
                if (menu) menu.classList.add('show');
            });
            submenu.addEventListener('mouseleave', function(){
                const menu = submenu.querySelector('.dropdown-menu');
                if (menu) {
                    timeout = setTimeout(() => menu.classList.remove('show'), 200);
                }
            });
        });
    }
});
</script>
