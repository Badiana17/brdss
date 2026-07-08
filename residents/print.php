<?php
/**
 * residents/print.php — Print-friendly residents report.
 * Accepts same filter params as index.php.
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username = $_SESSION["username"] ?? "User";
$role     = $_SESSION["role"] ?? "admin_staff";

/* --- Filters (same as index.php) --- */
$category = trim($_GET["category"] ?? "All");
$gender   = trim($_GET["gender"] ?? "All");
$search   = trim($_GET["q"] ?? "");

$where  = ["deleted_at IS NULL"];
$params = [];
$types  = "";
$filterLabels = [];

$allowedCats = ["All","Student","Senior","PWD","None"];
if ($category !== "All" && in_array($category, $allowedCats, true)) {
    $where[]  = "beneficiary_category = ?";
    $params[] = $category;
    $types   .= "s";
    $filterLabels[] = "Category: $category";
}

$allowedGenders = ["All","Male","Female","Other"];
if ($gender !== "All" && in_array($gender, $allowedGenders, true)) {
    $where[]  = "gender = ?";
    $params[] = $gender;
    $types   .= "s";
    $filterLabels[] = "Gender: $gender";
}

if ($search !== "") {
    $where[] = "(last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR address LIKE ? OR barangay LIKE ? OR zone LIKE ? OR contact_no LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
    $types .= "sssssss";
    $filterLabels[] = "Search: $search";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$sql = "
    SELECT resident_id, first_name, middle_name, last_name, suffix,
           birthday, age, gender, civil_status, is_voter,
           address, barangay, zone, contact_no,
           beneficiary_category, status
    FROM residents
    $whereSql
    ORDER BY last_name ASC, first_name ASC
    LIMIT 2000
";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --- Log print action --- */
write_activity_log($conn, "PRINT", "residents", null, "Printed residents report (" . count($rows) . " records). Filters: " . (empty($filterLabels) ? "None" : implode(", ", $filterLabels)));

$filterSummary = empty($filterLabels) ? "All Residents" : implode(" | ", $filterLabels);
$printDate = date("F j, Y g:i A");

/* Count stats */
$activeCount   = count(array_filter($rows, fn($r) => ($r["status"] ?? "") === "Active"));
$voterCount    = count(array_filter($rows, fn($r) => (int)($r["is_voter"] ?? 0) === 1));
$maleCount     = count(array_filter($rows, fn($r) => ($r["gender"] ?? "") === "Male"));
$femaleCount   = count(array_filter($rows, fn($r) => ($r["gender"] ?? "") === "Female"));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Residents Report</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --rp-dark: #1B2B3A;
      --rp-accent: #2F5D8A;
      --rp-text: #202124;
      --rp-muted: #5f6368;
      --rp-border: #e0e0e0;
      --rp-bg-even: #f9fafb;
      --rp-bg-hover: #e8f0fe;
      --rp-green: #1e8e3e;
      --rp-green-bg: #e6f4ea;
      --rp-red: #d93025;
      --rp-red-bg: #fce8e6;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      background: #fff;
      color: var(--rp-text);
      font-size: 14px;
      line-height: 1.5;
    }

    /* --- Page Container --- */
    .print-page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 32px;
    }

    /* --- Header --- */
    .print-header {
      text-align: center;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 3px solid var(--rp-dark);
    }
    .print-header h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--rp-dark);
      margin: 0 0 4px;
    }
    .print-header p {
      color: var(--rp-muted);
      font-size: .9rem;
      margin: 0;
    }

    /* --- Meta Row --- */
    .print-meta {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      font-size: .85rem;
      color: var(--rp-muted);
      margin-bottom: 12px;
    }
    .print-meta strong { color: var(--rp-text); }

    /* --- Stats Strip --- */
    .print-stats {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .print-stat {
      background: #f1f3f4;
      border-radius: 8px;
      padding: 10px 18px;
      flex: 1;
      min-width: 120px;
      text-align: center;
    }
    .print-stat-label {
      font-size: .7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--rp-muted);
      margin-bottom: 2px;
    }
    .print-stat-value {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--rp-dark);
    }

    /* --- Action Bar (hidden on print) --- */
    .print-actions {
      text-align: center;
      margin-bottom: 24px;
    }
    .print-actions .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: .85rem;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all .15s ease;
    }
    .btn-print-primary { background: var(--rp-accent); color: #fff; }
    .btn-print-primary:hover { background: #1a5276; color: #fff; }
    .btn-print-outline { background: #fff; color: var(--rp-muted); border: 1px solid var(--rp-border); }
    .btn-print-outline:hover { background: var(--rp-bg-hover); color: var(--rp-accent); }

    /* --- Table --- */
    .print-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .82rem;
    }
    .print-table thead th {
      background: var(--rp-dark);
      color: #fff;
      padding: 10px 10px;
      text-align: left;
      font-weight: 600;
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .3px;
      white-space: nowrap;
    }
    .print-table tbody td {
      padding: 7px 10px;
      border-bottom: 1px solid var(--rp-border);
      vertical-align: middle;
      white-space: nowrap;
    }
    .print-table tbody tr:nth-child(even) { background: var(--rp-bg-even); }
    .print-table tbody tr:hover { background: var(--rp-bg-hover); }

    /* Color-coded column groups */
    .col-id { background: rgba(224,237,255,.35) !important; }
    .col-demo { background: rgba(240,230,255,.3) !important; }
    .col-loc { background: rgba(255,242,230,.3) !important; }
    .col-cls { background: rgba(230,250,235,.3) !important; }

    /* --- Badges --- */
    .badge-s {
      display: inline-block;
      font-size: .7rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 10px;
    }
    .badge-active   { background: var(--rp-green-bg); color: var(--rp-green); }
    .badge-inactive { background: var(--rp-red-bg); color: var(--rp-red); }
    .badge-yes { background: var(--rp-green-bg); color: var(--rp-green); }
    .badge-no  { background: #f1f3f4; color: var(--rp-muted); }

    /* --- Footer --- */
    .print-footer {
      margin-top: 24px;
      border-top: 1px solid var(--rp-border);
      padding-top: 12px;
      font-size: .78rem;
      color: #999;
      text-align: center;
    }

    /* --- Empty --- */
    .print-empty {
      text-align: center;
      padding: 48px 20px;
      color: var(--rp-muted);
    }
    .print-empty i { font-size: 48px; opacity: .25; display: block; margin-bottom: 8px; }

    /* ======================== PRINT MEDIA ======================== */
    @media print {
      .print-actions { display: none !important; }

      body { font-size: 9pt; }
      .print-page { padding: 0; max-width: 100%; }

      .print-header {
        border-bottom: 2px solid #000;
      }

      .print-table thead th {
        background: var(--rp-dark) !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .print-table tbody tr:nth-child(even) {
        background: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .print-table tbody tr:hover { background: none !important; }

      .col-id, .col-demo, .col-loc, .col-cls {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .badge-s, .print-stat {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .print-stats { gap: 6px; }
      .print-stat { padding: 6px 10px; }
      .print-stat-value { font-size: 1.1rem; }
    }
  </style>
</head>
<body>

<div class="print-page">

  <!-- Header -->
  <div class="print-header">
    <div style="display:flex; align-items:center; justify-content:center; gap:16px;">
      <img src="../assets/brgylogo.jpg" alt="Barangay Logo" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid var(--rp-dark);">
      <div style="text-align:left;">
        <h1><i class="bi bi-people-fill" style="margin-right:8px;"></i>Residents Master List</h1>
        <p>Barangay Records and Distribution Support System (BRDSS)</p>
      </div>
    </div>
  </div>

  <!-- Meta -->
  <div class="print-meta">
    <div><strong>Filters:</strong> <?= h($filterSummary) ?></div>
    <div><strong>Generated:</strong> <?= h($printDate) ?> by <?= h($username) ?></div>
  </div>

  <!-- Stats -->
  <div class="print-stats">
    <div class="print-stat">
      <div class="print-stat-label">Total Residents</div>
      <div class="print-stat-value"><?= count($rows) ?></div>
    </div>
    <div class="print-stat">
      <div class="print-stat-label">Active</div>
      <div class="print-stat-value" style="color:var(--rp-green)"><?= $activeCount ?></div>
    </div>
    <div class="print-stat">
      <div class="print-stat-label">Male</div>
      <div class="print-stat-value"><?= $maleCount ?></div>
    </div>
    <div class="print-stat">
      <div class="print-stat-label">Female</div>
      <div class="print-stat-value"><?= $femaleCount ?></div>
    </div>
    <div class="print-stat">
      <div class="print-stat-label">Voters</div>
      <div class="print-stat-value"><?= $voterCount ?></div>
    </div>
  </div>

  <div style="margin:-8px 0 14px; text-align:center; color:var(--rp-muted); font-size:.8rem;">
    Count excludes soft-deleted residents.
  </div>

  <!-- Actions (screen only) -->
  <div class="print-actions">
    <button class="btn btn-print-primary" onclick="window.print()">
      <i class="bi bi-printer-fill"></i> Print Report
    </button>
    <button class="btn btn-print-outline" onclick="window.close()" style="margin-left:8px;">
      <i class="bi bi-x-lg"></i> Close
    </button>
    <a class="btn btn-print-outline" href="index.php" style="margin-left:8px;">
      <i class="bi bi-arrow-left"></i> Back to Residents
    </a>
  </div>

  <!-- Table -->
  <table class="print-table">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th>Status</th>
        <th class="col-id">Last Name</th>
        <th class="col-id">First Name</th>
        <th class="col-id">Middle</th>
        <th class="col-id">Suffix</th>
        <th class="col-demo">Gender</th>
        <th class="col-demo">Age</th>
        <th class="col-demo">Birthday</th>
        <th class="col-demo">Civil Status</th>
        <th class="col-demo">Voter</th>
        <th class="col-loc">Contact</th>
        <th class="col-loc">Address</th>
        <th class="col-loc">Barangay</th>
        <th class="col-loc">Zone</th>
        <th class="col-cls">Category</th>
      </tr>
    </thead>
    <tbody>
    <?php if (count($rows) === 0): ?>
      <tr>
        <td colspan="16">
          <div class="print-empty">
            <i class="bi bi-inbox"></i>
            No residents found matching the selected filters.
          </div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td>
            <?php if (($r["status"] ?? "") === "Active"): ?>
              <span class="badge-s badge-active">Active</span>
            <?php else: ?>
              <span class="badge-s badge-inactive">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="col-id"><strong><?= h($r["last_name"] ?? "") ?></strong></td>
          <td class="col-id"><?= h($r["first_name"] ?? "") ?></td>
          <td class="col-id"><?= h($r["middle_name"] ?? "") ?></td>
          <td class="col-id"><?= h($r["suffix"] ?? "") ?></td>
          <td class="col-demo"><?= h($r["gender"] ?? "") ?></td>
          <td class="col-demo"><?= h($r["age"] ?? "") ?></td>
          <td class="col-demo"><?= h($r["birthday"] ?? "") ?></td>
          <td class="col-demo"><?= h($r["civil_status"] ?? "") ?></td>
          <td class="col-demo">
            <?php if ((int)($r["is_voter"] ?? 0) === 1): ?>
              <span class="badge-s badge-yes">Yes</span>
            <?php else: ?>
              <span class="badge-s badge-no">No</span>
            <?php endif; ?>
          </td>
          <td class="col-loc"><?= h($r["contact_no"] ?? "") ?></td>
          <td class="col-loc"><?= h($r["address"] ?? "") ?></td>
          <td class="col-loc"><?= h($r["barangay"] ?? "") ?></td>
          <td class="col-loc"><?= h($r["zone"] ?? "") ?></td>
          <td class="col-cls"><?= h($r["beneficiary_category"] ?? "") ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- Footer -->
  <div class="print-footer">
    Generated by BRDSS on <?= h($printDate) ?> &mdash; Total: <?= count($rows) ?> resident(s)
  </div>

</div>
</body>
</html>