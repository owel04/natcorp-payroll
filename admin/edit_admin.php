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

$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = new Employee($conn);
$admin = $employee->getAdmin($admin_id);

if (!$admin) {
    echo "Admin account not found";
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($password !== '' && $password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = $employee->updateAdmin($admin_id, [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);

        if ($result['success']) {
            $message = $result['message'];
            $auth->logAction('Updated admin ' . htmlspecialchars($admin['username']));
            $admin = $employee->getAdmin($admin_id);
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
    <title>Edit Admin - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Admin</h1>
                <p class="subtitle"><?php echo htmlspecialchars($admin['username']); ?></p>
            </div>
            <a href="employees.php?tab=employees" class="btn btn-secondary">← Back</a>
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
                        <label>Username *</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Repeat new password">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-success">Update Admin</button>
                    <a href="employees.php?tab=employees" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validatePassword() {
            var password = document.querySelector('input[name="password"]').value;
            var confirm = document.querySelector('input[name="confirm_password"]').value;
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
