
<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// Fetch dropdown data
$users      = $pdo->query("SELECT ID, username FROM Users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$places     = $pdo->query("SELECT PlaceID, PlaceName FROM Places ORDER BY PlaceName")->fetchAll(PDO::FETCH_ASSOC);
$types      = $pdo->query("SELECT TypeID, TypeName FROM TransactionTypes ORDER BY TypeName")->fetchAll(PDO::FETCH_ASSOC);
$provinces  = $pdo->query("SELECT ProvinceID, ProvinceCode FROM Provinces ORDER BY ProvinceCode")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC);
$items      = $pdo->query("SELECT ItemID, ItemName FROM Items ORDER BY ItemName")->fetchAll(PDO::FETCH_ASSOC);
$units      = $pdo->query("SELECT UnitID, UnitName FROM Units ORDER BY UnitName")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Insert New Transaction</h4>
    </div>
    <div class="card-body">
        <!-- Header Form -->
        <form id="transactionHeaderForm" class="row g-2 align-items-end">
            <!-- User -->
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#userModal" tabindex="-1">
                    User +
                </button>
                <select id="userDropdown" name="user" class="form-select" required>
                    <option value="">--Select User--</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['ID'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date -->
            <div class="col-md-2">
                <input type="date" name="date" class="form-control" required>
            </div>

            <!-- Place -->
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#placeModal" tabindex="-1">
                    Place +
                </button>
                <select name="place" class="form-select">
                    <option value="">--Select Place--</option>
                    <?php foreach ($places as $p): ?>
                        <option value="<?= $p['PlaceID'] ?>"><?= htmlspecialchars($p['PlaceName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Account -->
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#accountModal" tabindex="-1">
                    Account +
                </button>
                <select id="accountDropdown" name="account" class="form-select" required>
                    <option value="">--Select Account--</option>
                </select>
            </div>

            <!-- Type -->
            <div class="col-md-2">
                <select name="type" class="form-select" required>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['TypeID'] ?>"><?= htmlspecialchars($t['TypeName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Province -->
            <div class="col-md-1">
                <label class="form-label">Province</label>
                <select name="province" class="form-select">
                    <option value="">--</option>
                    <?php foreach ($provinces as $pr): ?>
                        <option value="<?= $pr['ProvinceID'] ?>"><?= htmlspecialchars($pr['ProvinceCode']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category in Header -->
            <div class="col-md-2 mt-2">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#categoryModal" tabindex="-1">
                    Category +
                </button>
                <select id="headerCategory" name="category" class="form-select">
                    <option value="">--Select Category--</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Add Items button -->
            <div class="col-12 mt-3">
                <button type="button" id="headerNextBtn" class="btn btn-primary">Add Items</button>
            </div>
        </form>

        <!-- Detail Section (hidden initially) -->
        <form id="transactionDetailForm" class="row g-2 align-items-end mt-3" style="display:none;">
            <!-- Category (pre-filled from header) -->
            <div class="col-md-2">
                <select id="detailCategory" name="detailCategory" class="form-select">
                    <option value="">--Select Category--</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Item -->
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#itemModal" tabindex="-1">
                    Item +
                </button>
                <select name="item" class="form-select" required>
                    <option value="">--Select Item--</option>
                    <?php foreach ($items as $i): ?>
                        <option value="<?= $i['ItemID'] ?>"><?= htmlspecialchars($i['ItemName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tax -->
            <div class="col-auto d-flex flex-column">
                <div class="form-check mt-1">
                    <input type="checkbox" name="tax" value="1" class="form-check-input">
                    <label class="form-check-label">Tax</label>
                </div>
            </div>

            <!-- Quantity -->
            <div class="col-auto">
                <input type="number" name="quantity" class="form-control" placeholder="Qty" step="0.01">
            </div>

            <!-- Price -->
            <div class="col-auto">
                <input type="number" name="price" class="form-control" placeholder="Price" step="0.01">
            </div>

            <!-- Unit -->
            <div class="col-auto">
                <button type="button" class="btn btn-outline-primary w-100 mb-1" data-bs-toggle="modal" data-bs-target="#unitModal" tabindex="-1">
                    Unit +
                </button>
                <select name="unit" class="form-select">
                    <option value="">--</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['UnitID'] ?>"><?= htmlspecialchars($u['UnitName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Comment -->
            <div class="col-md-4">
                <input type="text" name="comment" class="form-control" placeholder="Comment">
            </div>

            <!-- Insert Transaction button -->
            <div class="col-12 text-end mt-2">
                <button type="submit" class="btn btn-success">Insert Transaction</button>
            </div>
        </form>
    </div>
</div>

    <div class="mb-3">
        <button id="toggleTransactions" class="btn btn-secondary">
            Show Transactions
        </button>
    </div>
</div>

<div id="transactionsContainer" style="display: none;">
    <!-- Your table goes here -->
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Transactions for Selected User</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Place</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Province</th>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Tax</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Comment</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be filled dynamically via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>  
</div>
<div class="card mt-4">
    <div class="card-header bg-light">
        <strong>Current Bill</strong>
    </div>
    <div class="card-body">
        <table class="table table-bordered" id="billTable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Tax</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Unit</th>
                    <th>Comment</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="text-end">
            <strong id="billTotal">Total: $0.00</strong>
        </div>
        <div class="mt-2 text-end">
            <button type="button" id="finalizeBillBtn" class="btn btn-success">Finalize Bill</button>
        </div>
    </div>
</div>


<!-- ============================ -->
<!-- Modals (Place, Account, Item, Category, Unit) -->
<!-- ============================ -->
<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="editTransactionModalLabel">Edit Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editTransactionForm" novalidate>
          <input type="hidden" name="IDFinancialTransaction" id="editID">

          <div class="row mb-3">
            <div class="col">
              <label>Date</label>
              <input type="date" class="form-control" name="Date" id="editDate" required>
            </div>
            <div class="col">
              <label>Quantity</label>
              <input type="number" step="0.01" class="form-control" name="Quantity" id="editQuantity" required>
            </div>
            <div class="col">
              <label>Price</label>
              <input type="number" step="0.01" class="form-control" name="Price" id="editPrice" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col">
              <label>Place</label>
              <select class="form-select" name="PlaceID" id="editPlace" required></select>
            </div>
            <div class="col">
              <label>Account</label>
              <select class="form-select" name="AccountID" id="editAccount" required></select>
            </div>
            <div class="col">
              <label>Type</label>
              <select class="form-select" name="TypeID" id="editType" required></select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col">
              <label>Province</label>
              <select class="form-select" name="ProvinceID" id="editProvince" required></select>
            </div>
            <div class="col">
              <label>Category</label>
              <select class="form-select" name="CategoryID" id="editCategory" required></select>
            </div>
            <div class="col">
              <label>Item</label>
              <select class="form-select" name="ItemID" id="editItem" required></select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col">
              <label>Unit</label>
              <select class="form-select" name="UnitID" id="editUnit" required></select>
            </div>
            <div class="col">
              <label>Tax</label>
              <input type="number" step="0.01" class="form-control" name="Tax" id="editTax">
            </div>
            <div class="col">
              <label>Comment</label>
              <input type="text" class="form-control" name="Comment" id="editComment">
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="saveTransactionBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>



<?php
// Modals same as before: #placeModal, #accountModal, #itemModal, #categoryModal, #unitModal
// Use the same structure as in previous example
?>

<!-- ============================ -->
<!-- AJAX Handlers -->
<!-- ============================ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

// ---------- Header/Detail Forms ----------
const headerForm = document.getElementById('transactionHeaderForm');
const detailForm = document.getElementById('transactionDetailForm');
const headerNextBtn = document.getElementById('headerNextBtn');

let headerData = {};

// Show detail section on Add Items click
if (headerNextBtn) {
    headerNextBtn.addEventListener('click', function () {
    // Save header values
    const formData = new FormData(headerForm);
    formData.forEach((value, key) => { headerData[key] = value; });

    // Show detail form
    detailForm.style.display = 'flex';

    // Disable header inputs to prevent changes
    headerForm.querySelectorAll('input, select, button').forEach(el => el.disabled = true);

    // pre-fill detail Category from header ---
    const detailCategorySelect = document.getElementById('detailCategory');
    if (detailCategorySelect && headerData.category) {
        detailCategorySelect.value = headerData.category;
    }
        // set focus on Item dropdown ---
        const itemSelect = detailForm.querySelector('select[name="item"]');
    if (itemSelect) itemSelect.focus();
});

}

// ---------- Populate Accounts based on User ----------
const userDropdown = document.getElementById('userDropdown');
const accountDropdown = document.getElementById('accountDropdown');

if (userDropdown && accountDropdown) {
    userDropdown.addEventListener('change', function () {
        const userId = this.value;
        accountDropdown.innerHTML = '<option value="">--Select Account--</option>'; // reset

        if (!userId) return;

        // AJAX to get accounts
        $.getJSON('get_accounts.php', { userId: userId }, function (data) {
            if (data.length === 0) {
                accountDropdown.innerHTML += '<option value="">No accounts for this user</option>';
            } else {
                data.forEach(function (acc) {
                    const opt = document.createElement('option');
                    opt.value = acc.AccountID;
                    opt.textContent = acc.AccountName;
                    accountDropdown.appendChild(opt);
                });
            }
        });

        // Load transactions table for this user
        loadTransactions(userId);
    });
}

// ---------- Transactions Table Toggle ----------
const toggleBtn = document.getElementById("toggleTransactions");
const container = document.getElementById("transactionsContainer");
if (toggleBtn && container) {
    toggleBtn.addEventListener("click", function () {
        if (container.style.display === "none") {
            container.style.display = "block";
            toggleBtn.textContent = "Hide Transactions";
        } else {
            container.style.display = "none";
            toggleBtn.textContent = "Show Transactions";
        }
    });
}

// ---------- Begin Load Transactions Table ----------
function loadTransactions(userId) {
    const tbody = document.querySelector('#transactionsTable tbody');
    if (!userId || !tbody) return;

    $.getJSON('get_transactions.php', { userId: userId }, function (data) {
        let rows = '';
        if (!data || data.length === 0) {
            rows = `<tr><td colspan="13" class="text-center text-muted">No transactions found</td></tr>`;
        } else {
            data.forEach(function (tx) {
                rows += `<tr data-id="${tx.IDFinancialTransaction}">
                    <td>${tx.Date}</td>
                    <td>${tx.Place}</td>
                    <td>${tx.Account}</td>
                    <td>${tx.Type}</td>
                    <td>${tx.Province}</td>
                    <td>${tx.Category}</td>
                    <td>${tx.Item}</td>
                    <td>${tx.Tax}</td>
                    <td>${tx.Quantity}</td>
                    <td>${tx.Price}</td>
                    <td>${tx.Unit}</td>
                    <td>${tx.Comment}</td>
                    <td style="width: 150px;">
                        <div class="d-flex">
                            <button class="btn btn-sm btn-primary me-1" onclick="openEditModal(${tx.IDFinancialTransaction})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteTransaction(${tx.IDFinancialTransaction})">Delete</button>
                        </div>
                    </td>
                </tr>`;
            });
        }
        tbody.innerHTML = rows;
    });
}

// ---------- End Load Transactions Table ----------

let currentBill = []; // array to hold bill items

// Intercept detail form submission
document.getElementById('transactionDetailForm').addEventListener('submit', function(e){
    e.preventDefault();

    const formData = new FormData(this);
    const detail = {};
    formData.forEach((value, key) => { detail[key] = value; });
    
  // Compute numeric tax
    const isTaxed  = this.querySelector('input[name="tax"]').checked;
    const quantity = parseFloat(detail.quantity || 0);
    const price    = parseFloat(detail.price || 0);

    const taxAmount = isTaxed ? price * quantity * 0.15 : 0;
    const lineTotal = price * quantity + taxAmount;

    // Replace checkbox value with actual tax amount
    detail.tax = taxAmount.toFixed(2);

    // Merge with headerData
    const transaction = { ...headerData, ...detail };
    currentBill.push(transaction);


    // Add row to bill table
    const tbody = document.querySelector('#billTable tbody');
    const row = document.createElement('tr');

    const itemName     = getSelectText('item', this);
    const categoryName = getSelectText('detailCategory', this);
    const unitName     = getSelectText('unit', this);

// Add row to bill table
row.innerHTML = `
    <td>${categoryName}</td>
    <td>${itemName}</td>
    <td>${detail.tax}</td>
    <td>${quantity}</td>
    <td>${price}</td>
    <td>${unitName}</td>
    <td>${detail.comment}</td>
    <td>${lineTotal.toFixed(2)}</td>
`;
tbody.appendChild(row);


    // Update running total
    let total = currentBill.reduce((sum, item) => {
        const multiplier = item.tax ? 1.15 : 1;
        return sum + (parseFloat(item.price || 0) * parseFloat(item.quantity || 1) * multiplier);
    }, 0);
    document.getElementById('billTotal').innerText = "Total: $" + total.toFixed(2);

    // Clear detail form (ready for next item)
    this.reset();

   // --- Re-populate detail Category from header ---
   const detailCategorySelect = document.getElementById('detailCategory');
    if (detailCategorySelect && headerData.category) {
        detailCategorySelect.value = headerData.category;
    }

    // Focus back on Item dropdown for next entry
    this.querySelector('select[name="item"]').focus();
});

document.getElementById('finalizeBillBtn').addEventListener('click', function () {
    if (currentBill.length === 0) {
        alert("No items in bill!");
        return;
    }

    fetch('finalize_bill.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(currentBill)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);

            // Force page reload
            window.location.href = window.location.href;
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error saving bill.');
    });
});

// ---------- Quick-add Modals (unchanged) ----------
function setupQuickAdd(formId, phpEndpoint, selectName) {
    $(formId).on('submit', function(e){
        e.preventDefault();
        $.post(phpEndpoint, $(this).serialize(), function(data){
            if(data.success){
                $(`select[name="${selectName}"]`).append(
                    `<option value="${data.id}" selected>${data.name}</option>`
                );
                $(formId).closest('.modal').modal('hide');
                $(formId)[0].reset();
            } else {
                alert('Error adding ' + selectName);
            }
        }, 'json');
    });
}

setupQuickAdd('#itemForm', 'add_item.php', 'item');
setupQuickAdd('#categoryForm', 'add_category.php', 'category');
setupQuickAdd('#accountForm', 'add_account.php', 'account');
setupQuickAdd('#unitForm', 'add_unit.php', 'unit');
setupQuickAdd('#placeForm', 'add_place.php', 'place');

function getSelectText(selectName, form) {
    const sel = form.querySelector(`select[name="${selectName}"]`);
    return sel ? sel.options[sel.selectedIndex].text : '';
}

});
document.addEventListener("DOMContentLoaded", function() {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.focus();
    }
});
// begin JS for EDIT MODAL
// Preload dropdown options
// Store dropdown data globally
let currentUserId = $('#userSelect').val();
let places, accounts, types, provinces, categories, items, units;

// Toast function
function showToast(message, type='success') {
    const toastHTML = `<div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;
    const $toast = $(toastHTML);
    $('#toastContainer').append($toast);
    const toast = new bootstrap.Toast($toast[0]);
    toast.show();
}

// Load dropdowns
function loadDropdowns(callback) {
    $.getJSON('get_dropdowns.php', function(data) {
        places = data.places; accounts = data.accounts; types = data.types;
        provinces = data.provinces; categories = data.categories; items = data.items; units = data.units;

        fillDropdown('#editPlace', places, 'PlaceID', 'PlaceName');
        fillDropdown('#editAccount', accounts, 'AccountID', 'AccountName');
        fillDropdown('#editType', types, 'TypeID', 'TypeName');
        fillDropdown('#editProvince', provinces, 'ProvinceID', 'ProvinceCode');
        fillDropdown('#editCategory', categories, 'CategoryID', 'CategoryName');
        fillDropdown('#editItem', items, 'ItemID', 'ItemName');
        fillDropdown('#editUnit', units, 'UnitID', 'UnitName');

        if (typeof callback === 'function') callback();
    });
}

function fillDropdown(selector, data, valueField, textField) {
    const $sel = $(selector);
    $sel.empty();
    data.forEach(d => $sel.append($('<option>').val(String(d[valueField])).text(d[textField])));
}

// Load transactions
function loadTransactions(userId) {
    currentUserId = userId;

    $.getJSON('get_transactions.php', { userId: userId }, function(data) {
        const tbody = $('#transactionsTable tbody');
        tbody.empty();

        data.forEach(tx => {
            tbody.append(`
                <tr data-id="${tx.IDFinancialTransaction}">
                    <td>${tx.Date}</td>
                    <td>${tx.Place}</td>
                    <td>${tx.Account}</td>
                    <td>${tx.Type}</td>
                    <td>${tx.Province}</td>
                    <td>${tx.Category}</td>
                    <td>${tx.Item}</td>
                    <td>${tx.Tax}</td>
                    <td>${tx.Quantity}</td>
                    <td>${tx.Price}</td>
                    <td>${tx.Unit}</td>
                    <td>${tx.Comment}</td>
                    <td>
                        <div class="d-flex">
                            <button class="btn btn-sm btn-primary me-1" onclick="openEditModal(${tx.IDFinancialTransaction})">Edit</button>
                            <a href="delete_transaction.php?id=${tx.IDFinancialTransaction}" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                        </div>
                    </td>
                </tr>
            `);
        });
    });
}


// Open modal
function openEditModal(id) {
    $.getJSON('get_transaction.php', { id: id }, function(tx) {
        $('#editID').val(tx.IDFinancialTransaction);
        $('#editDate').val(tx.Date);
        $('#editQuantity').val(tx.Quantity);
        $('#editPrice').val(tx.Price);
        $('#editTax').val(tx.Tax);
        $('#editComment').val(tx.Comment);

        loadDropdowns(function() {
            $('#editPlace').val(String(tx.PlaceID));
            $('#editAccount').val(String(tx.AccountID));
            $('#editType').val(String(tx.TypeID));
            $('#editProvince').val(String(tx.ProvinceID));
            $('#editCategory').val(String(tx.CategoryID));
            $('#editItem').val(String(tx.ItemID));
            $('#editUnit').val(String(tx.UnitID));

            new bootstrap.Modal(document.getElementById('editTransactionModal')).show();
        });
    });
}

// Save changes
$('#saveTransactionBtn').click(function() {
    if (!$('#editTransactionForm')[0].checkValidity()) {
        $('#editTransactionForm')[0].reportValidity();
        return;
    }

    $.post('update_transaction.php', $('#editTransactionForm').serialize(), function(response) {
        if (response.success && response.updatedTx) {
            // ✅ Close modal
            bootstrap.Modal.getInstance($('#editTransactionModal')[0]).hide();

            // ✅ Show toast
            showToast('Transaction updated successfully!', 'success');

            // ✅ Find and update only the edited row
            const tx = response.updatedTx;
            const row = $(`#transactionsTable tbody tr[data-id="${tx.IDFinancialTransaction}"]`);

            if (row.length) {
                row.html(`
                    <td>${tx.Date}</td>
                    <td>${tx.Place}</td>
                    <td>${tx.Account}</td>
                    <td>${tx.Type}</td>
                    <td>${tx.Province}</td>
                    <td>${tx.Category}</td>
                    <td>${tx.Item}</td>
                    <td>${tx.Tax}</td>
                    <td>${tx.Quantity}</td>
                    <td>${tx.Price}</td>
                    <td>${tx.Unit}</td>
                    <td>${tx.Comment}</td>
                    <td>
                        <div class="d-flex">
                            <button class="btn btn-sm btn-primary me-1" onclick="openEditModal(${tx.IDFinancialTransaction})">Edit</button>
                            <a href="delete_transaction.php?id=${tx.IDFinancialTransaction}" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                        </div>
                    </td>
                `);
            }
        } else {
            showToast(response.message || 'Failed to update transaction', 'danger');
        }
    }, 'json');
});

function deleteTransaction(transactionId) {
    console.log("deleteTransaction called with ID:", transactionId); // 🔍 Debug line

    if (!transactionId) {
        console.error("Invalid transactionId:", transactionId);
        return;
    }

    if (!confirm('Are you sure you want to delete this transaction?')) return;

    $.post('delete_transaction.php', { id: transactionId }, function(response) {
        console.log("Response from delete_transaction.php:", response); // 🔍 Debug line
        if (response.success) {
            const row = $(`#transactionsTable tbody tr[data-id="${transactionId}"]`);
            if (row.length) row.remove();
            showToast('Transaction deleted successfully!', 'success');
        } else {
            showToast(response.message || 'Failed to delete transaction', 'danger');
        }
    }, 'json');
}

// Initialize
$(document).ready(function() {
    loadDropdowns(); // preload dropdowns
    loadTransactions(currentUserId);

    $('#userSelect').change(function() {
        loadTransactions($(this).val());
    });
});
// END JS for EDIT MODAL

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
