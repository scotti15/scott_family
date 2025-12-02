<?php
require_once '../../config/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/header.php';
include '../../includes/navbar.php';

// Get user role from session
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');
?>

<div class="container mt-4">

    <h2>Shopping List</h2>

    <!-- Row 1: Main inputs -->
    <div class="card p-3 mb-4">
        <div class="row mb-2">
          <label>
            <input type="checkbox" id="bargainCheckbox" />
            Bargain item
        </label>
            <!-- Item -->
            <div class="col-md-3 mt-2">
                <button type="button"
                        class="btn btn-outline-primary w-100 mb-1"
                        data-bs-toggle="modal"
                        data-bs-target="#addItemModal"
                        tabindex="-1">
                    Item +
                </button>

                <select id="itemSelect" name="item" class="form-select">
                    <option value="">--Select Item--</option>
                    <?php foreach ($items as $it): ?>
                    <option value="<?= $it['ItemID'] ?>"><?= htmlspecialchars($it['ItemName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mt-2">
    <!-- Button that replaces the label -->
            <button type="button"
                    class="btn btn-outline-primary w-100 mb-1"
                    data-bs-toggle="modal"
                    data-bs-target="#addBrandModal"
                    tabindex="-1">
                Brand +
            </button>
            <!-- Dropdown -->
            <select id="brandSelect" class="form-select">
                <option value="">--Select Brand--</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['BrandID'] ?>"><?= htmlspecialchars($b['BrandName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Place -->
        <div class="col-md-3 mt-2">
            <button type="button"
                    class="btn btn-outline-primary w-100 mb-1"
                    data-bs-toggle="modal"
                    data-bs-target="#addPlaceModal"
                    tabindex="-1">
                Place +
            </button>
            <select id="placeSelect" name="place" class="form-select">
                <option value="">--Select Place--</option>
                <?php foreach ($places as $p): ?>
                <option value="<?= $p['PlaceID'] ?>"><?= htmlspecialchars($p['PlaceName']) ?></option>
                <?php endforeach; ?>
        </select>
        </div>
            <div class="col-md-1 mt-2">
                <label for="priceInput"
                    class="btn btn-outline-primary w-100 mb-1"
                    tabindex="-1">Price (¢)</label>
                <input type="number" step="1" id="priceInput" class="form-control">
            </div>
            <div class="col-md-1 mt-2">
                <label for="amountInput"
                    class="btn btn-outline-primary w-100 mb-1"
                    tabindex="-1">Amount</label>
                <input type="number" step="0.001" id="amountInput" class="form-control">
            </div>
            <!-- Unit -->
            <div class="col-md-1 mt-2">
                <button type="button"
                        class="btn btn-outline-primary w-100 mb-1"
                        data-bs-toggle="modal"
                        data-bs-target="#addUnitModal"
                        tabindex="-1">
                    Unit +
                </button>
                <select id="unitSelect" name="unit" class="form-select">
                    <option value="">--Select Unit--</option>
                    <?php foreach ($units as $u): ?>
                    <option value="<?= $u['UnitID'] ?>"><?= htmlspecialchars($u['UnitName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Row 2: Comments + Admin-only -->
        <div class="row mb-2">
            <div class="col-md-10">
                <label for="commentsInput">Comments</label>
                <input type="text" id="commentsInput" class="form-control">
            </div>
            <div class="col-md-2 align-self-end">
                <?php if ($isAdmin): ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="isAdminItem">
                    <label class="form-check-label" for="isAdminItem">Admin-only</label>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <button id="addToCartBtn" class="btn btn-primary mt-2">Add to Cart</button>
    </div>

    <!-- Price History Table -->
<div id="priceHistoryContainer" style="display:none;">
  <h5>Price History</h5>
  <table id="priceHistoryTable" class="table table-striped">
    <thead>
      <tr>
        <th>Brand</th>
        <th>Place</th>
        <th>Price</th>
        <th>Amount</th>
        <th>Unit</th>
        <th>Normalized Price</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>


    <!-- Shopping List Table -->
    <div class="card p-3">
    <table id="shoppingListTable" class="table table-striped">
        <thead>
            <tr>
            <th>Item</th>
            <th>Brand</th>
            <th>Place</th>
            <th>Price</th>
            <th>Amount</th>
            <th>Unit</th>
            <th>Normalized Price</th>
            <th>Comments</th>
            <th>Expiry Date</th> <!-- NEW -->
            <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
</table>

    </div>

</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addBrandLabel">Add Brand</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="brandForm">
          <div class="mb-3">
            <label class="form-label">Brand Name</label>
            <input type="text" class="form-control" name="brand_name" required>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="brandForm">Save Brand</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addItemLabel">Add Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="itemForm">
          <div class="mb-3">
            <label class="form-label">Item Name</label>
            <input type="text" class="form-control" name="item_name" required>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="itemForm">Save Item</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Place Modal -->
<div class="modal fade" id="addPlaceModal" tabindex="-1" aria-labelledby="addPlaceLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addPlaceLabel">Add Place</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="placeForm">
          <div class="mb-3">
            <label class="form-label">Place Name</label>
            <input type="text" class="form-control" name="place_name" required>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="placeForm">Save Place</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUnitLabel">Add Unit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="unitForm">
          <div class="mb-3">
            <label class="form-label">Unit Name</label>
            <input type="text" class="form-control" name="unit_name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Unit Type</label>
            <select class="form-select" name="unit_type" required>
              <option value="">--Select Type--</option>
              <option value="solid">Solid</option>
              <option value="liquid">Liquid</option>
              <option value="each">Each</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Conversion to Base</label>
            <input type="number" step="0.000001" class="form-control" name="conversion_to_base" required>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="unitForm">Save Unit</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- wider to fit two columns -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-2">
        <form id="editItemForm">
          <input type="hidden" id="editListID">

          <div class="row g-2"> <!-- g-2 adds small gutters -->
            <div class="col-md-6">
              <label for="editItemSelect" class="form-label">Item</label>
              <select id="editItemSelect" class="form-select"></select>
            </div>
            <div class="col-md-6">
              <label for="editBrandSelect" class="form-label">Brand</label>
              <select id="editBrandSelect" class="form-select"></select>
            </div>

            <div class="col-md-6">
              <label for="editPlaceSelect" class="form-label">Place</label>
              <select id="editPlaceSelect" class="form-select"></select>
            </div>
            <div class="col-md-6">
              <label for="editUnitSelect" class="form-label">Unit</label>
              <select id="editUnitSelect" class="form-select"></select>
            </div>

            <div class="col-md-6">
              <label for="editPriceInput" class="form-label">Price</label>
              <input type="number" step="0.000001" class="form-control" id="editPriceInput">
            </div>
            <div class="col-md-6">
              <label for="editAmountInput" class="form-label">Amount</label>
              <input type="number" step="0.000001" class="form-control" id="editAmountInput">
            </div>
            <div class="mb-3">
              <label for="edit-expiry" class="form-label">Expiry Date</label>
              <input type="date" id="edit-expiry" class="form-control">
          </div>

            <div class="col-12">
              <label for="editCommentsInput" class="form-label">Comments</label>
              <input type="text" class="form-control" id="editCommentsInput">
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer p-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveEditBtn">Save</button>
      </div>
    </div>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="shopping_list.js"></script>
<?php include '../../includes/footer.php'; ?>
