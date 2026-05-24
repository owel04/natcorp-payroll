<?php
// Production-ready path detection for different hosting environments
// This works on localhost, subdomains, and shared hosting

// Prefer __DIR__ first - this is the most reliable method
$base_path = __DIR__;

// Verify all required files exist
$required_files = [
    '/config.php',
    '/includes/Auth.php',
    '/includes/Employee.php',
    '/includes/Payroll.php'
];

$missing = [];
foreach ($required_files as $file) {
    if (!file_exists($base_path . $file)) {
        $missing[] = $file;
    }
}

if (!empty($missing)) {
    die('<h2>Setup Error</h2>' .
        '<p>Cannot find required application files:</p>' .
        '<ul><li>' . implode('</li><li>', htmlspecialchars_recursive($missing)) . '</li></ul>' .
        '<p>Path checked: ' . htmlspecialchars($base_path) . '</p>' .
        '<p>Please ensure all folders are uploaded to your host.</p>' .
        '<p><strong>Debug Info:</strong> __DIR__=' . htmlspecialchars($base_path) . '</p>');
}

require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';

// Helper function for recursive htmlspecialchars
function htmlspecialchars_recursive($data) {
    if (is_array($data)) {
        return array_map('htmlspecialchars_recursive', $data);
    }
    return htmlspecialchars($data);
}

$error_message = '';
$success_message = '';
$auth = new Auth($conn);

if (!$auth->isLoggedIn()) {
    $auth->autoLogin();
}

if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin/admin_dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter username and password';
    } else {
        if ($auth->login($username, $password, $remember)) {
            $success_message = 'Login successful!';
            if ($auth->isAdmin()) {
                header('Location: admin/admin_dashboard.php');
            } else {
                header('Location: employee/dashboard.php');
            }
            exit;
        } else {
            $error_message = $auth->getLastError() ?: 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Natcorp Agency - Payroll System Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <img src="assets/images/natcorp-logo.png" alt="Natcorp Logo">
            <h1>NATCORP</h1>
            <p>Career Growth & Manpower Services</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="login-alert login-alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="login-alert login-alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="login-form-group">
                <label for="username">Username or Employee ID</label>
                <input type="text" id="username" name="username" placeholder="Enter your username or employee ID" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="login-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

         
            
            <button type="submit" class="login-btn">Sign In</button>
            <div class="login-help">
                <a href="https://www.facebook.com/natcorphermosa" target="_blank" rel="noopener noreferrer">Need help? Contact admin on Facebook</a>
            </div>
        </form>
    </div>
</body>
</html>
