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

$payroll_id = $_GET['id'] ?? 0;
$payroll = new Payroll($conn);
$slip = $payroll->getPayroll($payroll_id);

if (!$slip) {
    echo "Payroll record not found";
    exit;
}

$current_page = 'manage';

$earnings = $slip['earnings'] ?? [];
$adjustments = $slip['adjustments'] ?? [];
$deductions = $slip['deductions'] ?? [];

$calculateAmount = function($hrs, $amt, $multiplier = 1.0) {
    if ($amt > 0) {
        return $amt;
    }
    if ($hrs > 0) {
        return round($hrs * (570 / 8) * $multiplier, 2);
    }
    return 0;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payroll - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .payslip-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            font-family: Arial, sans-serif;
        }
        .payslip-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px 20px;
            border-bottom: 2px solid #333;
            background: #f8f9fa;
        }
        .company-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .company-logo img {
            height: 50px;
        }
        .company-name {
            font-weight: bold;
            font-size: 18px;
        }
        .payroll-period {
            text-align: right;
            font-size: 12px;
        }
        .payroll-period .period-title {
            font-weight: bold;
            font-size: 14px;
        }
        .employee-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px 20px;
            border-bottom: 2px solid #333;
            font-size: 12px;
        }
        .employee-details .row {
            display: flex;
            gap: 10px;
        }
        .employee-details .label {
            font-weight: bold;
            min-width: 100px;
        }
        .employee-details .value {
            flex: 1;
        }
        .payslip-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .earnings-section {
            border-right: 2px solid #333;
        }
        .section-header {
            background: #333;
            color: white;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 13px;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .section-table th {
            background: #e9ecef;
            padding: 5px 8px;
            text-align: center;
            border-bottom: 1px solid #333;
            font-size: 10px;
        }
        .section-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #ddd;
        }
        .section-table td.label {
            text-align: left;
        }
        .section-table td.hours,
        .section-table td.amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .section-table tr.total-row {
            background: #333;
            color: white;
            font-weight: bold;
        }
        .section-table tr.total-row td {
            padding: 8px;
            border: none;
        }
        .net-pay-row {
            background: #059669;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
        }
        .net-pay-row .amount {
            font-size: 24px;
        }
        @media print {
            .main-content { padding: 0; }
            .page-header, nav { display: none !important; }
            .payslip-container { border: 1px solid #000; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Payroll Details</h1>
                <p class="subtitle"><?php echo htmlspecialchars($slip['first_name'] . ' ' . $slip['last_name']); ?> — <?php echo date('j F Y', mktime(0, 0, 0, $slip['month'], $slip['day'] ?? 1, $slip['year'])); ?></p>
            </div>
            <a href="manage_payroll.php?month=<?php echo $slip['month']; ?>&year=<?php echo $slip['year']; ?>" class="btn btn-secondary">Back</a>
        </div>
        
        <div class="payslip-container">
            <!-- Header -->
            <div class="payslip-header">
                <div class="company-logo">
                    <img src="../assets/images/natcorp-logo.png" alt="NCG Logo" onerror="this.style.display='none'">
                    <div>
                       
                        <div style="font-size:11px;">NATCORP CAREER GROWTH AND MANPOWER SERVICES</div>
                        <div style="font-size:10px;color:#666;">Unit C, 2nd FL Cedilla Bldg Magsay Road, Brgy. Parang, Cainta, Rizal</div>
                    </div>
                </div>
                <div class="payroll-period">
                    <div>Payroll Date</div>
                    <div class="period-title"><?php echo strtoupper(date('j F Y', mktime(0, 0, 0, $slip['month'], $slip['day'] ?? 1, $slip['year']))); ?></div>
                    
            
                </div>
            </div>
            
            <!-- Employee Details -->
            <div class="employee-details">
                <div>
                    <div class="row"><span class="label">Employee ID:</span><span class="value"><?php echo htmlspecialchars($slip['emp_id']); ?></span></div>
                    <div class="row"><span class="label">Name:</span><span class="value"><?php echo htmlspecialchars($slip['first_name'] . ' ' . $slip['last_name']); ?></span></div>
                    <div class="row"><span class="label">Position:</span><span class="value"><?php echo htmlspecialchars($slip['position'] ?: 'N/A'); ?></span></div>
                    <div class="row"><span class="label">Department:</span><span class="value"><?php echo htmlspecialchars($slip['department'] ?: 'N/A'); ?></span></div>
                    <div class="row"><span class="label">Client Company:</span><span class="value"><?php echo htmlspecialchars($slip['client_company'] ?? 'N/A'); ?></span></div>
                </div>
                <div>
                    <div class="row"><span class="label">Date Hired:</span><span class="value"><?php echo $slip['date_of_joining'] ? date('d-M-y', strtotime($slip['date_of_joining'])) : 'N/A'; ?></span></div>
                    <div class="row"><span class="label">SSS No.:</span><span class="value">-</span></div>
                    <div class="row"><span class="label">PHIC No.:</span><span class="value">-</span></div>
                    <div class="row"><span class="label">Pagibig No.:</span><span class="value">-</span></div>
                </div>
            </div>
            
            <!-- Two-Column Layout: Earnings & Deductions -->
            <div class="payslip-body">
                <!-- EARNINGS Column -->
                <div class="earnings-section">
                    <div class="section-header">EARNINGS</div>
                    <table class="section-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;width:50%;">Description</th>
                                <th style="width:25%;">HOURS</th>
                                <th style="width:25%;">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $earning_items = [
                                ['reg_days', 'Regular Work Days', 1.0],
                                ['rot', 'Req. Overtime', 1.25],
                                ['nd', 'Req. Night Diff', 0.1],
                                ['cnw', 'Company No Work', 1.0],
                                ['cnw_ot', 'Company No Work O.T', 1.25],
                                ['cnd_nd', 'Company No Work N.D', 0.1],
                                ['rd', 'Rest Day OT', 1.3],
                                ['rd_exc', 'RD Excess', 1.69],
                                ['rd_nd', 'RD Night Diff', 1.3],
                                ['shd', 'Special Holiday', 0.5],
                                ['shd_ot', 'SH Excess', 1.0],
                                ['shd_nd', 'SH Night Diff', 1.3],
                                ['shd_rd', 'Sunday + Special Holiday', 1.69],
                                ['shd_rd_ot', 'Sunday + SH Excess', 1.69],
                                ['shd_rd_nd', 'Sunday + SH Night Diff', 1.69],
                                ['rd_ndot', 'RD SH Night Diff. OT', 1.69],
                                ['lh', 'Legal Holiday', 1.0],
                                ['lh_exc', 'LH Excess', 1.0],
                                ['lh_unworked', 'Legal Holiday No Show', 1.0],
                            ];
                            
                            $total_earnings = 0;
                            foreach ($earning_items as $item) {
                                $field = $item[0];
                                $label = $item[1];
                                $multiplier = $item[2] ?? 1.0;
                                $hrs = (float)($earnings[$field . '_hrs'] ?? 0);
                                $amt = (float)($earnings[$field . '_amt'] ?? 0);
                                $displayAmt = $calculateAmount($hrs, $amt, $multiplier);
                                $total_earnings += $displayAmt;
                            ?>
                            <tr>
                                <td class="label"><?php echo $label; ?></td>
                                <td class="hours"><?php echo $hrs > 0 ? number_format($hrs, 2) : '-'; ?></td>
                                <td class="amount"><?php echo $displayAmt > 0 ? number_format($displayAmt, 2) : '-'; ?></td>
                            </tr>
                            <?php } ?>
                            
                            <!-- Adjustments/Incentives -->
                            <?php
                            $adj_items = [
                                ['assy_incentive', 'ASSY INCENTIVE'],
                                ['perfect_attendance', 'PERFECT ATTENDANCE'],
                                ['qa_incentive', 'QA INCENTIVE'],
                                ['special_process_allowance', 'SPECIAL PROCESS ALLOWANCE'],
                                ['superprocess', 'SUPERPROCESS'],
                                ['wcd_kaizen', 'WCD KAIZEN'],
                                ['mt_incentive', 'MT INCENTIVE'],
                                ['skt_incentive', 'SKT INCENTIVE'],
                                ['contribution_refund', 'CONTRIBUTION REFUND'],
                                ['hai_v', 'HAI V'],
                                ['salary_complaint', 'SALARY COMPLAINT ADJUSTMENT'],
                            ];
                            
                            foreach ($adj_items as $item) {
                                $field = $item[0];
                                $label = $item[1];
                                $amt = (float)($adjustments[$field] ?? 0);
                                $total_earnings += $amt;
                            ?>
                            <tr>
                                <td class="label"><?php echo $label; ?></td>
                                <td class="hours">-</td>
                                <td class="amount"><?php echo $amt > 0 ? number_format($amt, 2) : '-'; ?></td>
                            </tr>
                            <?php } ?>
                            
                            <tr class="total-row">
                                <td colspan="2">TOTAL EARNINGS</td>
                                <td class="amount"><?php echo number_format($total_earnings, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- DEDUCTIONS Column -->
                <div class="deductions-section">
                    <div class="section-header" style="background:#dc2626;">DEDUCTIONS</div>
                    <table class="section-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;width:70%;">Description</th>
                                <th style="width:30%;">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ded_items = [
                                ['sss_sl', 'SSS SL'],
                                ['hdmf_mpl', 'HDMF MPL'],
                                ['hdmf_cl', 'HDMF CL'],
                                ['hmo', 'HMO'],
                                ['uniform_upon_deployment', 'UNIFORM UPON DEPLOYMENT'],
                                ['uniform_atd', 'UNIFORM'],
                                ['housing', 'HOUSING ASSISTANCE'],
                                ['medifund_loan', 'MEDIFUND LOAN'],
                                ['negats_payroll', 'NEGATIVE PAYROLL LAST CUT OFF'],
                                ['canteen_chit', 'CANTEEN CHIT'],
                                ['late_undertime', 'LATE/UNDERTIME'],
                                ['id_deduction', 'ID'],
                                ['shoes', 'SHOES'],
                                ['cash_advance', 'CASH ADVANCE'],
                                ['hmo_availment', 'HMO AVAILMENT'],
                            ];
                            
                            $total_deductions = 0;
                            foreach ($ded_items as $item) {
                                $field = $item[0];
                                $label = $item[1];
                                
                                // Check both deductions and adjustments for late_undertime
                                if ($field === 'late_undertime') {
                                    $amt = (float)($adjustments[$field] ?? $deductions[$field] ?? 0);
                                } else {
                                    $amt = (float)($deductions[$field] ?? 0);
                                }
                                $total_deductions += $amt;
                            ?>
                            <tr>
                                <td class="label"><?php echo $label; ?></td>
                                <td class="amount"><?php echo $amt > 0 ? number_format($amt, 2) : '-'; ?></td>
                            </tr>
                            <?php } ?>
                            
                            <!-- Empty rows for spacing -->
                            <?php for($i = 0; $i < 10; $i++): ?>
                            <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                            <?php endfor; ?>
                            
                            <tr class="total-row" style="background:#dc2626;">
                                <td>TOTAL DEDUCTIONS</td>
                                <td class="amount"><?php echo number_format($total_deductions, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- NET PAY -->
            <div class="net-pay-row">
                <span>NET PAY &gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;&gt;</span>
                <span class="amount">&#8369;<?php echo number_format($slip['net_pay'], 2); ?></span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #64748b; font-size: 12px;">
            <p>This is an electronically generated payslip. No signature required.</p>
            <p>Generated on: <?php echo date('M d, Y H:i'); ?></p>
        </div>
    </div>
<script src="../assets/js/script.js"></script>
</body>
</html>
