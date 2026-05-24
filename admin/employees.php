<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Employee.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$employee = new Employee($conn);
$message = '';
$error = '';

$current_page = 'employees';
$tab = $_GET['tab'] ?? 'employees';

// Employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
    $employee_data = [
        'employee_id' => $_POST['employee_id'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'] ?? '',
        'department' => $_POST['department'] ?? '',
        'position' => $_POST['position'],
        'phone' => $_POST['phone'] ?? '',
        'dob' => $_POST['dob'] ? date('Y-m-d', strtotime($_POST['dob'])) : NULL,
        'date_of_joining' => $_POST['date_of_joining'] ? date('Y-m-d', strtotime($_POST['date_of_joining'])) : NULL,
        'client_company' => $_POST['client_company'] ?? ''
    ];
    
    $result = $employee->addEmployee($employee_data);
    if ($result['success']) {
        $message = 'Employee added successfully. Login: ' . strtolower($_POST['first_name'] . '.' . $_POST['last_name']) . ' / Password: admin123';
        $auth->logAction('Added new employee ' . htmlspecialchars($employee_data['employee_id']) . ' - ' . htmlspecialchars($employee_data['first_name'] . ' ' . $employee_data['last_name']));
    } else {
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $admin_data = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'password' => trim($_POST['password']),
        'role' => 'admin',
    ];

    $result = $employee->addUserAccount($admin_data);
    if ($result['success']) {
        $message = 'Admin account created successfully. Username: ' . htmlspecialchars($admin_data['username']);
        $auth->logAction('Added new admin ' . htmlspecialchars($admin_data['username']));
    } else {
        $error = $result['message'];
    }
}

// Employee activation actions
if (isset($_GET['deactivate'])) {
    $empId = (int)$_GET['deactivate'];
    $empData = $employee->getEmployee($empId);
    $employee->deactivateEmployee($empId);
    $message = 'Employee deactivated successfully';
    $auth->logAction('Deactivated employee ' . ($empData['employee_id'] ?? $empId));
}

if (isset($_GET['activate'])) {
    $empId = (int)$_GET['activate'];
    $empData = $employee->getEmployee($empId);
    $employee->activateEmployee($empId);
    $message = 'Employee activated successfully';
    $auth->logAction('Activated employee ' . ($empData['employee_id'] ?? $empId));
}

if (isset($_GET['deactivate_admin'])) {
    $adminId = (int)$_GET['deactivate_admin'];
    $adminData = $employee->getAdmin($adminId);
    $employee->deactivateAdmin($adminId);
    $message = 'Admin account deactivated successfully';
    $auth->logAction('Deactivated admin ' . ($adminData['username'] ?? $adminId));
}

if (isset($_GET['activate_admin'])) {
    $adminId = (int)$_GET['activate_admin'];
    $adminData = $employee->getAdmin($adminId);
    $employee->activateAdmin($adminId);
    $message = 'Admin account activated successfully';
    $auth->logAction('Activated admin ' . ($adminData['username'] ?? $adminId));
}

$search_term = $_GET['search'] ?? '';
$employees = $search_term ? $employee->searchEmployees($search_term) : $employee->getAllEmployees(1000);
$admins = $employee->getAdmins();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .tab-nav { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-nav a { padding: 10px 18px; border-radius: 999px; background: #f4f7fb; border: 1px solid #d9e2ec; color: #2d3748; text-decoration: none; font-weight: 600; }
        .tab-nav a.active { background: #1d75db; border-color: #1d75db; color: #fff; }
        .section-panel { display: none; }
        .section-panel.active { display: block; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; color: #fff; }
        .badge-success { background: #2f9b2f; }
        .badge-danger { background: #db3a32; }
        .form-subsection { background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 24px; }
        .form-subsection h3 { margin-top: 0; }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Employees</h1>
                <p class="subtitle">Manage your workforce</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tab-nav">
            <a href="employees.php?tab=employees" class="<?php echo $tab === 'employees' ? 'active' : ''; ?>">Employees/Admins</a>
            <a href="employees.php?tab=users" class="<?php echo $tab === 'users' ? 'active' : ''; ?>">Add Employee</a>
            <a href="employees.php?tab=admin" class="<?php echo $tab === 'admin' ? 'active' : ''; ?>">Add Admin</a>
        </div>

        <div id="employeesSection" class="section-panel <?php echo $tab === 'employees' ? 'active' : ''; ?>">
            <div class="page-header" style="margin-bottom: 15px; gap: 10px;">
                <form method="GET" action="" style="width: 100%; display: flex; gap: 10px;">
                    <input type="hidden" name="tab" value="employees">
                    <div class="search-box" style="flex: 1;">
                        <input type="text" name="search" placeholder="Search by Employee ID, Name, or Email..." value="<?php echo htmlspecialchars($search_term); ?>" style="width: 100%;">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search_term): ?>
                        <a href="employees.php?tab=employees" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No employees found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $emp['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                        <?php if ($emp['status'] === 'active'): ?>
                                            <a href="employees.php?tab=employees&deactivate=<?php echo $emp['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Deactivate this employee?');">Deactivate</a>
                                        <?php else: ?>
                                            <a href="employees.php?tab=employees&activate=<?php echo $emp['id']; ?>" class="btn btn-success btn-small">Activate</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-subsection">
                <h3>Administrator Accounts</h3>
                <div class="table-container" style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No admin accounts found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($admin['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                            <?php if ($admin['status'] === 'active'): ?>
                                                <a href="employees.php?tab=employees&deactivate_admin=<?php echo $admin['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Deactivate this admin account?');">Deactivate</a>
                                            <?php else: ?>
                                                <a href="employees.php?tab=employees&activate_admin=<?php echo $admin['id']; ?>" class="btn btn-success btn-small">Activate</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="usersSection" class="section-panel <?php echo $tab === 'users' ? 'active' : ''; ?>">
            <div class="page-header" style="margin-bottom: 15px; gap: 10px;">
                <div>
                    <h2>Add Employee</h2>
                    <p class="subtitle">Create a new employee record.</p>
                </div>
            </div>

            <div class="form-subsection">
                <h3>Add New Employee</h3>
                <form method="POST" action="employees.php?tab=users">
                    <input type="hidden" name="action" value="add_employee">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Employee ID *</label>
                            <input type="text" name="employee_id" required>
                        </div>
                        <div class="form-group">
                            <label>Position *</label>
                            <input type="text" name="position" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Client Company *</label>
                            <input type="text" name="client_company" required>
                        </div>
                        <div class="form-group">
                            <label>Date Hired *</label>
                            <input type="date" name="date_of_joining" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="adminSection" class="section-panel <?php echo $tab === 'admin' ? 'active' : ''; ?>">
            <div class="page-header" style="margin-bottom: 15px; gap: 10px;">
                <div>
                    <h2>Add Admin</h2>
                    <p class="subtitle">Create a new administrator account.</p>
                </div>
            </div>

            <div class="form-subsection">
                <h3>Create Admin Account</h3>
                <form method="POST" action="employees.php?tab=admin">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Create Admin</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
<script src="../assets/js/script.js"></script>
</body>
</html>
