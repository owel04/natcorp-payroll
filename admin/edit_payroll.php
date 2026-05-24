<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/Payroll.php';
require_once $base_path . '/includes/Employee.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$payroll = new Payroll($conn);
$employee_obj = new Employee($conn);

$employees_ids = isset($_GET['employees']) ? explode(',', $_GET['employees']) : [];
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$day = intval($_GET['day'] ?? 1);

$message = '';
$error = '';

// Helper function to get payroll field value
function getPayrollValue($existing_payroll, $emp_id, $section, $field, $default = 0) {
    if (isset($existing_payroll[$emp_id][$section][$field])) {
        return $existing_payroll[$emp_id][$section][$field];
    }
    return $default;
}

// Helper function to return amount value or blank when zero
function getPayrollAmountValue($existing_payroll, $emp_id, $section, $field) {
    $value = getPayrollValue($existing_payroll, $emp_id, $section, $field);
    return ((float)$value !== 0.0) ? $value : '';
}

// Fetch selected employees details
$employees = [];
if (!empty($employees_ids)) {
    $placeholders = implode(',', array_fill(0, count($employees_ids), '?'));
    $sql = "SELECT id, employee_id, first_name, last_name, department, position FROM employees WHERE id IN ($placeholders) ORDER BY first_name, last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($employees_ids)), ...$employees_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payroll_entries = $_POST['payroll'] ?? [];
    $saved_count = 0;
    $errors = [];

    foreach ($payroll_entries as $emp_id => $data) {
        if (empty($data['employee_id'])) continue;
        $existingPayrollId = $payroll->getExistingPayrollId((int)$emp_id, $day, $month, $year);

        $payroll_data = [
            'employee_id' => (int)$emp_id,
            'day' => $day,
            'month' => (int)$month,
            'year' => (int)$year,
            'employee_no' => $data['employee_no'] ?? '',
            
            // Earnings
            'reg_days_hrs' => (float)($data['reg_days_hrs'] ?? 0),
            'reg_days_amt' => (float)($data['reg_days_amt'] ?? 0),
            'lh_unworked_hrs' => (float)($data['lh_unworked_hrs'] ?? 0),
            'lh_unworked_amt' => (float)($data['lh_unworked_amt'] ?? 0),
            'rot_hrs' => (float)($data['rot_hrs'] ?? 0),
            'rot_amt' => (float)($data['rot_amt'] ?? 0),
            'nd_hrs' => (float)($data['nd_hrs'] ?? 0),
            'nd_amt' => (float)($data['nd_amt'] ?? 0),
            'rd_hrs' => (float)($data['rd_hrs'] ?? 0),
            'rd_amt' => (float)($data['rd_amt'] ?? 0),
            'rd_exc_hrs' => (float)($data['rd_exc_hrs'] ?? 0),
            'rd_exc_amt' => (float)($data['rd_exc_amt'] ?? 0),
            'rd_nd_hrs' => (float)($data['rd_nd_hrs'] ?? 0),
            'rd_nd_amt' => (float)($data['rd_nd_amt'] ?? 0),
            'rd_ndot_hrs' => (float)($data['rd_ndot_hrs'] ?? 0),
            'rd_ndot_amt' => (float)($data['rd_ndot_amt'] ?? 0),
            'lh_rd_hrs' => (float)($data['lh_rd_hrs'] ?? 0),
            'lh_rd_amt' => (float)($data['lh_rd_amt'] ?? 0),
            'lh_rd_exc_hrs' => (float)($data['lh_rd_exc_hrs'] ?? 0),
            'lh_rd_exc_amt' => (float)($data['lh_rd_exc_amt'] ?? 0),
            'lh_rd_nd_hrs' => (float)($data['lh_rd_nd_hrs'] ?? 0),
            'lh_rd_nd_amt' => (float)($data['lh_rd_nd_amt'] ?? 0),
            'lh_rd_ndot_hrs' => (float)($data['lh_rd_ndot_hrs'] ?? 0),
            'lh_rd_ndot_amt' => (float)($data['lh_rd_ndot_amt'] ?? 0),
            'lh_hrs' => (float)($data['lh_hrs'] ?? 0),
            'lh_amt' => (float)($data['lh_amt'] ?? 0),
            'lh_exc_hrs' => (float)($data['lh_exc_hrs'] ?? 0),
            'lh_exc_amt' => (float)($data['lh_exc_amt'] ?? 0),
            'lh_nd_hrs' => (float)($data['lh_nd_hrs'] ?? 0),
            'lh_nd_amt' => (float)($data['lh_nd_amt'] ?? 0),
            'lh_ndot_hrs' => (float)($data['lh_ndot_hrs'] ?? 0),
            'lh_ndot_amt' => (float)($data['lh_ndot_amt'] ?? 0),
            'shd_hrs' => (float)($data['shd_hrs'] ?? 0),
            'shd_amt' => (float)($data['shd_amt'] ?? 0),
            'shd_ot_hrs' => (float)($data['shd_ot_hrs'] ?? 0),
            'shd_ot_amt' => (float)($data['shd_ot_amt'] ?? 0),
            'shd_nd_hrs' => (float)($data['shd_nd_hrs'] ?? 0),
            'shd_nd_amt' => (float)($data['shd_nd_amt'] ?? 0),
            'shd_rd_hrs' => (float)($data['shd_rd_hrs'] ?? 0),
            'shd_rd_amt' => (float)($data['shd_rd_amt'] ?? 0),
            'shd_rd_ot_hrs' => (float)($data['shd_rd_ot_hrs'] ?? 0),
            'shd_rd_ot_amt' => (float)($data['shd_rd_ot_amt'] ?? 0),
            'shd_rd_nd_hrs' => (float)($data['shd_rd_nd_hrs'] ?? 0),
            'shd_rd_nd_amt' => (float)($data['shd_rd_nd_amt'] ?? 0),
            'cnw_hrs' => (float)($data['cnw_hrs'] ?? 0),
            'cnw_amt' => (float)($data['cnw_amt'] ?? 0),
            'cnw_ot_hrs' => (float)($data['cnw_ot_hrs'] ?? 0),
            'cnw_ot_amt' => (float)($data['cnw_ot_amt'] ?? 0),
            'cnd_nd_hrs' => (float)($data['cnd_nd_hrs'] ?? 0),
            'cnd_nd_amt' => (float)($data['cnd_nd_amt'] ?? 0),
            
            // Adjustments
            'late_undertime' => (float)($data['late_undertime'] ?? 0),
            'assy_incentive' => (float)($data['assy_incentive'] ?? 0),
            'perfect_attendance' => (float)($data['perfect_attendance'] ?? 0),
            'qa_incentive' => (float)($data['qa_incentive'] ?? 0),
            'special_process_allowance' => (float)($data['special_process_allowance'] ?? 0),
            'superprocess' => (float)($data['superprocess'] ?? 0),
            'wcd_kaizen' => (float)($data['wcd_kaizen'] ?? 0),
            'mt_incentive' => (float)($data['mt_incentive'] ?? 0),
            'skt_incentive' => (float)($data['skt_incentive'] ?? 0),
            'contribution_refund' => (float)($data['contribution_refund'] ?? 0),
            'salary_complaint' => (float)($data['salary_complaint'] ?? 0),
            'hai_v' => (float)($data['hai_v'] ?? 0),
            'total_adjustment' => (float)($data['total_adjustment'] ?? 0),
            
            // Deductions
            'sss_sl' => (float)($data['sss_sl'] ?? 0),
            'sss_cl' => (float)($data['sss_cl'] ?? 0),
            'hdmf_mpl' => (float)($data['hdmf_mpl'] ?? 0),
            'hdmf_cl' => (float)($data['hdmf_cl'] ?? 0),
            'hmo' => (float)($data['hmo'] ?? 0),
            'uniform_upon_deployment' => (float)($data['uniform_upon_deployment'] ?? 0),
            'uniform_atd' => (float)($data['uniform_atd'] ?? 0),
            'housing' => (float)($data['housing'] ?? 0),
            'medifund_loan' => (float)($data['medifund_loan'] ?? 0),
            'negats_payroll' => (float)($data['negats_payroll'] ?? 0),
            'canteen_chit' => (float)($data['canteen_chit'] ?? 0),
            'shoes' => (float)($data['shoes'] ?? 0),
            'id_deduction' => (float)($data['id_deduction'] ?? 0),
            'cash_advance' => (float)($data['cash_advance'] ?? 0),
            'hmo_availment' => (float)($data['hmo_availment'] ?? 0),
        ];

        $result = $payroll->addPayrollData($payroll_data);
        if ($result['success']) {
            $saved_count++;
            $employee = $employee_obj->getEmployee((int)$emp_id);
            $employee_code = $employee['employee_id'] ?? $emp_id;
            $actionType = $existingPayrollId ? 'Updated' : 'Added';
            $auth->logAction("{$actionType} payroll for employee {$employee_code} on " . date('j F Y', mktime(0, 0, 0, $month, $day, $year)));
        } else {
            $errors[] = "Employee {$data['employee_id']}: " . $result['message'];
        }
    }

    if ($saved_count > 0) {
        $message = "✓ Successfully saved payroll for $saved_count employee(s)";
    }
    if (!empty($errors)) {
        $error = "Some entries failed: " . implode('; ', $errors);
    }
}

