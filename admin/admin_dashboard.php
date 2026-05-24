<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Employee.php';
require_once $base_path . '/includes/Payroll.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get dashboard statistics
$employee_count = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status='active'")->fetch_assoc()['count'];
$total_payrolls = $conn->query("SELECT COUNT(*) as count FROM payroll_summary")->fetch_assoc()['count'];
$this_month_payrolls = $conn->query("SELECT COUNT(*) as count FROM payroll_summary WHERE month=" . date('m') . " AND year=" . date('Y'))->fetch_assoc()['count'];
$total_salary = $conn->query("SELECT SUM(net_pay) as total FROM payroll_summary WHERE month=" . date('m') . " AND year=" . date('Y'))->fetch_assoc()['total'] ?? 0;

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon blue">👥</div>
                <h3>Total Employees</h3>
                <div class="card-value"><?php echo $employee_count; ?></div>
                <div class="card-subtitle">Active members</div>
            </div>
            
            <div class="card">
                <div class="card-icon green">💼</div>
                <h3>Total Payroll Records</h3>
                <div class="card-value"><?php echo $total_payrolls; ?></div>
                <div class="card-subtitle">All time</div>
            </div>
            
            <div class="card">
                <div class="card-icon orange">📅</div>
                <h3>This Month Payrolls</h3>
                <div class="card-value"><?php echo $this_month_payrolls; ?></div>
                <div class="card-subtitle"><?php echo date('F Y'); ?></div>
            </div>
            
            <div class="card">
                <div class="card-icon purple">💰</div>
                <h3>This Month Net Pay</h3>
                <div class="card-value currency">&#8369;<?php echo number_format($total_salary, 2); ?></div>
                <div class="card-subtitle">Total disbursed</div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Recent Payroll Uploads</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Payroll Date</th>
                        <th>Total Earnings</th>
                        <th>Total Deductions</th>
                        <th>Net Pay</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT ps.*, e.employee_id, e.first_name, e.last_name, e.department 
                           FROM payroll_summary ps 
                           JOIN employees e ON ps.employee_id = e.id 
                           ORDER BY ps.upload_date DESC LIMIT 10";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo date('j F Y', mktime(0, 0, 0, $row['month'], $row['day'] ?? 1, $row['year'])); ?></td>
                            <td class="currency">&#8369;<?php echo number_format($row['total_earnings'], 2); ?></td>
                            <td class="currency" style="color:#dc2626;">&#8369;<?php echo number_format($row['total_deductions'], 2); ?></td>
                            <td class="currency"><strong style="color:#059669;">&#8369;<?php echo number_format($row['net_pay'], 2); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($row['upload_date'])); ?></td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No payroll records yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script src="../assets/js/script.js"></script>
</body>
</html>
