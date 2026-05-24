<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$current_month = (int) date('n');
$current_year = (int) date('Y');

$active_employees = $conn->query("SELECT COUNT(*) AS count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
$inactive_employees = $conn->query("SELECT COUNT(*) AS count FROM employees WHERE status = 'inactive'")->fetch_assoc()['count'];
$new_hires_this_month = $conn->query("SELECT COUNT(*) AS count FROM employees WHERE MONTH(date_of_joining) = {$current_month} AND YEAR(date_of_joining) = {$current_year}")->fetch_assoc()['count'];
$deactivated_accounts = $inactive_employees;

$summaryTotals = $conn->query("SELECT IFNULL(SUM(total_earnings), 0) AS total_earnings, IFNULL(SUM(total_deductions), 0) AS total_deductions, IFNULL(SUM(net_pay), 0) AS total_net_pay, IFNULL(SUM(gross_pay), 0) AS total_gross_pay, COUNT(*) AS total_records FROM payroll_summary")->fetch_assoc();
$total_records = (int) $summaryTotals['total_records'];
$total_earnings = (float) $summaryTotals['total_earnings'];
$total_deductions = (float) $summaryTotals['total_deductions'];
$total_net_pay = (float) $summaryTotals['total_net_pay'];
$total_gross_pay = (float) $summaryTotals['total_gross_pay'];
$gross_vs_net_ratio = $total_gross_pay > 0 ? ($total_net_pay / $total_gross_pay) * 100 : 0;

$current_month_payslips = $conn->query("SELECT COUNT(*) AS count FROM payroll_summary WHERE month = {$current_month} AND year = {$current_year}")->fetch_assoc()['count'];
$missing_payslips = $conn->query("SELECT COUNT(DISTINCT e.id) AS count FROM employees e LEFT JOIN payroll_summary ps ON e.id = ps.employee_id AND ps.month = {$current_month} AND ps.year = {$current_year} WHERE e.status = 'active' AND ps.id IS NULL")->fetch_assoc()['count'];
$last_upload_date = $conn->query("SELECT MAX(upload_date) AS last_upload FROM payroll_summary")->fetch_assoc()['last_upload'];

$monthly_summary = $conn->query("SELECT year, month, COUNT(*) AS record_count, IFNULL(SUM(total_earnings), 0) AS total_earnings, IFNULL(SUM(total_deductions), 0) AS total_deductions, IFNULL(SUM(net_pay), 0) AS total_net FROM payroll_summary GROUP BY year, month ORDER BY year DESC, month DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$yearly_trend = $conn->query("SELECT year, IFNULL(SUM(total_earnings), 0) AS total_earnings, IFNULL(SUM(total_deductions), 0) AS total_deductions, IFNULL(SUM(net_pay), 0) AS total_net FROM payroll_summary GROUP BY year ORDER BY year DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_uploads = $conn->query("SELECT ps.upload_date, ps.month, ps.year, ps.gross_pay, ps.net_pay, e.employee_id, CONCAT(e.first_name, ' ', e.last_name) AS employee_name FROM payroll_summary ps JOIN employees e ON ps.employee_id = e.id ORDER BY ps.upload_date DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$recent_audit_logs = $conn->query("SELECT al.created_at, al.action, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$current_page = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Reports</h1>
                <p class="subtitle">Payroll summary, headcount, payslip status, trends, and audit activity</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon blue">📊</div>
                <h3>Total Payroll Records</h3>
                <div class="card-value"><?php echo $total_records; ?></div>
            </div>

            <div class="card">
                <div class="card-icon green">💼</div>
                <h3>Total Earnings</h3>
                <div class="card-value currency">₱<?php echo number_format($total_earnings, 2); ?></div>
            </div>

            <div class="card">
                <div class="card-icon purple">💸</div>
                <h3>Total Net Pay</h3>
                <div class="card-value currency">₱<?php echo number_format($total_net_pay, 2); ?></div>
            </div>

            <div class="card">
                <div class="card-icon orange">📉</div>
                <h3>Total Deductions</h3>
                <div class="card-value currency">₱<?php echo number_format($total_deductions, 2); ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon blue">👥</div>
                <h3>Active Employees</h3>
                <div class="card-value"><?php echo $active_employees; ?></div>
            </div>

            <div class="card">
                <div class="card-icon gray">🚫</div>
                <h3>Inactive Employees</h3>
                <div class="card-value"><?php echo $inactive_employees; ?></div>
            </div>

            <div class="card">
                <div class="card-icon teal">🆕</div>
                <h3>New Hires This Month</h3>
                <div class="card-value"><?php echo $new_hires_this_month; ?></div>
            </div>

            <div class="card">
                <div class="card-icon red">🔒</div>
                <h3>Deactivated Accounts</h3>
                <div class="card-value"><?php echo $deactivated_accounts; ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-icon orange">✅</div>
                <h3>This Month Payslips</h3>
                <div class="card-value"><?php echo $current_month_payslips; ?></div>
                <div class="card-subtitle"><?php echo date('F Y'); ?></div>
            </div>

            <div class="card">
                <div class="card-icon red">⚠️</div>
                <h3>Missing Payslips</h3>
                <div class="card-value"><?php echo $missing_payslips; ?></div>
                <div class="card-subtitle">Active employees pending</div>
            </div>

            <div class="card">
                <div class="card-icon purple">⏱️</div>
                <h3>Last Upload</h3>
                <div class="card-value"><?php echo $last_upload_date ? date('M d, Y h:i A', strtotime($last_upload_date)) : 'No uploads yet'; ?></div>
            </div>

            <div class="card">
                <div class="card-icon green">📈</div>
                <h3>Gross vs Net Ratio</h3>
                <div class="card-value"><?php echo number_format($gross_vs_net_ratio, 1); ?>%</div>
            </div>
        </div>

        <div class="table-container">
            <h2>Monthly Payroll Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Payslips</th>
                        <th>Total Earnings</th>
                        <th>Total Deductions</th>
                        <th>Total Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($monthly_summary)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No payroll data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($monthly_summary as $row): ?>
                            <tr>
                                <td><?php echo date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])); ?></td>
                                <td><?php echo $row['record_count']; ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_earnings'], 2); ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_net'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h2>Year-over-Year Payroll Growth</h2>
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Total Earnings</th>
                        <th>Total Deductions</th>
                        <th>Total Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($yearly_trend)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">No yearly trend data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($yearly_trend as $row): ?>
                            <tr>
                                <td><?php echo $row['year']; ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_earnings'], 2); ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                                <td class="currency">₱<?php echo number_format($row['total_net'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h2>Recent Payroll Uploads</h2>
            <table>
                <thead>
                    <tr>
                        <th>Upload Date</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Gross Pay</th>
                        <th>Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_uploads)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No recent uploads found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_uploads as $upload): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($upload['upload_date'])); ?></td>
                                <td><?php echo htmlspecialchars($upload['employee_id'] . ' — ' . $upload['employee_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($upload['upload_date'])); ?></td>
                                <td class="currency">₱<?php echo number_format($upload['gross_pay'], 2); ?></td>
                                <td class="currency">₱<?php echo number_format($upload['net_pay'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h2>Audit & Activity Logs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_audit_logs)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: var(--text-muted);">No audit logs available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_audit_logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?: 'System'); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
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
