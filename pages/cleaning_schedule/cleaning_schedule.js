// -------------------------------
// Cleaning Schedule: Add Location Modal
// -------------------------------

document.addEventListener("DOMContentLoaded", () => {
  // Enable/disable Frequency dropdown based on checkbox
  const addToScheduleCheckbox = document.getElementById("addToSchedule");
  const frequencyDropdown = document.getElementById("frequency");


  addToScheduleCheckbox.addEventListener("change", () => {
    frequencyDropdown.disabled = !addToScheduleCheckbox.checked;

    // optional: reset dropdown if unchecked
    if (!addToScheduleCheckbox.checked) {
      frequencyDropdown.value = "";
    }
  });
  // REMOVE TASK FROM LOG

  document.addEventListener("click", function (event) {
    const cell = event.target.closest("td[data-task-id][data-date]");
    if (!cell) return;

    const taskId = cell.dataset.taskId;
    const day = cell.dataset.date;

    // Determine action: add or remove
    const isCleaned = cell.classList.contains("cleaned");
    const url = isCleaned ? "delete_task.php" : "save_task.php";

    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `task_id=${encodeURIComponent(taskId)}&day=${encodeURIComponent(
        day
      )}`,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Toggle visual state
          cell.classList.toggle("cleaned", !isCleaned);
        } else {
          console.error("Error:", data.error);
        }
      })
      .catch((err) => console.error("AJAX error:", err));
  });

  // Attach click listener to all grid cells
  document.addEventListener("click", function (event) {
    const cell = event.target.closest("td[data-task-id][data-date]");
    if (!cell) return;

    const taskId = cell.dataset.taskId;
    const day = cell.dataset.date;

    // Send AJAX request
    fetch("save_task.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `task_id=${encodeURIComponent(taskId)}&day=${encodeURIComponent(
        day
      )}`,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Toggle visual state
          cell.classList.toggle("cleaned", true);
        } else {
          console.error("Error saving task:", data.error);
        }
      })
      .catch((err) => console.error("AJAX error:", err));
  });

  // Modal elements
  const addLocationModal = document.getElementById("addLocationModal");
  const addLocationForm = document.getElementById("addLocationForm");
  const closeAddLocationBtn = document.getElementById("closeAddLocationModal");
  const cancelLocationBtn = document.getElementById("cancelLocationBtn");

  // Dynamic grid
  const cleaningGrid = document.querySelector(".cleaning-grid");

  // -------------------------------
  // Open modal when Location header clicked (event delegation)
  // -------------------------------
  cleaningGrid.addEventListener("click", (event) => {
    if (event.target.classList.contains("location-header")) {
      addLocationModal.style.display = "flex";
      addLocationForm.reset();
    }
  });

  // -------------------------------
  // Close modal: X button
  // -------------------------------
  closeAddLocationBtn.addEventListener("click", () => {
    addLocationModal.style.display = "none";
    addLocationForm.reset();
  });

  // -------------------------------
  // Close modal: Cancel button
  // -------------------------------
  cancelLocationBtn.addEventListener("click", () => {
    addLocationModal.style.display = "none";
    addLocationForm.reset();
  });

  // -------------------------------
  // Close modal: click outside box
  // -------------------------------
  window.addEventListener("click", (event) => {
    if (event.target === addLocationModal) {
      addLocationModal.style.display = "none";
      addLocationForm.reset();
    }
  });

  // Example tasks; later pull from DB via AJAX
  const tasks = ["Sweep Kitchen Floor", "Mop Kitchen Floor", "Clean Counter"];

  function formatDueLabel(task, frequency) {
    if (frequency === "daily") return "";
  
    const weekdayNames = [
      "Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"
    ];
  
    const weekday = parseInt(task.schedule_weekday);
    const nth = parseInt(task.schedule_nth);
  
    const dayName = weekdayNames[weekday];
  
    function getOrdinal(n) {
      if (n % 10 === 1 && n !== 11) return n + "st";
      if (n % 10 === 2 && n !== 12) return n + "nd";
      if (n % 10 === 3 && n !== 13) return n + "rd";
      return n + "th";
    }
  
    // Weekly (no nth)
    if (weekday && !nth) {
      return dayName;
    }
  
    // Monthly / Quarterly
    if (weekday && nth) {
      return `${getOrdinal(nth)} ${dayName}`;
    }
  
    return "";
  }
  

  function buildGrid(frequency, period) {
    const table = document.querySelector(".cleaning-grid");
    table.innerHTML = "";
    
  updateCurrentPeriodLabel(frequency);
  
    fetch(`load_tasks.php?frequency=${frequency}&t=${Date.now()}`)
      .then((res) => res.json())
      .then((tasks) => {
        console.log("GRID TASK SAMPLE:", tasks[0]);
        if (!tasks.length) return;
  
        let year, month;
        if (frequency === "monthly" || frequency === "quarterly") {
          year = parseInt(period);
        } else {
          [year, month] = period.split("-").map(Number);
        }
  
        let columns = [];
  
        // Build columns
        if (frequency === "daily") {
          const lastDay = new Date(year, month, 0);
          for (let d = 1; d <= lastDay.getDate(); d++) {
            columns.push(new Date(year, month - 1, d));
          }
        } else if (frequency === "weekly") {
          const lastDay = new Date(year, month, 0);
          for (let d = 1; d <= lastDay.getDate(); d++) {
            let dt = new Date(year, month - 1, d);
            if (dt.getDay() === 0) columns.push(dt); // Sundays
          }
        } else if (frequency === "monthly") {
          for (let m = 0; m < 12; m++) {
            const firstDayOfMonth = new Date(year, m, 1);
            columns.push({
              date: firstDayOfMonth,
              label: firstDayOfMonth.toLocaleString(undefined, { month: "short" }),
            });
          }
        } else if (frequency === "quarterly") {
          const quarterLabels = ["Jan – Mar","Apr – Jun","Jul – Sep","Oct – Dec"];
          for (let q = 0; q < 4; q++) {
            const firstMonth = q * 3;
            const firstDayOfQuarter = new Date(year, firstMonth, 1);
            columns.push({ date: firstDayOfQuarter, label: quarterLabels[q] });
          }
        }
  
        // Header
        const thead = document.createElement("thead");
        const headRow = document.createElement("tr");
  
        const thTask = document.createElement("th");
        thTask.textContent = "Location";
        headRow.appendChild(thTask);

        if (frequency !== "daily") {
          const thDue = document.createElement("th");
          thDue.textContent = "Due";
          headRow.appendChild(thDue);
        }
  
        columns.forEach((col) => {
          const th = document.createElement("th");
          th.textContent = frequency === "daily" || frequency === "weekly" ? col.getDate() : col.label;
          headRow.appendChild(th);
        });
  
        thead.appendChild(headRow);
        table.appendChild(thead);
  
        const tbody = document.createElement("tbody");
  
        // Normalize today
        const today = new Date();
        today.setHours(0,0,0,0);
        let todayDay = today.getDay();
        todayDay = todayDay === 0 ? 7 : todayDay; // Mon=1 ... Sun=7
  
        tasks.forEach((task) => {
          const tr = document.createElement("tr");
          const tdTask = document.createElement("td");
          tdTask.textContent = task.location_path;
          tr.appendChild(tdTask);

          if (frequency !== "daily") {
            const tdDue = document.createElement("td");
            tdDue.textContent = formatDueLabel(task, frequency);
            tr.appendChild(tdDue);
          }
          
  
          let isOverdue = false;
  
          if (frequency !== "daily") {
            // 🔹 WEEKLY
            if (task.schedule_weekday && !task.schedule_nth) {
              const scheduledDay = parseInt(task.schedule_weekday);
  
              if (scheduledDay < todayDay) {
                // Start of this week (Monday)
                const startOfWeek = new Date(today);
                startOfWeek.setHours(0,0,0,0);
                startOfWeek.setDate(today.getDate() - today.getDay()); // Sunday start
                
                const endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(startOfWeek.getDate() + 6);
                endOfWeek.setHours(23,59,59,999);
  
                const doneThisWeek = task.completed_dates.some(dStr => {
                  const d = new Date(dStr + "T00:00:00");
                  return d >= startOfWeek && d <= endOfWeek;
                });
  
                if (!doneThisWeek) isOverdue = true;
              }
            }
            if (task.schedule_weekday && !task.schedule_nth) {
              console.log("WEEKLY OVERDUE CHECK:", {
                task: task.location_path,
                scheduledDay: task.schedule_weekday,
                todayDay: todayDay,
                completed_dates: task.completed_dates,
                isOverdue: isOverdue
              });
            }
  
            // 🔹 MONTHLY / QUARTERLY (nth weekday logic)
            else if (task.schedule_weekday && task.schedule_nth) {
              let weekday = parseInt(task.schedule_weekday);
              let nth = parseInt(task.schedule_nth);
              if (weekday === 0) weekday = 7;
  
              // First day of period
              let periodStart;
              if (frequency === "monthly") periodStart = new Date(today.getFullYear(), today.getMonth(), 1);
              else { // quarterly
                const quarterStartMonth = Math.floor(today.getMonth() / 3) * 3;
                periodStart = new Date(today.getFullYear(), quarterStartMonth, 1);
              }
  
              // Find first weekday
              let firstWeekday = new Date(periodStart);
              while ((firstWeekday.getDay() || 7) !== weekday) firstWeekday.setDate(firstWeekday.getDate() + 1);
  
              const scheduledDate = new Date(firstWeekday);
              scheduledDate.setDate(firstWeekday.getDate() + (nth - 1) * 7);
              scheduledDate.setHours(0,0,0,0);
  
              // End of period
              let periodEnd;
              if (frequency === "monthly") periodEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
              else periodEnd = new Date(periodStart.getFullYear(), periodStart.getMonth() + 3, 0);
              periodEnd.setHours(23,59,59,999);
  
              const doneThisPeriod = task.completed_dates.some(dStr => {
                const d = new Date(dStr + "T00:00:00");
                return d >= periodStart && d <= periodEnd;
              });
  
              if (!doneThisPeriod && scheduledDate < today) {
                isOverdue = true;
              } else {
                isOverdue = false;
              }
  
              console.log("PERIOD DEBUG:", {task: task.location_path, scheduledDate, periodStart, periodEnd, doneThisPeriod, isOverdue});
            }
          }
  
          if (isOverdue) tdTask.classList.add("overdue-label");
          tr.appendChild(tdTask);
  
          // Cells
          columns.forEach((col) => {
            const td = document.createElement("td");
            const div = document.createElement("div");
            div.className = "toggle-cell";
            div.dataset.task = task.task_id;
  
            const cellDate = frequency === "daily" || frequency === "weekly" ? new Date(col) : new Date(col.date);
            div.dataset.day = cellDate.toISOString().split("T")[0];
  
            if (task.completed_dates.includes(div.dataset.day)) div.classList.add("completed");
  
            td.appendChild(div);
            tr.appendChild(td);
          });
  
          tbody.appendChild(tr);
        });
  
        table.appendChild(tbody);
  
        highlightTodayColumn(columns, frequency, table);
  
        document.querySelectorAll(".toggle-cell").forEach((cell) => {
          cell.addEventListener("click", function() {
            const isCompleted = this.classList.toggle("completed");
            const taskId = this.dataset.task;
            const day = this.dataset.day;
  
            fetch("save_task.php", {
              method: "POST",
              headers: {"Content-Type":"application/x-www-form-urlencoded"},
              body: `task_id=${taskId}&day=${day}&completed=${isCompleted ? 1 : 0}`
            });
          });
        });
      });
  
    loadTodayTasks();
  }

  // Initial grid
  const frequencySelect = document.getElementById("frequency");
  const periodInput = document.getElementById("period");
  buildGrid(frequencySelect.value, periodInput.value);

  // Rebuild grid on change
  frequencySelect.addEventListener("change", () => {
    if (
      frequencySelect.value === "monthly" ||
      frequencySelect.value === "quarterly"
    ) {
      periodInput.type = "number";
      periodInput.min = 2000;
      periodInput.max = 2100;
      periodInput.value = new Date().getFullYear();
    } else {
      periodInput.type = "month";
      periodInput.value = new Date().toISOString().slice(0, 7);
    }
    buildGrid(frequencySelect.value, periodInput.value);
  });
  periodInput.addEventListener("change", () => {
    buildGrid(frequencySelect.value, periodInput.value);
  });

  function highlightTodayColumn(columns, frequency, table) {
    // Only makes sense for daily/weekly views
    if (frequency !== "daily" && frequency !== "weekly") return;

    const today = new Date().toLocaleDateString("en-CA");

    let todayIndex = -1;

    columns.forEach((col, i) => {
      const dateStr = col.toISOString().split("T")[0];
      if (dateStr === today) todayIndex = i;
    });

    if (todayIndex === -1) return; // not in current period

    // +1 because column 0 is Location
    const colPosition = todayIndex + 2;

    // Highlight header
    const headerCell = table.querySelector(
      `thead th:nth-child(${colPosition})`
    );
    if (headerCell) headerCell.classList.add("today");

    // Highlight all body cells
    table
      .querySelectorAll(`tbody td:nth-child(${colPosition})`)
      .forEach((cell) => {
        cell.classList.add("today");
      });
  }

  function loadTodayTasks() {
    const frequency = document.getElementById("frequency").value;
  
    if (frequency === "daily") {
      document.getElementById("todayTasks").innerHTML = "";
      return;
    }
  
    // ✅ PASS FREQUENCY HERE
    fetch(`load_today.php?frequency=${frequency}`)
      .then((res) => res.json())
      .then((tasks) => {
        console.log("TASK SAMPLE FULL:", tasks[0]);
  
        const container = document.getElementById("todayTasks");
        container.innerHTML = "<h3>To do Today</h3>";
  
        if (tasks.length === 0) {
          const p = document.createElement("div");
          p.textContent = "No tasks today!";
          container.appendChild(p);
          return;
        }
  
        tasks.forEach((task) => {
          const div = document.createElement("div");
          div.textContent = task.location_path;
  
          div.style.padding = "5px 10px";
          div.style.marginBottom = "3px";
          div.style.borderRadius = "4px";
  
          if (task.completed_today == 0) {
            div.style.background = "#ffdddd";
            div.style.color = "#800000";
          } else {
            div.style.background = "#ddffdd";
            div.style.color = "#006600";
          }
  
          container.appendChild(div);
        });
      });
  }

  function updateCurrentPeriodLabel(frequency) {
    const labelDiv = document.getElementById("currentPeriodLabel");
  
    if (frequency === "daily") {
      labelDiv.innerHTML = "";
      return;
    }
  
    const today = new Date();
    const weekdayNames = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const dayName = weekdayNames[today.getDay()];
  
    // Helper for 1st, 2nd, 3rd, 4th...
    function getOrdinal(n) {
      if (n % 10 === 1 && n !== 11) return n + "st";
      if (n % 10 === 2 && n !== 12) return n + "nd";
      if (n % 10 === 3 && n !== 13) return n + "rd";
      return n + "th";
    }
  
    // WEEKLY
    if (frequency === "weekly") {
      labelDiv.textContent = `Today: ${dayName}`;
      return;
    }
  
    // MONTHLY → nth weekday of month
    if (frequency === "monthly") {
      const date = today.getDate();
      const nth = Math.ceil(date / 7);
      labelDiv.textContent = `Today: ${getOrdinal(nth)} ${dayName}`;
      return;
    }
  
    // QUARTERLY → nth weekday of quarter
    if (frequency === "quarterly") {
      const startMonth = Math.floor(today.getMonth() / 3) * 3;
      const startOfQuarter = new Date(today.getFullYear(), startMonth, 1);
  
      let count = 0;
      let cursor = new Date(startOfQuarter);
  
      while (cursor <= today) {
        if (cursor.getDay() === today.getDay()) {
          count++;
        }
        cursor.setDate(cursor.getDate() + 1);
      }
  
      const quarter = Math.floor(today.getMonth() / 3) + 1;
  
      labelDiv.textContent = `Today: ${getOrdinal(count)} ${dayName} (Q${quarter})`;
    }
  }
  
  
  

  // ADD NEW FUNCCTIONS HERE
});
