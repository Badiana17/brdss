<?php
require_once 'config.php';
checkLogin();

$pageTitle = 'Dashboard - BRDSS';
$conn = getDBConnection();

// Get statistics
$totalResidents = $conn->query("SELECT COUNT(*) as count FROM residents WHERE status = 'Active'")->fetch_assoc()['count'];
$totalBeneficiaries = $conn->query("SELECT COUNT(DISTINCT resident_id) FROM resident_beneficiary WHERE is_active = 1")->fetch_assoc()['count'];
$totalAssistance = $conn->query("SELECT COUNT(*) as count FROM assistance_records WHERE YEAR(date_given) = YEAR(CURDATE())")->fetch_assoc()['count'];
$totalCategories = $conn->query("SELECT COUNT(*) as count FROM beneficiary_category WHERE is_active = 1")->fetch_assoc()['count'];

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Dashboard</h2>
    <p class="breadcrumb">Home / Dashboard</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Active Residents</h3>
        <div class="stat-number"><?php echo number_format($totalResidents); ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Total Beneficiaries</h3>
        <div class="stat-number"><?php echo number_format($totalBeneficiaries); ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Assistance This Year</h3>
        <div class="stat-number"><?php echo number_format($totalAssistance); ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Active Categories</h3>
        <div class="stat-number"><?php echo number_format($totalCategories); ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 1rem;">Recent Activity</h3>
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>User</th>
                <th>Activity</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT al.*, u.username 
                     FROM activity_log al 
                     JOIN users u ON al.user_id = u.user_id 
                     ORDER BY al.timestamp DESC 
                     LIMIT 15";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . date('M d, Y h:i A', strtotime($row['timestamp'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['activity']) . "</td>";
                    echo "<td>" . ($row['action_type'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>No activity yet</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
