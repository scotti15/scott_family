document.addEventListener('DOMContentLoaded', () => {
    const itemSelect = document.getElementById('itemSelect');
    itemSelect.focus();
    const brandSelect = document.getElementById('brandSelect');
    const placeSelect = document.getElementById('placeSelect');
    const unitSelect = document.getElementById('unitSelect');
    const priceInput = document.getElementById('priceInput');
    const amountInput = document.getElementById('amountInput');
    const commentsInput = document.getElementById('commentsInput');
    const isAdminItemCheckbox = document.getElementById('isAdminItem');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const shoppingListTableBody = document.querySelector('#shoppingListTable tbody');
    const bargainCheckbox = document.getElementById('bargainCheckbox');

    const fetchJSON = async url => (await fetch(url)).json();

    const populateDropdown = async (selectEl, url, placeholder='Select...') => {
        let data = await fetchJSON(url);
        data.sort((a,b) => a[Object.keys(a)[1]].localeCompare(b[Object.keys(b)[1]]));
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        data.forEach(d => {
            const option = document.createElement('option');
            option.value = d[Object.keys(d)[0]];
            option.textContent = d[Object.keys(d)[1]];
            selectEl.appendChild(option);
        });
    };

    const loadShoppingList = async () => {
        let data = await fetchJSON('get_shopping_list.php');
        console.log("data returned:", data);
        data.sort((a, b) => new Date(b.ExpiryDate) - new Date(a.ExpiryDate));

        shoppingListTableBody.innerHTML = '';
        data.forEach(row => {
            const tr = document.createElement('tr');
            if (row.IsBargain == "1") {
                console.log('Bargain row:', row.ItemName);
                tr.classList.add('bargain-item');
            }
            
            tr.innerHTML = `
                <td>${row.IsBargain == "1" ? "💰 " + row.ItemName : row.ItemName}</td>
                <td>${row.BrandName}</td>
                <td>${row.PlaceName}</td>
                <td>${parseFloat(row.Price).toFixed(2)}</td>
                <td>${parseFloat(row.Amount)}</td>
                <td>${row.UnitName}</td>
                <td>${computeNormalizedPrice(row)}</td>
                <td>${row.Comments ?? ''}</td>
                <td>${row.ExpiryDate ?? ''}</td> <!-- NEW -->
                <td>
                    <button class="btn btn-sm btn-primary edit-btn" data-id="${row.ListID}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${row.ListID}">Delete</button>
                </td>
            `;
            shoppingListTableBody.appendChild(tr);
        });
        
    };

    const computeNormalizedPrice = row => {
        const price = parseFloat(row.Price);      // already in cents
        const amount = parseFloat(row.Amount);
        const conv = parseFloat(row.ConversionToBase ?? 1);
    
        if (isNaN(price) || isNaN(amount) || amount <= 0) return '';
    
        if (row.UnitType === 'each') {
            // cents per each, formatted to 4 decimals
            return `${(price).toFixed(4)} ¢ / each`;
        } else {
            // convert amount to base unit (g or ml)
            const amountBase = amount * conv;
            if (amountBase <= 0) return '';
            const perUnit = price / amountBase;   // cents per g or ml
            return `${perUnit.toFixed(4)} ¢ / ${row.UnitType === 'solid' ? 'g' : 'ml'}`;
        }
    };
    
    const refreshDropdown = async (url, selectEl) => {
        let data = await fetchJSON(url);
        data.sort((a,b) => a[Object.keys(a)[1]].localeCompare(b[Object.keys(b)[1]]));
        selectEl.innerHTML = `<option value="">Select...</option>`;
        data.forEach(d => {
            const option = document.createElement('option');
            option.value = d[Object.keys(d)[0]];
            option.textContent = d[Object.keys(d)[1]];
            selectEl.appendChild(option);
        });
    };

    // ---------------- Add to Cart ----------------
    addToCartBtn.addEventListener('click', async () => {
        // Base payload
        const payload = {
            ItemID: itemSelect.value,
            Comments: commentsInput.value,
            IsAdminItem: isAdminItemCheckbox?.checked ? 1 : 0,
            IsBargain: bargainCheckbox?.checked ? 1 : 0
        };
    
        // Include extra fields only if it's a bargain item
        if (payload.IsBargain) {
            payload.BrandID = brandSelect.value;
            payload.PlaceID = placeSelect.value;
            payload.UnitID = unitSelect.value;
            payload.Price = priceInput.value;
            payload.Amount = amountInput.value;
    
            // Full validation for bargain items
            if (!payload.ItemID || !payload.BrandID || !payload.PlaceID || !payload.UnitID || !payload.Price || !payload.Amount) {
                alert('Please fill in all required fields for a bargain item.');
                return;
            }
        } else {
            // Normal items: only ItemID required
            if (!payload.ItemID) {
                alert('Please choose an item.');
                return;
            }
        }
    
        console.log("PAYLOAD SENT TO PHP:", JSON.stringify(payload, null, 2));
    
        try {
            const res = await fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
    
            const result = await res.json();
    
            if (result.success) {
                // Reset inputs
                itemSelect.value = '';
                brandSelect.value = '';
                placeSelect.value = '';
                unitSelect.value = '';
                priceInput.value = '';
                amountInput.value = '';
                commentsInput.value = '';
                if (isAdminItemCheckbox) isAdminItemCheckbox.checked = false;
                if (bargainCheckbox) bargainCheckbox.checked = false;
    
                loadShoppingList(); // refresh table
            } else {
                alert('Error adding item: ' + (result.message || 'Unknown error'));
            }
    
        } catch (err) {
            console.error('Fetch or JSON error:', err);
            alert('Error communicating with the server.');
        }
    });
    
    
    // ---------------- Modal Handlers ----------------
    const setupModal = (saveBtnId, modalId, inputs, endpoint, refreshUrl, refreshSelect) => {
        document.getElementById(saveBtnId).addEventListener('click', async () => {
            const payload = {};
            let empty = false;
            inputs.forEach(id => {
                const val = document.getElementById(id).value.trim();
                if(!val) empty = true;
                payload[id.replace(/^new/,'').toLowerCase()] = val;
            });
            if(empty) return;

            const res = await fetch(endpoint, {
                method:'POST',
                body: new URLSearchParams(payload)
            });
            const result = await res.json();
            if(result.success){
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                inputs.forEach(id=>document.getElementById(id).value='');
                refreshDropdown(refreshUrl, refreshSelect);
            } else alert(result.error || 'Error saving.');
        });
    };


    // ---------------- Initial Load ----------------
    populateDropdown(itemSelect,'get_items.php','Select Item');
    populateDropdown(brandSelect,'get_brands.php','Select Brand');
    populateDropdown(placeSelect,'get_places.php','Select Place');
    populateDropdown(unitSelect,'get_units.php','Select Unit');
    loadShoppingList();

// Add Brand submit handler
$(document).on('submit', '#brandForm', function (e) {
    e.preventDefault();

    $.post('add_brand.php', $(this).serialize(), function (data) {
        if (data.success) {
            // Add new option & select it
            $('#brandSelect').append(
                `<option value="${data.id}" selected>${data.name}</option>`
            );

            // Hide modal
            $('#addBrandModal').modal('hide');

            // Reset form
            $('#brandForm')[0].reset();
        } else {
            alert(data.error || 'Error adding brand');
        }
    }, 'json').fail(function () {
        alert('Server error adding brand');
    });
});

// Add Item submit handler
$(document).on('submit', '#itemForm', function (e) {
    e.preventDefault();

    $.post('add_item.php', $(this).serialize(), function (data) {
        if (data.success) {

            $('#itemSelect').append(
                `<option value="${data.id}" selected>${data.name}</option>`
            );

            $('#addItemModal').modal('hide');
            $('#itemForm')[0].reset();

        } else {
            alert(data.error || 'Error adding item');
        }
    }, 'json').fail(function () {
        alert('Server error adding item');
    });
});

// Add Place submit handler
$(document).on('submit', '#placeForm', function (e) {
    e.preventDefault();

    $.post('add_place.php', $(this).serialize(), function (data) {
        if (data.success) {

            $('#placeSelect').append(
                `<option value="${data.id}" selected>${data.name}</option>`
            );

            $('#addPlaceModal').modal('hide');
            $('#placeForm')[0].reset();

        } else {
            alert(data.error || 'Error adding place');
        }
    }, 'json').fail(function () {
        alert('Server error adding place');
    });
});


// Add Unit submit handler
$(document).on('submit', '#unitForm', function (e) {
    e.preventDefault();

    $.post('add_unit.php', $(this).serialize(), function (data) {
        if (data.success) {

            $('#unitSelect').append(
                `<option value="${data.id}" selected>${data.name}</option>`
            );

            $('#addUnitModal').modal('hide');
            $('#unitForm')[0].reset();

        } else {
            alert(data.error || 'Error adding unit');
        }
    }, 'json').fail(function () {
        alert('Server error adding unit');
    });
});

// Delegate because rows may be dynamically added
shoppingListTableBody.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('delete-btn')) return;

    const listId = e.target.dataset.id;
    if (!listId) return;

    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        const res = await fetch('delete_shopping_list.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${listId}`
        });

        const result = await res.json();
        if (result.success) {
            // Remove row from table
            e.target.closest('tr').remove();
        } else {
            alert('Error deleting item.');
        }
    } catch (err) {
        console.error(err);
        alert('Server error deleting item.');
    }
});


