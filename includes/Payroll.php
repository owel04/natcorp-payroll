<?php
require_once __DIR__ . '/../config.php';

class Payroll {
    private $conn;

    // Earnings labels mapping (column name => display label)
    public static $earningsLabels = [
        'reg_days' => 'REG DAYS',
        'lh_unworked' => 'Legal Holiday Unworked',
        'rot' => 'ROT',
        'nd' => 'ND',
        'rd' => 'RD',
        'rd_exc' => 'RD EXC',
        'rd_nd' => 'RD ND',
        'rd_ndot' => 'RD NDOT',
        'lh_rd' => 'LH+RD',
        'lh_rd_exc' => 'LH+RD EXC',
        'lh_rd_nd' => 'LH+RD ND',
        'lh_rd_ndot' => 'LH+RD NDOT',
        'lh' => 'LH',
        'lh_exc' => 'LH EXC',
        'lh_nd' => 'LH ND',
        'lh_ndot' => 'LH NDOT',
        'shd' => 'SHD',
        'shd_ot' => 'SHD OT',
        'shd_nd' => 'SHD ND',
        'shd_rd' => 'SHD + RD',
        'shd_rd_ot' => 'SHD RD OT',
        'shd_rd_nd' => 'SHD RD ND',
        'cnw' => 'CNW',
        'cnw_ot' => 'CNW OT',
        'cnd_nd' => 'CND ND',
    ];

    // Adjustments labels mapping
    public static $adjustmentsLabels = [
        'late_undertime' => 'Late/Undertime',
        'assy_incentive' => 'ASSY INCENTIVE',
        'perfect_attendance' => 'PERFECT ATTENDANCE',
        'qa_incentive' => 'QA INCENTIVE',
        'special_process_allowance' => 'SPECIAL PROCESS ALLOWANCE',
        'superprocess' => 'SUPERPROCESS',
        'wcd_kaizen' => 'WCD KAIZEN',
        'mt_incentive' => 'MT INCENTIVE',
        'skt_incentive' => 'SKT INCENTIVE',
        'contribution_refund' => 'CONTRIBUTION REFUND',
        'salary_complaint' => 'SALARY COMPLAINT',
        'hai_v' => 'HAI-V',
        'total_adjustment' => 'TOTAL ADJUSTMENT',
    ];

