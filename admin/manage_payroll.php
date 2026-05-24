<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Payroll.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$payroll = new Payroll($conn);
$month = intval($_GET['month'] ?? date('m'));
$year = intval($_GET['year'] ?? date('Y'));
$day = intval($_GET['day'] ?? date('j'));

// Get all active employees
$employees_sql = "SELECT id, employee_id, first_name, last_name, department, position, client_company FROM employees WHERE status = 'active' ORDER BY first_name, last_name";
$employees_result = $conn->query($employees_sql);
$employees = $employees_result->fetch_all(MYSQLI_ASSOC);

// Get existing payrolls for this period to show status
$payrolls = $payroll->getAllPayrolls($month, $year, $day);
$payroll_map = [];
foreach ($payrolls as $p) {
    $payroll_map[$p['employee_id']] = $p;
}

$current_page = 'manage';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payroll - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .payroll-selector {
            margin-bottom: 20px;
        }
        
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .period-selector select {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .checkbox-table thead th:first-child {
            width: 50px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.has-payroll {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.no-payroll {
            background: #fef2f2;
            color: #991b1b;
        }
        
        .selected-count {
            padding: 10px 14px;
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            font-weight: 500;
            color: #1e40af;
        }

        .search-bar {
            display: flex;
            justify-content: flex-start;
        }

        .search-input {
            width: 100%;
            max-width: 420px;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Manage Payroll</h1>
                <p class="subtitle"><?php echo date('j F Y', mktime(0, 0, 0, $month, $day, $year)); ?></p>
            </div>
        </div>
        
        <div class="payroll-selector">
            <!-- Period Selector -->
            <div class="period-selector">
                <label style="font-weight: 500;">Select Period:</label>
                <select id="monthSelect" onchange="updatePeriod()">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select id="daySelect" onchange="updatePeriod()">
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $day ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <select id="yearSelect" onchange="updatePeriod()">
                    <?php for ($i = 2024; $i <= 2030; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $year ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="selected-count">
                    Selected: <span id="selectedCount">0</span> employee(s)
                </div>
                <button type="button" class="btn btn-primary" onclick="selectAll()" id="selectAllBtn">Select All</button>
                <button type="button" class="btn btn-secondary" onclick="deselectAll()">Clear Selection</button>
                <button type="button" class="btn btn-success" onclick="editPayroll()" id="editBtn" disabled>Manage Payroll</button>
            </div>

            <!-- Search Bar -->
            <div class="search-bar" style="margin-bottom: 20px;">
                <input type="text" id="employeeSearch" class="search-input" placeholder="Search by Employee ID or Name" oninput="filterEmployees()">
            </div>
        </div>
        
        <!-- Employees Table with Checkboxes -->
        <div class="table-container">
            <table class="checkbox-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this.checked)"></th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Client Company</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No active employees found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="emp-checkbox" value="<?php echo $emp['id']; ?>" 
                                           data-emp-id="<?php echo htmlspecialchars($emp['employee_id']); ?>"
                                           onchange="updateSelectedCount()">
                                </td>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['position'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['client_company'] ?? '-'); ?></td>
                                <td>
                                    <?php if (isset($payroll_map[$emp['employee_id']])): ?>
                                        <span class="status-badge has-payroll">✓ Has Entry</span>
                                    <?php else: ?>
                                        <span class="status-badge no-payroll">⊘ No Entry</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="../assets/js/script.js"></script>
<script>
    function updatePeriod() {
        const month = document.getElementById('monthSelect').value;
        const day = document.getElementById('daySelect').value;
        const year = document.getElementById('yearSelect').value;
        window.location.href = `?day=${day}&month=${month}&year=${year}`;
    }
    
    function toggleSelectAll(checked) {
        document.querySelectorAll('.emp-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateSelectedCount();
    }
    
    function selectAll() {
        document.getElementById('selectAllCheckbox').checked = true;
        toggleSelectAll(true);
    }
    
    function deselectAll() {
        document.getElementById('selectAllCheckbox').checked = false;
        toggleSelectAll(false);
    }
    
    function updateSelectedCount() {
        const count = document.querySelectorAll('.emp-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('editBtn').disabled = count === 0;
    }
    
    function filterEmployees() {
        const query = document.getElementById('employeeSearch').value.trim().toLowerCase();
        const rows = document.querySelectorAll('.checkbox-table tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) {
                return;
            }

            const employeeId = cells[1].textContent.trim().toLowerCase();
            const employeeName = cells[2].textContent.trim().toLowerCase();
            const matches = employeeId.includes(query) || employeeName.includes(query);

            row.style.display = matches ? '' : 'none';
            if (matches) {
                visibleCount++;
            }
        });
    }

    function editPayroll() {
        const selected = Array.from(document.querySelectorAll('.emp-checkbox:checked'))
            .map(cb => cb.value)
            .join(',');
        
        if (!selected) {
            alert('Please select at least one employee');
            return;
        }
        
        const month = document.getElementById('monthSelect').value;
        const day = document.getElementById('daySelect').value;
        const year = document.getElementById('yearSelect').value;
        
        window.location.href = `edit_payroll.php?employees=${selected}&day=${day}&month=${month}&year=${year}`;
    }
    
    // Initialize selected count on page load
    updateSelectedCount();
</script>
</body>
</html>
