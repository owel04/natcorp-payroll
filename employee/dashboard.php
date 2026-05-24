<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Employee.php';
require_once $base_path . '/includes/Payroll.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isEmployee()) {
    header('Location: ../login.php');
    exit;
}

$employee_class = new Employee($conn);
$payroll = new Payroll($conn);
$employee = $employee_class->getEmployeeByUserId($_SESSION['user_id']);

if (!$employee) {
    echo "Employee record not found";
    exit;
}

$payslips = $payroll->getEmployeePayslips($employee['id']);
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($employee['first_name']); ?>!</h1>
                <p class="subtitle">Here's your payroll overview</p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon blue">👤</div>
                <h3>Employee ID</h3>
                <div class="card-value"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                <div class="card-subtitle">Your identifier</div>
            </div>
            
            <div class="card">
                <div class="card-icon green">💼</div>
                <h3>Department</h3>
                <div class="card-value" style="font-size: 20px;"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></div>
                <div class="card-subtitle">Current assignment</div>
            </div>
            
            <div class="card">
                <div class="card-icon orange">📄</div>
                <h3>Total Payslips</h3>
                <div class="card-value"><?php echo count($payslips); ?></div>
                <div class="card-subtitle">All records</div>
            </div>
            
            <div class="card">
                <div class="card-icon purple">📧</div>
                <h3>Email</h3>
                <div class="card-value" style="font-size: 14px; word-break: break-all;"><?php echo htmlspecialchars($employee['email']); ?></div>
                <div class="card-subtitle">Contact info</div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Recent Payslips</h2>
            <table>
                <thead>
                    <tr>
                        <th>Payroll Date</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Date Upload</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payslips)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No payslips available yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payslips as $slip): ?>
                            <tr>
                                <td><?php echo date('j F Y', mktime(0, 0, 0, $slip['month'], $slip['day'] ?? 1, $slip['year'])); ?></td>
                                <td class="currency">&#8369;<?php echo number_format($slip['gross_pay'], 2); ?></td>
                                <td class="currency" style="color:#dc2626;">&#8369;<?php echo number_format($slip['total_deductions'], 2); ?></td>
                                <td class="currency"><strong style="color:#059669;">&#8369;<?php echo number_format($slip['net_pay'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($slip['upload_date'])); ?></td>
                                <td>
                                    <a href="view_payslip.php?id=<?php echo $slip['id']; ?>" class="btn btn-primary btn-small">View</a>
                                    <a href="print_payslip.php?id=<?php echo $slip['id']; ?>" class="btn btn-secondary btn-small" target="_blank">Print</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script src="../assets/js/script.js"></script>
</body>
</html>
