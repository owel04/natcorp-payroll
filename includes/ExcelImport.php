<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/Payroll.php';

class ExcelImport {
    private $conn;
    private $employee;
    private $payroll;
    private $debugLog = [];
    
    // Column mappings - maps Excel header variations to database field names
    private $columnMappings = [
        // ID variations
        'id' => 'id',
        'i.d' => 'id',
        'i.d.' => 'id',
        'id no' => 'id',
        'idno' => 'id',
        'id no.' => 'id',
        'employee id' => 'id',
        'employeeid' => 'id',
        'emp id' => 'id',
        'no' => 'row_no',
        'no.' => 'row_no',
        'emp no' => 'id',
        'emp no.' => 'id',
        
        // Name variations
        'name' => 'name',
        'employee name' => 'name',
        'employeename' => 'name',
        'emp name' => 'name',
        'full name' => 'name',
        'fullname' => 'name',
        
        // Department & Position
        'department' => 'department',
        'dept' => 'department',
        'position' => 'position',
        'job title' => 'position',
        'date hired' => 'date_hired',
        'datehired' => 'date_hired',
        'hire date' => 'date_hired',
        'date hiree' => 'date_hired',
        'datehiree' => 'date_hired',
        'hired date' => 'date_hired',
        'date of birth' => 'dob',
        'birth date' => 'dob',
        'dateofbirth' => 'dob',
        'birthdate' => 'dob',
        'dob' => 'dob',
        
        // Client Company
        'client company' => 'client_company',
        'clientcompany' => 'client_company',
        'client' => 'client_company',
        'company' => 'client_company',
        
        // EARNINGS - REG DAYS (Hours and Amount variations)
        'reg days' => 'reg_days_hrs',
        'regdays' => 'reg_days_hrs',
        'regular days' => 'reg_days_hrs',
        'regular work days' => 'reg_days_hrs',
        'reg days hrs' => 'reg_days_hrs',
        'reg days hours' => 'reg_days_hrs',
        'reg days amt' => 'reg_days_amt',
        'reg days amount' => 'reg_days_amt',
        
        // ROT - Amount variations
        'rot amt' => 'rot_amt',
        'rot amount' => 'rot_amt',
        'reg ot amt' => 'rot_amt',
        'reg overtime amt' => 'rot_amt',
        
        // ND - Amount variations
        'nd amt' => 'nd_amt',
        'nd amount' => 'nd_amt',
        'night diff amt' => 'nd_amt',
        
        // RD - Amount variations
        'rd amt' => 'rd_amt',
        'rd amount' => 'rd_amt',
        'rest day amt' => 'rd_amt',
        
        // CNW - Amount variations
        'cnw amt' => 'cnw_amt',
        'cnw amount' => 'cnw_amt',
        'cnw ot amt' => 'cnw_ot_amt',
        'cnw ot amount' => 'cnw_ot_amt',
        
        // CND ND - Amount variations
        'cnd nd amt' => 'cnd_nd_amt',
        'cnd nd amount' => 'cnd_nd_amt',
        
        // Amount column variations (when column header just says "AMOUNT")
        'amount' => 'amount_col',
        'amt' => 'amount_col',
        
        // Legal Holiday Unworked
        'legal holiday unworked' => 'lh_unworked_hrs',
        'lh unworked' => 'lh_unworked_hrs',
        'legalholidayunworked' => 'lh_unworked_hrs',
        
        // ROT - Regular Overtime
        'rot' => 'rot_hrs',
        'r.o.t' => 'rot_hrs',
        'reg ot' => 'rot_hrs',
        'reg overtime' => 'rot_hrs',
        'regular overtime' => 'rot_hrs',
        'regular ot' => 'rot_hrs',
        
        // ND - Night Differential
        'nd' => 'nd_hrs',
        'n.d' => 'nd_hrs',
        'night diff' => 'nd_hrs',
        'night differential' => 'nd_hrs',
        'nightdiff' => 'nd_hrs',
        
        // RD - Rest Day
        'rd' => 'rd_hrs',
        'r.d' => 'rd_hrs',
        'rest day' => 'rd_hrs',
        'restday' => 'rd_hrs',
        
        // RD EXC
        'rd exc' => 'rd_exc_hrs',
        'rd excess' => 'rd_exc_hrs',
        'rdexc' => 'rd_exc_hrs',
        'rd exc amt' => 'rd_exc_amt',
        'rd excess amt' => 'rd_exc_amt',
        
        // RD ND
        'rd nd' => 'rd_nd_hrs',
        'rdnd' => 'rd_nd_hrs',
        'rd night diff' => 'rd_nd_hrs',
        'rd nd amt' => 'rd_nd_amt',
        
        // RD NDOT
        'rd ndot' => 'rd_ndot_hrs',
        'rdndot' => 'rd_ndot_hrs',
        'rd night diff ot' => 'rd_ndot_hrs',
        'rd ndot amt' => 'rd_ndot_amt',
        
        // LH+RD combinations
        'lh+rd' => 'lh_rd_hrs',
        'lh rd' => 'lh_rd_hrs',
        'lhrd' => 'lh_rd_hrs',
        
        'lh+rd exc' => 'lh_rd_exc_hrs',
        'lh rd exc' => 'lh_rd_exc_hrs',
        'lhrdexc' => 'lh_rd_exc_hrs',
        
        'lh+rd nd' => 'lh_rd_nd_hrs',
        'lh rd nd' => 'lh_rd_nd_hrs',
        'lhrdnd' => 'lh_rd_nd_hrs',
        
        'lh+rd ndot' => 'lh_rd_ndot_hrs',
        'lh rd ndot' => 'lh_rd_ndot_hrs',
        'lhrdndot' => 'lh_rd_ndot_hrs',
        
        // LH - Legal Holiday
        'lh' => 'lh_hrs',
        'l.h' => 'lh_hrs',
        'legal holiday' => 'lh_hrs',
        'legalholiday' => 'lh_hrs',
        'lh amt' => 'lh_amt',
        'lh amount' => 'lh_amt',
        
        'lh exc' => 'lh_exc_hrs',
        'lh excess' => 'lh_exc_hrs',
        'lhexc' => 'lh_exc_hrs',
        'lh exc amt' => 'lh_exc_amt',
        
        'lh nd' => 'lh_nd_hrs',
        'lhnd' => 'lh_nd_hrs',
        'lh nd amt' => 'lh_nd_amt',
        
        'lh ndot' => 'lh_ndot_hrs',
        'lhndot' => 'lh_ndot_hrs',
        'lh ndot amt' => 'lh_ndot_amt',
        
        // SHD - Special Holiday
        'shd' => 'shd_hrs',
        's.h.d' => 'shd_hrs',
        'special holiday' => 'shd_hrs',
        'specialholiday' => 'shd_hrs',
        'shd amt' => 'shd_amt',
        'shd amount' => 'shd_amt',
        
        'shd ot' => 'shd_ot_hrs',
        'shdot' => 'shd_ot_hrs',
        'shd ot amt' => 'shd_ot_amt',
        'shdot amt' => 'shd_ot_amt',
        
        'shd nd' => 'shd_nd_hrs',
        'shd nd amt' => 'shd_nd_amt',
        'shdnd' => 'shd_nd_hrs',
        
        'shd + rd' => 'shd_rd_hrs',
        'shd+rd' => 'shd_rd_hrs',
        'shd rd' => 'shd_rd_hrs',
        'shdrd' => 'shd_rd_hrs',
        
        'shd rd ot' => 'shd_rd_ot_hrs',
        'shdrdot' => 'shd_rd_ot_hrs',
        
        'shd rd nd' => 'shd_rd_nd_hrs',
        'shdrdnd' => 'shd_rd_nd_hrs',
        
        // CNW - Company Night Work
        'cnw' => 'cnw_hrs',
        'c.n.w' => 'cnw_hrs',
        
        'cnw ot' => 'cnw_ot_hrs',
        'cnwot' => 'cnw_ot_hrs',
        
        'cnd nd' => 'cnd_nd_hrs',
        'cndnd' => 'cnd_nd_hrs',
        
        // ADJUSTMENTS
        'late/undertime' => 'late_undertime',
        'late undertime' => 'late_undertime',
        'lateundertime' => 'late_undertime',
        'late / undertime' => 'late_undertime',
        
        'assy incentive' => 'assy_incentive',
        'assyincentive' => 'assy_incentive',
        'assembly incentive' => 'assy_incentive',
        
        'perfect attendance' => 'perfect_attendance',
        'perfectattendance' => 'perfect_attendance',
        
        'qa incentive' => 'qa_incentive',
        'qaincentive' => 'qa_incentive',
        'quality assurance incentive' => 'qa_incentive',
        
        'special process allowance' => 'special_process_allowance',
        'specialprocessallowance' => 'special_process_allowance',
        'sp allowance' => 'special_process_allowance',
        
        'superprocess' => 'superprocess',
        'super process' => 'superprocess',
        
        'wcd kaizen' => 'wcd_kaizen',
        'wcdkaizen' => 'wcd_kaizen',
        'kaizen' => 'wcd_kaizen',
        
        'mt incentive' => 'mt_incentive',
        'mtincentive' => 'mt_incentive',
        
        'skt incentive' => 'skt_incentive',
        'sktincentive' => 'skt_incentive',
        
        'contribution refund' => 'contribution_refund',
        'contributionrefund' => 'contribution_refund',
        'refund contribution' => 'contribution_refund',
        
        'salary complaint' => 'salary_complaint',
        'salarycomplaint' => 'salary_complaint',
        
        'hai v' => 'hai_v',
        'hai-v' => 'hai_v',
        'haiv' => 'hai_v',
        
        'total adjustment' => 'total_adjustment',
        'totaladjustment' => 'total_adjustment',
        'adj total' => 'total_adjustment',
        
        // DEDUCTIONS
        'sss sl' => 'sss_sl',
        'sss salary loan' => 'sss_sl',
        'ssssl' => 'sss_sl',
        
        'sss cl' => 'sss_cl',
        'sss calamity loan' => 'sss_cl',
        'ssscl' => 'sss_cl',
        
        'hdmf mpl' => 'hdmf_mpl',
        'hdmfmpl' => 'hdmf_mpl',
        'pag ibig mpl' => 'hdmf_mpl',
        'pagibig mpl' => 'hdmf_mpl',
        'pag-ibig mpl' => 'hdmf_mpl',
        
        'hdmf cl' => 'hdmf_cl',
        'hdmfcl' => 'hdmf_cl',
        'pag ibig cl' => 'hdmf_cl',
        'pagibig cl' => 'hdmf_cl',
        'pag-ibig cl' => 'hdmf_cl',
        
        'hmo' => 'hmo',
        'h.m.o' => 'hmo',
        
        'uniform upon deployment' => 'uniform_upon_deployment',
        'uniformupondeployment' => 'uniform_upon_deployment',
        'uniform deployment' => 'uniform_upon_deployment',
        
        'uniform atd' => 'uniform_atd',
        'uniformatd' => 'uniform_atd',
        
        'housing' => 'housing',
        
        'medifund loan' => 'medifund_loan',
        'medifundloan' => 'medifund_loan',
        'medifund' => 'medifund_loan',
        
        'negats payroll' => 'negats_payroll',
        'negatspayroll' => 'negats_payroll',
        'negative payroll' => 'negats_payroll',
        'negats' => 'negats_payroll',
        
        'canteen chit' => 'canteen_chit',
        'canteenchit' => 'canteen_chit',
        'canteen' => 'canteen_chit',
        
        'shoes' => 'shoes',
        'safety shoes' => 'shoes',
        
        'id deduction' => 'id_deduction',
        'id' => 'id_col',  // Will be resolved contextually
        
        'cash advance' => 'cash_advance',
        'cashadvance' => 'cash_advance',
        'ca' => 'cash_advance',
        
        'hmo availment' => 'hmo_availment',
        'hmoavailment' => 'hmo_availment',
        
        // NET PAY / GROSS PAY
        'net' => 'net_pay',
        'net pay' => 'net_pay',
        'netpay' => 'net_pay',
        'net salary' => 'net_pay',
        'take home pay' => 'net_pay',
        'take home' => 'net_pay',
        'amount' => 'net_pay',
        'ah' => 'net_pay',
        
        'gross' => 'gross_pay',
        'gross pay' => 'gross_pay',
        'grosspay' => 'gross_pay',
        'gross salary' => 'gross_pay',
    ];
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->employee = new Employee($connection);
        $this->payroll = new Payroll($connection);
    }
    
    public function importExcel($file_path, $month, $year) {
        $this->debugLog = [];
        
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'File not found: ' . $file_path];
        }
        
        $errors = [];
        $added_count = 0;
        $updated_count = 0;
        $skipped_duplicates = 0;
        $processed_ids = []; // Track processed IDs to skip duplicates
        
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $this->debugLog[] = "File extension: $extension";
        
        if (in_array($extension, ['xlsx', 'xltx', 'xlsm'])) {
            $data = $this->readXLSX($file_path);
        } elseif ($extension === 'xls') {
            // Try to read as XML spreadsheet (Excel 2003 XML format)
            $data = $this->readXLS($file_path);
        } else {
            $data = $this->readCSV($file_path);
        }
        
        $this->debugLog[] = "Data rows found: " . count($data);
        
        if (!$data || empty($data)) {
            $debug_info = implode("\n", $this->debugLog);
            return ['success' => false, 'message' => 'No data found in file. Debug: ' . $debug_info];
        }
        
        foreach ($data as $index => $row) {
            $line_num = $index + 1;
            
            try {
                // Try multiple ID field variations
                $id = trim($row['id'] ?? $row['id_col'] ?? $row['id_no'] ?? $row['idno'] ?? '');
                $name = trim($row['name'] ?? '');
                
                if (empty($id) || empty($name)) {
                    $this->debugLog[] = "Line $line_num: Empty ID or Name";
                    continue;
                }
                
                // Clean up ID - remove any non-alphanumeric except underscore
                $id = preg_replace('/[^A-Za-z0-9_]/', '', $id);
                
                // Check for duplicates within this import - skip if already processed
                if (in_array($id, $processed_ids)) {
                    $this->debugLog[] = "Line $line_num: Duplicate ID '{$id}' - skipping";
                    $skipped_duplicates++;
                    continue;
                }
                $processed_ids[] = $id;
                
                // Parse name - handle "LASTNAME FIRSTNAME" or "LASTNAME, FIRSTNAME" formats
                $name_parts = preg_split('/[,\s]+/', $name, 2);
                if (count($name_parts) >= 2) {
                    $last_name = trim($name_parts[0]);
                    $first_name = trim($name_parts[1]);
                } else {
                    $first_name = trim($name);
                    $last_name = '';
                }
                
                // Get department, position, client_company, date_hired if available
                $department = trim($row['department'] ?? '');
                $position = trim($row['position'] ?? '');
                $client_company = trim($row['client_company'] ?? $row['client'] ?? $row['company'] ?? '');
                $date_hired_raw = trim($row['date_hired'] ?? $row['hire_date'] ?? $row['date_of_joining'] ?? '');
                
                // Parse date_hired - handle various formats
                $date_hired = null;
                if (!empty($date_hired_raw)) {
                    if (is_numeric($date_hired_raw)) {
                        $unix_date = ($date_hired_raw - 25569) * 86400;
                        $date_hired = date('Y-m-d', $unix_date);
                    } else {
                        $date_obj = date_create($date_hired_raw);
                        if ($date_obj) {
                            $date_hired = $date_obj->format('Y-m-d');
                        }
                    }
                }
                
                // Validate required import fields
                if (empty($position) || empty($client_company) || empty($date_hired)) {
                    $errors[] = "Line $line_num ($id): Missing required field(s). Required: position, client company, date hired.";
                    $this->debugLog[] = "Line $line_num: Invalid required import values: position={$position}, client_company={$client_company}, date_hired={$date_hired_raw}";
                    continue;
                }
                
                // Check if employee exists
                $sql = "SELECT id FROM employees WHERE employee_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $employee_result = $stmt->get_result();
                
                if ($employee_result->num_rows === 0) {
                    // Add new employee
                    $employee_data = [
                        'employee_id' => $id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => '',
                        'department' => $department,
                        'position' => $position,
                        'phone' => '',
                        'dob' => NULL,
                        'date_of_joining' => $date_hired,
                        'client_company' => $client_company
                    ];
                    
                    $result = $this->employee->addEmployee($employee_data);
                    if ($result['success']) {
                        $employee_id = $result['employee_id'];
                        $added_count++;
                    } else {
                        $errors[] = "Line $line_num ($id): Could not create employee - " . ($result['message'] ?? 'Unknown error');
                        continue;
                    }
                } else {
                    $employee = $employee_result->fetch_assoc();
                    $employee_id = $employee['id'];
                    
                    // Update department/position/date_hired/client_company if provided
                    $update_sql = "UPDATE employees SET 
                        department = COALESCE(NULLIF(?, ''), department), 
                        position = COALESCE(NULLIF(?, ''), position),
                        date_of_joining = COALESCE(?, date_of_joining),
                        client_company = COALESCE(NULLIF(?, ''), client_company)
                        WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_sql);
                    $update_stmt->bind_param("ssssi", $department, $position, $date_hired, $client_company, $employee_id);
                    $update_stmt->execute();
                }
                
                // Build payroll data
                $payroll_data = [
                    'employee_id' => $employee_id,
                    'month' => (int)$month,
                    'year' => (int)$year,
                    'employee_no' => $id,
                    'employee_name' => $name,
                ];
                
                // Map earnings (hours - the amounts will be computed or stored separately)
                $earnings_fields = [
                    'reg_days', 'lh_unworked', 'rot', 'nd', 'rd', 'rd_exc', 'rd_nd', 'rd_ndot',
                    'lh_rd', 'lh_rd_exc', 'lh_rd_nd', 'lh_rd_ndot', 'lh', 'lh_exc', 'lh_nd', 'lh_ndot',
                    'shd', 'shd_ot', 'shd_nd', 'shd_rd', 'shd_rd_ot', 'shd_rd_nd', 'cnw', 'cnw_ot', 'cnd_nd'
                ];
                
                // Debug: log all available keys in this row for earnings detection
                $all_keys = array_keys($row);
                $earnings_keys = array_filter($all_keys, function($k) {
                    return strpos($k, 'reg') !== false || strpos($k, 'rot') !== false || 
                           strpos($k, 'nd') !== false || strpos($k, 'rd') !== false ||
                           strpos($k, 'lh') !== false || strpos($k, 'shd') !== false ||
                           strpos($k, 'cnw') !== false || strpos($k, 'cnd') !== false;
                });
                if (!empty($earnings_keys) && $line_num <= 3) {
                    $this->debugLog[] = "Row $line_num earnings keys: " . implode(', ', $earnings_keys);
                    foreach ($earnings_keys as $ek) {
                        $this->debugLog[] = "  $ek = " . ($row[$ek] ?? 'null');
                    }
                }
                
                foreach ($earnings_fields as $field) {
                    $hrs_key = $field . '_hrs';
                    $amt_key = $field . '_amt';
                    
                    // Hours - try multiple key variations
                    $hrs_value = $this->getNumericValue($row, $hrs_key);
                    if ($hrs_value == 0) {
                        // Try with _2 suffix (duplicate header handling)
                        $hrs_value = $this->getNumericValue($row, $field . '_2');
                    }
                    $payroll_data[$hrs_key] = $hrs_value;
                    
                    // Amount - try multiple key variations
                    $amt_value = $this->getNumericValue($row, $amt_key);
                    if ($amt_value == 0) {
                        // Try with _2 or _3 suffix (second/third occurrence)
                        $amt_value = $this->getNumericValue($row, $hrs_key . '_2');
                    }
                    if ($amt_value == 0) {
                        $amt_value = $this->getNumericValue($row, $field . '_amt_2');
                    }
                    
                    // If still no amount, try the raw field name (for single-column earnings)
                    // This handles Excel where earnings have only one column (the amount)
                    if ($amt_value == 0) {
                        $raw_value = $this->getNumericValue($row, $field);
                        // If raw value looks like an amount (> 100 typically), use it as amount
                        // If it looks like hours (usually < 100), use it as hours
                        if ($raw_value > 0) {
                            if ($raw_value >= 100) {
                                $amt_value = $raw_value;
                            } elseif ($hrs_value == 0) {
                                $hrs_value = $raw_value;
                                $payroll_data[$hrs_key] = $hrs_value;
                            }
                        }
                    }
                    
                    $payroll_data[$amt_key] = $amt_value;
                }
                
                // Map adjustments
                $adjustment_fields = [
                    'late_undertime', 'assy_incentive', 'perfect_attendance', 'qa_incentive',
                    'special_process_allowance', 'superprocess', 'wcd_kaizen', 'mt_incentive', 
                    'skt_incentive', 'contribution_refund', 'salary_complaint', 'hai_v', 'total_adjustment'
                ];
                
                foreach ($adjustment_fields as $field) {
                    $payroll_data[$field] = $this->getNumericValue($row, $field);
                }
                
                // Map deductions
                $deduction_fields = [
                    'sss_sl', 'sss_cl', 'hdmf_mpl', 'hdmf_cl', 'hmo', 'uniform_upon_deployment',
                    'uniform_atd', 'housing', 'medifund_loan', 'negats_payroll', 'canteen_chit', 
                    'shoes', 'id_deduction', 'cash_advance', 'hmo_availment'
                ];
                
                foreach ($deduction_fields as $field) {
                    $payroll_data[$field] = $this->getNumericValue($row, $field);
                }
                
                // Net pay and gross pay
                $payroll_data['net_pay'] = $this->getNumericValue($row, 'net_pay');
                $payroll_data['gross_pay'] = $this->getNumericValue($row, 'gross_pay');
                
                $payroll_result = $this->payroll->addPayrollData($payroll_data);
                if ($payroll_result['success']) {
                    $updated_count++;
                } else {
                    $errors[] = "Line $line_num ($id): " . ($payroll_result['message'] ?? 'Payroll save failed');
                }
                
            } catch (Exception $e) {
                $errors[] = "Line $line_num: " . $e->getMessage();
            }
        }
        
        $message = "Import completed. Employees added: $added_count, Payroll records: $updated_count";
        if ($skipped_duplicates > 0) {
            $message .= ", Duplicates skipped: $skipped_duplicates";
        }
        if (!empty($errors)) {
            $message .= ". Warnings: " . count($errors);
        }
        
        return [
            'success' => true,
            'message' => $message,
            'added_employees' => $added_count,
            'updated_payroll' => $updated_count,
            'skipped_duplicates' => $skipped_duplicates,
            'errors' => $errors,
            'debug' => $this->debugLog
        ];
    }
    
    private function getNumericValue($row, $field) {
        // Try the exact field name first
        $value = $row[$field] ?? null;
        
        // If not found, try variations
        if ($value === null || $value === '' || $value === '-') {
            // Try with underscores replaced by spaces
            $alt_field = str_replace('_', ' ', $field);
            $value = $row[$alt_field] ?? null;
        }
        
        if ($value === null || $value === '' || $value === '-') {
            return 0;
        }
        
        // Clean the value - remove currency symbols, spaces, commas
        $cleaned = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $value));
        
        if (is_numeric($cleaned)) {
            return (float)$cleaned;
        }
        
        return 0;
    }

    private function parseDateValue($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $unix_date = ($value - 25569) * 86400;
            return date('Y-m-d', (int)$unix_date);
        }

        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'Y/m/d'
        ];

        foreach ($formats as $format) {
            $date_obj = DateTime::createFromFormat($format, $value);
            if ($date_obj && $date_obj->format($format) === $value) {
                return $date_obj->format('Y-m-d');
            }
        }

        $date_obj = date_create($value);
        return $date_obj ? $date_obj->format('Y-m-d') : null;
    }

    private function normalizeHeaderName($header) {
        // Clean up the header name
        $header = strtolower(trim($header));
        
        // Remove special characters except + / -
        $header = preg_replace('/[^\p{L}\p{N}\/\+\-\s\.]+/u', ' ', $header);
        $header = trim(preg_replace('/\s+/', ' ', $header));
        
        // Check if this header maps to a known field
        if (isset($this->columnMappings[$header])) {
            return $this->columnMappings[$header];
        }
        
        // Try without dots
        $no_dots = str_replace('.', '', $header);
        if (isset($this->columnMappings[$no_dots])) {
            return $this->columnMappings[$no_dots];
        }
        
        // Try without spaces
        $no_spaces = str_replace(' ', '', $header);
        if (isset($this->columnMappings[$no_spaces])) {
            return $this->columnMappings[$no_spaces];
        }
        
        // Try with underscores
        $underscored = str_replace(' ', '_', $header);
        if (isset($this->columnMappings[$underscored])) {
            return $this->columnMappings[$underscored];
        }
        
        // Return normalized version
        return str_replace([' ', '.'], '_', $header);
    }
    
    private function getColumnIndexFromReference($reference) {
        if (!preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
    
    private function findHeaderRow($rows) {
        // Look for the row that contains both ID (or ID NO) and NAME headers
        foreach ($rows as $index => $row_data) {
            $has_id = false;
            $has_name = false;
            $id_col = -1;
            $name_col = -1;
            
            foreach ($row_data as $col => $val) {
                $normalized = $this->normalizeHeaderName($val);
                $val_lower = strtolower(trim($val));
                
                // Check for ID variations including "ID NO"
                // Note: "id" alone in deductions sheet may be deduction-id, so prefer "id no"
                if ($normalized === 'id' || $normalized === 'id_col' || 
                    $val_lower === 'id no' || $val_lower === 'id no.' ||
                    $val_lower === 'idno' || $val_lower === 'i.d' || $val_lower === 'i.d.' ||
                    $val_lower === 'employee id' || $val_lower === 'emp id') {
                    $has_id = true;
                    $id_col = $col;
                }
                if ($normalized === 'name' || $val_lower === 'name' || $val_lower === 'employee name') {
                    $has_name = true;
                    $name_col = $col;
                }
            }
            
            if ($has_id && $has_name) {
                $this->debugLog[] = "Found header row at index $index with ID col=$id_col, NAME col=$name_col";
                return $index;
            }
        }
        
        // If we didn't find exact match, look for first row with ID-like values
        foreach ($rows as $index => $row_data) {
            foreach ($row_data as $col => $val) {
                // Check if this looks like an employee ID (e.g., T10014)
                if (preg_match('/^[A-Z]\d{4,}$/i', trim($val))) {
                    $this->debugLog[] = "Found data starting at index $index (detected employee ID pattern)";
                    return $index - 1; // Return previous row as header
                }
            }
        }
        
        return null;
    }

    private function readCSV($file_path) {
        $data = [];
        $rows = [];
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = array_map('trim', $row);
            }
            fclose($handle);
        }
        
        $this->debugLog[] = "CSV: Total raw rows read: " . count($rows);
        
        $header_row_index = $this->findHeaderRow($rows);
        
        if ($header_row_index === null) {
            $this->debugLog[] = "CSV: Could not find header row";
            return $data;
        }
        
        // Build headers
        $headers = [];
        $seen = [];
        foreach ($rows[$header_row_index] as $col_index => $value) {
            $header = $this->normalizeHeaderName($value);
            if ($header === '' || $header === '_') {
                $headers[$col_index] = 'col_' . $col_index;
                continue;
            }
            if (!isset($seen[$header])) {
                $seen[$header] = 1;
                $headers[$col_index] = $header;
            } else {
                $seen[$header]++;
                // For duplicate columns, append _amt for second occurrence (likely the amount column)
                if ($seen[$header] == 2 && strpos($header, '_hrs') !== false) {
                    $headers[$col_index] = str_replace('_hrs', '_amt', $header);
                } else {
                    $headers[$col_index] = $header . '_' . $seen[$header];
                }
            }
        }
        
        $this->debugLog[] = "CSV: Headers found: " . implode(', ', array_slice($headers, 0, 10)) . "...";
        
        // Parse data rows
        for ($row_index = $header_row_index + 1; $row_index < count($rows); $row_index++) {
            $row_data = $rows[$row_index];
            $parsed_row = [];
            
            foreach ($headers as $col_index => $header) {
                $parsed_row[$header] = $row_data[$col_index] ?? '';
            }
            
            $id_value = trim($parsed_row['id'] ?? $parsed_row['id_col'] ?? $parsed_row['id_no'] ?? $parsed_row['idno'] ?? '');
            $name_value = trim($parsed_row['name'] ?? '');
            
            if (empty($id_value) || empty($name_value)) {
                continue;
            }
            
            // Store id in standard field for downstream processing
            $parsed_row['id'] = $id_value;
            $data[] = $parsed_row;
        }
        
        $this->debugLog[] = "CSV: Parsed " . count($data) . " employee records";
        return $data;
    }
    
    private function readXLS($file_path) {
        // Try reading as XML spreadsheet first
        $content = file_get_contents($file_path);
        
        // Check if it's an XML-based format
        if (strpos($content, '<?xml') !== false || strpos($content, '<Workbook') !== false) {
            return $this->readXMLSpreadsheet($file_path);
        }
        
        // Otherwise, try CSV
        return $this->readCSV($file_path);
    }
    
    private function readXMLSpreadsheet($file_path) {
        $data = [];
        
        try {
            $xml = simplexml_load_file($file_path);
            if (!$xml) {
                $this->debugLog[] = "XLS: Could not parse XML";
                return $this->readCSV($file_path);
            }
            
            $namespaces = $xml->getNamespaces(true);
            $rows = [];
            
            // Try to find worksheets
            foreach ($xml->children($namespaces['ss'] ?? '') as $worksheet) {
                if ($worksheet->getName() === 'Worksheet') {
                    foreach ($worksheet->children($namespaces['ss'] ?? '') as $table) {
                        if ($table->getName() === 'Table') {
                            foreach ($table->children($namespaces['ss'] ?? '') as $row) {
                                if ($row->getName() === 'Row') {
                                    $row_data = [];
                                    $col_index = 0;
                                    foreach ($row->children($namespaces['ss'] ?? '') as $cell) {
                                        if ($cell->getName() === 'Cell') {
                                            $data_node = $cell->children($namespaces['ss'] ?? '')->Data;
                                            $row_data[$col_index] = (string)$data_node;
                                            $col_index++;
                                        }
                                    }
                                    $rows[] = $row_data;
                                }
                            }
                            break 2; // Only process first worksheet's first table
                        }
                    }
                }
            }
            
            $this->debugLog[] = "XLS XML: Raw rows: " . count($rows);
            
            if (empty($rows)) {
                return $this->readCSV($file_path);
            }
            
            // Process like CSV
            $header_row_index = $this->findHeaderRow($rows);
            if ($header_row_index === null) {
                return $data;
            }
            
            $headers = [];
            $seen = [];
            foreach ($rows[$header_row_index] as $col_index => $value) {
                $header = $this->normalizeHeaderName($value);
                if ($header === '') continue;
                if (!isset($seen[$header])) {
                    $seen[$header] = 1;
                    $headers[$col_index] = $header;
                } else {
                    $seen[$header]++;
                    $headers[$col_index] = $header . '_' . $seen[$header];
                }
            }
            
            for ($i = $header_row_index + 1; $i < count($rows); $i++) {
                $parsed_row = [];
                foreach ($headers as $col_index => $header) {
                    $parsed_row[$header] = $rows[$i][$col_index] ?? '';
                }
                
                $id_value = trim($parsed_row['id'] ?? $parsed_row['id_col'] ?? $parsed_row['id_no'] ?? $parsed_row['idno'] ?? '');
                $name_value = trim($parsed_row['name'] ?? '');
                
                if (!empty($id_value) && !empty($name_value)) {
                    $parsed_row['id'] = $id_value;
                    $data[] = $parsed_row;
                }
            }
            
        } catch (Exception $e) {
            $this->debugLog[] = "XLS XML Error: " . $e->getMessage();
            return $this->readCSV($file_path);
        }
        
        return $data;
    }
    
    private function readXLSX($file_path) {
        $data = [];
        
        try {
            $temp_dir = sys_get_temp_dir() . '/xlsx_' . time() . '_' . mt_rand();
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            $this->debugLog[] = "XLSX: Extracting to $temp_dir";
            
            $zip = new ZipArchive();
            $open_result = $zip->open($file_path);
            
            if ($open_result !== true) {
                $this->debugLog[] = "XLSX: Failed to open ZIP (error code: $open_result)";
                return $this->readCSV($file_path);
            }
            
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // Load shared strings
            $shared_strings = [];
            $shared_strings_file = $temp_dir . '/xl/sharedStrings.xml';
            if (file_exists($shared_strings_file)) {
                $shared_xml = simplexml_load_file($shared_strings_file);
                if ($shared_xml) {
                    foreach ($shared_xml->si as $si) {
                        $text = '';
                        if (isset($si->t)) {
                            $text = (string)$si->t;
                        } elseif (isset($si->r)) {
                            // Rich text - concatenate all text parts
                            foreach ($si->r as $r) {
                                if (isset($r->t)) {
                                    $text .= (string)$r->t;
                                }
                            }
                        }
                        $shared_strings[] = trim($text);
                    }
                }
                $this->debugLog[] = "XLSX: Loaded " . count($shared_strings) . " shared strings";
            }
            
            // READ ALL SHEETS and merge data by employee ID/NAME
            $all_sheets = $this->getAllWorksheets($temp_dir);
            $this->debugLog[] = "XLSX: Found " . count($all_sheets) . " sheets to process";
            
            // Store employee data keyed by ID or NAME
            $employee_data = [];
            
            foreach ($all_sheets as $sheet_name => $sheet_file) {
                if (!file_exists($sheet_file)) {
                    continue;
                }
                
                $xml = @simplexml_load_file($sheet_file);
                if (!$xml) {
                    continue;
                }
                
                $rows = [];
                foreach ($xml->sheetData->row as $row) {
                    $row_data = [];
                    
                    foreach ($row->c as $cell) {
                        $col_ref = (string)$cell['r'];
                        $col_index = $this->getColumnIndexFromReference($col_ref);
                        $value = '';
                        
                        if (isset($cell->v)) {
                            $cell_type = (string)($cell['t'] ?? '');
                            
                            if ($cell_type === 's') {
                                $shared_index = (int)$cell->v;
                                $value = $shared_strings[$shared_index] ?? '';
                            } elseif ($cell_type === 'str' || $cell_type === 'inlineStr') {
                                if (isset($cell->is->t)) {
                                    $value = (string)$cell->is->t;
                                } else {
                                    $value = (string)$cell->v;
                                }
                            } else {
                                $value = (string)$cell->v;
                            }
                        } elseif (isset($cell->is)) {
                            if (isset($cell->is->t)) {
                                $value = (string)$cell->is->t;
                            }
                        }
                        
                        $row_data[$col_index] = trim($value);
                    }
                    
                    if (!empty($row_data)) {
                        $max_col = max(array_keys($row_data));
                        for ($i = 0; $i <= $max_col; $i++) {
                            if (!isset($row_data[$i])) {
                                $row_data[$i] = '';
                            }
                        }
                        ksort($row_data);
                    }
                    
                    $rows[] = $row_data;
                }
                
                if (empty($rows)) {
                    continue;
                }
                
                // Find header row for this sheet
                $header_row_index = $this->findHeaderRowFlexible($rows);
                
                if ($header_row_index === null) {
                    $this->debugLog[] = "XLSX: Sheet '$sheet_name' - no header found, skipping";
                    continue;
                }
                
                // Build headers for this sheet
                $headers = [];
                $seen = [];
                foreach ($rows[$header_row_index] as $col_index => $value) {
                    $header = $this->normalizeHeaderName($value);
                    if ($header === '' || $header === '_') {
                        $headers[$col_index] = 'col_' . $col_index;
                        continue;
                    }
                    if (!isset($seen[$header])) {
                        $seen[$header] = 1;
                        $headers[$col_index] = $header;
                    } else {
                        $seen[$header]++;
                        if ($seen[$header] == 2 && strpos($header, '_hrs') !== false) {
                            $headers[$col_index] = str_replace('_hrs', '_amt', $header);
                        } else {
                            $headers[$col_index] = $header . '_' . $seen[$header];
                        }
                    }
                }
                
                // Log ALL headers for debugging
                $this->debugLog[] = "XLSX: Sheet '$sheet_name' - ALL " . count($headers) . " headers:";
                $this->debugLog[] = "  " . implode(', ', $headers);
                
                // Parse data rows from this sheet
                for ($row_index = $header_row_index + 1; $row_index < count($rows); $row_index++) {
                    $row_data = $rows[$row_index];
                    $parsed_row = [];
                    
                    foreach ($headers as $col_index => $header) {
                        $parsed_row[$header] = $row_data[$col_index] ?? '';
                    }
                    
                    // Try to identify employee by ID or NAME
                    $id_value = trim($parsed_row['id'] ?? $parsed_row['id_col'] ?? $parsed_row['id_no'] ?? $parsed_row['idno'] ?? '');
                    $name_value = trim($parsed_row['name'] ?? '');
                    
                    // Skip empty rows
                    if (empty($name_value)) {
                        continue;
                    }
                    
                    // Skip header-like rows
                    if (strtolower($name_value) === 'name' || strtolower($id_value) === 'id' || strtolower($id_value) === 'id no') {
                        continue;
                    }
                    
                    // Use ID if available, otherwise use NAME as key
                    $employee_key = !empty($id_value) ? $id_value : $name_value;
                    
                    // Initialize employee data if not exists
                    if (!isset($employee_data[$employee_key])) {
                        $employee_data[$employee_key] = [
                            'id' => $id_value,
                            'name' => $name_value,
                        ];
                    }
                    
                    // Merge data from this sheet (don't overwrite existing values with empty ones)
                    foreach ($parsed_row as $key => $value) {
                        if ($key === 'id' || $key === 'name' || $key === 'id_col' || $key === 'id_no' || $key === 'idno') {
                            continue;
                        }
                        if ($value !== '' && $value !== '-' && $value !== '0') {
                            $employee_data[$employee_key][$key] = $value;
                        } elseif (!isset($employee_data[$employee_key][$key])) {
                            $employee_data[$employee_key][$key] = $value;
                        }
                    }
                }
            }
            
            // Convert merged employee data to array format
            foreach ($employee_data as $key => $emp_row) {
                $id_val = $emp_row['id'] ?? '';
                $name_val = $emp_row['name'] ?? '';
                
                if (empty($name_val)) continue;
                
                // If no ID, try to find from the key
                if (empty($id_val) && preg_match('/^[A-Z]?\d+$/', $key)) {
                    $id_val = $key;
                }
                
                if (empty($id_val)) continue;
                
                $emp_row['id'] = $id_val;
                $data[] = $emp_row;
            }
            
            $this->debugLog[] = "XLSX: Merged " . count($data) . " employee records from all sheets";
            
            $this->removeDirectory($temp_dir);
            
        } catch (Exception $e) {
            $this->debugLog[] = "XLSX Error: " . $e->getMessage();
            if (isset($temp_dir) && is_dir($temp_dir)) {
                $this->removeDirectory($temp_dir);
            }
            return $this->readCSV($file_path);
        }
        
        return $data;
    }
    
    private function findWorksheet($temp_dir, $shared_strings = []) {
        // Default to sheet1
        $default_sheet = $temp_dir . '/xl/worksheets/sheet1.xml';
        
        $wb_file = $temp_dir . '/xl/workbook.xml';
        $rels_file = $temp_dir . '/xl/_rels/workbook.xml.rels';
        
        if (!file_exists($wb_file) || !file_exists($rels_file)) {
            return $default_sheet;
        }
        
        try {
            $wb = simplexml_load_file($wb_file);
            $rels = simplexml_load_file($rels_file);
            
            // Build a map of sheet names to file paths
            $sheet_map = [];
            foreach ($wb->sheets->sheet as $sheet) {
                $sheet_name = (string)$sheet['name'];
                
                // Get the relationship ID
                $rid = '';
                $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                if (isset($attrs['id'])) {
                    $rid = (string)$attrs['id'];
                }
                if (empty($rid)) {
                    $attrs = $sheet->attributes('r', true);
                    if (isset($attrs['id'])) {
                        $rid = (string)$attrs['id'];
                    }
                }
                
                if (!empty($rid)) {
                    foreach ($rels->Relationship as $rel) {
                        if ((string)$rel['Id'] === $rid) {
                            $target = (string)$rel['Target'];
                            $sheet_path = $temp_dir . '/xl/' . $target;
                            if (file_exists($sheet_path)) {
                                $sheet_map[$sheet_name] = $sheet_path;
                            }
                        }
                    }
                }
            }
            
            $this->debugLog[] = "Available sheets: " . implode(', ', array_keys($sheet_map));
            
            // Now scan each sheet to find one with BOTH ID (or ID NO) AND NAME columns
            $valid_sheets = [];
            
            foreach ($sheet_map as $sheet_name => $sheet_path) {
                $has_id = false;
                $has_name = false;
                
                // Quick scan of the sheet to check headers
                $xml = @simplexml_load_file($sheet_path);
                if (!$xml) continue;
                
                // Read first 10 rows to find headers
                $row_count = 0;
                foreach ($xml->sheetData->row as $row) {
                    if ($row_count++ >= 10) break;
                    
                    foreach ($row->c as $cell) {
                        $value = '';
                        if (isset($cell->v)) {
                            $cell_type = (string)($cell['t'] ?? '');
                            if ($cell_type === 's' && !empty($shared_strings)) {
                                $shared_index = (int)$cell->v;
                                $value = $shared_strings[$shared_index] ?? '';
                            } else {
                                $value = (string)$cell->v;
                            }
                        }
                        
                        $val_lower = strtolower(trim($value));
                        
                        // Check for ID variations
                        if ($val_lower === 'id' || $val_lower === 'id no' || $val_lower === 'id no.' || 
                            $val_lower === 'idno' || $val_lower === 'employee id' || $val_lower === 'emp id' ||
                            $val_lower === 'i.d' || $val_lower === 'i.d.') {
                            $has_id = true;
                        }
                        
                        // Check for NAME
                        if ($val_lower === 'name' || $val_lower === 'employee name' || $val_lower === 'emp name') {
                            $has_name = true;
                        }
                    }
                    
                    // If this row has both, we found a good sheet
                    if ($has_id && $has_name) {
                        $valid_sheets[$sheet_name] = $sheet_path;
                        $this->debugLog[] = "Sheet '$sheet_name' has both ID and NAME columns";
                        break;
                    }
                }
                
                // Log if sheet doesn't have required columns
                if (!isset($valid_sheets[$sheet_name])) {
                    $missing = [];
                    if (!$has_id) $missing[] = 'ID';
                    if (!$has_name) $missing[] = 'NAME';
                    $this->debugLog[] = "Sheet '$sheet_name' missing: " . implode(', ', $missing);
                }
            }
            
            // If we found sheets with both ID and NAME, prioritize them
            if (!empty($valid_sheets)) {
                // Prefer specific sheet names in order
                $preferred = ['UPLOADING', 'TOTAL NETPAY', 'NETPAY', 'DEDUCTION', 'EARNINGS', 'PAYROLL', 'DATA', 'SALARY'];
                
                foreach ($preferred as $pref) {
                    foreach ($valid_sheets as $name => $path) {
                        if (stripos($name, $pref) !== false) {
                            $this->debugLog[] = "Selected sheet: $name (matched preference '$pref')";
                            return $path;
                        }
                    }
                }
                
                // If no preference match, return first valid sheet
                reset($valid_sheets);
                $first_name = key($valid_sheets);
                $this->debugLog[] = "Selected first valid sheet: $first_name";
                return $valid_sheets[$first_name];
            }
            
            $this->debugLog[] = "No sheet found with both ID and NAME columns, using default";
        } catch (Exception $e) {
            $this->debugLog[] = "Error finding worksheet: " . $e->getMessage();
        }
        
        return $default_sheet;
    }
    
    private function getAllWorksheets($temp_dir) {
        $sheets = [];
        
        $wb_file = $temp_dir . '/xl/workbook.xml';
        $rels_file = $temp_dir . '/xl/_rels/workbook.xml.rels';
        
        if (!file_exists($wb_file) || !file_exists($rels_file)) {
            // Fallback: return sheet1 if exists
            $default = $temp_dir . '/xl/worksheets/sheet1.xml';
            if (file_exists($default)) {
                return ['Sheet1' => $default];
            }
            return $sheets;
        }
        
        try {
            $wb = simplexml_load_file($wb_file);
            $rels = simplexml_load_file($rels_file);
            
            foreach ($wb->sheets->sheet as $sheet) {
                $sheet_name = (string)$sheet['name'];
                
                $rid = '';
                $attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                if (isset($attrs['id'])) {
                    $rid = (string)$attrs['id'];
                }
                if (empty($rid)) {
                    $attrs = $sheet->attributes('r', true);
                    if (isset($attrs['id'])) {
                        $rid = (string)$attrs['id'];
                    }
                }
                
                if (!empty($rid)) {
                    foreach ($rels->Relationship as $rel) {
                        if ((string)$rel['Id'] === $rid) {
                            $target = (string)$rel['Target'];
                            $sheet_path = $temp_dir . '/xl/' . $target;
                            if (file_exists($sheet_path)) {
                                $sheets[$sheet_name] = $sheet_path;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->debugLog[] = "Error getting all worksheets: " . $e->getMessage();
        }
        
        return $sheets;
    }
    
    private function findHeaderRowFlexible($rows) {
        // Look for a row that contains NAME header (at minimum)
        // This is more flexible than findHeaderRow which requires both ID and NAME
        foreach ($rows as $index => $row_data) {
            $has_name = false;
            $has_id = false;
            $has_data_column = false; // Has recognizable payroll column
            
            foreach ($row_data as $val) {
                $val_lower = strtolower(trim($val));
                
                if ($val_lower === 'name' || $val_lower === 'employee name' || $val_lower === 'emp name') {
                    $has_name = true;
                }
                
                if ($val_lower === 'id' || $val_lower === 'id no' || $val_lower === 'idno' || 
                    $val_lower === 'i.d' || $val_lower === 'employee id') {
                    $has_id = true;
                }
                
                // Check for recognizable payroll columns
                if (preg_match('/^(sss|hdmf|hmo|reg days|rot|nd|rd|lh|shd|cnw|late|incentive|attendance|uniform|housing|loan|canteen|shoes|cash advance|amount|gross)/i', $val_lower)) {
                    $has_data_column = true;
                }
            }
            
            // Accept row as header if it has NAME, or has ID + data columns
            if ($has_name || ($has_id && $has_data_column)) {
                return $index;
            }
        }
        
        return null;
    }
    
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function importEmployees($file_path) {
        $this->debugLog = [];
        
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'File not found: ' . $file_path];
        }
        
        $errors = [];
        $added_count = 0;
        $updated_count = 0;
        
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $this->debugLog[] = "File extension: $extension";
        
        if (in_array($extension, ['xlsx', 'xltx', 'xlsm'])) {
            $data = $this->readXLSX($file_path);
        } elseif ($extension === 'xls') {
            // Try to read as XML spreadsheet (Excel 2003 XML format)
            $data = $this->readXLS($file_path);
        } else {
            $data = $this->readCSV($file_path);
        }
        
        $this->debugLog[] = "Data rows found: " . count($data);
        
        if (!$data || empty($data)) {
            $debug_info = implode("\n", $this->debugLog);
            return ['success' => false, 'message' => 'No data found in file. Debug: ' . $debug_info];
        }
        
        foreach ($data as $index => $row) {
            $line_num = $index + 1;
            
            try {
                // Try multiple ID field variations
                $id = trim($row['id'] ?? $row['id_col'] ?? $row['id_no'] ?? $row['idno'] ?? $row['employee_id'] ?? $row['emp_id'] ?? '');
                $name = trim($row['name'] ?? $row['employee_name'] ?? $row['full_name'] ?? '');
                
                if (empty($id) || empty($name)) {
                    $this->debugLog[] = "Line $line_num: Empty ID or Name";
                    continue;
                }
                
                // Clean up ID - remove any non-alphanumeric except underscore
                $id = preg_replace('/[^A-Za-z0-9_]/', '', $id);
                
                // Parse name from either comma or whitespace format
                if (strpos($name, ',') !== false) {
                    $parts = array_map('trim', explode(',', $name, 2));
                    $last_name = $parts[0];
                    $first_name = $parts[1] ?? '';
                } else {
                    $name_parts = preg_split('/\s+/', $name);
                    if (count($name_parts) > 1) {
                        $first_name = array_shift($name_parts);
                        $last_name = implode(' ', $name_parts);
                    } else {
                        $first_name = trim($name);
                        $last_name = '';
                    }
                }
                
                // Get additional employee fields
                $department = trim($row['department'] ?? $row['dept'] ?? '');
                $position = trim($row['position'] ?? $row['job_title'] ?? '');
                $client_company = trim($row['client_company'] ?? $row['client'] ?? $row['company'] ?? '');
                $email = trim($row['email'] ?? '');
                $phone = trim($row['phone'] ?? '');
                $date_hired_raw = trim($row['date_hired'] ?? $row['hire_date'] ?? $row['date_of_joining'] ?? '');
                $dob_raw = trim($row['dob'] ?? $row['date_of_birth'] ?? $row['birth_date'] ?? '');
                
                // Parse dates
                $date_of_joining = $this->parseDateValue($date_hired_raw);
                if (!empty($date_hired_raw) && $date_of_joining === null) {
                    $this->debugLog[] = "Line $line_num: Could not parse Date Hired value '{$date_hired_raw}'";
                }

                $dob = $this->parseDateValue($dob_raw);
                if (!empty($dob_raw) && $dob === null) {
                    $this->debugLog[] = "Line $line_num: Could not parse Date of Birth value '{$dob_raw}'";
                }
                
                if (empty($position) || empty($client_company) || empty($date_of_joining)) {
                    $errors[] = "Line $line_num ($id): Missing required field(s). Required: position, client company, date hired.";
                    $this->debugLog[] = "Line $line_num: Invalid required import values: position={$position}, client_company={$client_company}, date_hired={$date_hired_raw}";
                    continue;
                }
                
                // Check if employee exists
                $sql = "SELECT id FROM employees WHERE employee_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $employee_result = $stmt->get_result();
                
                if ($employee_result->num_rows === 0) {
                    // Add new employee
                    $employee_data = [
                        'employee_id' => $id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'department' => $department,
                        'position' => $position,
                        'phone' => $phone,
                        'dob' => $dob,
                        'date_of_joining' => $date_of_joining,
                        'client_company' => $client_company
                    ];
                    
                    $result = $this->employee->addEmployee($employee_data);
                    if ($result['success']) {
                        $added_count++;
                    } else {
                        $errors[] = "Line $line_num ($id): Could not create employee - " . ($result['message'] ?? 'Unknown error');
                        continue;
                    }
                } else {
                    $employee = $employee_result->fetch_assoc();
                    $employee_id = $employee['id'];
                    
                    // Update employee information
                    $update_sql = "UPDATE employees SET 
                        first_name = COALESCE(NULLIF(?, ''), first_name),
                        last_name = COALESCE(NULLIF(?, ''), last_name),
                        email = COALESCE(NULLIF(?, ''), email),
                        department = COALESCE(NULLIF(?, ''), department), 
                        position = COALESCE(NULLIF(?, ''), position),
                        phone = COALESCE(NULLIF(?, ''), phone),
                        dob = COALESCE(?, dob),
                        date_of_joining = COALESCE(?, date_of_joining),
                        client_company = COALESCE(NULLIF(?, ''), client_company)
                        WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_sql);
                    $update_stmt->bind_param("sssssssssi", 
                        $first_name, $last_name, $email, $department, $position, $phone, 
                        $dob, $date_of_joining, $client_company, $employee_id);
                    $update_stmt->execute();
                    $updated_count++;
                }
                
            } catch (Exception $e) {
                $errors[] = "Line $line_num: Exception - " . $e->getMessage();
                $this->debugLog[] = "Line $line_num exception: " . $e->getMessage();
            }
        }
        
        $message = "Import completed. Added: $added_count, Updated: $updated_count";
        if (!empty($errors)) {
            $message .= ". Errors: " . count($errors);
        }
        
        return [
            'success' => true,
            'message' => $message,
            'added_employees' => $added_count,
            'updated_employees' => $updated_count,
            'errors' => $errors,
            'debug' => $this->debugLog
        ];
    }
}
