document.addEventListener('DOMContentLoaded', function () {
    const monthPicker = document.getElementById('monthPicker');

    // -------------------------
    // 1. Set default month
    // -------------------------
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');

    const currentMonth = `${year}-${month}`;
    loadHistory(currentMonth);
    monthPicker.value = currentMonth;

    const dates = getDatesInMonth(monthPicker.value);

    console.log('DATES:', dates);

    monthPicker.addEventListener('change', function () {
        loadHistory(this.value);
    });

    console.log('Current month set to:', currentMonth);

    // -------------------------
    // 2. Listen for changes
    // -------------------------

    function loadHistory(month) {
        const userId = 5; // TEMP (we'll improve later)

        console.log('loadHistory called with:', month);

        fetch(`ajax/get_habit_log.php?user_id=${userId}&month=${month}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    console.error('PHP error:', data.error);
                    return;
                }

                const rows = data.rows;

                console.log('ROWS:', rows.length);

                // -------------------------
                // Build grid structure
                // -------------------------
                const grid = {};

                rows.forEach((row) => {
                    const habit = row.habit_name;
                    const date = row.completed_date.trim();
                    const pct = parseFloat(row.percent_complete);

                    if (!grid[habit]) {
                        grid[habit] = {};
                    }

                    grid[habit][date] = {
                        pct: pct,
                        target: parseFloat(row.target),
                        create: row.create_date,
                        active: row.is_active,
                        modified: row.modified,
                    };
                });

                // -------------------------
                // Build month dates
                // -------------------------
                const dates = getDatesInMonth(month);

                console.log('DATES:', dates[0], '...', dates[dates.length - 1]);

                // -------------------------
                // Render once (IMPORTANT)
                // -------------------------
                renderGrid(grid, dates);
            })
            .catch((err) => {
                console.error('Fetch error:', err);
            });
    }

    function getDatesInMonth(month) {
        const [year, monthIndex] = month.split('-').map(Number);

        const dates = [];
        const date = new Date(year, monthIndex - 1, 1);

        while (date.getMonth() === monthIndex - 1) {
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');

            dates.push(`${yyyy}-${mm}-${dd}`);

            date.setDate(date.getDate() + 1);
        }

        return dates;
    }
    function renderGrid(grid, dates) {
        console.log('DATE CHECK:', dates[0]);
        console.log('GRID DATE KEYS:', Object.keys(grid[Object.keys(grid)[0]]));

        const container = document.getElementById('historyGrid');

        let html = '<table>';

        // -------------------------
        // Header row (dates)
        // -------------------------
        html += '<tr>';
        html += '<th>Habit</th>';

        dates.forEach((d) => {
            const date = d.trim();
            const day = parseInt(date.split('-')[2], 10);
            html += `<th>${day}</th>`;
        });

        html += '</tr>';

        // -------------------------
        // Rows (habits)
        // -------------------------
        Object.keys(grid).forEach((habit) => {
            html += '<tr>';
            html += `<td>${habit}</td>`;

            dates.forEach((d) => {
                const date = d.trim();
                const entry = grid[habit][date];
                console.log('ENTRY', entry);

                let cls = 'hm-na';
                let tooltip = '';

                if (!entry) {
                    cls = 'hm-na';
                } else if (Number(entry.active) === 0) {
                    cls = 'hm-na'; // inactive day (your system already defines this)
                } else if (entry.pct >= 100) {
                    cls = 'hm-100';
                } else if (entry.pct > 0) {
                    cls = 'hm-partial';
                    tooltip = `${entry.pct}%`;
                } else {
                    cls = 'hm-0';
                }
                html += `
                <td>
                    <div class="heatmap-cell ${cls}" ${tooltip ? `title="${tooltip}"` : ''}></div>
                </td>
                `;
            });

            html += '</tr>';
        });

        html += '</table>';

        container.innerHTML = html;
    }

    //ADD NEW FUNCTIONS HERE
});
