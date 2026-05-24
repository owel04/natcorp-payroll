<?php
// Downloadable employee upload template for Natcorp Payroll System
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employee_upload_template.csv"');

$handle = fopen('php://output', 'w');
if ($handle) {
    fputcsv($handle, ['Employee ID', 'Name', 'Position', 'Department', 'Client Company', 'Date Hired', 'Email', 'Phone', 'Date of Birth']);
    fputcsv($handle, ['E1001', 'CORDIAL, RODELLA', 'ASSY ASSOCIATE', 'PRODUCTION', 'SPWS', '2023-01-31', 'rodella@example.com', '09171234567', '1990-05-10']);
    fclose($handle);
}
exit;
