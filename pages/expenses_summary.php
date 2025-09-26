<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// Fetch users and categories for filters
$users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Expenses Summary</h4>
        </div>
        <div class="card-body">
            <form id="filtersForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="userFilter" class="form-label">User</label>
                    <select id="userFilter" name="user" class="form-select">
                        <option value="">-- All Users --</option>
                        <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="categoryDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            -- Select Categories --
                        </button>
                        <ul class="dropdown-menu p-3" aria-labelledby="categoryDropdownBtn" style="max-height: 250px; overflow-y: auto;">
                            <li class="mb-2">
                                <button type="button" id="toggleCategoriesBtn" class="btn btn-sm btn-outline-primary w-100">
                                    Select All
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach($categories as $c): ?>
                            <li>
                                <div class="form-check">
                                    <input class="form-check-input category-checkbox" type="checkbox" value="<?= $c['CategoryID'] ?>" id="cat<?= $c['CategoryID'] ?>">
                                    <label class="form-check-label" for="cat<?= $c['CategoryID'] ?>">
                                        <?= htmlspecialchars($c['CategoryName']) ?>
                                    </label>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="yearFilter" class="form-label">Year</label>
                    <input type="number" id="yearFilter" name="year" class="form-control" value="<?= date('Y') ?>">
                </div>

                <div class="col-md-3 text-end">
                    <button type="button" id="applyFiltersBtn" class="btn btn-primary mt-2">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Monthly Expenses</h5>
        </div>
        <div class="card-body">
            <canvas id="expensesChart" height="150"></canvas>
            <div id="expenseDetails" class="mt-4"></div>
        </div>
    </div>
</div>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('expensesChart').getContext('2d');
    let expensesChart;

    function getSelectedCategories() {
        const checked = document.querySelectorAll('.category-checkbox:checked');
        return Array.from(checked).map(cb => cb.value).join(',');
    }

    function updateDropdownLabel() {
        const checked = document.querySelectorAll('.category-checkbox:checked');
        const names = Array.from(checked).map(cb => cb.nextElementSibling.textContent.trim());
        const btn = document.getElementById('categoryDropdownBtn');
        btn.textContent = names.length > 0 ? names.join(', ') : '-- Select Categories --';
    }

    document.getElementById('toggleCategoriesBtn').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.category-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        this.textContent = allChecked ? 'Select All' : 'Unselect All';
        updateDropdownLabel();
    });

    document.querySelectorAll('.category-checkbox').forEach(cb => {
        cb.addEventListener('change', updateDropdownLabel);
    });

    async function loadExpenses() {
        const user = document.getElementById('userFilter').value;
        const category = getSelectedCategories();
        const year = document.getElementById('yearFilter').value;

        const params = new URLSearchParams({ user, category, year });
        const res = await fetch('get_expenses.php?' + params.toString());
        const data = await res.json();

        const labels = data.months;
        const datasets = data.categories.map((cat, index) => ({
            label: cat.name,
            categoryId: cat.id,
            data: data.values.map(row => row[index]),
            backgroundColor: `hsl(${index * 60 % 360}, 70%, 60%)`
        }));

        if (expensesChart) expensesChart.destroy();

        expensesChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                onClick: function(evt, elements) {
                    if (!elements.length) return;

                    const element = elements[0];
                    const datasetIndex = element.datasetIndex;
                    const dataIndex = element.index;

                    const dataset = expensesChart.data.datasets[datasetIndex];
                    const categoryId = dataset.categoryId;
                    const month = expensesChart.data.labels[dataIndex];
                    const year = document.getElementById('yearFilter').value;
                    const userId = document.getElementById('userFilter').value;

                    const params = new URLSearchParams({ categoryId, month, year, userId });
                    fetch('get_expense_details.php?' + params.toString())
                        .then(res => res.text())
                        .then(html => {
                            const container = document.getElementById('expenseDetails');
                            container.innerHTML = html;

                            $('#expenseDetailsTable').DataTable({
                                lengthChange: true,
                                pageLength: 10,
                                columnDefs: [
                                    { targets: 0, visible: false } // hide ID column
                                ],
                                order: [[1, 'asc']] // sort by Date
                            });
                        })
                        .catch(err => {
                            document.getElementById('expenseDetails').innerHTML =
                              `<div class="alert alert-danger">Error loading details</div>`;
                            console.error(err);
                        });
                }
            }
        });
    }

    document.getElementById('applyFiltersBtn').addEventListener('click', loadExpenses);

    // Initial load
    loadExpenses();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
