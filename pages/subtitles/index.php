<?php

require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// --------------------------------
// Retrieve result or error after redirect
// --------------------------------

$uploadResult = $_SESSION['uploadResult'] ?? null;
$uploadError = $_SESSION['uploadError'] ?? null;

unset($_SESSION['uploadResult']);
unset($_SESSION['uploadError']);


// --------------------------------
// Load subtitle page data
// --------------------------------

require_once __DIR__ . '/subtitles_data.php';


include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

?>


<script>
const selectedLanguageId = <?= json_encode($selectedLanguageId) ?>;
</script>

<link rel="stylesheet" href="subtitles.css">

<!-- ========================================= -->

<!-- Mock Hover Sidebar -->

<!-- ========================================= -->

<div class="subtitle-sidebar">

    <div class="sidebar-item">
        <span class="sidebar-icon">📁</span>
        <span class="sidebar-label">Project</span>
    </div>

    <div class="sidebar-submenu sidebar-project-select">

        <form method="GET">

            <select class="form-select form-select-sm" name="project_id" onchange="this.form.submit()">

                <?php foreach ($projects as $project): ?>

                <option value="<?= htmlspecialchars($project['project_id']) ?>"
                    <?= (string)$project['project_id'] === (string)$selectedProjectId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($project['name']) ?>
                </option>

                <?php endforeach; ?>

            </select>

        </form>

    </div>

    <div class="sidebar-item">
        <span class="sidebar-icon">🌐</span>
        <span class="sidebar-label">Languages</span>
    </div>
    <div class="sidebar-submenu">

        <?php foreach ($languages as $language): ?>

        <a href="?project_id=<?= urlencode($selectedProjectId) ?>&language_id=<?= urlencode($language['language_id']) ?>"
            class="sidebar-language">
            <?= htmlspecialchars($language['language_name']) ?>
        </a>

        <?php endforeach; ?>

    </div>
    <button type="button" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#loadSubtitleModal">
        <span class="sidebar-icon">📂</span>
        <span class="sidebar-label">Load File</span>
    </button>

    <div class="sidebar-item">
        <span class="sidebar-icon">✏️</span>
        <span class="sidebar-label">Edit</span>
    </div>

    <div class="sidebar-item">
        <span class="sidebar-icon">↔️</span>
        <span class="sidebar-label">Compare</span>
    </div>

    <div class="sidebar-item">
        <span class="sidebar-icon">💾</span>
        <span class="sidebar-label">Export</span>
    </div>

</div>


