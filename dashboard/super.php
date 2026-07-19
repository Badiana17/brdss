<?php
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

// COUNTS (CONNECTED TO DB)
$usersCount = 0;
$residentsCount = 0;
$aidCount = 0;

// ✅ Pie chart values
$studentsCount = 0;
$seniorCount = 0;
$pwdCount = 0;
$residentCount = 0;
$totalAssistedResidents = 0;

// ✅ ADDED ONLY: role-based display variables
$username = $_SESSION["username"] ?? "User";
$role = $_SESSION["role"] ?? "admin_staff";
$isSuperAdmin = ($role === "super_admin");
$displayRole = ucwords(str_replace("_", " ", $role));
$dashboardTitle = $isSuperAdmin ? "Super Admin Dashboard" : "Admin Dashboard";

// YEAR FILTER SETUP (ADDED ONLY)
$availableYears = [];
$selectedYear = isset($_GET["year"]) ? trim($_GET["year"]) : "";
$selectedYearLabel = "All Years";
$yearDateColumn = "";

// Detect usable date column from aid_distribution
$possibleDateColumns = ["distributed_at"];
$columnsResult = $conn->query("SHOW COLUMNS FROM aid_distribution");

if ($columnsResult) {
  $existingColumns = [];
  while ($col = $columnsResult->fetch_assoc()) {
    $existingColumns[] = $col["Field"];
  }

  foreach ($possibleDateColumns as $possibleCol) {
    if (in_array($possibleCol, $existingColumns, true)) {
      $yearDateColumn = $possibleCol;
      break;
    }
  }
}

// Build available years from detected date column
if ($yearDateColumn !== "") {
  $sqlYears = "
    SELECT DISTINCT YEAR($yearDateColumn) AS yr
    FROM aid_distribution
    WHERE $yearDateColumn IS NOT NULL
    ORDER BY yr DESC
  ";
  $qYears = $conn->query($sqlYears);

  if ($qYears) {
    while ($row = $qYears->fetch_assoc()) {
      if (!empty($row["yr"])) {
        $availableYears[] = (string)$row["yr"];
      }
    }
  }
}

// Validate selected year
if ($selectedYear !== "" && !preg_match('/^\d{4}$/', $selectedYear)) {
  $selectedYear = "";
}

if ($selectedYear !== "") {
  $selectedYearLabel = $selectedYear;
}

// Build dynamic WHERE for year filter
$yearFilterSql = "";
if ($yearDateColumn !== "" && $selectedYear !== "") {
  $safeSelectedYear = (int)$selectedYear;
  $yearFilterSql = " AND YEAR(a.$yearDateColumn) = $safeSelectedYear ";
}

// ✅ Users Count
$qUsers = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($qUsers) {
  $usersCount = (int)($qUsers->fetch_assoc()["c"] ?? 0);
}

// ✅ Residents Count (exclude deleted)
$qResidents = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE deleted_at IS NULL");
if ($qResidents) {
  $residentsCount = (int)($qResidents->fetch_assoc()["c"] ?? 0);
}

// ✅ Aid Records Count
$qAid = $conn->query("SELECT COUNT(*) AS c FROM aid_distribution");
if ($qAid) {
  $aidCount = (int)($qAid->fetch_assoc()["c"] ?? 0);
}

// ✅ Total Assisted Residents (distinct beneficiaries, RECEIVED only, year-filtered if available)
$sqlTotalAssisted = "
  SELECT COUNT(DISTINCT a.beneficiary_id) AS c
  FROM aid_distribution a
  INNER JOIN residents r ON r.resident_id = a.beneficiary_id
  WHERE a.status = 'Received'
    AND r.deleted_at IS NULL
  $yearFilterSql
";
$qTotalAssisted = $conn->query($sqlTotalAssisted);
if ($qTotalAssisted) {
  $totalAssistedResidents = (int)($qTotalAssisted->fetch_assoc()["c"] ?? 0);
}

// ✅ Pie breakdown by category (Residents table category, RECEIVED only, year-filtered if available)
$sqlPie = "
  SELECT r.beneficiary_category AS cat,
         COUNT(DISTINCT a.beneficiary_id) AS total
  FROM aid_distribution a
  INNER JOIN residents r ON r.resident_id = a.beneficiary_id
  WHERE a.status = 'Received'
    AND r.deleted_at IS NULL
    $yearFilterSql
  GROUP BY r.beneficiary_category
";