// ---------------- Edit Item Modal ----------------
const editModal = new bootstrap.Modal(document.getElementById('editItemModal'));

document.querySelector('#shoppingListTable').addEventListener('click', async (e) => {
    if (!e.target.classList.contains('edit-btn')) return;

    const listID = e.target.dataset.id;

    // Fetch row details
    const res = await fetch(`get_shopping_list_item.php?id=${listID}`);
    const row = await res.json();

    if (!row) return alert('Could not load item details.');

    // Set hidden ListID
    document.getElementById('editListID').value = row.ListID;

    // Populate dropdowns and pre-select current values
    await populateDropdown(document.getElementById('editItemSelect'), 'get_items.php', 'Select Item');
    document.getElementById('editItemSelect').value = row.ItemID;

    await populateDropdown(document.getElementById('editBrandSelect'), 'get_brands.php', 'Select Brand');
    document.getElementById('editBrandSelect').value = row.BrandID;

    await populateDropdown(document.getElementById('editPlaceSelect'), 'get_places.php', 'Select Place');
    document.getElementById('editPlaceSelect').value = row.PlaceID;

    await populateDropdown(document.getElementById('editUnitSelect'), 'get_units.php', 'Select Unit');
    document.getElementById('editUnitSelect').value = row.UnitID;

    // Set remaining input fields
    document.getElementById('editPriceInput').value = row.Price;
    document.getElementById('editAmountInput').value = row.Amount;
    document.getElementById('edit-expiry').value = row.ExpiryDate;
    document.getElementById('editCommentsInput').value = row.Comments ?? '';

    // Show modal
    editModal.show();
});
// Save changes
document.getElementById('saveEditBtn').addEventListener('click', async () => {
    const payload = {
        ListID: document.getElementById('editListID').value,
        ItemID: document.getElementById('editItemSelect').value || null,
        BrandID: document.getElementById('editBrandSelect').value || null,
        PlaceID: document.getElementById('editPlaceSelect').value || null,
        UnitID: document.getElementById('editUnitSelect').value || null,
        Price: document.getElementById('editPriceInput').value || null,
        Amount: document.getElementById('editAmountInput').value || null,
        ExpiryDate: document.getElementById('edit-expiry').value || null, // corrected key
        Comments: document.getElementById('editCommentsInput').value || ''
    };

    const res = await fetch('update_shopping_list_item.php', {
        method: 'POST',
        body: new URLSearchParams(payload)
    });

    const result = await res.json();

    if (result.success) {
        editModal.hide();
        loadShoppingList(); // refresh table
    } else {
        alert(result.error || 'Error saving changes.');
    }
});

