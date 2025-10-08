document.addEventListener('DOMContentLoaded', () => {

  const weekGrid = document.getElementById('weekGrid');
  const weekStartInput = document.getElementById('weekStart');
  const userSelect = document.getElementById('userSelect');
  const foodList = document.getElementById('foodList');

  let activeCell = null;

  

  function makeMealItemDraggable(div) {
    div.setAttribute('draggable', 'true');
  
    div.addEventListener('dragstart', e => {
      e.dataTransfer.setData('text/plain', JSON.stringify({
        itemId: div.dataset.itemId,
        itemName: div.textContent
      }));
      e.dataTransfer.effectAllowed = 'move';
      div.classList.add('dragging');
      
      // store actual dragged element
      window.draggedElement = div;
    });
  
    div.addEventListener('dragend', () => {
      div.classList.remove('dragging');
      window.draggedElement = null;
    });
  }
  
  
  // --- Load users ---
  fetch('get_users.php')
  .then(res => res.json())
  .then(data => {
    userSelect.innerHTML = '';
    data.forEach(user => {
      const option = document.createElement('option');
      option.value = user.id;
      option.textContent = user.username;
      userSelect.appendChild(option);
    });

    // Select the first user by default
    if(data.length > 0) userSelect.value = data[0].id;

    // Now generate grid and load meals
    generateTwoWeekGrid(weekStartInput.value);
  });


  let allFoods = []; // store all food elements

  // --- Load foods ---
  fetch('get_foods.php')
    .then(res => res.json())
    .then(data => {
      foodList.innerHTML = '';
      allFoods = data.map(food => {
        const div = document.createElement('div');
        div.className = 'food-item';
        div.dataset.id = food.ItemID;
        div.textContent = food.ItemName;
  
        // hover effect
        div.addEventListener('mouseenter', () => div.style.backgroundColor = '#b2ebf2');
        div.addEventListener('mouseleave', () => div.style.backgroundColor = '#e0f7fa');
  
        // Double-click to add food to active cell
        div.addEventListener('dblclick', () => {
          if (!activeCell) return;
          const existingIds = Array.from(activeCell.querySelectorAll('.meal-item'))
                                  .map(el => el.dataset.itemId);
          if (!existingIds.includes(food.ItemID.toString())) {
            const item = document.createElement('div');
            item.className = 'meal-item';
            item.textContent = food.ItemName;
            item.dataset.itemId = food.ItemID;
            makeMealItemDraggable(item);
            activeCell.appendChild(item);
            saveActiveCell(activeCell);
          }
        });
  
        foodList.appendChild(div);
        return div;
      });
    });
  
  // --- Food search filter ---
  const foodSearch = document.getElementById('foodSearch');
  foodSearch.addEventListener('input', () => {
    const query = foodSearch.value.toLowerCase();
    foodList.innerHTML = '';
    allFoods.forEach(div => {
      if (div.textContent.toLowerCase().includes(query)) {
        foodList.appendChild(div);
      }
    });
  });
  
  // --- Generate two-week grid ---
  function generateTwoWeekGrid(startDateStr) {
      weekGrid.innerHTML = '';// Parse date in local time to avoid timezone shift issues
      
      const [year, month, dayNum] = startDateStr.split('-').map(Number);
      const startDate = new Date(year, month - 1, dayNum); // local midnight
      
      // Force startDate to Sunday of that week
      const day = startDate.getDay(); // 0=Sunday
      startDate.setDate(startDate.getDate() - day);
      
      
      const weeks = [0, 7]; // two weeks

      weeks.forEach(offset => {
          let weekDate = new Date(startDate);
          weekDate.setDate(weekDate.getDate() + offset);

          const table = document.createElement('table');
          table.className = 'week-table';

          // Week header
          const thead = document.createElement('thead');
          const headRow = document.createElement('tr');
          const thWeek = document.createElement('th');
          thWeek.colSpan = 8;
          thWeek.style.textAlign = 'center';
          thWeek.style.padding = '10px';
          thWeek.style.backgroundColor = '#eee';
          thWeek.textContent = `Week of ${weekDate.toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'})}`;
          headRow.appendChild(thWeek);
          thead.appendChild(headRow);

          // Weekday headers
          const daysRow = document.createElement('tr');
          const emptyTh = document.createElement('th');
          daysRow.appendChild(emptyTh);
          const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
          for (let i = 0; i < 7; i++) {
              const th = document.createElement('th');
              th.textContent = dayNames[i];
              th.style.border = '1px solid #888';
              th.style.padding = '5px';
              th.style.textAlign = 'center';

              daysRow.appendChild(th);
          }
          thead.appendChild(daysRow);
          table.appendChild(thead);

          // Table body
          const tbody = document.createElement('tbody');
          ['Breakfast','Lunch','Dinner'].forEach(meal => {
              const tr = document.createElement('tr');

              // Meal label
              const tdMealLabel = document.createElement('td');
              tdMealLabel.textContent = meal;
              tdMealLabel.style.fontWeight = 'bold';
              tdMealLabel.style.border = '1px solid #888';
              tdMealLabel.style.padding = '5px';
              tr.appendChild(tdMealLabel);

              // Meal slots
              for (let d = 0; d < 7; d++) {
                  const td = document.createElement('td');
                  td.className = 'meal-slot';
                  td.dataset.meal = meal.toLowerCase();

                  // Compute date for this cell
                  let cellDate = new Date(weekDate);
                  cellDate.setDate(cellDate.getDate() + d);
                  td.dataset.date = cellDate.toISOString().split('T')[0];

                  td.style.minHeight = '50px';
                  td.style.border = '2px solid #888';
                  td.style.padding = '5px';

                  // Click to activate cell
                  td.addEventListener('click', () => {
                      if (activeCell) activeCell.classList.remove('active');
                      activeCell = td;
                      td.classList.add('active');
                  });

                  // Double-click to remove an item
                  td.addEventListener('dblclick', e => {
                      if (e.target.classList.contains('meal-item')) {
                          e.target.remove();
                          saveActiveCell(td);
                      }
                  });

                      /// begin accept drops
                      td.addEventListener('dragover', e => {
                        e.preventDefault();
                        td.style.backgroundColor = '#f1f8e9'; // highlight while dragging
                      });

                      td.addEventListener('dragleave', () => {
                        td.style.backgroundColor = '';
                      });

// --- inside generateTwoWeekGrid, in the td loop ---

                    td.addEventListener('drop', e => {
                      e.preventDefault();
                      td.style.backgroundColor = '';

                      const data = JSON.parse(e.dataTransfer.getData('text/plain'));

                      // Prevent duplicate in the same cell
                      const existingIds = Array.from(td.querySelectorAll('.meal-item'))
                        .map(el => el.dataset.itemId);
                      if (existingIds.includes(data.itemId)) return;

                      if (window.draggedElement) {  
                        // --- capture old cell BEFORE moving ---
                        const oldCell = window.draggedElement.closest('.meal-slot');

                        // Move dragged element into new cell
                        td.appendChild(window.draggedElement);

                        // Save both cells
                        saveActiveCell(td); // new
                        if (oldCell && oldCell !== td) saveActiveCell(oldCell); // old
                      }
                    });



                      /// end accept drops

                  

                  tr.appendChild(td);
              }

              tbody.appendChild(tr);
          });

          table.appendChild(tbody);
          weekGrid.appendChild(table);
      });

      loadMeals();
  }

  // --- Save active cell ---
  function saveActiveCell(cell) {
    if (!cell) return;
  
    const mealDate = cell.dataset.date;
    const mealType = cell.dataset.meal;
    const items = Array.from(cell.querySelectorAll('.meal-item'))
                       .map(el => el.dataset.itemId);
  
    console.log('Saving cell:', {mealDate, mealType, items});
  
    const params = new URLSearchParams();
    params.append('userId', userSelect.value);
    params.append('mealDate', mealDate);
    params.append('mealType', mealType);
    items.forEach(id => params.append('items[]', id)); // note the [] 
    
    fetch('save_meal.php', {
        method: 'POST',
        body: params
    })
        .then(res => res.json())
    .then(data => {
        if (!data.success) console.error('Save error:', data.error);
    })
    .catch(err => console.error('Fetch error:', err));
  }
  

  // --- Load meals ---
  function loadMeals() {
      const userId = userSelect.value;
      const weekStart = weekStartInput.value;

          const weekStartDate = new Date(weekStart);
          const day = weekStartDate.getDay();
          weekStartDate.setDate(weekStartDate.getDate() - day); // ensure Sunday

          const weekStartISO = weekStartDate.toISOString().split('T')[0];
          fetch(`load_meals.php?userId=${userId}&weekStart=${weekStartISO}&weeks=2`)

          .then(res => res.json())
          .then(data => {
              document.querySelectorAll('.meal-slot').forEach(cell => cell.innerHTML = '');

              for (let date in data) {
                  for (let mealType in data[date]) {
                      const cell = document.querySelector(`.meal-slot[data-date="${date}"][data-meal="${mealType}"]`);
                      if (!cell) continue;

                      data[date][mealType].forEach(item => {
                          const div = document.createElement('div');
                          div.className = 'meal-item';
                          div.dataset.itemId = item.id;
                          div.textContent = item.name;

                          makeMealItemDraggable(div); 

                          cell.appendChild(div);
                      });
                  }
              }
          })
          .catch(err => console.error('Load error:', err));
  }
  
  // --- Initial render ---
  //generateTwoWeekGrid(weekStartInput.value);

  // --- Event listeners ---
  weekStartInput.addEventListener('change', () => generateTwoWeekGrid(weekStartInput.value));
  userSelect.addEventListener('change', loadMeals);
  document.getElementById('printBtn').addEventListener('click', () => window.print());

});
