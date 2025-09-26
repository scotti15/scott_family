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

<div class="container-fluid mt-4">
  <div class="row">
    <!-- Sidebar: Account Balances -->
    <div class="col-md-3">
      <div class="card shadow-sm mb-3" id="sidebar">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">Account Balances</h5>
        </div>
        <div class="card-body p-2">
          <table class="table table-sm table-striped" id="accountBalancesTable">
            <thead>
              <tr>
                <th>Account</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="2" class="text-center text-muted">Select a user</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
      <!-- Header Form -->
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">Insert New Transaction</h4>
        </div>
        <div class="card-body">
          <form id="transactionHeaderForm" class="row g-2 align-items-end">
            <!-- User -->
            <div class="col-md-1">
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
            <div class="col-md-2 text-center">
              <label class="form-label">Date</label>
              <input type="date" name="date" class="form-control" required>
            </div>

            <!-- Place -->
            <div class="col-md-3">
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
            <div class="col-md-1">
              <label class="form-label">Account Type</label>
              <select name="type" class="form-select" required>
                <?php foreach ($types as $t): ?>
                  <option value="<?= $t['TypeID'] ?>"><?= htmlspecialchars($t['TypeName']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Province -->
            <div class="col-md-1">
              <label class="form-label">Province</label>
              <select id="provinceDropdown" name="province" class="form-select" required>
                <option value="">--</option>
                <?php foreach ($provinces as $pr): ?>
                  <option value="<?= $pr['ProvinceID'] ?>"><?= htmlspecialchars($pr['ProvinceCode']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Category -->
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

            <!-- Tax, Quantity, Price, Unit, Comment -->
            <div class="col-auto d-flex flex-column">
              <div class="form-check mt-1">
                <input type="checkbox" name="tax" value="1" class="form-check-input">
                <label class="form-check-label">Tax</label>
              </div>
            </div>
            <div class="col-auto">
              <input type="number" name="quantity" class="form-control" placeholder="Qty" step="0.0001">
            </div>
            <div class="col-auto">
              <input type="number" name="price" class="form-control" placeholder="Price" step="0.01">
            </div>
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

      <!-- Transactions Toggle Button -->
      <div class="mb-3">
        <button id="toggleTransactions" class="btn btn-secondary">
          Show Transactions
        </button>
      </div>

      
      <!-- Current Bill -->
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
                <th>Actions</th> <!-- new column for buttons -->
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
    </div>
  </div>
</div>

      <!-- Transactions Table -->
      <div id="transactionsContainer" style="display: none;">
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
                  <th>Total</th>
                  <th style="width: 150px;">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
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
let currentBill = []; // array to hold bill items
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
              
            let quantity = parseFloat(tx.Quantity) || 0;
            let price = parseFloat(tx.Price) || 0;
            let tax = parseFloat(tx.Tax) || 0;

            let total = (quantity * price) + tax;

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
                    <td>${total.toFixed(2)}</td> 
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

// let currentBill = []; // array to hold bill items

// Intercept detail form submission
document.getElementById('transactionDetailForm').addEventListener('submit', function(e){
    e.preventDefault();

    console.log("headerData at line add:", headerData);

    const formData = new FormData(this);
    const detail = {};
    formData.forEach((value, key) => { detail[key] = value; });
    
  // Compute numeric tax
    const isTaxed  = this.querySelector('input[name="tax"]').checked;
    const quantity = parseFloat(detail.quantity || 0);
    const price    = parseFloat(detail.price || 0);
    const isCredit = headerData.type === "1"; // adjust if you store type differently
    

    //const taxAmount = isTaxed ? price * quantity * 0.15 : 0;
    let taxAmount = isTaxed ? price * quantity * headerTaxRate : 0;
    
    // if (isCredit) taxAmount = -Math.abs(taxAmount);

    let lineTotal = price * quantity + taxAmount;

    // Store full precision internally
    detail.tax = taxAmount;       // store as number, not string
    detail.quantity = quantity;   // keep all decimals
    detail.lineTotal = lineTotal; // full precision
    
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
  <td>${taxAmount.toFixed(2)}</td>
  <td>${quantity.toFixed(3)}</td>
  <td>${price}</td>
  <td>${unitName}</td>
  <td>${detail.comment}</td>
  <td>${lineTotal.toFixed(2)}</td>
  <td>
    <button class="btn btn-sm btn-primary edit-bill-btn">Edit</button>
    <button class="btn btn-sm btn-danger delete-bill-btn">Delete</button>
  </td>
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

    const CASH_ACCOUNT_ID = 2; // Replace with your actual Cash account ID
    let roundingNeeded = false;
    let roundingAmount = 0;

    if (parseInt(headerData.account) === CASH_ACCOUNT_ID) {
        // Compute total
        const total = currentBill.reduce((sum, line) => {
            const qty = parseFloat(line.quantity || 0);
            const price = parseFloat(line.price || 0);
            const tax = parseFloat(line.tax || 0);
            return sum + qty * price + tax;
        }, 0);

        const roundedTotal = Math.round(total * 20) / 20; // nearest 0.05
        roundingAmount = parseFloat((roundedTotal - total).toFixed(2));

        if (roundingAmount !== 0) {
            roundingNeeded = true;

            // Show modal
            document.getElementById('roundingAmount').textContent =
                (roundingAmount >= 0 ? '+' : '-') + '$' + Math.abs(roundingAmount).toFixed(2);

            const roundingModal = new bootstrap.Modal(document.getElementById('roundingModal'));
            roundingModal.show();

            // Attach a one-time event listener to the OK button in modal
            const okBtn = document.getElementById('roundingOkBtn');
            okBtn.onclick = function () {
                // Add rounding line into currentBill
                const roundingLine = {
                    ...headerData,
                    detailCategory: '',
                    item: 'Rounding',
                    quantity: 1,
                    price: roundingAmount,
                    tax: 0,
                    lineTotal: roundingAmount,
                    comment: 'Auto rounding for Cash account'
                };
                currentBill.push(roundingLine);

                // Add visually into table
                const tbody = document.querySelector('#billTable tbody');
                const row = document.createElement('tr');
                row.innerHTML = `
                  <td></td>
                  <td>Rounding</td>
                  <td>0.00</td>
                  <td>1.000</td>
                  <td>${roundingAmount.toFixed(2)}</td>
                  <td></td>
                  <td>Auto rounding for Cash account</td>
                  <td>${roundingAmount.toFixed(2)}</td>
                  <td></td>
                `;
                tbody.appendChild(row);

                // Now save bill
                finalizeBill();
            };
        }
    }

    // If no rounding is needed, save immediately
    if (!roundingNeeded) {
        finalizeBill();
    }
});

function finalizeBill() {
  console.log("Preparing bill for FinalizeBill:", currentBill, headerData);

    fetch('finalize_bill.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(currentBill)
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = window.location.href; // reload
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error saving bill.');
        });
}

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
                const total = (parseFloat(tx.Quantity) * parseFloat(tx.Price)).toFixed(2);
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
                    <td>${total}</td> 
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

// Track current user globally

$('#userDropdown').change(function() {
    const userId = $(this).val();
    if (!userId) return;

    currentUserId = userId;

    // Load transactions table
    loadTransactions(userId);

    // Load account balances for sidebar
    loadAccountBalances(userId);
});

// Function to load account balances
function loadAccountBalances(userId) {
    $.getJSON('get_account_balances.php', { userId: userId }, function(data) {
        console.log("Data received from server:", data); // 🔍
        const tbody = $('#accountBalancesTable tbody');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.append('<tr><td colspan="2" class="text-center text-muted">No accounts found</td></tr>');
            return;
        }
        const formatter = new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD',
          });
          
        data.forEach(account => {
            tbody.append(`
                <tr>
                    <td>${account.AccountName}</td>
                    <td>${formatter.format(account.Balance)}</td>
                </tr>
            `);
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {

    const billTableBody = document.querySelector('#billTable tbody');
    const billTotalEl = document.getElementById('billTotal');

    // Global array holding the Current Bill items
   // let currentBill = []; // this should be the same array used when adding items

    // Function to recalculate and update the total
    function updateBillTotal() {
        let total = currentBill.reduce((sum, item) => {
            const qty = parseFloat(item.quantity || 0);
            const price = parseFloat(item.price || 0);
            const tax = parseFloat(item.tax || 0);
            return sum + qty * price + tax;
        }, 0);
        billTotalEl.textContent = 'Total: $' + total.toFixed(2);
    }

    // Delete button handler
    billTableBody.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-bill-btn')) {
            const row = e.target.closest('tr');
            if (!row) return;

            if (!confirm('Are you sure you want to delete this item?')) return;

            // Find index of the row in the table
            const rowsArray = Array.from(billTableBody.querySelectorAll('tr'));
            const index = rowsArray.indexOf(row);

            if (index > -1) {
                currentBill.splice(index, 1); // remove from array
            }

            // Remove the row from the table
            row.remove();

            // Update total
            updateBillTotal();
        }
    });
    // EDIT handler (replace your current edit handler with this)
billTableBody.addEventListener('click', function (e) {
  if (!(e.target && e.target.classList.contains('edit-bill-btn'))) return;

  const row = e.target.closest('tr');
  if (!row) return;

  // index of the row in the table (maps to currentBill)
  const rowsArray = Array.from(billTableBody.querySelectorAll('tr'));
  const index = rowsArray.indexOf(row);
  if (index === -1) return;

  const item = currentBill[index];
  if (!item) {
    console.warn('Edit: no corresponding item in currentBill at index', index);
    return;
  }

  // find the detail form
  const form = document.getElementById('transactionDetailForm');
  if (!form) {
    console.error('Edit: transactionDetailForm not found');
    return;
  }

  // make form visible (if you hide it with display:none)
  form.style.display = form.style.display === 'none' ? 'flex' : form.style.display;

  // helper to set a value if the element exists
  function setIfExists(selector, value, type = 'value') {
    const el = form.querySelector(selector);
    if (!el) {
      console.warn('Edit: missing selector', selector);
      return;
    }
    if (type === 'checked') {
      el.checked = !!value;
    } else {
      // for selects/inputs - assign the value (use '' if null/undefined)
      el.value = value == null ? '' : String(value);
      // If it's a select, optionally trigger change in case other logic depends on it:
      if (el.tagName.toLowerCase() === 'select') {
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  // Fill fields (adjust these selectors if your form uses different names)
  setIfExists('select[name="detailCategory"]', item.detailCategory ?? item.category ?? '');
  setIfExists('select[name="item"]', item.item ?? '');
  setIfExists('input[name="quantity"]', item.quantity ?? '');
  setIfExists('input[name="price"]', item.price ?? '');
  setIfExists('select[name="unit"]', item.unit ?? '');
  setIfExists('input[name="comment"]', item.comment ?? '');
  setIfExists('input[name="tax"]', parseFloat(item.tax) > 0, 'checked');

  // Remove the item from the array and row so re-submission will re-add it
  currentBill.splice(index, 1);
  row.remove();
  updateBillTotal();

  // Focus the first field in the form (item select)
  const firstField = form.querySelector('select[name="item"], input[name="item"], input, select');
  if (firstField) firstField.focus();
});

let headerTaxRate = 0; // default

});
document.addEventListener("DOMContentLoaded", function () {
    // ... your existing dropdown/insert transaction JS ...


    const provinceDropdown = document.getElementById('provinceDropdown');
    provinceDropdown.addEventListener('change', function () {
        const provinceId = this.value;

        if (!provinceId) {
            headerTaxRate = 0;
            return;
        }

        fetch('get_tax_rate.php?provinceId=' + provinceId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    headerTaxRate = data.taxRate;
                    console.log("Tax rate set to:", headerTaxRate);
                } else {
                    headerTaxRate = 0;
                    alert("Failed to get tax rate for selected province.");
                }
            })
            .catch(err => {
                console.error("Error fetching tax rate:", err);
                headerTaxRate = 0;
            });
    });

    // later in your Insert Transaction logic:
    // const taxAmount = isTaxed ? price * quantity * headerTaxRate : 0;
});

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
<!-- Rounding Info Modal -->
<!-- Rounding Modal -->
<div class="modal fade" id="roundingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content shadow-lg border-0 rounded-4">
      <div class="modal-header bg-gradient text-white rounded-top-4" 
           style="background: linear-gradient(135deg, #0d6efd, #6f42c1);">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-calculator-fill me-2"></i> Rounding Adjustment
        </h5>
        <button type="button" class="btn-close btn-close-white" 
                data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body text-center py-4">
        <p class="mb-3 fs-6 text-muted">
          A rounding adjustment is required for this <span class="fw-semibold">Cash</span> transaction:

          </p>  <!-- Penny image -->
      
        <img src="images/canadian_penny.jpg" alt="Canadian Penny" 
        class="mb-3 animate-spin" style="width:60px; height:auto; display:block; margin:0 auto;">

        <div class="display-6 fw-bold animate-scale" id="roundingAmount">-0.02</div>
      </div>

      <div class="modal-footer justify-content-center border-0 pb-4">
        <button type="button" id="roundingOkBtn" 
                class="btn btn-lg btn-primary px-4 rounded-pill shadow-sm"
                data-bs-dismiss="modal">
          <i class="bi bi-check-circle me-2"></i> OK
        </button>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 3s infinite;
}
</style>

