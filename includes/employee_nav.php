<?php
// Employee Top Navigation Bar
// Usage: include this file after auth check in employee pages
// Set $current_page before including (e.g., $current_page = 'dashboard';)
$current_page = $current_page ?? '';
$nav_display_name = isset($employee) ? htmlspecialchars($employee['first_name']) : htmlspecialchars($_SESSION['username']);
?>
<nav class="top-nav">
    <a href="dashboard.php" class="nav-brand">
        <img src="../assets/images/natcorp-logo.png" alt="Natcorp Logo" class="nav-logo">
        <span class="brand-name">NATCORP</span>
    </a>
    
    <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle navigation">☰</button>
    
    <ul class="nav-menu" id="navMenu">
        <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><span class="nav-icon">📊</span> Dashboard</a></li>
        <li><a href="payslips.php" class="<?php echo $current_page === 'payslips' ? 'active' : ''; ?>"><span class="nav-icon">📄</span> My Payslips</a></li>
        <li><a href="profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>"><span class="nav-icon">👤</span> My Profile</a></li>
    </ul>
    
    <div class="nav-user">
        <span class="username"><?php echo $nav_display_name; ?></span>
        <a href="https://www.facebook.com/natcorphermosa" target="_blank" rel="noopener noreferrer" class="nav-help" title="Contact admin on Facebook"> Need Help?</a>
        <a href="../logout.php" class="btn-logout" title="Sign out">🚪 Logout</a>
    </div>
</nav>
