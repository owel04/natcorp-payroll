<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Employee.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isEmployee()) {
    header('Location: ../login.php');
    exit;
}

$employee_class = new Employee($conn);
$employee = $employee_class->getEmployeeByUserId($_SESSION['user_id']);

$message = '';
$error = '';

if (!$employee) {
    echo "Employee record not found";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_contact') {
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $result = $employee_class->updateEmployeeProfile($_SESSION['user_id'], $email, $phone);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match.';
        } elseif (empty($current_password) || empty($new_password)) {
            $error = 'Please enter both current and new passwords.';
        } else {
            if ($auth->changePassword($_SESSION['user_id'], $current_password, $new_password)) {
                $message = 'Password changed successfully.';
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }

    $employee = $employee_class->getEmployeeByUserId($_SESSION['user_id']);
}

$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 760px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 18px;
            right: 18px;
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #555;
        }
        .modal-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 22px;
            margin: 0;
            color: #333;
        }
        .profile-header-actions {
            margin-top: 12px;
        }
        .btn-secondary {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-secondary:hover {
            background: #43a047;
        }
    </style>
</head>
<body>
    <?php include '../includes/employee_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>My Profile</h1>
                <p class="subtitle">Your personal information</p>
            </div>
        </div>
        
        <div class="profile-card">
            <div class="profile-header-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-name-section">
                    <h2><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                    <p><?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?> · <?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></p>
                    <div class="profile-header-actions">
                        <button id="openProfileModal" class="btn-secondary">Edit Profile</button>
                    </div>
                </div>
            </div>
            
            <div class="profile-grid">
                <div class="profile-field">
                    <label>Employee ID</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Email</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['email']); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Phone</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Department</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Position</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Date of Birth</label>
                    <div class="profile-value"><?php echo $employee['dob'] ? date('M d, Y', strtotime($employee['dob'])) : 'N/A'; ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Client Company</label>
                    <div class="profile-value"><?php echo htmlspecialchars($employee['client_company'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="profile-field">
                    <label>Date Hired</label>
                    <div class="profile-value"><?php echo $employee['date_of_joining'] ? date('M d, Y', strtotime($employee['date_of_joining'])) : 'N/A'; ?></div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="margin: 20px 0;">✓ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin: 20px 0;">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="btn-group">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <div id="profileModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" id="closeProfileModal">×</button>
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Edit Profile</h2>
                    <p>Update contact details and password from one place.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">✓ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container" style="margin-top: 0;">
                <h3>Update Contact</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_contact">
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="btn-group" style="margin-top: 10px;">
                        <button type="submit" class="btn btn-success">Save Contact</button>
                    </div>
                </form>
            </div>

            <div class="form-container" style="margin-top: 20px;">
                <h3>Change Password</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="width: 100%;">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="btn-group" style="margin-top: 10px;">
                        <button type="submit" class="btn btn-success">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    const openModalBtn = document.getElementById('openProfileModal');
    const closeModalBtn = document.getElementById('closeProfileModal');
    const profileModal = document.getElementById('profileModal');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            profileModal.classList.add('active');
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            profileModal.classList.remove('active');
        });
    }

    profileModal.addEventListener('click', function(e) {
        if (e.target === profileModal) {
            profileModal.classList.remove('active');
        }
    });

    <?php if ($message || $error): ?>
    profileModal.classList.add('active');
    <?php endif; ?>
</script>
<script src="../assets/js/script.js"></script>
</body>
</html>
