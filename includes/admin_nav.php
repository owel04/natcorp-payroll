<?php
// Admin Top Navigation Bar
// Usage: include this file after auth check in admin pages
// Set $current_page before including (e.g., $current_page = 'dashboard';)
$current_page = $current_page ?? '';
?>
<nav class="top-nav">
    <a href="admin_dashboard.php" class="nav-brand">
        <img src="../assets/images/natcorp-logo.png" alt="Natcorp Logo" class="nav-logo">
        <span class="brand-name">NATCORP</span>
    </a>
    
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle navigation">☰</button>
    
    <ul class="nav-menu" id="navMenu">
        <li><a href="admin_dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon">📊</span> Dashboard</a></li>
        <li><a href="employees.php" class="<?php echo $current_page === 'employees' ? 'active' : ''; ?>"><span class="nav-icon">👥</span> Employees/Admins</a></li>
        <li><a href="upload_employee.php" class="<?php echo $current_page === 'upload' ? 'active' : ''; ?>"><span class="nav-icon">📁</span> Upload Employees</a></li>
        <li><a href="manage_payroll.php" class="<?php echo $current_page === 'manage' ? 'active' : ''; ?>"><span class="nav-icon">💼</span> Payroll</a></li>
        <li><a href="generate_payslips.php" class="<?php echo $current_page === 'payslips' ? 'active' : ''; ?>"><span class="nav-icon">📄</span> Payslips</a></li>
        <li><a href="reports.php" class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>"><span class="nav-icon">📈</span> Reports</a></li>
    </ul>
    
    <div class="nav-user">
        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <a href="../logout.php" class="btn-logout" title="Sign out">🚪 Logout</a>
    </div>
</nav>