// Fetch existing payroll data for selected employees (if any)
$existing_payroll = [];
if (!empty($employees)) {
    foreach ($employees as $emp) {
        $emp_payroll_id = $payroll->getExistingPayrollId($emp['id'], $day, $month, $year);
        if ($emp_payroll_id) {
            $emp_data = $payroll->getPayrollDetails($emp_payroll_id);
            if ($emp_data) {
                $existing_payroll[$emp['id']] = [
                    'earnings' => $emp_data['earnings'] ?? [],
                    'adjustments' => $emp_data['adjustments'] ?? [],
                    'deductions' => $emp_data['deductions'] ?? []
                ];
            }
        }
    }
}

$current_page = 'manage';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payroll - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .payroll-forms {
            max-width: 100%;
        }
        
        .employee-payroll-form {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            page-break-inside: avoid;
        }
        
        .form-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 20px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #d1d5db;
        }
        
        .form-section-title:first-of-type {
            margin-top: 0;
        }
        
        .daily-rate-section {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            align-items: end;
        }
        
        .earnings-grid, .adjustments-grid, .deductions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .form-pair {
            display: grid;
            grid-template-columns: 0.7fr 1fr;
            gap: 10px;
            align-items: flex-start;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #f0f8ff;
        }
        
        .form-group input[readonly] {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .form-group small {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-back {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-back:hover {
            background: #e5e7eb;
        }
        
        .employee-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #d1d5db;
        }
        
        .employee-info h3 {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
            color: #111827;
        }
        
        .employee-info p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .payroll-status {
            font-size: 12px;
            padding: 8px 14px;
            border-radius: 5px;
            font-weight: 600;
        }
        
        .status-new {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-edit {
            background: #fef3c7;
            color: #92400e;
        }
        
        .calc-info {
            display: block;
            font-size: 11px;
            color: #6b7280;
            margin-top: 6px;
            font-weight: 500;
            min-height: 18px;
            transition: color 0.2s ease;
        }

        .calc-info.calc-active {
            color: #065f46;
        }

        .amount-input.calc-highlight {
            background-color: #ecfdf5;
            border-color: #34d399;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Payroll</h1>
                <p class="subtitle"><?php echo date('j F Y', mktime(0, 0, 0, $month, $day, $year)); ?></p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($employees)): ?>
            <div class="alert alert-info">
                No employees selected. <a href="manage_payroll.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>">Go back to select employees</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="daily-rate-section" style="max-width: 420px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label>Daily Rate</label>
                        <input type="number" id="globalDailyRate" class="daily-rate" name="daily_rate" step="0.01" value="570" placeholder="570">
                        <small>Base: 570 | Hourly: 71.25 (Changes apply to all employee forms)</small>
                    </div>
                </div>
                <div class="payroll-forms">
                    <?php foreach ($employees as $emp): ?>
                        <div class="employee-payroll-form">
                            <!-- Employee Header -->
                            <div class="employee-header">
                                <div class="employee-info">
                                    <h3><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($emp['employee_id']); ?> | <?php echo htmlspecialchars($emp['department']); ?> | <?php echo htmlspecialchars($emp['position']); ?></p>
                                </div>
                                <div class="payroll-status <?php echo isset($existing_payroll[$emp['id']]) ? 'status-edit' : 'status-new'; ?>">
                                    <?php echo isset($existing_payroll[$emp['id']]) ? '✎ Editing' : '+ New Entry'; ?>
                                </div>
                            </div>
                            
                            <input type="hidden" name="payroll[<?php echo $emp['id']; ?>][employee_id]" value="<?php echo $emp['id']; ?>">
                            
                            <input type="hidden" class="daily-rate" name="payroll[<?php echo $emp['id']; ?>][daily_rate]" value="570" data-emp-id="<?php echo $emp['id']; ?>">
                            <div style="font-size: 12px; color: #6b7280; padding: 10px; background: #f0f9ff; border-radius: 4px; border-left: 3px solid #0ea5e9; margin-bottom: 20px;">
                                <strong>Auto-Calculate:</strong> Enter hours to auto-calculate amounts. Use the global daily rate above to update all employee forms.
                            </div>
                            
                            <!-- EARNINGS SECTION -->
                            <div class="form-section-title">Earnings (Hours & Amount)</div>
                            
                            <div class="earnings-grid">
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Regular Work Days (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][reg_days_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'reg_days_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.0" data-amount-field="reg_days_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][reg_days_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'reg_days_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Reg. Overtime (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][rot_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'rot_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.25" data-amount-field="rot_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][rot_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'rot_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Reg. Night Diff (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][nd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'nd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="0.1" data-amount-field="nd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][nd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'nd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Company No Work (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][cnw_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'cnw_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.0" data-amount-field="cnw_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][cnw_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'cnw_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Company No Work OT (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][cnw_ot_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'cnw_ot_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.25" data-amount-field="cnw_ot_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][cnw_ot_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'cnw_ot_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Company No Work ND (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][cnd_nd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'cnd_nd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="0.1" data-amount-field="cnd_nd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][cnd_nd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'cnd_nd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Rest Day (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][rd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'rd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.3" data-amount-field="rd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][rd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'rd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Rest Day OT (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][rd_exc_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'rd_exc_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.69" data-amount-field="rd_exc_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][rd_exc_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'rd_exc_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Rest Day ND (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][rd_nd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'rd_nd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.3" data-amount-field="rd_nd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][rd_nd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'rd_nd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Rest Day ND OT (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][rd_ndot_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'rd_ndot_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.69" data-amount-field="rd_ndot_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][rd_ndot_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'rd_ndot_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Legal Holiday (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][lh_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'lh_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.0" data-amount-field="lh_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][lh_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'lh_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Legal Holiday Excess (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][lh_exc_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'lh_exc_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.0" data-amount-field="lh_exc_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][lh_exc_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'lh_exc_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Legal Holiday ND (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][lh_nd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'lh_nd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.3" data-amount-field="lh_nd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][lh_nd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'lh_nd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>LH+RD (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][lh_rd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'lh_rd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="1.69" data-amount-field="lh_rd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][lh_rd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'lh_rd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                                
                                <div class="form-pair">
                                    <div class="form-group">
                                        <label>Special Holiday (Hours)</label>
                                        <input type="number" class="hours-input" name="payroll[<?php echo $emp['id']; ?>][shd_hrs]" step="0.01" placeholder="0.00" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'earnings', 'shd_hrs') ?: ''; ?>" data-emp-id="<?php echo $emp['id']; ?>" data-multiplier="0.5" data-amount-field="shd_amt">
                                    </div>
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" class="amount-input" placeholder="Auto-calculated" name="payroll[<?php echo $emp['id']; ?>][shd_amt]" step="0.01" value="<?php echo getPayrollAmountValue($existing_payroll, $emp['id'], 'earnings', 'shd_amt'); ?>" readonly>
                                        <span class="calc-info">Auto-calculated</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ADJUSTMENTS SECTION -->
                            <div class="form-section-title">Adjustments & Incentives</div>
                            
                            <div class="adjustments-grid">
                                <div class="form-group">
                                    <label>Late/Undertime</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][late_undertime]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'late_undertime'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>ASSY INCENTIVE</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][assy_incentive]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'assy_incentive'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Perfect Attendance</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][perfect_attendance]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'perfect_attendance'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>QA INCENTIVE</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][qa_incentive]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'qa_incentive'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Process Allowance</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][special_process_allowance]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'special_process_allowance'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Superprocess</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][superprocess]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'superprocess'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>WCD KAIZEN</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][wcd_kaizen]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'wcd_kaizen'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>MT INCENTIVE</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][mt_incentive]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'mt_incentive'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>SKT INCENTIVE</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][skt_incentive]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'skt_incentive'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Contribution Refund</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][contribution_refund]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'contribution_refund'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Salary Complaint Adjustment</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][salary_complaint]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'salary_complaint'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>HAI-V</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][hai_v]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'hai_v'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Total Adjustment</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][total_adjustment]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'adjustments', 'total_adjustment'); ?>">
                                </div>
                            </div>
                            
                            <!-- DEDUCTIONS SECTION -->
                            <div class="form-section-title">Deductions</div>
                            
                            <div class="deductions-grid">
                                <div class="form-group">
                                    <label>SSS SL</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][sss_sl]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'sss_sl'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>SSS CL</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][sss_cl]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'sss_cl'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>HDMF MPL</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][hdmf_mpl]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'hdmf_mpl'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>HDMF CL</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][hdmf_cl]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'hdmf_cl'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>HMO</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][hmo]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'hmo'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Uniform Upon Deployment</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][uniform_upon_deployment]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'uniform_upon_deployment'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Uniform ATD</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][uniform_atd]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'uniform_atd'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Housing</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][housing]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'housing'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Medifund Loan</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][medifund_loan]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'medifund_loan'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Negats Payroll</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][negats_payroll]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'negats_payroll'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Canteen Chit</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][canteen_chit]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'canteen_chit'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Shoes</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][shoes]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'shoes'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>ID</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][id_deduction]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'id_deduction'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Cash Advance</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][cash_advance]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'cash_advance'); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>HMO Availment</label>
                                    <input type="number" name="payroll[<?php echo $emp['id']; ?>][hmo_availment]" step="0.01" value="<?php echo getPayrollValue($existing_payroll, $emp['id'], 'deductions', 'hmo_availment'); ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="manage_payroll.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-back">← Back</a>
                    <button type="submit" class="btn btn-success">✓ Save All Payroll</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

