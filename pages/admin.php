<?php
// admin.php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<!-- Bootstrap CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<div class="container mt-4">
  <h2>Admin Panel</h2>

  <!-- Horizontal Controls -->
  <div class="d-flex align-items-end mb-3 gap-2 flex-wrap">

    <!-- Table Selector -->
    <div class="w-25">
      <label for="tableSelect" class="form-label mb-0">Select Table:</label>
      <select id="tableSelect" class="form-select">
        <option value="accounts">Accounts</option>
        <option value="categories">Categories</option>
        <option value="items">Items</option>
        <option value="places">Places</option>
        <option value="units">Units</option>
        <option value="transactiontypes">Transaction Types</option>
        <option value="menu_items">Menu Items</option>
      </select>
    </div>

    <!-- Search Filter -->
    <div class="w-25">
      <label class="form-label mb-0">&nbsp;</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search table...">
      </div>
    </div>

    <!-- Add Button -->
    <div class="w-auto ms-auto">
      <label class="form-label mb-0">&nbsp;</label>
      <button id="addItemBtn" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add New Item
      </button>
    </div>
  </div>

  <!-- Table Container -->
  <div id="adminTableContainer" class="table-responsive"></div>

</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="itemForm" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Add/Edit Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="formFields"></div>
        <input type="hidden" id="itemId" name="id">
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  const tableSelect = document.getElementById('tableSelect');
  const container = document.getElementById('adminTableContainer');
  const addBtn = document.getElementById('addItemBtn');
  const formFields = document.getElementById('formFields');
  const itemForm = document.getElementById('itemForm');
  const modalTitle = document.getElementById('modalTitle');
  const itemIdInput = document.getElementById('itemId');
  const tableSearch = document.getElementById('tableSearch');
  const bootstrapModal = new bootstrap.Modal(document.getElementById('itemModal'));

  let currentTable = tableSelect.value;
  let currentData = [];
  let filteredData = [];

  const tableColumns = {
    accounts: ['AccountID','AccountName','UserID'],
    categories: ['CategoryID','CategoryName'],
    items: ['ItemID','ItemName','is_food','food_category'],
    places: ['PlaceID','PlaceName','logoPath','Province'],
    units: ['UnitID','UnitName'],
    transactiontypes: ['TypeID','TypeName'],
    menu_items: ['id','parent_id','title','link','sort_order']
  };

  const booleanFields = {
  items: ['is_food'],
  accounts: [],          // add any boolean fields if you have
  categories: [],
  places: [],
  units: [],
  transactiontypes: [],
  menu_items: []
};

  // Fetch and render table
  async function fetchTable(table){
    const res = await fetch(`admin_fetch.php?table=${table}`);
    const data = await res.json();
    currentData = data;
    filteredData = data;
    renderTable(filteredData);
  }

  function renderTable(data){
    if(!data.length){
      container.innerHTML = '<p>No records.</p>';
      return;
    }

    const cols = tableColumns[currentTable];
    let html = '<table class="table table-striped table-hover"><thead class="table-dark"><tr>';
    cols.forEach(c=> html += `<th>${c}</th>`);
    html += '<th>Actions</th></tr></thead><tbody>';

    data.forEach(row => {
  html += `<tr data-id="${row.id}">`;
  cols.forEach(c => {
    let value = row[c] ?? '';

  // Check if this column is a boolean field
  if (booleanFields[currentTable] && booleanFields[currentTable].includes(c)) {
    value = `<input type="checkbox" disabled ${value ? 'checked' : ''}>`;
  }

    html += `<td class="text-center">${value}</td>`;
  });
  html += `<td>
    <button class="btn btn-sm btn-primary me-1 edit-btn">
      <i class="bi bi-pencil-square"></i>
    </button>
    <button class="btn btn-sm btn-danger delete-btn">
      <i class="bi bi-trash"></i>
    </button>
  </td></tr>`;
});

    html += '</tbody></table>';
    container.innerHTML = html;
  }

  // Show modal
  function showModal(edit=false, data=null){
    formFields.innerHTML = '';
    const cols = tableColumns[currentTable].filter(c=> c !== 'id');

    cols.forEach(c=>{
      let val = edit && data ? data[c] : '';

      // Special case: items.is_food → checkbox
      if(currentTable === 'items' && c === 'is_food'){
        formFields.innerHTML += `
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="fields[${c}]" id="${c}" ${val ? 'checked' : ''}>
            <label class="form-check-label" for="${c}">${c}</label>
          </div>
        `;
      } else {
        formFields.innerHTML += `
          <div class="mb-3">
            <label class="form-label">${c}</label>
            <input class="form-control" name="fields[${c}]" value="${val}" required>
          </div>
        `;
      }
    });

    itemIdInput.value = edit && data ? data.id : '';
    modalTitle.textContent = edit ? 'Edit Item' : 'Add Item';
    bootstrapModal.show();
  }

  // Edit / Delete functions
  function editItem(id){
    const row = currentData.find(r=>r.id == id);
    showModal(true, row);
  }

  async function deleteItem(id){
    if(!confirm('Delete this item?')) return;
    const res = await fetch('admin_delete.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `table=${currentTable}&id=${id}`
    });
    const data = await res.json();
    if(data.success) fetchTable(currentTable);
  }

  // Event delegation for dynamic buttons
  container.addEventListener('click', e => {
    const tr = e.target.closest('tr');
    if(!tr) return;
    const id = tr.dataset.id;

    if(e.target.closest('.edit-btn')){
      editItem(id);
    } else if(e.target.closest('.delete-btn')){
      deleteItem(id);
    }
  });

  // Table selector change
  tableSelect.addEventListener('change', e=>{
    currentTable = e.target.value;
    fetchTable(currentTable);
    tableSearch.value = ''; // reset search
  });

  // Add button
  addBtn.addEventListener('click', ()=>showModal(false));

  // Form submission
  itemForm.addEventListener('submit', async e=>{
    e.preventDefault();
    const formData = new FormData(itemForm);

    // Handle is_food checkbox for Items table
    if(currentTable === 'items'){
      const checkbox = itemForm.querySelector('input[name="fields[is_food]"]');
      formData.set('fields[is_food]', checkbox.checked ? 1 : 0);
    }

    formData.append('table', currentTable);
    formData.append('action', itemIdInput.value ? 'edit' : 'add');

    const res = await fetch('admin_save.php',{
      method: 'POST',
      body: new URLSearchParams(formData)
    });

    const data = await res.json();
    if(data.success){
      bootstrapModal.hide();
      fetchTable(currentTable);
    }
  });

  // Search filter
  tableSearch.addEventListener('input', () => {
    const val = tableSearch.value.toLowerCase();
    filteredData = currentData.filter(row => {
      return tableColumns[currentTable].some(c => {
        return (row[c]+'').toLowerCase().includes(val);
      });
    });
    renderTable(filteredData);
  });

  // Initial fetch
  fetchTable(currentTable);

});
</script>

<?php include '../includes/footer.php'; ?>
