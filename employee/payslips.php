<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Employee.php';
require_once $base_path . '/includes/Payroll.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || (!$auth->isEmployee() && !$auth->isAdmin())) {
    header('Location: ../login.php');
    exit;
}

$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error_message = 'You are not authorized to view that payslip.';
    } elseif ($_GET['error'] === 'not_found') {
        $error_message = 'Payslip not found.';
    }
}

$employee_class = new Employee($conn);
$payroll = new Payroll($conn);
$employee = $employee_class->getEmployeeByUserId($_SESSION['user_id']);

if (!$employee) {
    echo "Employee record not found";
    exit;
}

$filter = isset($_GET['filter']);
$month = $filter && isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
$year = $filter && isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$day = $filter && isset($_GET['day']) && $_GET['day'] !== '' ? intval($_GET['day']) : null;

if ($filter && $month !== null && $year !== null) {
    $payslips = $payroll->getEmployeePayslips($employee['id'], $month, $year, $day);
} else {
    $payslips = $payroll->getEmployeePayslips($employee['id']);
}

$current_page = 'payslips';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/employee_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>My Payslips</h1>
                <p class="subtitle">View and download your payslip records</p>
            </div>
        </div>

        <div class="search-filter" style="margin-bottom: 20px;">
            <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="filter" value="1">

                <div class="form-group" style="margin-bottom: 0;">
                    <select name="month" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;">
                            <option value="" <?php echo $month === null ? 'selected' : ''; ?>>All months</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i === $month ? 'selected' : ''; ?>>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <select name="day" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;">
                        <option value="" <?php echo $day === null ? 'selected' : ''; ?>>All days</option>
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === $day ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <select name="year" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;">
                            <option value="" <?php echo $year === null ? 'selected' : ''; ?>>All years</option>
                            <?php for ($i = 2024; $i <= 2030; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i === $year ? 'selected' : ''; ?>>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="payslips.php" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Reset</a>
            </form>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Payroll Date</th>
                        <th>Total Earnings</th>
                        <th>Adjustments</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Date Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payslips)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No payslips available
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payslips as $slip): ?>
                            <tr>
                                <td><strong><?php echo date('j F Y', mktime(0, 0, 0, $slip['month'], $slip['day'] ?? 1, $slip['year'])); ?></strong></td>
                                <td class="currency">&#8369;<?php echo number_format($slip['total_earnings'], 2); ?></td>
                                <td class="currency">&#8369;<?php echo number_format($slip['total_adjustments'], 2); ?></td>
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