$qPie = $conn->query($sqlPie);
if ($qPie) {
  while ($row = $qPie->fetch_assoc()) {
    $cat = $row["cat"] ?? "";
    $val = (int)($row["total"] ?? 0);

    if ($cat === "Student") $studentsCount = $val;
    elseif ($cat === "Senior") $seniorCount = $val;
    elseif ($cat === "PWD") $pwdCount = $val;
    elseif ($cat === "None") $residentCount = $val;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | <?= htmlspecialchars($dashboardTitle) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .layout { display:flex; height:100vh; }
    .side{
      width: 280px;
      background: linear-gradient(180deg, #1B2B3A 0%, #243B53 100%);
      color:#fff;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
    }
    .side a{ color: rgba(255,255,255,.85); text-decoration:none; }
    .side a:hover{ color:#fff; }
    .side .nav-link{
      border-radius: 8px;
      padding: 10px 12px;
    }
    .side .nav-link.active,
    .side .nav-link:hover{
      background: rgba(255,255,255,.10);
    }
    .content{
      flex:1;
      background:#f6f7fb;
      height:100vh;
      overflow-y:auto;
    }
    .topbar{
      background:#fff;
      border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .stat-card, .tile-card { border-radius: 8px; }
    .badge-soft{
      background: rgba(47,93,138,.12);
      color: #2F5D8A;
      border: 1px solid rgba(47,93,138,.18);
    }
    .tile-card{
      border: 1px solid rgba(0,0,0,.06);
      transition: transform .12s ease, box-shadow .12s ease;
      overflow:hidden;
    }
    .tile-card:hover{
      transform: translateY(-2px);
      box-shadow: 0 12px 26px rgba(0,0,0,.10);
    }
    .tile-icon-wrap{
      height: 140px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: #fff;
    }
    .tile-icon{
      font-size: 70px;
      color: #2F5D8A;
      line-height: 1;
    }
    .tile-title{
      font-weight: 800;
      color: #1B2B3A;
      font-size: 1.25rem;
    }
    .tile-desc{
      color: #6b7280;
      font-size: .95rem;
      min-height: 44px;
    }
    .tile-btn{
      border-top: 1px solid rgba(0,0,0,.06);
      padding: 14px;
      background:#fff;
    }
    .tile-btn a{
      border-radius: 3px;
      padding: 12px 14px;
    }
    .soft-box { border-radius: 8px; }
  </style>
</head>

<body>
<div class="layout">
  <!-- SIDEBAR -->
  <aside class="side p-3">
    <div class="d-flex align-items-center gap-2 mb-3">
      <div class="brdss-logo"></div>
      <div>
        <div class="fw-bold">BRDSS</div>
        <div class="small opacity-75"><?= htmlspecialchars($_SESSION["username"] ?? "User") ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link active" href="super.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link" href="../aid/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>

      <?php if ($isSuperAdmin): ?>
        <a class="nav-link" href="../backups/index.php"><i class="bi bi-database-fill-gear me-2"></i>Backups</a>
        <a class="nav-link" href="../users/index.php"><i class="bi bi-shield-lock-fill me-2"></i>Users</a>
      <?php endif; ?>
    </nav>

    <hr class="border-light opacity-25 my-3">

    <a class="btn btn-outline-light w-100" href="../auth/logout.php">
      <i class="bi bi-box-arrow-right me-2"></i>Logout
    </a>

    <div class="small opacity-50 mt-3">
      Works offline via LAN (XAMPP + MySQL)
    </div>
  </aside>

  <!-- CONTENT -->
  <main class="content">
    <!-- TOPBAR -->
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-bold fs-4"><?= htmlspecialchars($dashboardTitle) ?></div>
        <div class="small text-muted">Overview and management of system metrics.</div>
      </div>
      <span class="badge badge-soft rounded-pill px-3 py-2">
        Role: <?= htmlspecialchars($displayRole) ?>
      </span>
    </div>

    <div class="container-fluid px-4 py-4">
      <!-- STATS -->
      <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
          <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
              <div class="small text-muted">Total Residents</div>
              <div class="display-6 fw-bold"><?= $residentsCount ?></div>
              <div class="small text-muted">Residents (excluding soft-deleted).</div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
              <div class="small text-muted">Aid Records</div>
              <div class="display-6 fw-bold"><?= $aidCount ?></div>
              <div class="small text-muted">All aid distribution rows.</div>
            </div>
          </div>
        </div>

        <?php if ($isSuperAdmin): ?>
        <div class="col-12 col-md-4">
          <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
              <div class="small text-muted">User Accounts</div>
              <div class="display-6 fw-bold"><?= $usersCount ?></div>
              <div class="small text-muted">Active system users.</div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- PIE CHART -->
      <div class="row g-3 mb-3">
        <div class="col-12">
          <div class="card shadow-sm border-0" style="border-radius:8px;">
            <div class="card-body">
              <div class="d-flex flex-wrap align-items-center justify-content-between mb-1 gap-2">
                <div class="fw-bold">Aid Beneficiaries Breakdown</div>

                <div class="d-flex align-items-center gap-2">
                  <form method="GET" class="d-flex align-items-center gap-2 mb-0">
                    <label for="yearFilter" class="small text-muted mb-0">Year</label>
                    <select
                      name="year"
                      id="yearFilter"
                      class="form-select form-select-sm"
                      style="min-width: 130px;"
                      onchange="this.form.submit()"
                    >
                      <option value="">All Years</option>
                      <?php foreach ($availableYears as $yr): ?>
                        <option value="<?= htmlspecialchars($yr) ?>" <?= $selectedYear === $yr ? "selected" : "" ?>>
                          <?= htmlspecialchars($yr) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </form>

                  <div class="small text-muted">
                    Received only
                  </div>
                </div>
              </div>

              <div class="small text-muted mb-3">
                Shows distribution of assisted residents by category: Students, Seniors, PWD, and Residents.
                <span class="ms-1">
                  (Showing: <b><?= htmlspecialchars($selectedYearLabel) ?></b>)
                </span>
              </div>

              <div class="row g-3 align-items-center">
                <div class="col-12 col-lg-7">
                  <div style="width: 100%; height: 300px;">
                    <canvas id="beneficiaryPie"></canvas>
                  </div>
                </div>
                <div class="col-12 col-lg-5">
                  <div class="p-3 bg-light soft-box">
                    <div class="small text-muted">Total Assisted Residents</div>
                    <div class="h2 fw-bold mb-0"><?= $totalAssistedResidents ?></div>
                    <div class="small text-muted mt-2">
                      Counted as DISTINCT beneficiaries with status = <b>Received</b>
                      <?php if ($selectedYear !== ""): ?>
                        for year <b><?= htmlspecialchars($selectedYear) ?></b>.
                      <?php else: ?>
                        across <b>all years</b>.
                      <?php endif; ?>
                    </div>

                    <?php if ($yearDateColumn === ""): ?>
                      <div class="small text-muted mt-2">
                        *Year filter is ready, but no supported date column was detected yet in <b>aid_distribution</b>.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-bold">Quick Actions</div>
        <div class="small text-muted">
          <?= $isSuperAdmin ? "Super Admin has full access." : "Admin Staff access only." ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card tile-card shadow-sm">
            <div class="tile-icon-wrap">
              <i class="bi bi-people-fill tile-icon"></i>
            </div>
            <div class="p-3 text-center">
              <div class="tile-title">Residents</div>
              <div class="tile-desc mt-1">Add, update, search, and manage resident records.</div>
            </div>
            <div class="tile-btn">
              <a class="btn brdss-btn w-100" href="../residents/index.php">Residents</a>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="card tile-card shadow-sm">
            <div class="tile-icon-wrap">
              <i class="bi bi-box-seam-fill tile-icon"></i>
            </div>
            <div class="p-3 text-center">
              <div class="tile-title">Aid Distribution</div>
              <div class="tile-desc mt-1">Record and monitor distribution history per category.</div>
            </div>
            <div class="tile-btn">
              <a class="btn brdss-btn w-100" href="../aid/index.php">Aid Distribution</a>
            </div>
          </div>
        </div>

        <?php if ($isSuperAdmin): ?>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="card tile-card shadow-sm">
            <div class="tile-icon-wrap">
              <i class="bi bi-database-fill-gear tile-icon"></i>
            </div>
            <div class="p-3 text-center">
              <div class="tile-title">Backups</div>
              <div class="tile-desc mt-1">Export database and restore records safely (offline).</div>
            </div>
            <div class="tile-btn">
              <a class="btn brdss-btn w-100" href="../backups/index.php">Backups</a>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <div class="card tile-card shadow-sm">
            <div class="tile-icon-wrap">
              <i class="bi bi-shield-lock-fill tile-icon"></i>
            </div>
            <div class="p-3 text-center">
              <div class="tile-title">Users</div>
              <div class="tile-desc mt-1">Create/edit accounts and assign roles & access.</div>
            </div>
            <div class="tile-btn">
              <a class="btn brdss-btn w-100" href="../users/index.php">Users</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="small text-muted mt-4">
        <?= $isSuperAdmin
          ? "Note: Super Admin can access all modules including Users and Backups."
          : "Note: Admin Staff can access Residents and Aid Distribution only."; ?>
      </div>
    </div>
  </main>
</div>

<script>
const beneficiaryLabels = ["Students", "Seniors", "PWD", "Residents"];
const rawValues = [
  <?= (int)$studentsCount ?>,
  <?= (int)$seniorCount ?>,
  <?= (int)$pwdCount ?>,
  <?= (int)$residentCount ?>
];

// Calculate total to convert to percentages
const total = rawValues.reduce((sum, val) => sum + val, 0);
let percentageValues = [0, 0, 0, 0];

if (total > 0) {
  // Map raw values to rounded percentages
  percentageValues = rawValues.map(val => Math.round((val / total) * 100));
  
  // Ensure exact 100% total by adjusting the largest value for any rounding discrepancies
  const currentTotal = percentageValues.reduce((sum, val) => sum + val, 0);
  const diff = 100 - currentTotal;
  
  if (diff !== 0) {
    const maxIdx = percentageValues.indexOf(Math.max(...percentageValues));
    percentageValues[maxIdx] += diff;
  }
}

new Chart(document.getElementById("beneficiaryPie"), {
  type: "pie",
  data: {
    labels: beneficiaryLabels,
    datasets: [{
      data: percentageValues,
      rawCounts: rawValues, // Retain raw counts for tooltip
      backgroundColor: ["#2F5D8A", "#4C7EA8", "#6FA3C9", "#9BC3E2"],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { 
        position: "bottom",
        labels: {
          padding: 20,
          usePointStyle: true,
          font: { size: 12 }
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.label || '';
            let percent = context.parsed || 0;
            return `${label}: ${percent}%`;
          }
        }
      }
    }
  }
});
</script>

</body>
</html>