const priceHistoryContainer = document.getElementById('priceHistoryContainer');
const priceHistoryTableBody = document.querySelector('#priceHistoryTable tbody');

const loadPriceHistory = async (itemID) => {
    if (!itemID) {
        priceHistoryContainer.style.display = 'none'; // hide when no item selected
        priceHistoryTableBody.innerHTML = '';
        return;
    }

    const data = await fetchJSON(`get_price_history.php?item_id=${itemID}`);
    
    if (data.length === 0) {
        priceHistoryContainer.style.display = 'none'; // hide if no history
        priceHistoryTableBody.innerHTML = '';
        return;
    }
    priceHistoryTableBody.innerHTML = '';
    data.forEach(row => {
        const tr = document.createElement('tr');
        const normalizedPrice = row.UnitType === 'each'
            ? parseFloat(row.Price).toFixed(2)
            : ((parseFloat(row.Price) * 100) / (parseFloat(row.Amount) * parseFloat(row.ConversionToBase))).toFixed(4);

        tr.innerHTML = `
            <td>${row.BrandName}</td>
            <td>${row.PlaceName}</td>
            <td>${parseFloat(row.Price).toFixed(2)}</td>
            <td>${parseFloat(row.Amount)}</td>
            <td>${row.UnitName}</td>
            <td>${normalizedPrice} ¢ / ${row.UnitType==='solid'?'g':'ml'}</td>
            <td>${row.ExpiryDate ?? ''}</td>

        `;
        priceHistoryTableBody.appendChild(tr);
    });
        priceHistoryContainer.style.display = 'block'; // show table when data exists
};

// Trigger when item is selected
itemSelect.addEventListener('change', () => {
    loadPriceHistory(itemSelect.value);
});

// Function to toggle fields based on bargain checkbox
const toggleFieldsForBargain = () => {
    const isBargain = bargainCheckbox.checked;

    // Fields to enable/disable
    const fields = [brandSelect, placeSelect, unitSelect, priceInput, amountInput];

    fields.forEach(field => {
        field.disabled = !isBargain;
        // Optionally, clear the field when disabling
        if (!isBargain) field.value = '';
    });
};

// Run initially on page load
toggleFieldsForBargain();

// Listen for checkbox changes
bargainCheckbox.addEventListener('change', toggleFieldsForBargain);







});
