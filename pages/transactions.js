document.addEventListener("DOMContentLoaded", () => {
  // -----------------------------
  // DOM ELEMENTS
  // -----------------------------
  const openTransferBtn = document.getElementById("openTransferModal");
  const transferModalEl = document.getElementById("transferModal");
  const userDropdown = document.getElementById("userDropdown");

  const fromSelect = document.getElementById("transferFrom");
  const toSelect = document.getElementById("transferTo");
  const amountInput = document.getElementById("transferAmount");
  const submitBtn = document.getElementById("submitTransferBtn");

  if (!transferModalEl) {
    console.warn("Transfer modal not found on this page.");
    return;
  }

  const transferModal = new bootstrap.Modal(transferModalEl);
  let accounts = [];

  // -----------------------------
  // HELPERS
  // -----------------------------
  const fetchJSON = async (url, options = {}) => {
    const res = await fetch(url, options);
    if (!res.ok) throw new Error("Network error");
    return res.json();
  };

  
  const populateAccountsWithExclusion = (select, accounts, excludeId = null) => {
    const currentValue = select.value; // preserve current selection
    select.innerHTML = `<option value="">Select account</option>`;
    accounts.forEach(acc => {
      if (excludeId && acc.AccountID == excludeId) return;
      const opt = document.createElement("option");
      opt.value = acc.AccountID;
      opt.textContent = acc.AccountName;
      select.appendChild(opt);
    });
    // Restore selection if still valid
    if (currentValue && Array.from(select.options).some(o => o.value === currentValue)) {
      select.value = currentValue;
    } else {
      select.value = "";
    }
  };

  const loadAccounts = async (userId) => {
    if (!userId) {
      accounts = [];
      populateAccountsWithExclusion(fromSelect, accounts);
      populateAccountsWithExclusion(toSelect, accounts);
      return;
    }

    accounts = await fetchJSON(`get_accounts.php?userId=${userId}`);
    console.log("accounts loaded:", accounts);
    populateAccountsWithExclusion(fromSelect, accounts);
    populateAccountsWithExclusion(toSelect, accounts);
  };

  // -----------------------------
  // USER DROPDOWN LOGIC
  // -----------------------------
  const updateTransferBtnState = () => {
    openTransferBtn.disabled = !userDropdown.value;
  };
  userDropdown.addEventListener("change", updateTransferBtnState);
  updateTransferBtnState(); // initial state

  // -----------------------------
  // MODAL EVENTS
  // -----------------------------
  openTransferBtn.addEventListener("click", async () => {
    const selectedUserId = userDropdown.value;
    if (!selectedUserId) {
      alert("Please select a user first.");
      return;
    }

    await loadAccounts(selectedUserId);

    // Reset fields
    fromSelect.value = "";
    toSelect.value = "";
    amountInput.value = "";

    transferModal.show();
  });

  // Prevent same FROM / TO without clearing the other
  fromSelect.addEventListener("change", () => {
    populateAccountsWithExclusion(toSelect, accounts, fromSelect.value);
  });

  toSelect.addEventListener("change", () => {
    populateAccountsWithExclusion(fromSelect, accounts, toSelect.value);
  });

  // -----------------------------
  // SUBMIT TRANSFER
  // -----------------------------
  submitBtn.addEventListener("click", async () => {
    const payload = {
      from_account: fromSelect.value,
      to_account: toSelect.value,
      amount: parseFloat(amountInput.value)
    };




    if (!payload.from_account || !payload.to_account || payload.amount <= 0) {
      alert("Please complete all fields.");
      return;
    }

    if (payload.from_account === payload.to_account) {
      alert("Accounts must be different.");
      return;
    }

    try {
      const result = await fetchJSON("transfer_funds.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (result.success) {
        transferModal.hide();
        alert("Transfer completed successfully.");
        if (typeof loadTransactions === "function") loadTransactions();
      } else {
        alert(result.error || "Transfer failed.");
      }
    } catch (err) {
      console.error(err);
      alert("Server error processing transfer.");
    }
  });




//INSERT NEW CODE HERE

});
