<style>
    .sidebar {
        width: 250px;
        background-color: #2c3e50;
        color: white;
        min-height: 100vh;
        padding: 2rem 0;
    }
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .sidebar-menu li {
        margin: 0;
    }
    .sidebar-menu a {
        display: block;
        color: white;
        text-decoration: none;
        padding: 1rem 2rem;
        transition: background 0.3s;
        border-left: 4px solid transparent;
    }
    .sidebar-menu a:hover {
        background-color: #34495e;
        border-left-color: #3498db;
    }
    .sidebar-menu a.active {
        background-color: #34495e;
        border-left-color: #3498db;
    }
    .sidebar-menu a i {
        margin-right: 0.5rem;
    }
</style>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>📊 Dashboard</a></li>
        <li><a href="residents.php" <?php echo basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'class="active"' : ''; ?>>👥 Residents</a></li>
        <li><a href="beneficiary_category.php" <?php echo basename($_SERVER['PHP_SELF']) == 'beneficiary_category.php' ? 'class="active"' : ''; ?>>📋 Categories</a></li>
        <li><a href="resident_beneficiary.php" <?php echo basename($_SERVER['PHP_SELF']) == 'resident_beneficiary.php' ? 'class="active"' : ''; ?>>🎯 Beneficiaries</a></li>
        <li><a href="assistance_records.php" <?php echo basename($_SERVER['PHP_SELF']) == 'assistance_records.php' ? 'class="active"' : ''; ?>>🤝 Assistance</a></li>
        <li><a href="activity_log.php" <?php echo basename($_SERVER['PHP_SELF']) == 'activity_log.php' ? 'class="active"' : ''; ?>>📝 Activity Log</a></li>
        <li><a href="backup_history.php" <?php echo basename($_SERVER['PHP_SELF']) == 'backup_history.php' ? 'class="active"' : ''; ?>>💾 Backups</a></li>
        <?php if ($_SESSION['role'] === 'Super Admin'): ?>
        <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>👤 Users</a></li>
        <?php endif; ?>
    </ul>
</aside>