<!-- Extra CSS -->
<style>
@keyframes popIn {
  0% { transform: scale(0.7); opacity: 0; }
  60% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(1); }
}

.animate-scale {
  display: inline-block;
}

.animate-scale.active {
  animation: popIn 0.4s ease;
}
.text-good { color: #198754 !important; } /* Bootstrap green */
.text-bad  { color: #dc3545 !important; } /* Bootstrap red */
</style>

<!-- JS to trigger animation + color -->
<script>
const roundingModal = document.getElementById('roundingModal');
const roundingAmount = document.getElementById('roundingAmount');

roundingModal.addEventListener('shown.bs.modal', () => {
  // Restart animation
  roundingAmount.classList.remove('active');
  void roundingAmount.offsetWidth; 
  roundingAmount.classList.add('active');

  
  // --- NEW: strip $ and convert to cents ---
  let raw = roundingAmount.textContent.replace('$', '').trim(); // remove $ symbol
  let value = parseFloat(raw); // now numeric
  if (isNaN(value)) value = 0;

  // --- NEW: convert to cents and format ---
  let cents = Math.round(Math.abs(value * 100));     // 2
  let sign = value > 0 ? '+' : '-';                  // keep sign
  roundingAmount.textContent = `${sign}${cents}¢`;   // display +2¢ or -2¢

  // Determine value & set color
  roundingAmount.classList.remove('text-good', 'text-bad');
  if (value < 0) {
    roundingAmount.classList.add('text-good'); // ✅ Negative = Green (good)
  } else if (value > 0) {
    roundingAmount.classList.add('text-bad');  // ✅ Positive = Red (bad)
  }
   // Clear previous pennies
   pennyContainer.innerHTML = '';

// Add spinning pennies
const pennySrc = 'images/canadian_penny.jpg';
for (let i = 0; i < cents; i++) {
  const img = document.createElement('img');
  img.src = pennySrc;
  img.alt = 'Canadian Penny';
  img.className = 'animate-spin';
  pennyContainer.appendChild(img);
}
});
</script>



<!-- Place Modal -->
<div class="modal fade" id="placeModal" tabindex="-1" aria-labelledby="placeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="placeForm">
        <div class="modal-header">
          <h5 class="modal-title" id="placeModalLabel">Add Place</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="placeName" class="form-label">Place Name</label>
            <input type="text" class="form-control" id="placeName" name="place_name" required>
          </div>
          <div id="placeFeedback" class="text-danger small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Place</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="itemForm">
        <div class="modal-header">
          <h5 class="modal-title" id="itemModalLabel">Add Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="itemName" class="form-label">Item Name</label>
            <input type="text" class="form-control" id="itemName" name="item_name" required>
          </div>
          <div id="itemFeedback" class="text-danger small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Item</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="categoryForm">
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="categoryName" class="form-label">Category Name</label>
            <input type="text" class="form-control" id="categoryName" name="category_name" required>
          </div>
          <div id="categoryFeedback" class="text-danger small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="accountForm">
        <div class="modal-header">
          <h5 class="modal-title" id="accountModalLabel">Add Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="accountName" class="form-label">Account Name</label>
            <input type="text" class="form-control" id="accountName" name="account_name" required>
          </div>
          <div id="accountFeedback" class="text-danger small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>
