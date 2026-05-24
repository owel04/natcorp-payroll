<?php
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config.php';
require_once $base_path . '/includes/Auth.php';
require_once $base_path . '/includes/ExcelImport.php';

$auth = new Auth($conn);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';
$import_results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error';
    } else {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, ['xlsx', 'xltx', 'xls', 'csv'])) {
            $error = 'Only Excel (.xlsx, .xltx, .xls) and CSV files are allowed';
        } else {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = 'employees_' . date('YmdHis') . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $importer = new ExcelImport($conn);
                $import_results = $importer->importEmployees($filepath);

                if ($import_results['success']) {
                    $message = $import_results['message'];
                } else {
                    $error = $import_results['message'];
                }
            } else {
                $error = 'Failed to upload file';
            }
        }
    }
}

$current_page = 'upload';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Employees - Natcorp Payroll System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Upload Employees</h1>
                <p class="subtitle">Import employee data from Excel or CSV</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php if ($import_results && !empty($import_results['debug'])): ?>
                <div class="table-container" style="margin-top: 15px;">
                    <details>
                        <summary style="cursor: pointer; padding: 10px; font-weight: bold;">Debug Information</summary>
                        <pre style="padding: 15px; background: #f8f9fa; font-size: 12px; overflow-x: auto;"><?php 
                            foreach ($import_results['debug'] as $log) {
                                echo htmlspecialchars($log) . "\n";
                            }
                        ?></pre>
                    </details>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="form-container" style="max-width: 800px;">
            <h2>Upload Employee Excel File</h2>
            <p style="margin-bottom: 20px; color: var(--text-muted); font-size: 14px;">
                Upload an Excel or CSV file with employee records. The system will add new employees and update existing records.
            </p>
            <div style="margin-bottom: 16px;">
                <a href="download_employee_template.php" class="btn btn-secondary">Download Employee Template</a>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Excel File *</label>
                    <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                        <div class="file-upload-icon">📁</div>
                        <p class="file-upload-text">Click to upload or drag and drop</p>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">Excel files (.xlsx, .xltx, .xls) or CSV</p>
                        <input type="file" id="fileInput" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <strong>Excel Format Requirements:</strong>
                    </p>
                    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">
                        Your Excel file should contain employee information with the following columns. The system will intelligently map column names.
                    </p>
                    
                    <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <p style="font-weight: bold; color: #0369a1; margin-bottom: 10px;">REQUIRED COLUMNS:</p>
                        <p style="font-size: 11px; color: #0c4a6e;">
                            ID (Employee ID), NAME (Employee Name), POSITION, CLIENT COMPANY, DATE HIRED
                        </p>
                    </div>
                    
                    <div style="background: #f0fdf4; border: 1px solid #22c55e; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <p style="font-weight: bold; color: #166534; margin-bottom: 10px;">OPTIONAL COLUMNS:</p>
                        <p style="font-size: 11px; color: #14532d;">
                            Department, Email, Phone, Date of Birth
                        </p>
                    </div>
                    
                    <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px;">
                        <strong>Note:</strong> New employees will be created with default login credentials. Existing employees will be updated with the provided information.
                    </p>
                    <p style="font-size: 11px; color: var(--text-muted);">
                        Date fields can be provided as <strong>YYYY-MM-DD</strong>, <strong>DD/MM/YYYY</strong>, or <strong>MM/DD/YYYY</strong>. "Date Hired" is required; "Date of Birth" is optional.
                    </p>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success">Upload & Process</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <?php if ($import_results && $import_results['success']): ?>
            <div class="table-container" style="margin-top: 30px;">
                <h3>Import Results</h3>
                <table>
                    <tbody>
                        <tr>
                            <td><strong>Employees Added:</strong></td>
                            <td><?php echo $import_results['added_employees']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Employees Updated:</strong></td>
                            <td><?php echo $import_results['updated_employees']; ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if (!empty($import_results['errors'])): ?>
                    <div style="padding: 16px 24px;">
                        <p style="color: var(--red-600); font-weight: 600; margin-bottom: 8px;">Errors Encountered:</p>
                        <ul style="margin-left: 20px;">
                            <?php foreach ($import_results['errors'] as $err): ?>
                                <li style="margin-bottom: 5px; font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($import_results['debug'])): ?>
                    <div style="padding: 16px 24px; margin-top: 15px;">
                        <details open>
                            <summary style="cursor: pointer; font-weight: bold; color: var(--primary);">Debug Log (Column Mapping)</summary>
                            <pre style="padding: 15px; background: #1e293b; color: #e2e8f0; border-radius: 8px; font-size: 11px; overflow-x: auto; margin-top: 10px; max-height: 400px; overflow-y: auto;"><?php 
                                foreach ($import_results['debug'] as $log) {
                                    echo htmlspecialchars($log) . "\n";
                                }
                            ?></pre>
                        </details>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const fileInput = document.getElementById('fileInput');
        const fileUpload = document.querySelector('.file-upload');
        
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.style.borderColor = 'var(--blue-400)';
            fileUpload.style.backgroundColor = 'var(--blue-50)';
        });
        
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
        });
        
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.style.borderColor = '';
            fileUpload.style.backgroundColor = '';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const filename = e.target.files[0].name;
                fileUpload.querySelector('.file-upload-text').textContent = '📄 ' + filename;
            }
        });
    </script>
<script src="../assets/js/script.js"></script>
</body>
</html>