<script src="../assets/js/script.js"></script>

<script>
    // Auto-calculate earnings amounts based on hours and daily rate
    function calculateAmount(hoursInput) {
        const empId = hoursInput.getAttribute('data-emp-id');
        const multiplier = parseFloat(hoursInput.getAttribute('data-multiplier')) || 1;
        const amountFieldName = hoursInput.getAttribute('data-amount-field');
        const hours = parseFloat(hoursInput.value) || 0;

        const dailyRateInput = document.querySelector(`.daily-rate[data-emp-id="${empId}"]`);
        const dailyRate = parseFloat(dailyRateInput?.value) || 570;
        const hourlyRate = dailyRate / 8;
        const calculatedAmount = hours * hourlyRate * multiplier;
        const amount = calculatedAmount > 0 ? calculatedAmount.toFixed(2) : '';

        const form = hoursInput.closest('.employee-payroll-form');
        const amountInput = form?.querySelector(`input[name="payroll[${empId}][${amountFieldName}]"]`);
        const calcInfo = amountInput ? amountInput.closest('.form-group').querySelector('.calc-info') : null;

        if (amountInput) {
            amountInput.value = amount;
            amountInput.placeholder = 'Auto-calculated';
            if (hours > 0) {
                amountInput.classList.add('calc-highlight');
            } else {
                amountInput.classList.remove('calc-highlight');
            }
        }

        if (calcInfo) {
            if (hours > 0) {
                calcInfo.textContent = `${hours.toFixed(2)}h × ₱${hourlyRate.toFixed(2)} × ${multiplier.toFixed(2)} = ₱${calculatedAmount.toFixed(2)}`;
                calcInfo.classList.add('calc-active');
            } else {
                calcInfo.textContent = 'Auto-calculated';
                calcInfo.classList.remove('calc-active');
            }
        }
    }

    function attachAutoCalc() {
        const hoursInputs = document.querySelectorAll('.hours-input');
        const globalRateInput = document.querySelector('#globalDailyRate');
        const hiddenRates = document.querySelectorAll('.daily-rate[data-emp-id]');

        function syncRates(value) {
            hiddenRates.forEach(input => {
                input.value = value;
            });
        }

        function recalcAll() {
            hoursInputs.forEach(input => calculateAmount(input));
        }

        hoursInputs.forEach(input => {
            input.addEventListener('input', () => calculateAmount(input));
            input.addEventListener('keyup', () => calculateAmount(input));
            input.addEventListener('change', () => calculateAmount(input));
            calculateAmount(input);
        });

        if (globalRateInput) {
            syncRates(globalRateInput.value);
            globalRateInput.addEventListener('input', function() {
                syncRates(this.value);
                recalcAll();
            });
            globalRateInput.addEventListener('change', function() {
                syncRates(this.value);
                recalcAll();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachAutoCalc);
    } else {
        attachAutoCalc();
    }
</script>
</body>
</html>
