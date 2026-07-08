<?php
/**
 * aid/print.php — Print-friendly report for aid distribution records.
 *
 * Accepts same filter params as list.php.
 * Renders a clean HTML page optimized for printing.
 * Logs print action to activity_log.
 */
require_once "../config/auth.php";
require_role(["admin_staff", "super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

function format_print_date(?string $value): string {
  if (!$value) {
    return "—";
  }

  $date = DateTime::createFromFormat("Y-m-d H:i:s", $value) ?: new DateTime($value);
  return $date->format("F j, Y");
}

$redirectError = "Select a specific Aid Program and Beneficiary Type before printing.";

$username = $_SESSION["username"] ?? "User";
$role     = $_SESSION["role"] ?? "admin_staff";

/* --- Parse filters (same as list.php) --- */
$aid_type_id     = isset($_GET["aid_type_id"]) ? (int)$_GET["aid_type_id"] : 0;
$beneficiary_type = trim($_GET["beneficiary_type"] ?? "");
$status          = trim($_GET["status"] ?? "");
$date_from       = trim($_GET["date_from"] ?? "");
$date_to         = trim($_GET["date_to"] ?? "");
$keyword         = trim($_GET["q"] ?? "");

/* --- Build WHERE conditions --- */
$where  = ["1=1"];
$params = [];
$types  = "";
$filterLabels = [];

if ($aid_type_id > 0) {
    $where[]  = "d.aid_type_id = ?";
    $params[] = $aid_type_id;
    $types   .= "i";
}

$allowedBeneficiary = ["Resident", "Student", "Senior", "PWD"];
if ($beneficiary_type !== "" && in_array($beneficiary_type, $allowedBeneficiary, true)) {
    $where[]  = "d.beneficiary_type = ?";
    $params[] = $beneficiary_type;
    $types   .= "s";
    $filterLabels[] = "Type: $beneficiary_type";
}

  if ($aid_type_id <= 0 && $beneficiary_type === "") {
    header("Location: index.php?tab=records&error=" . urlencode($redirectError));
    exit;
  }

$allowedStatus = ["Pending", "Received", "Cancelled"];
if ($status !== "" && in_array($status, $allowedStatus, true)) {
    $where[]  = "d.status = ?";
    $params[] = $status;
    $types   .= "s";
    $filterLabels[] = "Status: $status";
}

if ($date_from !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where[]  = "d.distributed_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types   .= "s";
    $filterLabels[] = "From: $date_from";
}

if ($date_to !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where[]  = "d.distributed_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types   .= "s";
    $filterLabels[] = "To: $date_to";
}

if ($keyword !== "") {
    $where[]  = "(r.last_name LIKE ? OR r.first_name LIKE ? OR r.middle_name LIKE ? OR at.aid_name LIKE ? OR d.remarks LIKE ?)";
    $like     = "%" . $keyword . "%";
    array_push($params, $like, $like, $like, $like, $like);
    $types   .= "sssss";
    $filterLabels[] = "Keyword: $keyword";
}

$whereSql = implode(" AND ", $where);

/* --- Fetch records (no pagination for print) --- */
$sql = "
    SELECT
    r.resident_id,
    CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) AS beneficiary_name,
    r.address,
    r.barangay,
    r.contact_no,
        d.beneficiary_type,
    GROUP_CONCAT(DISTINCT at.aid_name ORDER BY at.aid_name SEPARATOR ', ') AS aid_names,
    GROUP_CONCAT(DISTINCT COALESCE(DATE_FORMAT(d.distributed_at, '%M %e, %Y'), '—') ORDER BY d.distributed_at SEPARATOR ' | ') AS distributed_dates,
    MIN(d.distributed_at) AS first_distributed_at,
    MAX(d.distributed_at) AS last_distributed_at,
    GROUP_CONCAT(DISTINCT NULLIF(TRIM(d.remarks), '') ORDER BY d.distributed_at SEPARATOR ' | ') AS remarks_list
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    WHERE $whereSql
  GROUP BY r.resident_id, beneficiary_name, r.address, r.barangay, r.contact_no, d.beneficiary_type
  ORDER BY beneficiary_name ASC, MIN(at.aid_name) ASC
    LIMIT 2000
";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --- Log print action --- */
write_activity_log($conn, "PRINT", "aid_distribution", null, "Printed grouped aid distribution report (" . count($rows) . " beneficiaries). Filters: " . (empty($filterLabels) ? "None" : implode(", ", $filterLabels)));

$filterSummary = empty($filterLabels) ? "All Records" : implode(" | ", $filterLabels);
$printDate = date("F j, Y g:i A");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Aid Distribution Report</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/aid_distribution.css" rel="stylesheet">

  <style>
    :root {
      --report-ink: #1b2b3a;
      --report-muted: #6b7280;
      --report-border: #e5e7eb;
      --report-soft: #f8fafc;
    }
    body {
      margin: 0;
      background: #fff;
      font-family: 'Segoe UI', system-ui, sans-serif;
      color: #111827;
    }
    .print-page {
      max-width: 1280px;
      margin: 0 auto;
      padding: 28px 32px 36px;
    }
    .print-page-header {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 4px;
      padding-bottom: 14px;
      border-bottom: 3px solid var(--report-ink);
      margin-bottom: 18px;
      text-align: center;
    }
    .print-page-header h1 {
      margin: 0;
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--report-ink);
      letter-spacing: .2px;
    }
    .print-page-header p {
      margin: 4px 0 0;
      color: var(--report-muted);
      font-size: .95rem;
    }
    .print-page-meta {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin: 0 0 18px;
    }
    .print-meta-card {
      border: 1px solid var(--report-border);
      border-radius: 12px;
      background: var(--report-soft);
      padding: 12px 14px;
      min-height: 72px;
    }
    .print-meta-label {
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .4px;
      color: var(--report-muted);
      font-weight: 700;
      margin-bottom: 5px;
    }
    .print-meta-value {
      font-size: .96rem;
      color: #111827;
      font-weight: 700;
      line-height: 1.35;
      word-break: break-word;
    }
    .print-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .82rem;
      table-layout: fixed;
    }
    .print-table th {
      background: var(--report-ink);
      color: #fff;
      padding: 10px 12px;
      text-align: left;
      font-weight: 700;
      font-size: .74rem;
      text-transform: uppercase;
      letter-spacing: .35px;
      white-space: nowrap;
    }
    .print-table td {
      padding: 9px 12px;
      border-bottom: 1px solid var(--report-border);
      vertical-align: top;
      word-break: break-word;
    }
    .print-table tbody tr:nth-child(even) { background: #fafafa; }
    .report-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: .7rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 999px;
      line-height: 1;
    }
    .report-badge-received { background: #e6f4ea; color: #137333; }
    .report-badge-pending { background: #fef7e0; color: #e37400; }
    .report-badge-cancelled { background: #fce8e6; color: #d93025; }
    .report-badge-locked { background: #fce8e6; color: #d93025; }
    .report-badge-unlocked { background: #e6f4ea; color: #1e8e3e; }
    .print-note {
      margin-top: 14px;
      padding-top: 12px;
      border-top: 1px solid var(--report-border);
      color: var(--report-muted);
      font-size: .8rem;
      text-align: center;
    }

    @media print {
      @page { size: auto; margin: 12mm; }
      .no-print { display: none !important; }
      body { font-size: 10pt; }
      .print-page { padding: 0; max-width: none; }
      .print-page-header,
      .print-page-meta,
      .report-badge,
      .print-table th,
      .print-table tbody tr:nth-child(even) {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .print-page-header { margin-bottom: 14px; }
      .print-page-meta { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .print-table { page-break-inside: auto; }
      .print-table tr { page-break-inside: avoid; page-break-after: auto; }
    }
  </style>
</head>
<body>

<div class="print-page">
  <!-- Header -->
  <div class="print-page-header" style="flex-direction:row; gap:16px;">
    <img src="../assets/brgylogo.jpg" alt="Barangay Logo" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid var(--report-ink);">
    <div style="text-align:left;">
      <h1><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution Report</h1>
      <p>Barangay Records and Distribution Support System (BRDSS)</p>
    </div>
  </div>

  <!-- Meta -->
  <div class="print-page-meta">
    <div class="print-meta-card">
      <div class="print-meta-label">Filters</div>
      <div class="print-meta-value"><?= h($filterSummary) ?></div>
    </div>
    <div class="print-meta-card">
      <div class="print-meta-label">Generated</div>
      <div class="print-meta-value"><?= h($printDate) ?> by <?= h($username) ?></div>
    </div>
    <div class="print-meta-card">
      <div class="print-meta-label">Total Records</div>
      <div class="print-meta-value"><?= count($rows) ?> beneficiaries</div>
    </div>
  </div>

  <!-- Actions (hidden on print) -->
  <div class="print-page-actions no-print">
    <button class="ad-btn ad-btn-primary" onclick="window.print()">
      <i class="bi bi-printer-fill"></i> Print Report
    </button>
    <button class="ad-btn ad-btn-outline ms-2" onclick="window.close()">
      <i class="bi bi-x-lg"></i> Close
    </button>
    <a class="ad-btn ad-btn-outline ms-2" href="index.php">
      <i class="bi bi-arrow-left"></i> Back to Distribution
    </a>
  </div>

  <!-- Table -->
  <table class="print-table">
    <thead>
      <tr>
        <th style="width:50px">#</th>
        <th>Beneficiary</th>
        <th>Type</th>
        <th>Aid Program</th>
        <th>Address</th>
        <th>Date</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
    <?php if (count($rows) === 0): ?>
      <tr><td colspan="6" style="text-align:center;padding:32px;color:#888;">No records found matching the selected filters.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= h($r["beneficiary_name"]) ?></td>
          <td><?= h($r["beneficiary_type"]) ?></td>
          <td><?= h($r["aid_names"] ?? "—") ?></td>
          <td><?= h(($r["address"] ?? "") . ", " . ($r["barangay"] ?? "")) ?></td>
          <td>
            <?php
              $firstDate = format_print_date($r["first_distributed_at"] ?? null);
              $lastDate  = format_print_date($r["last_distributed_at"] ?? null);
              echo h($firstDate === $lastDate ? $firstDate : ($firstDate . " - " . $lastDate));
            ?>
          </td>
          <td><?= h(trim((string)($r["remarks_list"] ?? "")) !== "" ? $r["remarks_list"] : "—") ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- Footer -->
  <div class="print-note">
    Generated by BRDSS on <?= h($printDate) ?> — Page 1 of 1
  </div>
</div>

</body>
</html>