    // Deductions labels mapping
    public static $deductionsLabels = [
        'sss_sl' => 'SSS SL',
        'sss_cl' => 'SSS CL',
        'hdmf_mpl' => 'HDMF MPL',
        'hdmf_cl' => 'HDMF CL',
        'hmo' => 'HMO',
        'uniform_upon_deployment' => 'UNIFORM UPON DEPLOYMENT',
        'uniform_atd' => 'UNIFORM ATD',
        'housing' => 'HOUSING',
        'medifund_loan' => 'MEDIFUND LOAN',
        'negats_payroll' => 'NEGATS PAYROLL',
        'canteen_chit' => 'CANTEEN CHIT',
        'shoes' => 'SHOES',
        'id_deduction' => 'ID',
        'cash_advance' => 'CASH ADVANCE',
        'hmo_availment' => 'HMO AVAILMENT',
    ];

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function addPayrollData($payroll_data) {
        $day = (int)($payroll_data['day'] ?? 1);
        $existingId = $this->getExistingPayrollId($payroll_data['employee_id'], $day, $payroll_data['month'], $payroll_data['year']);
        if ($existingId) {
            return $this->updatePayrollData($existingId, $payroll_data);
        }

        // Calculate totals
        $total_earnings = $this->calculateTotalEarnings($payroll_data);
        $total_adjustments = $this->calculateTotalAdjustments($payroll_data);
        $total_deductions = $this->calculateTotalDeductions($payroll_data);
        $gross_pay = $total_earnings + $total_adjustments;
        $net_pay = $gross_pay - $total_deductions;

        // Insert payroll summary
        $day = (int)($payroll_data['day'] ?? 1);
        $sql = "INSERT INTO payroll_summary (employee_id, day, month, year, total_earnings, total_adjustments, total_deductions, gross_pay, net_pay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'iiiiddddd',
            $payroll_data['employee_id'],
            $day,
            $payroll_data['month'],
            $payroll_data['year'],
            $total_earnings,
            $total_adjustments,
            $total_deductions,
            $gross_pay,
            $net_pay
        );

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to add payroll summary: ' . $stmt->error];
        }

        $summaryId = $this->conn->insert_id;
        
        // Insert related data
        $this->insertPayrollEarnings($summaryId, $payroll_data);
        $this->insertPayrollAdjustments($summaryId, $payroll_data);
        $this->insertPayrollDeductions($summaryId, $payroll_data);
        $this->insertPayrollNetpay($summaryId, $payroll_data, $gross_pay, $net_pay);

        return ['success' => true, 'message' => 'Payroll data added successfully', 'payroll_id' => $summaryId];
    }

    public function updatePayrollData($payroll_id, $payroll_data) {
        // Calculate totals
        $total_earnings = $this->calculateTotalEarnings($payroll_data);
        $total_adjustments = $this->calculateTotalAdjustments($payroll_data);
        $total_deductions = $this->calculateTotalDeductions($payroll_data);
        $gross_pay = $total_earnings + $total_adjustments;
        $net_pay = $gross_pay - $total_deductions;

        $sql = "UPDATE payroll_summary SET total_earnings = ?, total_adjustments = ?, total_deductions = ?, gross_pay = ?, net_pay = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('dddddi', $total_earnings, $total_adjustments, $total_deductions, $gross_pay, $net_pay, $payroll_id);

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to update payroll summary: ' . $stmt->error];
        }

        // Delete existing related data and re-insert
        $this->deletePayrollRelatedData($payroll_id);
        $this->insertPayrollEarnings($payroll_id, $payroll_data);
        $this->insertPayrollAdjustments($payroll_id, $payroll_data);
        $this->insertPayrollDeductions($payroll_id, $payroll_data);
        $this->insertPayrollNetpay($payroll_id, $payroll_data, $gross_pay, $net_pay);

        return ['success' => true, 'message' => 'Payroll data updated successfully'];
    }

    private function calculateEarningAmount($data, $field, $multiplier = 1.0) {
        $hrs = (float)($data[$field . '_hrs'] ?? 0);
        $amt = (float)($data[$field . '_amt'] ?? 0);

        if ($amt > 0) {
            return round($amt, 2);
        }
        if ($hrs > 0) {
            return round($hrs * (570 / 8) * $multiplier, 2);
        }
        return 0.0;
    }

    private function calculateTotalEarnings($data) {
        $total = 0;
        $fields = [
            ['reg_days', 1.0],
            ['lh_unworked', 1.0],
            ['rot', 1.25],
            ['nd', 0.1],
            ['rd', 1.3],
            ['rd_exc', 1.69],
            ['rd_nd', 1.3],
            ['rd_ndot', 1.69],
            ['lh_rd', 1.0],
            ['lh_rd_exc', 1.0],
            ['lh_rd_nd', 1.0],
            ['lh_rd_ndot', 1.0],
            ['lh', 1.0],
            ['lh_exc', 1.0],
            ['lh_nd', 1.0],
            ['lh_ndot', 1.0],
            ['shd', 0.5],
            ['shd_ot', 1.0],
            ['shd_nd', 1.3],
            ['shd_rd', 1.69],
            ['shd_rd_ot', 1.69],
            ['shd_rd_nd', 1.69],
            ['cnw', 1.0],
            ['cnw_ot', 1.25],
            ['cnd_nd', 0.1],
        ];

        foreach ($fields as [$field, $multiplier]) {
            $total += $this->calculateEarningAmount($data, $field, $multiplier);
        }
        return round($total, 2);
    }

    private function calculateTotalAdjustments($data) {
        $total = 0;
        $fields = ['late_undertime', 'assy_incentive', 'perfect_attendance', 'qa_incentive', 'special_process_allowance',
                   'superprocess', 'wcd_kaizen', 'mt_incentive', 'skt_incentive', 'contribution_refund', 
                   'salary_complaint', 'hai_v', 'total_adjustment'];
        foreach ($fields as $field) {
            $total += (float)($data[$field] ?? 0);
        }
        return round($total, 2);
    }

    private function calculateTotalDeductions($data) {
        $total = 0;
        $fields = ['sss_sl', 'sss_cl', 'hdmf_mpl', 'hdmf_cl', 'hmo', 'uniform_upon_deployment', 'uniform_atd',
                   'housing', 'medifund_loan', 'negats_payroll', 'canteen_chit', 'shoes', 'id_deduction', 
                   'cash_advance', 'hmo_availment'];
        foreach ($fields as $field) {
            $total += (float)($data[$field] ?? 0);
        }
        return round($total, 2);
    }

    public function getExistingPayrollId($employee_id, $day, $month, $year) {
        $sql = "SELECT id FROM payroll_summary WHERE employee_id = ? AND day = ? AND month = ? AND year = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiii', $employee_id, $day, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows ? $result->fetch_assoc()['id'] : null;
    }

    private function insertPayrollEarnings($summaryId, $data) {
        $sql = "INSERT INTO payroll_earnings (payroll_summary_id,
            reg_days_hrs, reg_days_amt, lh_unworked_hrs, lh_unworked_amt, rot_hrs, rot_amt, nd_hrs, nd_amt,
            rd_hrs, rd_amt, rd_exc_hrs, rd_exc_amt, rd_nd_hrs, rd_nd_amt, rd_ndot_hrs, rd_ndot_amt,
            lh_rd_hrs, lh_rd_amt, lh_rd_exc_hrs, lh_rd_exc_amt, lh_rd_nd_hrs, lh_rd_nd_amt, lh_rd_ndot_hrs, lh_rd_ndot_amt,
            lh_hrs, lh_amt, lh_exc_hrs, lh_exc_amt, lh_nd_hrs, lh_nd_amt, lh_ndot_hrs, lh_ndot_amt,
            shd_hrs, shd_amt, shd_ot_hrs, shd_ot_amt, shd_nd_hrs, shd_nd_amt,
            shd_rd_hrs, shd_rd_amt, shd_rd_ot_hrs, shd_rd_ot_amt, shd_rd_nd_hrs, shd_rd_nd_amt,
            cnw_hrs, cnw_amt, cnw_ot_hrs, cnw_ot_amt, cnd_nd_hrs, cnd_nd_amt
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        $values = [
            $summaryId,
            (float)($data['reg_days_hrs'] ?? 0), (float)($data['reg_days_amt'] ?? 0),
            (float)($data['lh_unworked_hrs'] ?? 0), (float)($data['lh_unworked_amt'] ?? 0),
            (float)($data['rot_hrs'] ?? 0), (float)($data['rot_amt'] ?? 0),
            (float)($data['nd_hrs'] ?? 0), (float)($data['nd_amt'] ?? 0),
            (float)($data['rd_hrs'] ?? 0), (float)($data['rd_amt'] ?? 0),
            (float)($data['rd_exc_hrs'] ?? 0), (float)($data['rd_exc_amt'] ?? 0),
            (float)($data['rd_nd_hrs'] ?? 0), (float)($data['rd_nd_amt'] ?? 0),
            (float)($data['rd_ndot_hrs'] ?? 0), (float)($data['rd_ndot_amt'] ?? 0),
            (float)($data['lh_rd_hrs'] ?? 0), (float)($data['lh_rd_amt'] ?? 0),
            (float)($data['lh_rd_exc_hrs'] ?? 0), (float)($data['lh_rd_exc_amt'] ?? 0),
            (float)($data['lh_rd_nd_hrs'] ?? 0), (float)($data['lh_rd_nd_amt'] ?? 0),
            (float)($data['lh_rd_ndot_hrs'] ?? 0), (float)($data['lh_rd_ndot_amt'] ?? 0),
            (float)($data['lh_hrs'] ?? 0), (float)($data['lh_amt'] ?? 0),
            (float)($data['lh_exc_hrs'] ?? 0), (float)($data['lh_exc_amt'] ?? 0),
            (float)($data['lh_nd_hrs'] ?? 0), (float)($data['lh_nd_amt'] ?? 0),
            (float)($data['lh_ndot_hrs'] ?? 0), (float)($data['lh_ndot_amt'] ?? 0),
            (float)($data['shd_hrs'] ?? 0), (float)($data['shd_amt'] ?? 0),
            (float)($data['shd_ot_hrs'] ?? 0), (float)($data['shd_ot_amt'] ?? 0),
            (float)($data['shd_nd_hrs'] ?? 0), (float)($data['shd_nd_amt'] ?? 0),
            (float)($data['shd_rd_hrs'] ?? 0), (float)($data['shd_rd_amt'] ?? 0),
            (float)($data['shd_rd_ot_hrs'] ?? 0), (float)($data['shd_rd_ot_amt'] ?? 0),
            (float)($data['shd_rd_nd_hrs'] ?? 0), (float)($data['shd_rd_nd_amt'] ?? 0),
            (float)($data['cnw_hrs'] ?? 0), (float)($data['cnw_amt'] ?? 0),
            (float)($data['cnw_ot_hrs'] ?? 0), (float)($data['cnw_ot_amt'] ?? 0),
            (float)($data['cnd_nd_hrs'] ?? 0), (float)($data['cnd_nd_amt'] ?? 0),
        ];
        
        $types = 'i' . str_repeat('d', 50);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
    }

    private function insertPayrollAdjustments($summaryId, $data) {
        $sql = "INSERT INTO payroll_adjustments (payroll_summary_id,
            late_undertime, assy_incentive, perfect_attendance, qa_incentive, special_process_allowance,
            superprocess, wcd_kaizen, mt_incentive, skt_incentive, contribution_refund,
            salary_complaint, hai_v, total_adjustment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        $values = [
            $summaryId,
            (float)($data['late_undertime'] ?? 0),
            (float)($data['assy_incentive'] ?? 0),
            (float)($data['perfect_attendance'] ?? 0),
            (float)($data['qa_incentive'] ?? 0),
            (float)($data['special_process_allowance'] ?? 0),
            (float)($data['superprocess'] ?? 0),
            (float)($data['wcd_kaizen'] ?? 0),
            (float)($data['mt_incentive'] ?? 0),
            (float)($data['skt_incentive'] ?? 0),
            (float)($data['contribution_refund'] ?? 0),
            (float)($data['salary_complaint'] ?? 0),
            (float)($data['hai_v'] ?? 0),
            (float)($data['total_adjustment'] ?? 0),
        ];
        
        $stmt->bind_param('iddddddddddddd', ...$values);
        $stmt->execute();
    }

    private function insertPayrollDeductions($summaryId, $data) {
        $sql = "INSERT INTO payroll_deductions (payroll_summary_id,
            sss_sl, sss_cl, hdmf_mpl, hdmf_cl, hmo, uniform_upon_deployment, uniform_atd,
            housing, medifund_loan, negats_payroll, canteen_chit, shoes, id_deduction, cash_advance, hmo_availment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        $values = [
            $summaryId,
            (float)($data['sss_sl'] ?? 0),
            (float)($data['sss_cl'] ?? 0),
            (float)($data['hdmf_mpl'] ?? 0),
            (float)($data['hdmf_cl'] ?? 0),
            (float)($data['hmo'] ?? 0),
            (float)($data['uniform_upon_deployment'] ?? 0),
            (float)($data['uniform_atd'] ?? 0),
            (float)($data['housing'] ?? 0),
            (float)($data['medifund_loan'] ?? 0),
            (float)($data['negats_payroll'] ?? 0),
            (float)($data['canteen_chit'] ?? 0),
            (float)($data['shoes'] ?? 0),
            (float)($data['id_deduction'] ?? 0),
            (float)($data['cash_advance'] ?? 0),
            (float)($data['hmo_availment'] ?? 0),
        ];
        
        $stmt->bind_param('iddddddddddddddd', ...$values);
        $stmt->execute();
    }

    private function insertPayrollNetpay($summaryId, $data, $gross, $net) {
        $sql = "INSERT INTO payroll_netpay (payroll_summary_id, employee_no, employee_name, gross_amount, net_amount) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $employee_no = $data['employee_no'] ?? '';
        $employee_name = $data['employee_name'] ?? '';
        
        $stmt->bind_param('issdd', $summaryId, $employee_no, $employee_name, $gross, $net);
        $stmt->execute();
    }

    private function deletePayrollRelatedData($summaryId) {
        $tables = ['payroll_earnings', 'payroll_adjustments', 'payroll_deductions', 'payroll_netpay'];
        foreach ($tables as $table) {
            $sql = "DELETE FROM {$table} WHERE payroll_summary_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $summaryId);
            $stmt->execute();
        }
    }

    public function getPayroll($payroll_id) {
        $sql = "SELECT ps.*, e.employee_id AS emp_id, e.first_name, e.last_name, e.department, e.position, e.date_of_joining, e.client_company, u.email 
                FROM payroll_summary ps 
                JOIN employees e ON ps.employee_id = e.id 
                JOIN users u ON e.user_id = u.id 
                WHERE ps.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $payroll_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $slip = $result->fetch_assoc();

        if (!$slip) {
            return null;
        }

        $slip['earnings'] = $this->getPayrollEarnings($payroll_id);
        $slip['adjustments'] = $this->getPayrollAdjustments($payroll_id);
        $slip['deductions'] = $this->getPayrollDeductions($payroll_id);
        $slip['netpay'] = $this->getPayrollNetpay($payroll_id);

        return $slip;
    }

    public function getEmployeePayslips($employee_id, $month = null, $year = null, $day = null) {
        if ($month !== null && $year !== null) {
            if ($day !== null) {
                $sql = "SELECT * FROM payroll_summary WHERE employee_id = ? AND month = ? AND year = ? AND day = ? ORDER BY year DESC, month DESC, day DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('iiii', $employee_id, $month, $year, $day);
            } else {
                $sql = "SELECT * FROM payroll_summary WHERE employee_id = ? AND month = ? AND year = ? ORDER BY year DESC, month DESC, day DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('iii', $employee_id, $month, $year);
            }
        } else {
            $sql = "SELECT * FROM payroll_summary WHERE employee_id = ? ORDER BY year DESC, month DESC, day DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $employee_id);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPayslip($employee_id, $month, $year) {
        $sql = "SELECT ps.*, e.employee_id AS emp_id, e.first_name, e.last_name, e.department, e.position, e.date_of_joining, e.client_company, u.email 
                FROM payroll_summary ps 
                JOIN employees e ON ps.employee_id = e.id 
                JOIN users u ON e.user_id = u.id 
                WHERE ps.employee_id = ? AND ps.month = ? AND ps.year = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iii', $employee_id, $month, $year);
        $stmt->execute();
        $slip = $stmt->get_result()->fetch_assoc();

        if (!$slip) {
            return null;
        }

        $slip['earnings'] = $this->getPayrollEarnings($slip['id']);
        $slip['adjustments'] = $this->getPayrollAdjustments($slip['id']);
        $slip['deductions'] = $this->getPayrollDeductions($slip['id']);
        $slip['netpay'] = $this->getPayrollNetpay($slip['id']);

        return $slip;
    }

    public function getAllPayrolls($month = null, $year = null, $day = null, $search = null) {
        $sql = "SELECT ps.*, e.employee_id, e.first_name, e.last_name, e.department FROM payroll_summary ps 
                JOIN employees e ON ps.employee_id = e.id";
        $conditions = [];
        $types = '';
        $params = [];

        if ($month !== null && $year !== null) {
            $conditions[] = 'ps.month = ?';
            $types .= 'i';
            $params[] = $month;
            $conditions[] = 'ps.year = ?';
            $types .= 'i';
            $params[] = $year;

            if ($day !== null) {
                $conditions[] = 'ps.day = ?';
                $types .= 'i';
                $params[] = $day;
            }
        }

        if ($search !== null && $search !== '') {
            $conditions[] = "(e.employee_id LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ? )";
            $searchParam = "%{$search}%";
            $types .= 'ssss';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY e.first_name ASC';
        $stmt = $this->conn->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getPayrollEarnings($summaryId) {
        $sql = "SELECT * FROM payroll_earnings WHERE payroll_summary_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $summaryId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getPayrollAdjustments($summaryId) {
        $sql = "SELECT * FROM payroll_adjustments WHERE payroll_summary_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $summaryId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getPayrollDeductions($summaryId) {
        $sql = "SELECT * FROM payroll_deductions WHERE payroll_summary_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $summaryId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getPayrollNetpay($summaryId) {
        $sql = "SELECT * FROM payroll_netpay WHERE payroll_summary_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $summaryId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPayrollDetails($payroll_id) {
        $sql = "SELECT ps.*, e.employee_id AS emp_id, e.first_name, e.last_name, e.department, e.position, e.date_of_joining, e.client_company, u.email 
                FROM payroll_summary ps 
                JOIN employees e ON ps.employee_id = e.id 
                JOIN users u ON e.user_id = u.id 
                WHERE ps.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $payroll_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $slip = $result->fetch_assoc();

        if (!$slip) {
            return null;
        }

        $slip['earnings'] = $this->getPayrollEarnings($payroll_id) ?? [];
        $slip['adjustments'] = $this->getPayrollAdjustments($payroll_id) ?? [];
        $slip['deductions'] = $this->getPayrollDeductions($payroll_id) ?? [];
        $slip['netpay'] = $this->getPayrollNetpay($payroll_id) ?? [];

        return $slip;
    }

    public function recordPayslipView($payroll_id, $user_id) {
        $sql = "INSERT INTO payslip_history (payroll_id, user_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $payroll_id, $user_id);
        return $stmt->execute();
    }

    public function recordPayslipDownload($payroll_id, $user_id) {
        $sql = "UPDATE payslip_history SET downloaded_at = NOW() WHERE payroll_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $payroll_id, $user_id);
        return $stmt->execute();
    }
}
