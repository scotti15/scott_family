$(document).ready(function () {
    $("#cancelEdit").hide();
    let formMode = "add"; // "add" or "edit"
    // ===============================
    // DataTable (child locations only)
    // ===============================
    const table = $("#locationsTable").DataTable({
        ajax: {
          url: "ajax_locations.php",
          type: "POST",
          data: { action: "list" },
          dataSrc: function (json) {
            console.log("AJAX data returned:", json.data);
            return json.data;
          }
        },
      
        columns: [
            { data: "parent_name",render: d => d || "—"}, // Parent
          { data: "name" },               // Name
          { data: "frequency_name" },     // Frequency
          { data: "schedule", defaultContent: "" },          // Schedule
          { data: "display_order" },      // Order
          {
            data: "active",
            render: data => (data == 1 ? "Yes" : "No"),
          },
          {
            data: null,
            orderable: false,
            searchable: false,
            render: row => `
              <button class="btn btn-sm btn-primary edit-btn"
                      data-id="${row.location_id}">
                Edit
              </button>
              <button class="btn btn-sm btn-danger delete-btn"
                      data-id="${row.location_id}">
                Delete
              </button>
            `,
          }
        ],
      
        pageLength: 10,
      
        stripeClasses: ["table-white", "table-light"],
      
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>tip',
      
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search...",
        },
      });
  
    // Make controls white
    $(".dataTables_filter input").css("background-color", "white");
    $(".dataTables_length select").css("background-color", "white");
  
  
    // ======================================
    // Parent dropdown (ALL locations)
    // ======================================
    function loadParentDropdown(selectedId = null, excludeId = null) {
  
      $.post(
        "ajax_locations.php",
        { action: "all" },   // ⭐ IMPORTANT: get ALL locations
        function (res) {
          if (!res.data) return;
  
          const select = $("#parent_id");
          select.empty().append('<option value="">No parent</option>');
  
          res.data.forEach(loc => {
  
            // Prevent choosing itself as parent when editing
            if (excludeId && loc.location_id == excludeId) return;
  
            select.append(
              `<option value="${loc.location_id}">${loc.name}</option>`
            );
          });
  
          if (selectedId) select.val(selectedId);
        },
        "json"
      );
    }
  
    // Load for "Add new"
    loadParentDropdown();
  
  
    // ===============================
    // Save (Add or Edit)
    // ===============================
    $("#saveBtn").click(function () {
  const isCleaning = $("#cleanable").is(":checked");

  const data = {
    name: $("#name").val(),
    parent_id: $("#parent_id").val() || null,
    display_order: $("#display_order").val(),
    active: $("#active").is(":checked") ? 1 : 0,
    cleanable: isCleaning ? 1 : 0,
    frequency_id: isCleaning ? $("#frequency_id").val() || null : null,
    schedule: isCleaning ? $("#schedule").val() || null : null, // NEW
    action: formMode
  };

  if (formMode === "edit") {
    data.location_id = $("#location_id").val();
  }

  $.post("ajax_locations.php", data, function (res) {
    
  console.log("ADD RESPONSE:", res);
    if (res.success) {
      table.ajax.reload(null, false);
      loadParentDropdown();
      enterAddMode(); // reset to Add mode
    } else {
      alert(res.message || "Save failed");
    }
  }, "json");
});
  
    // ===============================
    // Edit button
    // ===============================
    $("#locationsTable tbody").on("click", ".edit-btn", function () {

        const row = table.row($(this).closest("tr")).data();
      
        $("#location_id").val(row.location_id);
        $("#name").val(row.name);
        $("#display_order").val(row.display_order);
        $("#cleanable").prop("checked", row.cleanable == 1);
        $("#frequency_id").val(row.frequency_id || "");
        $("#active").prop("checked", row.active == 1);
      
        loadParentDropdown(row.parent_id, row.location_id);
      
        updateFrequencyState();
      
        enterEditMode(); // ⭐ NEW
      });
  
  
    // ===============================
    // Delete button
    // ===============================
    $("#locationsTable tbody").on("click", ".delete-btn", function () {
  
      const locId = $(this).data("id");
  
      if (!confirm("Are you sure you want to delete this location?")) return;
  
      $.post(
        "ajax_locations.php",
        { action: "delete", id: locId },
        function (res) {
  
          if (res.success) {
            table.ajax.reload();
          } else {
            alert("Delete failed: " + (res.error || res.message || "Unknown error"));
          }
  
        },
        "json"
      ).fail(function (jqXHR, textStatus, errorThrown) {
        alert("AJAX failed: " + textStatus + ", " + errorThrown);
      });
    });
  
  
    // ===============================
    // Cancel edit
    // ===============================
    $("#cancelEdit").click(function () {
  
      $("#location_id").val("");
      $("#name").val("");
      $("#parent_id").val("");
      $("#display_order").val("0");
      $("#active").prop("checked", true);
      $("#cleanable").prop("checked", false);
      $("#frequency_id").val("1"); // default Daily
      updateFrequencyState();
      $(this).hide();
      enterAddMode();
    });
  
    function updateFrequencyState() {
        const isCleaning = $("#cleanable").is(":checked");
      
        $("#frequency_id")
          .prop("disabled", !isCleaning)
          .toggleClass("bg-light", !isCleaning);
      }
      
      // Run on page load
      updateFrequencyState();
      
      // Run when checkbox changes
      $("#cleanable").on("change", updateFrequencyState);

      function enterAddMode() {
        formMode = "add";
      
        $("#location_id").val("");
      
        $("#saveBtn")
          .text("Add Location")
          .removeClass("btn-primary")
          .addClass("btn-success");
      
        $("#cancelEdit").hide();
      
        resetLocationForm();
      }

      function enterEditMode() {
        formMode = "edit";
      
        $("#saveBtn")
          .text("Save Changes")
          .removeClass("btn-success")
          .addClass("btn-primary");
      
        $("#cancelEdit").show();
      }

      function populateScheduleOptions() {
        const freq = document.getElementById("frequency_id").value;
        const scheduleSelect = document.getElementById("schedule");
      
        // Clear current options
        scheduleSelect.innerHTML = '<option value="">-- Select --</option>';
      
        if (!freq) {
          scheduleSelect.disabled = true;
          return;
        }
      
        scheduleSelect.disabled = false;
      
        if (freq === "1") { // Daily
          const option = document.createElement("option");
          option.value = "Daily";
          option.text = "Daily";
          scheduleSelect.appendChild(option);
          scheduleSelect.value = "Daily"; // auto-select
        } else if (freq === "2") { // Weekly
          const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
          days.forEach(day => {
            const option = document.createElement("option");
            option.value = day;
            option.text = day;
            scheduleSelect.appendChild(option);
          });
        } else if (freq === "3") { // Monthly
          const weekdays = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
          for (let i = 1; i <= 5; i++) { // max 5 occurrences in a month
            weekdays.forEach(day => {
              const option = document.createElement("option");
              option.value = `${day}${i.toString().padStart(2,'0')}`; // e.g., Monday01
              option.text = `${day} ${i}`; // display: Monday 1
              scheduleSelect.appendChild(option);
            });
          }
        } else if (freq === "4") { // Quarterly
          const weekdays = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
          for (let i = 1; i <= 12; i++) { // max 12 weeks in a quarter
            weekdays.forEach(day => {
              const option = document.createElement("option");
              option.value = `${day}${i.toString().padStart(2,'0')}`; // e.g., Saturday03
              option.text = `${day} ${i}`; // display: Saturday 3
              scheduleSelect.appendChild(option);
            });
          }
        }
      }
      
      // Attach listener to frequency change
      document.getElementById("frequency_id").addEventListener("change", populateScheduleOptions);
      
      // Run on page load in case frequency already has a value
      populateScheduleOptions();
      
      function resetLocationForm() {
        $("#location_id").val("");
      
        $("#name").val("");
        $("#parent_id").val("");       // blank parent
        $("#display_order").val("0");
      
        $("#active").prop("checked", true);
        $("#cleanable").prop("checked", false);
      
        $("#frequency_id").val("");    // blank frequency
        populateScheduleOptions();     // <-- regenerate schedule options
        $("#schedule").val("");    // blank schedule
        updateFrequencyState();
      
        $("#cancelEdit").hide();
      }
      


      // ADD FUNCTIONS HERE 
      
      
      

  });