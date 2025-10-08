<?php
require_once '../../config/db.php';
require_once '../../auth/check_login.php';
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    die("No user ID found. Please log in.");
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Fetch all other users
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$owner_id = $_GET['user_id'] ?? $userId;

if ($owner_id == $userId) {
    // Your own wishlist: only gifts you added
    $stmt = $pdo->prepare("
        SELECT *
        FROM gifts
        WHERE owner_id = ? AND giver_added = 0
        ORDER BY created_at DESC
    ");
    $stmt->execute([$owner_id]);
} else {
    // Other user's wishlist: all gifts, including those added by others
    $stmt = $pdo->prepare("
        SELECT g.*,
        COALESCE(SUM(c.quantity), 0) AS claimed_quantity,
        MAX(CASE WHEN c.claimer_id = ? THEN 1 ELSE 0 END) AS i_claimed,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, ' (', c.quantity, ')') SEPARATOR ', ') AS claimers
        FROM gifts g
        LEFT JOIN claims c ON g.gift_id = c.gift_id
        LEFT JOIN users u ON c.claimer_id = u.id
        WHERE g.owner_id = ?
        AND (g.expiry_date IS NULL OR g.expiry_date >= CURRENT_DATE)
        GROUP BY g.gift_id
        ORDER BY g.created_at DESC
");
$stmt->execute([$userId, $owner_id]);
}
$gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h2>Browse Wishlists</h2>

    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="user_id" class="form-label">Select a user:</label>
            </div>
            <div class="col-auto">
                <select name="user_id" id="user_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Choose a User --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($owner_id == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <?php if ($owner_id && !empty($gifts)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Link</th>
                    <th>Price</th>
                    <th>Multiple Allowed</th>
                    <?php if ($owner_id != $userId): ?>
                        <th>Claimed Quantity</th>
                    <?php endif; ?>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gifts as $gift): ?>
                    <tr>
                        <td><?= htmlspecialchars($gift['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($gift['description'])) ?></td>
                        <td>
                            <?php if ($gift['link']): ?>
                                <a href="<?= htmlspecialchars($gift['link']) ?>" target="_blank">View</a>
                            <?php endif; ?>
                        </td>
                        <td><?= isset($gift['price']) ? number_format($gift['price'],2) : '-' ?></td>
                        <td><?= $gift['allow_multiple'] ? 'Yes' : 'No' ?></td>
                        <?php if ($owner_id != $userId): ?>
                            <td><?= $gift['claimed_quantity'] ?? 0 ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($owner_id != $userId): 
                                if ($gift['claimed_quantity'] == 0) {
                                    $btnClass = 'btn-success'; // green
                                } elseif ($gift['allow_multiple'] || $gift['claimed_quantity'] < 1) {
                                    $btnClass = 'btn-warning'; // yellow
                                } else {
                                    $btnClass = 'btn-danger'; // red
                                }
                                ?>
                                <button class="btn btn-sm <?= $btnClass ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#claimModal"
                                    data-gift-id="<?= $gift['gift_id'] ?>"
                                    data-title="<?= htmlspecialchars($gift['title'], ENT_QUOTES) ?>"
                                    data-allow-multiple="<?= $gift['allow_multiple'] ?>"
                                    data-bs-toggle="tooltip"
                                    title="<?= $gift['claimers'] ? 'Claimed by: ' . htmlspecialchars($gift['claimers']) : 'Not yet claimed' ?>">
                                Claim
                            </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning editGiftBtn"
                                        data-gift-id="<?= $gift['gift_id'] ?>"
                                        data-title="<?= htmlspecialchars($gift['title'], ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($gift['description'] ?? '', ENT_QUOTES) ?>"
                                        data-link="<?= htmlspecialchars($gift['link'] ?? '', ENT_QUOTES) ?>"
                                        data-price="<?= $gift['price'] ?? '' ?>"
                                        data-allow-multiple="<?= $gift['allow_multiple'] ?>">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger deleteGiftBtn"
                                        data-gift-id="<?= $gift['gift_id'] ?>"
                                        data-title="<?= htmlspecialchars($gift['title'], ENT_QUOTES) ?>">
                                    Delete
                                </button>
                            <?php endif; ?>
                        </td>


                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($owner_id): ?>
        <div class="alert alert-info">This user has no active gifts.</div>
    <?php endif; ?>
</div>

<!-- Bootstrap Claim Modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-labelledby="claimModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="claimForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="claimModalLabel">Claim Gift</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="gift_id" id="modalGiftId">
          <p><strong id="modalGiftTitle"></strong></p>
          <div class="mb-3" id="quantityDiv">
            <label for="modalQuantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="modalQuantity" value="1" min="1" class="form-control">
          </div>
          <div id="claimAlert" class="alert d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="confirmClaimBtn">Confirm Claim</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelClaimBtn">Cancel</button>
          <button type="button" class="btn btn-success d-none" id="closeClaimBtn" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap Edit Modal -->
<div class="modal fade" id="editGiftModal" tabindex="-1" aria-labelledby="editGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editGiftForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editGiftModalLabel">Edit Gift</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="gift_id" id="editModalGiftId">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="editModalTitle" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" id="editModalDescription" class="form-control"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Link</label>
            <input type="url" name="link" id="editModalLink" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" name="price" id="editModalPrice" class="form-control">
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="allow_multiple" id="editModalAllowMultiple">
            <label class="form-check-label" for="editModalAllowMultiple">Allow multiple</label>
          </div>
          <div id="editGiftAlert" class="alert d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="editGiftSubmitBtn">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap Delete Modal -->

<div class="modal fade" id="deleteGiftModal" tabindex="-1" aria-labelledby="deleteGiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="deleteGiftForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteGiftModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="gift_id" id="deleteModalGiftId">
          <p>Are you sure you want to delete <strong id="deleteModalGiftTitle"></strong> from your wishlist?</p>
          <div id="deleteGiftAlert" class="alert d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger" id="deleteGiftSubmitBtn">Yes, Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Populate modal on show
const claimModal = document.getElementById('claimModal');
claimModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const giftId = button.getAttribute('data-gift-id');
    const title = button.getAttribute('data-title');
    const allowMultiple = button.getAttribute('data-allow-multiple') === '1';

    document.getElementById('modalGiftId').value = giftId;
    document.getElementById('modalGiftTitle').textContent = title;

    const qtyDiv = document.getElementById('quantityDiv');
    if (allowMultiple) {
        qtyDiv.style.display = 'block';
    } else {
        qtyDiv.style.display = 'none';
        document.getElementById('modalQuantity').value = 1;
    }

    const alertDiv = document.getElementById('claimAlert');
    alertDiv.classList.add('d-none');
    alertDiv.textContent = '';

    // Reset buttons
    document.getElementById('confirmClaimBtn').disabled = false;
    document.getElementById('cancelClaimBtn').disabled = false;
    document.getElementById('closeClaimBtn').classList.add('d-none');
});

