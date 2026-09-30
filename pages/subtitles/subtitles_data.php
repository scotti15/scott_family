<?php

// --------------------------------
// Project selection
// --------------------------------

$selectedProjectId = $_GET['project_id'] ?? '';
$selectedLanguageId = $_GET['language_id'] ?? '';


// --------------------------------
// Get projects
// --------------------------------

$stmt = $pdo->query(
    "SELECT project_id, name
     FROM subtitle_projects
     ORDER BY name"
);

$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// --------------------------------
// Default to first project
// --------------------------------

if ($selectedProjectId === '' && !empty($projects)) {
    $selectedProjectId = $projects[0]['project_id'];
}


// --------------------------------
// Get languages for selected project
// --------------------------------

$languages = [];

if ($selectedProjectId !== '') {

    $stmt = $pdo->prepare(
        "SELECT language_id, language_name
         FROM subtitle_languages
         WHERE project_id = ?
         ORDER BY language_name"
    );

    $stmt->execute([$selectedProjectId]);

    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// --------------------------------
// Get subtitles for selected language
// --------------------------------

$subtitles = [];

if ($selectedLanguageId !== '') {

    $stmt = $pdo->prepare(
        "SELECT
        entry_id,
            subtitle_number,
            start_time,
            end_time,
            text
         FROM subtitle_entries
         WHERE language_id = ?
         ORDER BY subtitle_number"
    );

    $stmt->execute([$selectedLanguageId]);

    $subtitles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}