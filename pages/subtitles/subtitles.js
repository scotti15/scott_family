document.addEventListener("DOMContentLoaded", function () {
  const projectSelect = document.getElementById("project_id");
  const projectTitleGroup = document.getElementById("project-title-group");
  const projectTitle = document.getElementById("project-title");
  let currentSubtitles = [];
  let compareSubtitles = [];
  let compareLanguageId = "";

  document.querySelectorAll(".sidebar-language-load").forEach((button) => {
    button.addEventListener("click", () => {
      window.location.href = button.dataset.url;
    });
  });

  document.getElementById("combine-subtitles").style.display = compareLanguageId
    ? "inline-block"
    : "none";

  /**********************************
   * CLEAR SUBTITLES
   **********************************/

  document.getElementById("clear-subtitles").addEventListener("click", () => {
    const tableHead = document.querySelector(".subtitle-table thead");
    const tableBody = document.getElementById("subtitle-table-body");

    tableHead.innerHTML = "";
    tableBody.innerHTML = "";

    console.log("Subtitles cleared.");
  });

  function updateProjectTitleVisibility() {
    if (projectSelect.value === "new") {
      projectTitleGroup.style.display = "";
      projectTitle.required = true;
    } else {
      projectTitleGroup.style.display = "none";
      projectTitle.required = false;
      projectTitle.value = "";
    }
  }

  projectSelect.addEventListener("change", updateProjectTitleVisibility);

  updateProjectTitleVisibility();

  console.log(
    "Edit buttons found:",
    document.querySelectorAll(".edit-subtitle").length
  );
  const subtitleTableBody = document.getElementById("subtitle-table-body");

  if (subtitleTableBody) {
    subtitleTableBody.addEventListener("dblclick", function (event) {
      const textCell = event.target.closest(".subtitle-text");
  
      if (!textCell) {
        return;
      }
  
      // Don't create another textarea if already editing
      if (textCell.querySelector("textarea")) {
        return;
      }
  
      const currentText = textCell.innerText;
  
      textCell.classList.add("editing");
  
      textCell.innerHTML = `
        <textarea class="form-control subtitle-edit-text" rows="3">${currentText}</textarea>
      `;
  
      const textarea = textCell.querySelector("textarea");
  
      textarea.focus();
  
      // Save when the textarea loses focus
      textarea.addEventListener("blur", async function () {
        const newText = textarea.value;
        const entryId =
          textCell.dataset.entryId || textCell.closest("tr").dataset.entryId;
        console.log("Editing entry:", entryId);
  
        try {
          const formData = new FormData();
  
          formData.append("entry_id", entryId);
          formData.append("text", newText);
  
          const response = await fetch("subtitle_update_text.php", {
            method: "POST",
            body: formData,
          });
  
          const result = await response.json();
  
          console.log("Text update:", result);
  
          if (!result.success) {
            console.error("Text update failed:", result.error);
            alert(result.error || "The subtitle text could not be saved.");
            return;
          }
  
          textCell.classList.remove("editing");
          textCell.textContent = newText;
        } catch (error) {
          console.error("Text update failed:", error);
          alert("The subtitle text could not be saved.");
        }
      });
    });
  }

  document.querySelectorAll(".subtitle-time").forEach(function (timeCell) {
    timeCell.addEventListener("dblclick", function () {
      // Don't create another input if already editing
      if (timeCell.querySelector("input")) {
        return;
      }

      const currentTime = timeCell.innerText.trim();

      timeCell.classList.add("editing");

      timeCell.innerHTML = `
                <input
                    type="text"
                    class="form-control subtitle-edit-time"
                    value="${currentTime}"
                >
            `;

      const input = timeCell.querySelector("input");

      input.focus();

      // Save only to the UI for now
      input.addEventListener("blur", function () {
        const newTime = input.value.trim();

        timeCell.classList.remove("editing");

        timeCell.textContent = newTime;
      });
    });
  });

  let selectedEntryIds = [];
  let lastClickedRow = null;

  /**********************************
   * DELETE SUBTITLES
   **********************************/

  document
    .getElementById("delete-subtitles")
    .addEventListener("click", async () => {
      if (selectedEntryIds.length === 0) {
        console.log("No subtitle lines selected.");
        return;
      }

      const confirmed = confirm(
        `Delete ${selectedEntryIds.length} selected subtitle line(s)?`
      );

      if (!confirmed) {
        return;
      }

      console.log("Deleting entries:", selectedEntryIds);

      try {
        for (const entryId of selectedEntryIds) {
          const formData = new FormData();

          formData.append("entry_id", entryId);

          const response = await fetch("subtitle_delete.php", {
            method: "POST",
            body: formData,
          });

          const result = await response.json();

          console.log("Delete result:", result);

          if (!result.success) {
            console.error("Delete failed:", result.error);
            alert(result.error || "The subtitle could not be deleted.");
            return;
          }
        }

        /**********************************
         * RESTORE COMPARE MODE AFTER DELETE
         **********************************/

        compareLanguageId = document.getElementById("compare-language").value;

        const currentUrl = new URL(window.location.href);

        if (compareLanguageId) {
          currentUrl.searchParams.set("compare_language_id", compareLanguageId);
        }

        window.location.href = currentUrl.toString();
      } catch (error) {
        console.error("Delete request failed:", error);
        alert("The subtitle could not be deleted.");
      }
    });

  function selectComparisonEntry(entryId, selected) {
    document
      .querySelectorAll(`#subtitle-table-body td[data-entry-id="${entryId}"]`)
      .forEach((cell) => {
        cell.classList.toggle("selected", selected);
      });
  }

  if (subtitleTableBody) {
    subtitleTableBody.addEventListener("click", (event) => {
      const cell = event.target.closest("[data-entry-id]");

      if (!cell) {
        return;
      }

      const row = cell.closest("tr");

      const rows = Array.from(
        document.querySelectorAll("#subtitle-table-body tr")
      );

      const entryId = cell.dataset.entryId;

      // ==========================================
      // SHIFT+CLICK: Select a range
      // ==========================================
      if (event.shiftKey && lastClickedRow) {
        const startIndex = rows.indexOf(lastClickedRow);
        const endIndex = rows.indexOf(row);

        const rangeStart = Math.min(startIndex, endIndex);
        const rangeEnd = Math.max(startIndex, endIndex);

        for (let i = rangeStart; i <= rangeEnd; i++) {
          const rangeRow = rows[i];

          const rangeCell = rangeRow.querySelector(
            `[data-language-id="${cell.dataset.languageId}"]`
          );

          const rangeEntryId = rangeCell?.dataset.entryId;

          if (rangeEntryId && !selectedEntryIds.includes(rangeEntryId)) {
            selectedEntryIds.push(rangeEntryId);
          }

          selectComparisonEntry(rangeEntryId, true);
        }
      }

      // ==========================================
      // CTRL+CLICK: Toggle individual row
      // ==========================================
      else if (event.ctrlKey) {
        if (selectedEntryIds.includes(entryId)) {
          selectedEntryIds = selectedEntryIds.filter((id) => id !== entryId);

          row.classList.remove("selected");

          selectComparisonEntry(entryId, false);
        } else {
          selectedEntryIds.push(entryId);

          selectComparisonEntry(entryId, true);
        }
      }

      // ==========================================
      // NORMAL CLICK: Start a new selection
      // ==========================================
      else {
        document
          .querySelectorAll("#subtitle-table-body .selected")
          .forEach((selectedElement) => {
            selectedElement.classList.remove("selected");
          });

        selectedEntryIds = [entryId];

        const isCompareRow =
          row.querySelectorAll("td[data-entry-id]").length > 0;

        if (isCompareRow) {
          selectComparisonEntry(entryId, true);
        } else {
          row.classList.add("selected");
        }
      }

      // Remember this row for the next Shift+click
      lastClickedRow = row;

      document.getElementById("selected-count").textContent = `${
        selectedEntryIds.length
      } ${selectedEntryIds.length === 1 ? "line" : "lines"} selected`;

      console.log("Selected subtitle entries:", selectedEntryIds);
    });
  }

  function adjustSelectedTiming(direction) {
    if (selectedEntryIds.length === 0) {
      console.log("No subtitle lines selected.");
      return;
    }

    const target = document.querySelector(
      'input[name="timing-target"]:checked'
    ).value;

    const adjustment = getTimingAdjustment() * direction;

    console.log("Timing adjustment:", {
      entries: selectedEntryIds,
      target: target,
      adjustment: adjustment,
    });
  }

  document.getElementById("time-increase").addEventListener("click", () => {
    applyTimingAdjustment(1);
  });

  document.getElementById("time-decrease").addEventListener("click", () => {
    applyTimingAdjustment(-1);
  });

  document
    .getElementById("compare-subtitles")
    .addEventListener("click", async () => {
      compareLanguageId = document.getElementById("compare-language").value;

      if (!compareLanguageId) {
        console.log("No comparison language selected.");
        return;
      }

      const projectId = new URLSearchParams(window.location.search).get(
        "project_id"
      );

      const formData = new FormData();

      formData.append("project_id", projectId);
      formData.append("language_id", compareLanguageId);

      try {
        const response = await fetch("subtitle_compare.php", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        compareSubtitles = result.subtitles;

        const tableHead = document.querySelector(".subtitle-table thead");
        const tableBody = document.getElementById("subtitle-table-body");

        currentSubtitles = [];

        document
          .querySelectorAll("#subtitle-table-body tr[data-entry-id]")
          .forEach((row) => {
            const cells = row.querySelectorAll("td");

            currentSubtitles.push({
              entry_id: row.dataset.entryId,
              subtitle_number: cells[0].textContent.trim(),
              start_time: cells[1].textContent.trim(),
              end_time: cells[2].textContent.trim(),
              text: cells[3].textContent.trim(),
            });
          });

        console.log("Current subtitles:", currentSubtitles);

        tableHead.innerHTML = `
          <tr>
              <th>#</th>
              <th>Current Start</th>
              <th>Current End</th>
              <th>Current Text</th>
              <th>Compare Start</th>
              <th>Compare End</th>
              <th>Compare Text</th>
          </tr>
      `;

        tableBody.innerHTML = "";

        currentSubtitles.forEach((currentEntry, index) => {
          const compareEntry = compareSubtitles[index];

          const row = document.createElement("tr");

          row.dataset.entryId = currentEntry.entry_id;

          row.innerHTML = `
    <td>${currentEntry.subtitle_number}</td>

    <td class="subtitle-time"
        data-entry-id="${currentEntry.entry_id}"
        data-language-id="${selectedLanguageId}"
        data-subtitle-number="${currentEntry.subtitle_number}">
        ${currentEntry.start_time}
    </td>

    <td class="subtitle-time"
        data-entry-id="${currentEntry.entry_id}"
        data-language-id="${selectedLanguageId}"
        data-subtitle-number="${currentEntry.subtitle_number}">
        ${currentEntry.end_time}
    </td>

    <td class="subtitle-text"
        data-entry-id="${currentEntry.entry_id}"
        data-language-id="${selectedLanguageId}"
        data-subtitle-number="${currentEntry.subtitle_number}">
        ${currentEntry.text}
    </td>

    <td class="subtitle-time"
        data-entry-id="${compareEntry.entry_id}"
        data-language-id="${compareLanguageId}"
        data-subtitle-number="${compareEntry.subtitle_number}">
        ${compareEntry.start_time}
    </td>

    <td class="subtitle-time"
        data-entry-id="${compareEntry.entry_id}"
        data-language-id="${compareLanguageId}"
        data-subtitle-number="${compareEntry.subtitle_number}">
        ${compareEntry.end_time}
    </td>

    <td class="subtitle-text"
        data-entry-id="${compareEntry.entry_id}"
        data-language-id="${compareLanguageId}"
        data-subtitle-number="${compareEntry.subtitle_number}">
        ${compareEntry.text}
    </td>
`;

          tableBody.appendChild(row);
        });
        document.getElementById("combine-subtitles").style.display = "inline-block";
      } catch (error) {
        console.error("Compare request failed:", error);
      }
      
    });

  /**********************************
   * RESTORE COMPARE MODE
   **********************************/

  compareLanguageId = new URLSearchParams(window.location.search).get(
    "compare_language_id"
  );

  if (compareLanguageId) {
    const compareSelect = document.getElementById("compare-language");

    if (compareSelect) {
      compareSelect.value = compareLanguageId;

      document.getElementById("compare-subtitles").click();
    }
  }

  function getTimingAdjustment() {
    const amount = Number(document.getElementById("time-amount").value);
    const unit = document.getElementById("time-unit").value;

    const unitValues = {
      tenth: 100,
      second: 1000,
      "ten-second": 10000,
      minute: 60000,
      "ten-minute": 600000,
    };

    return amount * unitValues[unit];
  }

  async function applyTimingAdjustment(direction) {
    if (selectedEntryIds.length === 0) {
      console.log("No subtitle lines selected.");
      return;
    }

    const target = document.querySelector(
      'input[name="timing-target"]:checked'
    ).value;

    const adjustment = getTimingAdjustment() * direction;

    console.log("Timing adjustment:", {
      entries: selectedEntryIds,
      target: target,
      adjustment: adjustment,
    });

    // ==========================================
    // Save adjustment to database first
    // ==========================================

    try {
      const response = await fetch("subtitle_adjust_timing.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          entryIds: selectedEntryIds,
          target: target,
          adjustment: adjustment,
        }),
      });

      const result = await response.json();

      console.log("Database update:", result);

      if (!result.success) {
        console.error("Database update failed:", result.error);
        alert(result.error || "The timing adjustment could not be saved.");
        return;
      }

      // ==========================================
      // Database succeeded — update the display
      // ==========================================

      const compareTimeCells = document.querySelectorAll(
        ".subtitle-time[data-entry-id]"
      );

      if (compareTimeCells.length > 0) {
        // ==========================================
        // Compare mode
        // ==========================================

        selectedEntryIds.forEach((entryId) => {
          const timeCells = document.querySelectorAll(
            `.subtitle-time[data-entry-id="${entryId}"]`
          );

          if (timeCells.length < 2) {
            return;
          }

          const startCell = timeCells[0];
          const endCell = timeCells[1];

          if (target === "start" || target === "both") {
            startCell.textContent = adjustSubtitleTime(
              startCell.textContent.trim(),
              adjustment
            );
          }

          if (target === "end" || target === "both") {
            endCell.textContent = adjustSubtitleTime(
              endCell.textContent.trim(),
              adjustment
            );
          }
        });
      } else {
        // ==========================================
        // Single-language mode
        // Existing working code
        // ==========================================

        const rows = document.querySelectorAll("tbody tr[data-entry-id]");

        rows.forEach((row) => {
          const entryId = row.dataset.entryId;

          if (!selectedEntryIds.includes(entryId)) {
            return;
          }

          const timeCells = row.querySelectorAll(".subtitle-time");

          const startCell = timeCells[0];
          const endCell = timeCells[1];

          if (target === "start" || target === "both") {
            startCell.textContent = adjustSubtitleTime(
              startCell.textContent.trim(),
              adjustment
            );
          }

          if (target === "end" || target === "both") {
            endCell.textContent = adjustSubtitleTime(
              endCell.textContent.trim(),
              adjustment
            );
          }
        });
      }
    } catch (error) {
      console.error("Timing update failed:", error);
      alert("The timing adjustment could not be saved.");
    }
  }

  function adjustSelectedTiming(direction) {
    if (selectedEntryIds.length === 0) {
      console.log("No subtitle lines selected.");
      return;
    }

    const target = document.querySelector(
      'input[name="timing-target"]:checked'
    ).value;

    const adjustment = getTimingAdjustment() * direction;

    console.log("Timing adjustment:", {
      entries: selectedEntryIds,
      target: target,
      adjustment: adjustment,
    });
  }
  function adjustSubtitleTime(timeString, adjustment) {
    const [hours, minutes, secondsMilliseconds] = timeString.split(":");
    const [seconds, milliseconds] = secondsMilliseconds.split(",");

    let totalMilliseconds =
      Number(hours) * 3600000 +
      Number(minutes) * 60000 +
      Number(seconds) * 1000 +
      Number(milliseconds);

    totalMilliseconds += adjustment;

    const newHours = Math.floor(totalMilliseconds / 3600000);
    totalMilliseconds %= 3600000;

    const newMinutes = Math.floor(totalMilliseconds / 60000);
    totalMilliseconds %= 60000;

    const newSeconds = Math.floor(totalMilliseconds / 1000);
    const newMilliseconds = totalMilliseconds % 1000;

    return (
      String(newHours).padStart(2, "0") +
      ":" +
      String(newMinutes).padStart(2, "0") +
      ":" +
      String(newSeconds).padStart(2, "0") +
      "," +
      String(newMilliseconds).padStart(3, "0")
    );
  }


  /**********************************
 * COMBINE SUBTITLES
 **********************************/

