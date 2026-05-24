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

$employee_id = $_GET['id'] ?? 0;
$employee_class = new Employee($conn);
$employee = $employee_class->getEmployee($employee_id);

if (!$employee) {
    echo "Employee not found";
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_password !== '' && $new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } else {
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
        
        $result = $employee_class->updateEmployee($employee_id, $employee_data);
        if ($result['success']) {
            $message = $result['message'];
            $auth->logAction('Updated employee ' . htmlspecialchars($employee['employee_id']));
            if ($new_password !== '') {
                if ($auth->resetPassword($employee['user_id'], $new_password)) {
                    $message .= ' Password updated successfully.';
                    $auth->logAction('Reset password for employee ' . htmlspecialchars($employee['employee_id']));
                } else {
                    $error = 'Employee updated, but password change failed.';
                }
            }
            $employee = $employee_class->getEmployee($employee_id);
        } else {
            $error = $result['message'];
        }
    }
}

$current_page = 'employees';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Employee</h1>
                <p class="subtitle"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?><?php echo $employee['date_of_joining'] ? ' — Joined ' . date('j F Y', strtotime($employee['date_of_joining'])) : ''; ?></p>
            </div>
            <a href="employees.php" class="btn btn-secondary">← Back</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" onsubmit="return validatePassword();">
                <div class="form-row">
                    <div class="form-group">
                        <label>Employee ID *</label>
                        <input type="text" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat new password">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Client Company</label>
                        <input type="text" name="client_company" value="<?php echo htmlspecialchars($employee['client_company']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Hired</label>
                        <input type="date" name="date_of_joining" value="<?php echo htmlspecialchars($employee['date_of_joining']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($employee['dob']); ?>">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success">Update Employee</button>
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function validatePassword() {
            const password = document.querySelector('input[name="new_password"]').value;
            const confirm = document.querySelector('input[name="confirm_password"]').value;
            if (password !== '' && password !== confirm) {
                alert('Passwords do not match.');
                return false;
            }
            return true;
        }
    </script>
<script src="../assets/js/script.js"></script>
</body>
</html>