// AJAX form submission
document.getElementById('claimForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('claim_gift_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('claimAlert');
        if (data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.textContent = data.message;

            // Disable Confirm and Cancel buttons
            document.getElementById('confirmClaimBtn').disabled = true;
            document.getElementById('cancelClaimBtn').disabled = true;

            // Show Close button
            document.getElementById('closeClaimBtn').classList.remove('d-none');

            // Update claimed quantity in table dynamically
            const button = document.querySelector(`button[data-gift-id="${formData.get('gift_id')}"]`);
            if (button) {
                const row = button.closest('tr');
                const claimedCell = row.querySelector('td:nth-last-child(2)');
                if (claimedCell) {
                    claimedCell.textContent = parseInt(claimedCell.textContent) + parseInt(formData.get('quantity'));
                }
            }

        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.message;
        }
    })
    .catch(err => {
        console.error(err);
    });
});

// Open Edit modal
document.querySelectorAll('.editGiftBtn').forEach(button => {
    button.addEventListener('click', function() {
        const giftId = this.dataset.giftId;
        document.getElementById('editModalGiftId').value = giftId;
        document.getElementById('editModalTitle').value = this.dataset.title;
        document.getElementById('editModalDescription').value = this.dataset.description;
        document.getElementById('editModalLink').value = this.dataset.link;
        document.getElementById('editModalPrice').value = this.dataset.price;
        document.getElementById('editModalAllowMultiple').checked = this.dataset.allowMultiple === '1';

        const modal = new bootstrap.Modal(document.getElementById('editGiftModal'));
        modal.show();

        const alertDiv = document.getElementById('editGiftAlert');
        alertDiv.classList.add('d-none');
        alertDiv.textContent = '';
    });
});

// AJAX submit for editing
document.getElementById('editGiftForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('edit_gift_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('editGiftAlert');
        if (data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.textContent = data.message;

            // Update table row dynamically
            const row = document.querySelector(`button[data-gift-id="${formData.get('gift_id')}"]`).closest('tr');
            row.querySelector('td:nth-child(1)').textContent = formData.get('title'); // adjust index to match table

            document.getElementById('editGiftSubmitBtn').disabled = true;
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.message;
        }
    })
    .catch(err => console.error(err));
});

// Open Delete modal
document.querySelectorAll('.deleteGiftBtn').forEach(button => {
    button.addEventListener('click', function() {
        const giftId = this.dataset.giftId;
        const title = this.dataset.title;

        document.getElementById('deleteModalGiftId').value = giftId;
        document.getElementById('deleteModalGiftTitle').textContent = title;

        const modal = new bootstrap.Modal(document.getElementById('deleteGiftModal'));
        modal.show();

        const alertDiv = document.getElementById('deleteGiftAlert');
        alertDiv.classList.add('d-none');
        alertDiv.textContent = '';
    });
});

// AJAX submit for deleting gift
document.getElementById('deleteGiftForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('delete_gift_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('deleteGiftAlert');
        if (data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.textContent = data.message;

            // Remove table row dynamically
            const row = document.querySelector(`button[data-gift-id="${formData.get('gift_id')}"]`).closest('tr');
            if (row) row.remove();

            document.getElementById('deleteGiftSubmitBtn').disabled = true;
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.message;
        }
    })
    .catch(err => console.error(err));
});

// Enable Bootstrap tooltips on hover
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});


</script>

<?php require_once '../../includes/footer.php'; ?>
