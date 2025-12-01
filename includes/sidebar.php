<?php
$currentPage = basename($_SERVER['PHP_SELF']) ?? '';
$userRole = $_SESSION['role'] ?? 'Staff';
?>
<style>
    .sidebar {
        width: 250px;
        background-color: #2c3e50;
        color: white;
        min-height: 100vh;
        padding: 2rem 0;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
        box-shadow: 2px 0 4px rgba(0,0,0,0.1);
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
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        font-size: 0.95rem;
    }
    .sidebar-menu a:hover {
        background-color: #34495e;
        border-left-color: #3498db;
        padding-left: 2.25rem;
    }
    .sidebar-menu a.active {
        background-color: #34495e;
        border-left-color: #3498db;
        font-weight: 600;
    }
    .sidebar-menu a::before {
        margin-right: 0.75rem;
        display: inline-block;
    }
    .sidebar-divider {
        height: 1px;
        background-color: #34495e;
        margin: 1rem 0;
    }
    .sidebar-title {
        padding: 1rem 2rem 0.5rem;
        color: #95a5a6;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            max-width: 250px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <!-- Main Navigation -->
        <li class="sidebar-title">Menu</li>
        <li><a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="residents.php" class="<?php echo $currentPage === 'residents.php' ? 'active' : ''; ?>">👥 Residents</a></li>
        
        <li class="sidebar-divider"></li>
        
        <!-- Management -->
        <li class="sidebar-title">Management</li>
        <li><a href="beneficiary_category.php" class="<?php echo $currentPage === 'beneficiary_category.php' ? 'active' : ''; ?>">📋 Categories</a></li>
        <li><a href="resident_beneficiary.php" class="<?php echo $currentPage === 'resident_beneficiary.php' ? 'active' : ''; ?>">🎯 Beneficiaries</a></li>
        <li><a href="assistance_records.php" class="<?php echo $currentPage === 'assistance_records.php' ? 'active' : ''; ?>">🤝 Assistance</a></li>
        
        <li class="sidebar-divider"></li>
        
        <!-- Monitoring -->
        <li class="sidebar-title">Monitoring</li>
        <li><a href="activity_log.php" class="<?php echo $currentPage === 'activity_log.php' ? 'active' : ''; ?>">📝 Activity Log</a></li>
        <li><a href="backup_history.php" class="<?php echo $currentPage === 'backup_history.php' ? 'active' : ''; ?>">💾 Backups</a></li>
        
        <!-- Admin Only -->
        <?php if ($userRole === 'Super Admin'): ?>
            <li class="sidebar-divider"></li>
            <li class="sidebar-title">Administration</li>
            <li><a href="users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">👤 Users</a></li>
        <?php endif; ?>
    </ul>
</aside>