<div class="container mt-4">

    <!-- ========================================= -->
    <!-- Subtitle Control Panel -->
    <!-- ========================================= -->

    <div class="subtitle-control-panel">

        <div class="subtitle-control-heading">
            Subtitle Controls
        </div>

        <div class="control-section selection-section">

            <div class="control-section-title">
                Selection
            </div>

            <div>
                <span id="selected-count">0 lines selected</span>
            </div>

        </div>


        <div class="control-section timing-section">

            <div class="control-section-title">
                Timing
            </div>

            <div class="timing-controls">

                <div class="timing-target">

                    <span>Adjust:</span>

                    <label>
                        <input type="radio" name="timing-target" value="start" checked>
                        Start
                    </label>

                    <label>
                        <input type="radio" name="timing-target" value="end">
                        End
                    </label>

                    <label>
                        <input type="radio" name="timing-target" value="both">
                        Both
                    </label>

                </div>


                <div class="timing-adjustment">

                    <label for="time-amount">
                        Amount
                    </label>

                    <select id="time-amount">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>


                    <label for="time-unit">
                        Unit
                    </label>

                    <select id="time-unit">
                        <option value="tenth">tenth of a second</option>
                        <option value="second" selected>second</option>
                        <option value="ten-second">ten seconds</option>
                        <option value="minute">minute</option>
                        <option value="ten-minute">ten minutes</option>
                    </select>


                    <button type="button" id="time-increase">
                        ▲
                    </button>

                    <button type="button" id="time-decrease">
                        ▼
                    </button>

                </div>

            </div>

        </div>


        <div class="control-section actions-section">

            <div class="control-section-title">
                Actions
            </div>

            <div>
                <label for="compare-language">
                    Compare with:
                </label>

                <select id="compare-language">

                    <option value="">Select language</option>

                    <?php foreach ($languages as $language): ?>

                    <?php if ($language['language_id'] != $selectedLanguageId): ?>

                    <option value="<?= htmlspecialchars($language['language_id']) ?>">
                        <?= htmlspecialchars($language['language_name']) ?>
                    </option>

                    <?php endif; ?>

                    <?php endforeach; ?>

                </select>
            </div>

            <button type="button" id="compare-subtitles">
                Compare
            </button>
            
            <button type="button" id="clear-subtitles">
                Clear
            </button>


            <button type="button" id="delete-subtitles">Delete</button>
            <button type="button" id="export-subtitles">
                Export
            </button>

        </div>

    </div>

    <!-- ========================================= -->
    <!-- Load Subtitle File Modal -->
    <!-- ========================================= -->

    <div class="modal fade" id="loadSubtitleModal" tabindex="-1" aria-labelledby="loadSubtitleModalLabel"
        aria-hidden="true">

        <div class="modal-dialog">

            <div class="modal-content">

                <form method="POST" action="subtitle_upload.php" enctype="multipart/form-data">

                    <div class="modal-header">

                        <h5 class="modal-title" id="loadSubtitleModalLabel">
                            Load Subtitle File
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>

                    <div class="modal-body">

                        <!-- Project -->

                        <div class="mb-3">

                            <label for="project_id" class="form-label">
                                Project
                            </label>

                            <select class="form-select" id="project_id" name="project_id">

                                <option value="new" selected>
                                    New Project
                                </option>

                                <?php
                            $stmt = $pdo->query(
                                "SELECT project_id, name
                                 FROM subtitle_projects
                                 ORDER BY name"
                            );

                            while (
                                $project = $stmt->fetch(PDO::FETCH_ASSOC)
                            ):
                            ?>

                                <option value="<?= htmlspecialchars($project['project_id']) ?>">
                                    <?= htmlspecialchars($project['name']) ?>
                                </option>

                                <?php endwhile; ?>

                            </select>

                        </div>


                        <!-- Project Title -->

                        <div class="mb-3" id="project-title-group">

                            <label for="project-title" class="form-label">
                                Project Title
                            </label>

                            <input type="text" class="form-control" id="project-title" name="project_title">

                        </div>


                        <!-- Language -->

                        <div class="mb-3">

                            <label for="subtitle-language" class="form-label">
                                Language
                            </label>

                            <select class="form-select" id="subtitle-language" name="language_code" required>

                                <option value="" selected>
                                    Select Language
                                </option>

                                <option value="en">
                                    English
                                </option>

                                <option value="fr">
                                    French
                                </option>

                                <option value="de">
                                    German
                                </option>

                                <option value="es">
                                    Spanish
                                </option>

                            </select>

                        </div>


                        <!-- Subtitle File -->

                        <div class="mb-3">

                            <label for="subtitle-file" class="form-label">
                                Subtitle File
                            </label>

                            <input type="file" class="form-control" id="subtitle-file" name="subtitle_file"
                                accept=".srt,.vtt,.ass" required>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Load File
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <!-- ========================================= -->
    <!-- Subtitles -->
    <!-- ========================================= -->

    <?php if ($selectedLanguageId !== ''): ?>

    <div class="container mt-4">

        <h4>Subtitles</h4>



        <table class="table table-bordered table-striped subtitle-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Text</th>
                </tr>
            </thead>

            <tbody id="subtitle-table-body">

                <?php foreach ($subtitles as $entry): ?>

                <tr data-entry-id="<?= htmlspecialchars($entry['entry_id']) ?>">

                    <td>
                        <?= htmlspecialchars($entry['subtitle_number']) ?>
                    </td>
                    <td class="subtitle-time" data-entry-id="<?= htmlspecialchars($entry['entry_id']) ?>"
                        data-language-id="<?= htmlspecialchars($selectedLanguageId) ?>"
                        data-subtitle-number="<?= htmlspecialchars($entry['subtitle_number']) ?>">
                        <?= htmlspecialchars($entry['start_time']) ?>
                    </td>

                    <td class="subtitle-time" data-entry-id="<?= htmlspecialchars($entry['entry_id']) ?>"
                        data-language-id="<?= htmlspecialchars($selectedLanguageId) ?>"
                        data-subtitle-number="<?= htmlspecialchars($entry['subtitle_number']) ?>">
                        <?= htmlspecialchars($entry['end_time']) ?>
                    </td>

                    <td class="subtitle-text" data-entry-id="<?= htmlspecialchars($entry['entry_id']) ?>"
                        data-language-id="<?= htmlspecialchars($selectedLanguageId) ?>"
                        data-subtitle-number="<?= htmlspecialchars($entry['subtitle_number']) ?>">
                        <?= nl2br(htmlspecialchars($entry['text'])) ?>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>

        </table>

    </div>

</div>

<?php endif; ?>


<!-- ========================================= -->
<!-- Successful Upload -->
<!-- ========================================= -->

<?php if ($uploadResult !== null): ?>

<div class="container mt-4">

    <div class="alert alert-success">

        <h5>Upload Successful</h5>

        <p>
            <strong>Project:</strong>
            <?= htmlspecialchars($uploadResult['project_title']) ?>
        </p>

        <p>
            <strong>Language:</strong>
            <?= htmlspecialchars($uploadResult['language_name']) ?>
        </p>

        <p>
            <strong>File:</strong>
            <?= htmlspecialchars($uploadResult['file_name']) ?>
        </p>

        <p class="mb-0">
            <strong>Subtitle Entries:</strong>
            <?= htmlspecialchars($uploadResult['entry_count']) ?>
        </p>

    </div>

</div>

<?php endif; ?>


<!-- ========================================= -->
<!-- Upload Error Modal -->
<!-- ========================================= -->

<?php if ($uploadError !== null): ?>

<div class="modal fade" id="uploadErrorModal" tabindex="-1" aria-labelledby="uploadErrorModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="uploadErrorModalLabel">
                    Upload Failed
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <?= htmlspecialchars($uploadError) ?>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>


<script src="subtitles.js"></script>

<?php if ($uploadError !== null): ?>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const errorModalElement =
        document.getElementById("uploadErrorModal");

    const errorModal =
        new bootstrap.Modal(errorModalElement);

    errorModal.show();

});
</script>

<?php endif; ?>


<?php include __DIR__ . '/../../includes/footer.php'; ?>