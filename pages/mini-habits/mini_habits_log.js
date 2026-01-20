document.addEventListener("DOMContentLoaded", () => {
    const monthPicker = document.getElementById("monthPicker");
    const heatmap = document.getElementById("heatmap");

    function getDaysInMonth(year, month) {
        return new Date(year, month + 1, 0).getDate();
    }

    function intensityClass(percent) {
        percent = Number(percent);
        if (percent >= 100) return "hm-100";
        if (percent >= 75) return "hm-75";
        if (percent >= 50) return "hm-50";
        if (percent >= 25) return "hm-25";
        return "hm-0";
    }

    async function loadHeatmap() {
        const [year, month] = monthPicker.value.split("-");
        const daysInMonth = getDaysInMonth(year, Number(month) - 1);

        const res = await fetch(`ajax/get_habit_log.php?month=${monthPicker.value}`);
        const data = await res.json();

        console.log("Heatmap data:", data);
        
        if (!data.success) {
            heatmap.innerHTML = "<p>Error loading data</p>";
            return;
        }

        // group by habit
        const habits = {};
        data.rows.forEach(row => {
            if (!habits[row.habit_name]) {
                habits[row.habit_name] = {};
            }
            const day = Number(row.completed_date.split("-")[2]);
            habits[row.habit_name][day] = row.percent_complete;
        });

        // grid: habit name + days
        heatmap.innerHTML = "";
        const grid = document.createElement("div");
        grid.className = "heatmap-grid";
        grid.style.gridTemplateColumns = `200px repeat(${daysInMonth}, 22px)`;

        // header row
        grid.appendChild(document.createElement("div"));
        for (let d = 1; d <= daysInMonth; d++) {
            const h = document.createElement("div");
            h.className = "heatmap-header";
            h.textContent = d;
            grid.appendChild(h);
        }

        // habit rows
        Object.entries(habits).forEach(([habit, days]) => {
            const label = document.createElement("div");
            label.className = "heatmap-habit";
            label.textContent = habit;
            grid.appendChild(label);

            for (let d = 1; d <= daysInMonth; d++) {
                const cell = document.createElement("div");
                const pct = days[d] ?? 0;
                cell.className = `heatmap-cell ${intensityClass(pct)}`;
                cell.title = `${pct}%`;
                grid.appendChild(cell);
            }
        });

        heatmap.appendChild(grid);
    }

    monthPicker.addEventListener("change", loadHeatmap);
    loadHeatmap();
});
