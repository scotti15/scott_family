document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('newHabitForm');
    const showDeactivatedBtn = document.getElementById('showDeactivatedBtn');
    const showDeactivatedModalEl = document.getElementById('showDeactivatedModal');
    const showDeactivatedList = document.getElementById('showDeactivatedList');

    // Load habits initially
    loadHabits();

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const name = document.getElementById('newHabitName').value.trim();
        const dailyTarget = document.querySelector('input[name="daily_target"]').value;

        if (!name) return;

        fetch('ajax/add_habit.php', {
            method: 'POST',
            body: new URLSearchParams({
                habit_name: name,
                daily_target: dailyTarget,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                console.log('Server response:', data); // <-- debug
                if (data.success) {
                    document.getElementById('habitMessage').textContent = 'Habit added!';
                    document.getElementById('newHabitName').value = '';
                    loadHabits(); // refresh list
                } else {
                    document.getElementById('habitMessage').textContent = data.error;
                }
            })
            .catch((err) => console.error('Fetch error:', err));
    });

    // Switch between user lists
    const selector = document.getElementById("userSelector");
    if (selector) {
        selector.addEventListener("change", function() {
            const userId = this.value;
            // Reload the page with the selected user's habits
            window.location.href = "index.php?user_id=" + userId;
        });
    }
    

    document.addEventListener('DOMContentLoaded', () => {
        loadHabits(); // NEW
    });

    // Fetch and render habits for the selected user
    function loadHabits() {
        const userId = document.getElementById('userSelector').value;

        fetch('ajax/get_habits.php?user_id=' + userId)
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) return;

                // Disable add habit form if not owner
                document.getElementById('newHabitName').disabled = !data.isOwner;
                document.querySelector('#newHabitForm button').disabled = !data.isOwner;

                // Render To-Do and Completed columns
                renderHabits(data);
            });
    }

    function renderHabits(data) {
        const todo = document.getElementById("todoList");
        const done = document.getElementById("completedList");
    
        todo.innerHTML = "";
        done.innerHTML = "";
    
        // Helper to create button HTML
        const btnHTML = (classes, text, habitId) => 
            `<button class="${classes}" data-id="${habitId}" ${!data.isOwner ? 'disabled' : ''}>${text}</button>`;
    
        // Pending habits → To Do
        data.pending.forEach(h => {
            const li = document.createElement("li");
            li.className = "list-group-item d-flex justify-content-between align-items-center";
    
            li.innerHTML = `
                <span>${h.habit_name} (${h.completed}/${h.daily_target})</span>
                <div>
                    ${btnHTML('doneBtn btn btn-success btn-sm me-1', 'Done', h.habit_id)}
                    ${btnHTML('undoBtn btn btn-warning btn-sm me-1', 'Undo', h.habit_id)}
                    ${btnHTML('editBtn btn btn-secondary btn-sm me-1', 'Edit', h.habit_id)}
                    ${btnHTML('deleteBtn btn btn-danger btn-sm me-1', 'Delete', h.habit_id)}
                    ${btnHTML('deactivateBtn btn btn-warning btn-sm', 'Deactivate', h.habit_id)}
                </div>
            `;
    
            todo.appendChild(li);
        });
    
        // Completed habits → Completed column
        data.completed.forEach(h => {
            const li = document.createElement("li");
            li.className = "list-group-item d-flex justify-content-between align-items-center";
    
            li.innerHTML = `
                <span>${h.habit_name} (${h.completed}/${h.daily_target})</span>
                <div>
                    ${btnHTML('undoBtn btn btn-warning btn-sm me-1', 'Undo', h.habit_id)}
                    ${btnHTML('editBtn btn btn-secondary btn-sm me-1', 'Edit', h.habit_id)}
                    ${btnHTML('deleteBtn btn btn-danger btn-sm', 'Delete', h.habit_id)}
                </div>
            `;
    
            done.appendChild(li);
        });
    }
    
    document.addEventListener('click', (e) => {
        const btn = e.target;
        const habitId = btn.dataset.id;

        if (e.target.classList.contains('reactivateBtn')) {
            const habitId = e.target.dataset.id;

            fetch('ajax/reactivate_habit.php', {
                method: 'POST',
                body: new URLSearchParams({ habit_id: habitId }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        loadHabits(); // refresh main table
                        e.target.closest('li').remove(); // remove from modal
                    }
                });
        }

        
    if (btn.classList.contains("editBtn")) {
        const li = btn.closest("li");
        const span = li.querySelector("span");
        const textMatch = span.textContent.match(/^(.*) \((\d+)\/(\d+)\)$/);
        if (!textMatch) return;

        const habitId = btn.dataset.id;
        const currentName = textMatch[1];
        const currentTarget = textMatch[3];

        // Fill modal inputs
        document.getElementById("editHabitId").value = habitId;
        document.getElementById("editHabitName").value = currentName;
        document.getElementById("editHabitTarget").value = currentTarget;

        // Show modal
        const modalEl = document.getElementById("editHabitModal");
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }


        // Save edited habit
        document.getElementById('saveEditHabitBtn').addEventListener('click', () => {
            const habitId = document.getElementById('editHabitId').value;
            const newName = document.getElementById('editHabitName').value.trim();
            const newTarget = parseInt(document.getElementById('editHabitTarget').value, 10);
    
            if (!newName || newTarget < 1) {
                alert('Please enter valid habit name and daily target.');
                return;
            }
    
            fetch('ajax/edit_habit.php', {
                method: 'POST',
                body: new URLSearchParams({
                    habit_id: habitId,
                    habit_name: newName,
                    daily_target: newTarget,
                }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        loadHabits(); // refresh lists
                        const modalEl = document.getElementById('editHabitModal');
                        bootstrap.Modal.getInstance(modalEl).hide();
                    } else {
                        alert('Error updating habit: ' + data.error);
                    }
                })
                .catch((err) => console.error('Fetch error:', err));
        });

        if (!habitId) return;


        // Done button
        if (btn.classList.contains('doneBtn')) {
            fetch('ajax/mark_done.php', {
                method: 'POST',
                body: new URLSearchParams({ habit_id: habitId }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) loadHabits(); // reload both columns
                });
        }

        // Undo button
        if (btn.classList.contains('undoBtn')) {
            fetch('ajax/undo_done.php', {
                method: 'POST',
                body: new URLSearchParams({ habit_id: habitId }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) loadHabits(); // refresh both columns
                })
                .catch((err) => console.error('Fetch error:', err));
        }

        if (btn.classList.contains('deleteBtn') && btn.textContent === 'Delete') {
            const habitId = btn.dataset.id;
            if (!habitId) return;

            if (!confirm('Are you sure you want to delete this habit?')) return;

            fetch('ajax/delete_habit.php', {
                method: 'POST',
                body: new URLSearchParams({ habit_id: habitId }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        loadHabits(); // refresh lists
                    } else {
                        alert('Error deleting habit: ' + data.error);
                    }
                })
                .catch((err) => console.error('Fetch error:', err));
        }

        if (btn.classList.contains('deactivateBtn')) {
            console.log('Deactivate button presed');
            const habitId = btn.dataset.id;
            if (!habitId) return;

            if (!confirm('Are you sure you want to deactivate this habit?')) return;

            fetch('ajax/deactivate_habit.php', {
                method: 'POST',
                body: new URLSearchParams({ habit_id: habitId }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        loadHabits(); // refresh the To Do and Completed lists
                    } else {
                        alert('Error deactivating habit: ' + data.error);
                    }
                })
                .catch((err) => console.error('Fetch error:', err));
        }
    });
});

