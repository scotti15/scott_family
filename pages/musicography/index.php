<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------------
// SESSION
// ------------------------
ini_set('session.cookie_path', '/'); // whole site
ini_set('session.cookie_domain', 'scotti.42web.io');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------
// INCLUDES
// ------------------------
require_once __DIR__ . '/../../config/db.php'; // <-- gives us $pdo
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

// ------------------------
// CURRENT USER
// ------------------------
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    header('Location: ../../auth/login.php');
    exit;
}

?>

<link rel="stylesheet" href="music.css">

<div class="container mt-4">

    <h1 class="mb-4">🎵 Musicography</h1>

    <!-- FILTERS -->
<div class="card mb-4 p-3">
    <div class="row g-3">
        <div class="col-md-2">
            <input id="filterType" class="form-control" placeholder="Filter by Type">
        </div>
        <div class="col-md-2">
            <input id="filterArtist" class="form-control" placeholder="Filter by Artist">
        </div>
        <div class="col-md-2">
            <input id="filterTrack" class="form-control" placeholder="Filter by Track">
        </div>
        <div class="col-md-2">
            <input id="filterAlbum" class="form-control" placeholder="Filter by Album">
        </div>
        <div class="col-md-2">
            <input id="filterGenre" class="form-control" placeholder="Filter by Genre">
        </div>
        <div class="col-md-2 text-end">
            <button id="clearFilters" class="btn btn-sm btn-outline-secondary">Clear Filters</button>
        </div>
    </div>
</div>

    <?php
    // ------------------------
    // FETCH MUSIC (PDO)
    // ------------------------
    $stmt = $pdo->query("
        SELECT media_id, media_type, artist_name, album_title, track_number,
               track_title, track_length, genre, release_year,
               composer, is_single, disc_number, featured_artist
        FROM music
        ORDER BY artist_name, album_title, track_number
    ");

    $music = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>


<!-- MUSIC TABLE -->
<table id="musicTable" class="table table-striped table-bordered table-hover" style="width:100%;">
    <thead class="table-dark">
        <tr>
            <th>Type</th>               <!-- new first column -->
            <th>Artist</th>
            <th>Track</th>
            <th>Album</th>
            <th>#</th>
            <th>Length</th>
            <th>Year</th>
            <th>Genre</th>
            <th>Composer</th>
            <th>Featured</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($music as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['media_type']) ?></td>          <!-- new -->
                <td><?= htmlspecialchars($row['artist_name']) ?></td>
                <td><?= htmlspecialchars($row['track_title']) ?></td>
                <td><?= htmlspecialchars($row['album_title']) ?></td>
                <td><?= $row['track_number'] ?></td>
                <td><?= $row['track_length'] ?></td>
                <td><?= $row['release_year'] ?></td>
                <td><?= htmlspecialchars($row['genre']) ?></td>
                <td><?= htmlspecialchars($row['composer'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['featured_artist'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>

<script src="music.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