const combineBtn = document.getElementById("combine-subtitles");

combineBtn.addEventListener("click", combineSubtitles);

function combineSubtitles() {
  if (currentSubtitles.length === 0 || compareSubtitles.length === 0) {
    console.log("Not enough subtitle data to combine.");
    return;
  }

  const outputLength = Math.max(
    currentSubtitles.length,
    compareSubtitles.length
  );

  const combinedSubtitles = [];

  for (let i = 0; i < outputLength; i++) {
    const currentEntry = currentSubtitles[i];
    const compareEntry = compareSubtitles[i];

    const textParts = [];

    if (currentEntry) {
      textParts.push(currentEntry.text.trim());
    }

    if (compareEntry) {
      textParts.push(compareEntry.text.trim());
    }

    const timingEntry = currentEntry || compareEntry;

    combinedSubtitles.push({
      subtitle_number: i + 1,
      start_time: timingEntry.start_time,
      end_time: timingEntry.end_time,
      text: textParts.join("\n"),
    });
  }

/**********************************
 * GENERATE AND DOWNLOAD SRT
 **********************************/

 const srtContent = combinedSubtitles
 .map((entry) => {
   return [
     entry.subtitle_number,
     `${entry.start_time} --> ${entry.end_time}`,
     entry.text,
   ].join("\n");
 })
 .join("\n\n");
 
   const blob = new Blob([srtContent], {
     type: "text/plain;charset=utf-8",
   });
 
   const downloadUrl = URL.createObjectURL(blob);
   const downloadLink = document.createElement("a");
 
   downloadLink.href = downloadUrl;
   downloadLink.download = "combined_subtitles.srt";
 
   document.body.appendChild(downloadLink);
   downloadLink.click();
   downloadLink.remove();
 
   URL.revokeObjectURL(downloadUrl);

   console.log("Combined subtitles before SRT formatting:", combinedSubtitles);

combinedSubtitles.forEach((entry, index) => {
  console.log(`Subtitle ${index + 1} text:`, JSON.stringify(entry.text));
});
 
   console.log("Combined SRT generated:", srtContent);

   console.log("SRT string as JSON:", JSON.stringify(srtContent));
}

  //ADD NEW FUNCTIONS HERE
});
