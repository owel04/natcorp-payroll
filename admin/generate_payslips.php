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
$month = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
$year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$day = isset($_GET['day']) && $_GET['day'] !== '' ? intval($_GET['day']) : null;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$payrolls = $payroll->getAllPayrolls($month, $year, $day, $search_term);

$current_page = 'payslips';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payslips - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>View Payslips</h1>
                <p class="subtitle"><?php echo count($payrolls); ?> payslip<?php echo count($payrolls) != 1 ? 's' : ''; ?> available for <?php echo ($month !== null && $year !== null) ? date('j F Y', mktime(0, 0, 0, $month, $day ?? 1, $year)) : 'all dates'; ?></p>
            </div>
        </div>
        
        <!-- Filter by Month/Year -->
        <div class="search-filter" style="margin-bottom: 20px;">
            <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 220px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by Employee ID or Name" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="month" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;">
                        <option value="" <?php echo $month === null ? 'selected' : ''; ?>>All months</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === $month ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
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
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                                <th>Employee Name</th>
                        <th>Payroll Date</th>
                        <th>Net Salary</th>
                        <th>Date Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No payslips available for this period</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                <td><?php echo date('j F Y', mktime(0, 0, 0, $p['month'], $p['day'] ?? 1, $p['year'])); ?></td>
                                <td class="currency">₱<?php echo number_format($p['net_pay'] ?? 0, 2); ?></td>
                                <td><?php echo $p['upload_date'] ? date('M d, Y', strtotime($p['upload_date'])) : '-'; ?></td>
                                <td>
                                    <a href="view_payroll.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-small">View Payslip</a>
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
