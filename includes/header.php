<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BRDSS - Barangay Resident Data & Social Services'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }
        .main-wrapper {
            display: flex;
            width: 100%;
        }
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .top-nav {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .top-nav h1 {
            font-size: 1.5rem;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-info span {
            background-color: #34495e;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        .user-info a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background-color: #e74c3c;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .user-info a:hover {
            background-color: #c0392b;
        }
        .content {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .breadcrumb {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background-color: #229954;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background-color: #d68910;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-collapse: collapse;
        }
        table thead {
            background-color: #34495e;
            color: white;
        }
        table th, table td {
            padding: 1rem;
            text-align: left;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr:hover {
            background-color: #ecf0f1;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php include 'includes/sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="top-nav">
                <h1>🏘️ BRDSS System</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content">