if (showDeactivatedBtn && showDeactivatedModalEl) {
    showDeactivatedBtn.addEventListener('click', () => {
        const userId = document.getElementById('userSelector').value;

        fetch(`ajax/get_deactivated.php?user_id=${userId}`)
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    console.error('Failed to fetch deactivated habits:', data.error);
                    return;
                }

                // Clear previous list
                showDeactivatedList.innerHTML = '';

                // Populate list
                data.habits.forEach((h) => {
                    const li = document.createElement('li');
                    li.className =
                        'list-group-item d-flex justify-content-between align-items-center';

                    // Only show Reactivate button if the current viewer is the owner
                    const reactivateButton = data.isOwner
                        ? `<button class="reactivateBtn btn btn-success btn-sm" data-id="${h.habit_id}">Reactivate</button>`
                        : '';

                    li.innerHTML = `
                            <span>${h.habit_name} (${h.completed}/${h.daily_target})</span>
                            ${reactivateButton}
                        `;
                    showDeactivatedList.appendChild(li);
                });

                // Show the modal
                const modal = new bootstrap.Modal(showDeactivatedModalEl);
                modal.show();
            })
            .catch((err) => console.error('Error fetching deactivated habits:', err));
    });


    // Fire the rollover script silently on page load
    fetch('rollover_daily_stats.php')
        .then(res => res.json())
        .then(data => {
            console.log("Daily rollover executed:", data);
        })
        .catch(err => {
            console.error("Daily rollover error:", err);
        });

    

}
