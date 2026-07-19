<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$csrfToken = generate_csrf();
$username  = $_SESSION["username"] ?? "User";
$role      = $_SESSION["role"] ?? "admin_staff";
$userId    = $_SESSION["user_id"] ?? 0;
$isSuperAdmin = ($role === "super_admin");
$dash      = "../dashboard/super.php";

/* Flash messages */
$flashSuccess = trim($_GET["success"] ?? "");
$flashError   = trim($_GET["error"] ?? "");

/* ================================================================
   TAB 1 — Create Distribution (existing flow, enhanced)
   ================================================================ */

/* Filters */
$category = trim($_GET["category"] ?? "All");
$aid_id   = isset($_GET["aid_id"]) ? (int)$_GET["aid_id"] : 0;
$search   = trim($_GET["q"] ?? "");

$allowedCategories = ["All","Resident","Student","Senior","PWD"];
if (!in_array($category, $allowedCategories, true)) $category = "Student";

/* Load Aid Types */
$aids = [];
if ($category === "All") {
  $stmt = $conn->prepare("SELECT id, aid_name, beneficiary_category FROM aid_types WHERE is_active = 1 ORDER BY aid_name ASC");
    $stmt->execute();
} else {
  $stmt = $conn->prepare("SELECT id, aid_name, beneficiary_category FROM aid_types WHERE is_active = 1 AND beneficiary_category = ? ORDER BY aid_name ASC");
    $stmt->bind_param("s", $category);
    $stmt->execute();
}
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $aids[] = $row;
$stmt->close();

$isValidAid = false;
foreach ($aids as $a) {
    if ((int)$a["id"] === $aid_id) {
        $isValidAid = true;
        break;
    }
}
if (!$isValidAid && count($aids) > 0) {
    $aid_id = (int)$aids[0]["id"];
} else if (!$isValidAid) {
    $aid_id = 0;
}

function beneficiary_type_from_category(string $category): string {
  return match ($category) {
    "Student" => "Student",
    "Senior" => "Senior",
    "PWD" => "PWD",
    default => "Resident",
  };
}

/* Load Beneficiaries */
/*
  Aid distribution needs to show residents even if they were soft-deleted,
  because historical beneficiary records may still be valid for aid release.
*/
$where = ["1=1"];
$params = [];
$types  = "";

if ($category !== "All") {
    if ($category === "Resident") {
        $where[] = "r.beneficiary_category IN ('Resident','None')";
    } else {
        $where[] = "r.beneficiary_category = ?";
        $params[] = $category;
        $types .= "s";
    }
}

if ($search !== "") {
    $where[] = "(r.last_name LIKE ? OR r.first_name LIKE ? OR r.middle_name LIKE ? OR r.address LIKE ? OR r.barangay LIKE ? OR r.zone LIKE ? OR r.contact_no LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
    $types .= "sssssss";
}

$whereSql = "WHERE " . implode(" AND ", $where);
$sql = "
    SELECT r.resident_id AS beneficiary_id, r.last_name, r.first_name, r.middle_name, r.suffix,
           r.address, r.barangay, r.zone, r.contact_no, r.beneficiary_category
    FROM residents r $whereSql
    ORDER BY r.last_name ASC, r.first_name ASC LIMIT 800
";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$beneficiaries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Preload last received dates */
$lastMap = [];
if ($aid_id > 0 && count($beneficiaries) > 0) {
  if ($category === "All") {
    $idsByType = [];
    foreach ($beneficiaries as $beneficiary) {
      $typeKey = beneficiary_type_from_category((string)($beneficiary["beneficiary_category"] ?? ""));
      $idsByType[$typeKey][] = (int)$beneficiary["beneficiary_id"];
    }

    foreach ($idsByType as $benefType => $ids) {
      if (count($ids) === 0) {
        continue;
      }

      $placeholders = implode(",", array_fill(0, count($ids), "?"));
      $inTypes = str_repeat("i", count($ids));

      $sql = "SELECT beneficiary_id, MAX(distributed_at) AS last_received_at FROM aid_distribution
          WHERE beneficiary_type = ? AND aid_type_id = ? AND status = 'Received'
          AND beneficiary_id IN ($placeholders) GROUP BY beneficiary_id";
      $stmt = $conn->prepare($sql);
      $bindTypes = "si" . $inTypes;
      $bindValues = array_merge([$benefType, $aid_id], $ids);
      $stmt->bind_param($bindTypes, ...$bindValues);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) {
        $lastMap[(int)$r["beneficiary_id"]] = $r["last_received_at"];
      }
      $stmt->close();
    }
  } else {
    $ids = array_map(fn($b) => (int)$b["beneficiary_id"], $beneficiaries);
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $inTypes = str_repeat("i", count($ids));
    $benefType = $category;

    $sql = "SELECT beneficiary_id, MAX(distributed_at) AS last_received_at FROM aid_distribution
        WHERE beneficiary_type = ? AND aid_type_id = ? AND status = 'Received'
        AND beneficiary_id IN ($placeholders) GROUP BY beneficiary_id";
    $stmt = $conn->prepare($sql);
    $bindTypes = "si" . $inTypes;
    $bindValues = array_merge([$benefType, $aid_id], $ids);
    $stmt->bind_param($bindTypes, ...$bindValues);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
      $lastMap[(int)$r["beneficiary_id"]] = $r["last_received_at"];
    }
    $stmt->close();
  }
}

/* ================================================================
   TAB 2 — Distribution Records (filtered list with lock)
   ================================================================ */

/* Filters for records tab */
$rec_aid_type     = isset($_GET["rec_aid_type"]) ? (int)$_GET["rec_aid_type"] : 0;
$rec_benef_type   = trim($_GET["rec_benef_type"] ?? "");
$rec_status       = trim($_GET["rec_status"] ?? "");
$rec_date_from    = trim($_GET["rec_date_from"] ?? "");
$rec_date_to      = trim($_GET["rec_date_to"] ?? "");
$rec_keyword      = trim($_GET["rec_q"] ?? "");
$activeTab        = trim($_GET["tab"] ?? "create");
$rec_page         = max(1, (int)($_GET["rec_page"] ?? 1));
$rec_perPage      = 30;

/* Load all aid types for filter dropdown */
$allAids = [];
$stmt = $conn->prepare("SELECT id, aid_name FROM aid_types WHERE is_active = 1 ORDER BY aid_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $allAids[] = $row;
$stmt->close();

/* Build query for records */
$rWhere  = ["1=1"];
$rParams = [];
$rTypes  = "";

if ($rec_aid_type > 0) {
    $rWhere[]  = "d.aid_type_id = ?";
    $rParams[] = $rec_aid_type;
    $rTypes   .= "i";
}
$abTypes = ["Resident","Student","Senior","PWD"];
if ($rec_benef_type !== "" && in_array($rec_benef_type, $abTypes, true)) {
    $rWhere[]  = "d.beneficiary_type = ?";
    $rParams[] = $rec_benef_type;
    $rTypes   .= "s";
}
$asTypes = ["Pending","Received","Cancelled"];
if ($rec_status !== "" && in_array($rec_status, $asTypes, true)) {
    $rWhere[]  = "d.status = ?";
    $rParams[] = $rec_status;
    $rTypes   .= "s";
}
if ($rec_date_from !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rec_date_from)) {
    $rWhere[]  = "d.distributed_at >= ?";
    $rParams[] = $rec_date_from . " 00:00:00";
    $rTypes   .= "s";
}
if ($rec_date_to !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rec_date_to)) {
    $rWhere[]  = "d.distributed_at <= ?";
    $rParams[] = $rec_date_to . " 23:59:59";
    $rTypes   .= "s";
}
if ($rec_keyword !== "") {
    $rWhere[]  = "(r.last_name LIKE ? OR r.first_name LIKE ? OR at.aid_name LIKE ? OR d.remarks LIKE ?)";
    $rLike     = "%" . $rec_keyword . "%";
    array_push($rParams, $rLike, $rLike, $rLike, $rLike);
    $rTypes   .= "ssss";
}

$rWhereSql = implode(" AND ", $rWhere);

/* Count total for pagination */
$countSql = "SELECT COUNT(*) AS total FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    WHERE $rWhereSql";
$stmt = $conn->prepare($countSql);
if (!empty($rParams)) $stmt->bind_param($rTypes, ...$rParams);
$stmt->execute();
$rec_total = (int)$stmt->get_result()->fetch_assoc()["total"];
$stmt->close();
$rec_totalPages = max(1, ceil($rec_total / $rec_perPage));
if ($rec_page > $rec_totalPages) $rec_page = $rec_totalPages;
$rec_offset = ($rec_page - 1) * $rec_perPage;

$recSql = "
    SELECT d.id, at.aid_name, d.beneficiary_type, d.beneficiary_id,
           CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name,'')) AS beneficiary_name,
           r.address, r.barangay, r.zone, r.contact_no, d.status, d.remarks, d.distributed_at,
           d.is_locked, d.locked_by, d.locked_at, d.finalized_at,
           lu.full_name AS locked_by_name
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    LEFT JOIN users lu ON lu.user_id = d.locked_by
    WHERE $rWhereSql
    ORDER BY d.distributed_at DESC, d.id DESC
    LIMIT $rec_perPage OFFSET $rec_offset
";
$stmt = $conn->prepare($recSql);
if (!empty($rParams)) $stmt->bind_param($rTypes, ...$rParams);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Stats for records tab — count from full dataset, not paginated */
$statSql = "SELECT
    COUNT(*) AS total_records,
    SUM(CASE WHEN d.status = 'Received' THEN 1 ELSE 0 END) AS received_count,
    SUM(CASE WHEN d.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN d.status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
    SUM(CASE WHEN d.is_locked = 1 THEN 1 ELSE 0 END) AS locked_count
    FROM aid_distribution d
    INNER JOIN aid_types at ON at.id = d.aid_type_id
    INNER JOIN residents r ON r.resident_id = d.beneficiary_id
    WHERE $rWhereSql";
$stmt = $conn->prepare($statSql);
if (!empty($rParams)) $stmt->bind_param($rTypes, ...$rParams);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalRecords  = (int)$stats["total_records"];
$receivedCount = (int)$stats["received_count"];
$pendingCount  = (int)$stats["pending_count"];
$cancelledCount = (int)$stats["cancelled_count"];
$lockedCount   = (int)$stats["locked_count"];

/* Check if any filters are active */
$hasActiveFilters = ($rec_aid_type > 0 || $rec_benef_type !== "" || $rec_status !== "" || $rec_date_from !== "" || $rec_date_to !== "" || $rec_keyword !== "");

/* Build print URL with current filters */
$printParams = http_build_query(array_filter([
    "aid_type_id"     => $rec_aid_type ?: null,
    "beneficiary_type" => $rec_benef_type ?: null,
    "status"          => $rec_status ?: null,
    "date_from"       => $rec_date_from ?: null,
    "date_to"         => $rec_date_to ?: null,
    "q"               => $rec_keyword ?: null,
]));
  $canPrintRecords = ($rec_aid_type > 0 || $rec_benef_type !== "");

/* Build export URL */
$exportParams = http_build_query(array_filter([
    "aid_type_id"     => $rec_aid_type ?: null,
    "beneficiary_type" => $rec_benef_type ?: null,
    "status"          => $rec_status ?: null,
    "date_from"       => $rec_date_from ?: null,
    "date_to"         => $rec_date_to ?: null,
    "q"               => $rec_keyword ?: null,
]));

/* Helper: human-readable date */
function formatDate(?string $dt): string {
    if (!$dt) return '—';
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
    if (!$d) $d = new DateTime($dt);
    return $d->format('M j, Y g:i A');
}
function formatDateShort(?string $dt): string {
    if (!$dt) return '—';
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
    if (!$d) $d = new DateTime($dt);
    return $d->format('M j, Y');
}

/* Build pagination URL */
function paginationUrl(int $page): string {
    $params = $_GET;
    $params['rec_page'] = $page;
    $params['tab'] = 'records';
    return 'index.php?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Aid Distribution</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="assets/aid_distribution.css" rel="stylesheet">

  <style>
    body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
    .layout { display:flex; height:100vh; }
    .side{
      width: 280px;
      background: linear-gradient(180deg, #1B2B3A 0%, #243B53 100%);
      color:#fff;
      position: sticky;
      top:0;
      height:100vh;
      overflow-y:auto;
      flex-shrink: 0;
    }
    .side a{ color: rgba(255,255,255,.85); text-decoration:none; }
    .side a:hover{ color:#fff; }
    .side .nav-link{ border-radius:10px; padding:10px 12px; transition: all .15s ease; }
    .side .nav-link.active, .side .nav-link:hover{ background: rgba(255,255,255,.10); }
    .content{ flex:1; height:100vh; overflow-y:auto; background:var(--ad-bg, #f6f7fb); }
    .topbar{
      background:#fff;
      border-bottom: 1px solid rgba(0,0,0,.06);
      position: sticky; top: 0; z-index: 5;
    }
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
        <div class="small opacity-75"><?= h($username) ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="<?= h($dash) ?>"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link active" href="index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>

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
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-bold fs-4">Aid Distribution</div>
        <div class="small" style="color:var(--ad-text-muted)">Manage and track aid distribution records for Residents, Students, Seniors, and PWDs.</div>
      </div>
      <div class="d-flex gap-2">
        <button class="ad-btn ad-btn-outline" type="button" data-bs-toggle="modal" data-bs-target="#aidTypeModal">
          <i class="bi bi-plus-circle"></i> Create Aid Type
        </button>
        <a class="ad-btn ad-btn-outline" href="<?= h($dash) ?>">
          <i class="bi bi-arrow-left"></i> Dashboard
        </a>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">

      <!-- Flash Messages -->
      <?php if ($flashSuccess !== ""): ?>
        <div class="ad-flash ad-flash-success"><i class="bi bi-check-circle-fill"></i> <?= h($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError !== ""): ?>
        <div class="ad-flash ad-flash-error"><i class="bi bi-exclamation-triangle-fill"></i> <?= h($flashError) ?></div>
      <?php endif; ?>

      <!-- TABS -->
      <div class="ad-tabs mb-0">
        <button class="ad-tab <?= ($activeTab !== 'records') ? 'active' : '' ?>" onclick="switchTab('create')" id="tab-create">
          <i class="bi bi-plus-square-fill me-1"></i> Create Distribution
        </button>
        <button class="ad-tab <?= ($activeTab === 'records') ? 'active' : '' ?>" onclick="switchTab('records')" id="tab-records">
          <i class="bi bi-table me-1"></i> Distribution Records
        </button>
      </div>

      <!-- ======================== TAB 1: CREATE ======================== -->
      <div class="ad-tab-panel <?= ($activeTab !== 'records') ? 'active' : '' ?>" id="panel-create">

        <!-- Filter Bar -->
        <div class="ad-filter-bar" style="border-radius: 0 0 var(--ad-radius) var(--ad-radius); border-top:none;">
          <form method="get" action="index.php">
            <input type="hidden" name="tab" value="create">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-3">
                <label>Category</label>
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php foreach ($allowedCategories as $c): ?>
                    <option value="<?= h($c) ?>" <?= ($category === $c) ? "selected" : "" ?>><?= h($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-3">
                <label>Aid Program</label>
                <select name="aid_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php if (count($aids) === 0): ?>
                    <option value="0">No aid created for this category</option>
                  <?php else: ?>
                    <?php foreach ($aids as $a): ?>
                      <option value="<?= (int)$a["id"] ?>" <?= ((int)$a["id"] === $aid_id) ? "selected" : "" ?>>
                        <?= h($a["aid_name"]) ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="col-12 col-md-2">
                <label>Search</label>
                <input type="text" class="form-control form-control-sm" name="q"
                       placeholder="Search..."
                       value="<?= h($search) ?>">
              </div>
              <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="ad-btn ad-btn-primary ad-btn-sm" style="margin-top:auto;">
                  <i class="bi bi-funnel-fill"></i> Apply
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Beneficiaries Table Card -->
        <div class="ad-card mt-3">
          <div class="ad-card-header">
            <h3><i class="bi bi-people-fill me-2"></i>Beneficiary List</h3>
            <div class="small" style="color:var(--ad-text-muted)"><?= count($beneficiaries) ?> record(s) found</div>
          </div>

          <form method="post" action="save.php" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="category" value="<?= h($category) ?>">
            <input type="hidden" name="aid_type_id" value="<?= (int)$aid_id ?>">

            <div class="px-3 py-3 border-bottom d-flex flex-wrap gap-2 align-items-center justify-content-between">
              <div class="d-flex gap-2 align-items-center">
                <button type="submit" class="ad-btn ad-btn-primary ad-btn-sm" <?= ($aid_id === 0) ? "disabled" : "" ?>
                        onclick="return confirm('Save distribution for selected beneficiaries? Records will be saved as Pending initially.')">
                  <i class="bi bi-save2-fill"></i> Save Distribution
                </button>
                <button type="button" class="ad-btn ad-btn-outline ad-btn-sm" onclick="selectAll(true)">
                  <i class="bi bi-check2-square"></i> Select All
                </button>
                <button type="button" class="ad-btn ad-btn-outline ad-btn-sm" onclick="selectAll(false)">
                  <i class="bi bi-square"></i> Clear
                </button>
                <div class="d-flex align-items-center gap-1 bg-light px-2 rounded border ms-2">
                  <span class="small fw-semibold text-muted"><i class="bi bi-funnel-fill"></i> Show:</span>
                  <select class="form-select form-select-sm border-0 bg-transparent" style="width:130px; box-shadow:none;" onchange="filterBeneficiaryTable(this.value)">
                      <option value="All">All</option>
                      <option value="Not Yet">Not Yet Given</option>
                      <option value="Given">Given (Received)</option>
                  </select>
                </div>
              </div>
              <div style="min-width:280px; flex:1; max-width:400px;">
                <input type="text" class="form-control form-control-sm" name="remarks"
                       placeholder="Remarks (optional, same remark for all selected)">
              </div>
            </div>

            <div class="ad-table-wrap">
              <div class="ad-table-vscroll">
                <table class="ad-table">
                  <thead>
                    <tr>
                      <th style="width:50px">Select</th>
                      <th>Last Name</th>
                      <th>First Name</th>
                      <th>Middle</th>
                      <th>Suffix</th>
                      <th>Address</th>
                      <th>Barangay</th>
                      <th>Zone</th>
                      <th>Contact No.</th>
                      <th style="width:100px">Status</th>
                      <th style="width:170px">Last Received</th>
                    </tr>
                  </thead>
                  <tbody id="beneficiaryTableBody">
                  <?php if (count($beneficiaries) === 0): ?>
                    <tr>
                      <td colspan="11">
                        <div class="ad-empty">
                          <i class="bi bi-inbox"></i>
                          No records found. Try adjusting your filters.
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($beneficiaries as $b):
                      $bid  = (int)$b["beneficiary_id"];
                      $last = $lastMap[$bid] ?? null;
                      $given = ($last !== null);
                    ?>
                      <tr>
                        <td>
                          <input class="form-check-input js-pick" type="checkbox" name="beneficiary_ids[]" value="<?= $bid ?>">
                        </td>
                        <td><?= h($b["last_name"] ?? "") ?></td>
                        <td><?= h($b["first_name"] ?? "") ?></td>
                        <td><?= h($b["middle_name"] ?? "") ?></td>
                        <td><?= h($b["suffix"] ?? "") ?></td>
                        <td><?= h($b["address"] ?? "") ?></td>
                        <td><?= h($b["barangay"] ?? "") ?></td>
                        <td><?= h($b["zone"] ?? "") ?></td>
                        <td><?= h($b["contact_no"] ?? "") ?></td>
                        <td>
                          <?php if ($given): ?>
                            <span class="ad-badge ad-badge-given"><i class="bi bi-check-circle-fill"></i> Given</span>
                          <?php else: ?>
                            <span class="ad-badge ad-badge-notyet"><i class="bi bi-clock"></i> Not Yet</span>
                          <?php endif; ?>
                        </td>
                        <td><?= $last ? h($last) : "<span style='color:var(--ad-text-muted)'>—</span>" ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </form>

          <div class="ad-card-footer d-flex justify-content-between align-items-center">
            <div class="small" style="color:var(--ad-text-muted)">
              <i class="bi bi-info-circle me-1"></i> "Save" inserts a new distribution record (history-based) — beneficiaries can receive aid again anytime.
            </div>
            <div class="small" style="color:var(--ad-text-muted)">
              Aid ID: <strong><?= (int)$aid_id ?></strong>
            </div>
          </div>
        </div>

      </div><!-- /panel-create -->

      <!-- ======================== TAB 2: RECORDS ======================== -->
      <div class="ad-tab-panel <?= ($activeTab === 'records') ? 'active' : '' ?>" id="panel-records">

        <!-- Filter Bar -->
        <div class="ad-filter-bar" style="border-radius: 0 0 var(--ad-radius) var(--ad-radius); border-top:none;">
          <form method="get" action="index.php" id="recordsFilterForm">
            <input type="hidden" name="tab" value="records">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-2">
                <label>Aid Program</label>
                <select name="rec_aid_type" class="form-select form-select-sm">
                  <option value="">All</option>
                  <?php foreach ($allAids as $a): ?>
                    <option value="<?= (int)$a["id"] ?>" <?= ($rec_aid_type === (int)$a["id"]) ? "selected" : "" ?>>
                      <?= h($a["aid_name"]) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label>Beneficiary Type</label>
                <select name="rec_benef_type" class="form-select form-select-sm">
                  <option value="">All</option>
                  <?php foreach (["Resident","Student","Senior","PWD"] as $bt): ?>
                    <option value="<?= h($bt) ?>" <?= ($rec_benef_type === $bt) ? "selected" : "" ?>><?= h($bt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-1">
                <label>Status</label>
                <select name="rec_status" class="form-select form-select-sm">
                  <option value="">All</option>
                  <?php foreach (["Pending","Received","Cancelled"] as $st): ?>
                    <option value="<?= h($st) ?>" <?= ($rec_status === $st) ? "selected" : "" ?>><?= h($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 col-md-2">
                <label>From</label>
                <input type="date" name="rec_date_from" class="form-control form-control-sm" value="<?= h($rec_date_from) ?>">
              </div>
              <div class="col-6 col-md-2">
                <label>To</label>
                <input type="date" name="rec_date_to" class="form-control form-control-sm" value="<?= h($rec_date_to) ?>">
              </div>
              <div class="col-12 col-md-2">
                <label>Keyword</label>
                <input type="text" name="rec_q" class="form-control form-control-sm" placeholder="Search..." value="<?= h($rec_keyword) ?>">
              </div>
              <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="ad-btn ad-btn-primary ad-btn-sm flex-grow-1">
                  <i class="bi bi-funnel-fill"></i> Filter
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Stats Row -->
        <div class="ad-stats mt-3">
          <div class="ad-stat-card">
            <div class="ad-stat-label">Total Records</div>
            <div class="ad-stat-value"><?= $totalRecords ?></div>
          </div>
          <div class="ad-stat-card">
            <div class="ad-stat-label"><i class="bi bi-check-circle-fill me-1" style="font-size:.7rem"></i> Received</div>
            <div class="ad-stat-value" style="color:var(--ad-received)"><?= $receivedCount ?></div>
          </div>
          <div class="ad-stat-card">
            <div class="ad-stat-label"><i class="bi bi-clock-fill me-1" style="font-size:.7rem"></i> Pending</div>
            <div class="ad-stat-value" style="color:var(--ad-pending)"><?= $pendingCount ?></div>
          </div>
          <div class="ad-stat-card">
            <div class="ad-stat-label"><i class="bi bi-x-circle-fill me-1" style="font-size:.7rem"></i> Cancelled</div>
            <div class="ad-stat-value" style="color:var(--ad-cancelled)"><?= $cancelledCount ?></div>
          </div>
          <div class="ad-stat-card">
            <div class="ad-stat-label"><i class="bi bi-lock-fill me-1" style="font-size:.7rem"></i> Locked</div>
            <div class="ad-stat-value" style="color:var(--ad-locked)"><?= $lockedCount ?></div>
          </div>
        </div>

        <!-- Records Table Card -->
        <div class="ad-card">
          <div class="ad-card-header">
            <h3><i class="bi bi-table me-2"></i>Distribution Records</h3>
            <div class="d-flex gap-2 flex-wrap">
              <!-- Bulk Actions -->
              <div class="d-flex gap-1 align-items-center" id="bulkActionsBar" style="display:none !important;">
                <span class="small fw-semibold" style="color:var(--ad-primary)" id="selectedCount">0 selected</span>
                <button class="ad-btn ad-btn-danger ad-btn-sm" onclick="bulkLockAction('lock')" title="Lock selected">
                  <i class="bi bi-lock-fill"></i> Lock All
                </button>
                <?php if ($isSuperAdmin): ?>
                  <button class="ad-btn ad-btn-success ad-btn-sm" onclick="bulkLockAction('unlock')" title="Unlock selected">
                    <i class="bi bi-unlock-fill"></i> Unlock All
                  </button>
                <?php endif; ?>
                <div style="width:1px;height:24px;background:var(--ad-border);margin:0 4px;"></div>
              </div>
              <!-- Export CSV -->
              <a class="ad-btn ad-btn-outline ad-btn-sm" href="export_csv.php?<?= h($exportParams) ?>" title="Export records as CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
              </a>
              <!-- Print -->
              <a class="ad-btn ad-btn-print ad-btn-sm<?= $canPrintRecords ? '' : ' disabled' ?>"
                 href="<?= $canPrintRecords ? 'print.php?' . h($printParams) : '#' ?>"
                 target="<?= $canPrintRecords ? '_blank' : '_self' ?>"
                 <?= $canPrintRecords ? '' : 'aria-disabled="true" tabindex="-1" onclick="return false;"' ?>
                 title="<?= $canPrintRecords ? 'Print report' : 'Select a specific Aid Program or Beneficiary Type before printing.' ?>">
                <i class="bi bi-printer-fill"></i> Print
              </a>
            </div>
          </div>

          <div class="ad-table-wrap">
            <div class="ad-table-vscroll">
              <table class="ad-table" id="recordsTable">
                <thead>
                  <tr>
                    <th style="width:40px"><input class="form-check-input" type="checkbox" id="selectAllRecords" title="Select all"></th>
                    <th style="width:55px">ID</th>
                    <th>Beneficiary</th>
                    <th>Type</th>
                    <th>Aid Program</th>
                    <th style="width:100px">Status</th>
                    <th style="width:140px">Date</th>
                    <th>Remarks</th>
                    <th style="width:120px">Lock</th>
                    <th style="width:80px">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (count($records) === 0): ?>
                  <tr>
                      <td colspan="10">
                      <div class="ad-empty">
                        <i class="bi bi-inbox"></i>
                        No distribution records found. Adjust filters or create new distributions.
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($records as $rec):
                    $isLocked = (int)$rec["is_locked"] === 1;
                    $canUnlock = $isSuperAdmin && $isLocked;
                    $canLock = !$isLocked;
                    $statusClass = match($rec["status"]) {
                        "Received"  => "ad-badge-received",
                        "Pending"   => "ad-badge-pending",
                        "Cancelled" => "ad-badge-cancelled",
                        default     => "",
                    };
                    $recId = (int)$rec["id"];
                  ?>
                    <tr class="<?= $isLocked ? 'row-locked' : '' ?>" data-record-id="<?= $recId ?>">
                      <td>
                        <input class="form-check-input js-rec-check" type="checkbox" value="<?= $recId ?>">
                      </td>
                      <td><strong><?= $recId ?></strong></td>
                      <td>
                        <div class="fw-semibold"><?= h($rec["beneficiary_name"]) ?></div>
                        <div class="small" style="color:var(--ad-text-muted)"><?= h(($rec["address"] ?? "") . ", " . ($rec["barangay"] ?? "")) ?></div>
                      </td>
                      <td><span class="ad-badge ad-badge-type"><?= h($rec["beneficiary_type"]) ?></span></td>
                      <td><?= h($rec["aid_name"]) ?></td>
                      <td>
                        <?php if (!$isLocked): ?>
                          <select class="form-select form-select-sm js-status-change" data-id="<?= $recId ?>" style="font-size:.78rem;padding:3px 8px;border-radius:6px;">
                            <?php foreach (["Pending","Received","Cancelled"] as $st): ?>
                              <option value="<?= h($st) ?>" <?= ($rec["status"] === $st) ? "selected" : "" ?>><?= h($st) ?></option>
                            <?php endforeach; ?>
                          </select>
                        <?php else: ?>
                          <span class="ad-badge <?= $statusClass ?>"><?= h($rec["status"]) ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="small"><?= formatDateShort($rec["distributed_at"]) ?></div>
                        <div class="small" style="color:var(--ad-text-muted)"><?= h((new DateTime($rec["distributed_at"]))->format('g:i A')) ?></div>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-1">
                          <span class="text-truncate" style="max-width:150px;" title="<?= h($rec["remarks"] ?? "") ?>">
                            <?= h($rec["remarks"] ?? "") ?: '<span style="color:var(--ad-text-muted)">—</span>' ?>
                          </span>
                          <?php if (!$isLocked): ?>
                            <button class="btn btn-sm p-0 js-edit-remarks" data-id="<?= $recId ?>" data-remarks="<?= h($rec["remarks"] ?? "") ?>" title="Edit remarks" style="color:var(--ad-primary);border:none;background:none;">
                              <i class="bi bi-pencil-square"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <?php if ($isLocked): ?>
                          <div>
                            <span class="ad-badge ad-badge-locked"><i class="bi bi-lock-fill"></i> Locked</span>
                            <?php if ($rec["locked_by_name"]): ?>
                              <div class="ad-lock-info">by <?= h($rec["locked_by_name"]) ?></div>
                            <?php endif; ?>
                            <?php if ($rec["locked_at"]): ?>
                              <div class="ad-lock-info"><?= formatDateShort($rec["locked_at"]) ?></div>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <span class="ad-badge ad-badge-unlocked"><i class="bi bi-unlock-fill"></i> Open</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="d-flex gap-1">
                          <?php if ($canLock): ?>
                            <button class="ad-btn ad-btn-danger ad-btn-sm js-lock"
                                    data-id="<?= $recId ?>" data-action="lock"
                                    title="Lock this record" style="padding:3px 8px;">
                              <i class="bi bi-lock-fill"></i>
                            </button>
                          <?php endif; ?>
                          <?php if ($canUnlock): ?>
                            <button class="ad-btn ad-btn-success ad-btn-sm js-lock"
                                    data-id="<?= $recId ?>" data-action="unlock"
                                    title="Unlock this record" style="padding:3px 8px;">
                              <i class="bi bi-unlock-fill"></i>
                            </button>
                          <?php endif; ?>
                          <button class="ad-btn ad-btn-outline ad-btn-sm js-view-detail"
                                  data-id="<?= $recId ?>"
                                  data-name="<?= h($rec["beneficiary_name"]) ?>"
                                  data-type="<?= h($rec["beneficiary_type"]) ?>"
                                  data-aid="<?= h($rec["aid_name"]) ?>"
                                  data-address="<?= h(($rec["address"] ?? "") . ", " . ($rec["barangay"] ?? "")) ?>"
                                  data-zone="<?= h($rec["zone"] ?? "") ?>"
                                  data-contact="<?= h($rec["contact_no"] ?? "") ?>"
                                  data-status="<?= h($rec["status"]) ?>"
                                  data-remarks="<?= h($rec["remarks"] ?? "") ?>"
                                  data-date="<?= h(formatDate($rec["distributed_at"])) ?>"
                                  data-locked="<?= $isLocked ? '1' : '0' ?>"
                                  data-locked-by="<?= h($rec["locked_by_name"] ?? "") ?>"
                                  data-locked-at="<?= h(formatDate($rec["locked_at"])) ?>"
                                  title="View details" style="padding:3px 8px;">
                            <i class="bi bi-eye"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="ad-card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small" style="color:var(--ad-text-muted)">
              <i class="bi bi-shield-lock me-1"></i>
              <?php if ($isSuperAdmin): ?>
                Super Admin: You can lock and unlock any record.
              <?php else: ?>
                Admin Staff: You can lock records. Only Super Admin can unlock.
              <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="d-flex align-items-center gap-2">
              <div class="small" style="color:var(--ad-text-muted)">
                Showing <?= (($rec_page - 1) * $rec_perPage) + 1 ?>–<?= min($rec_page * $rec_perPage, $rec_total) ?> of <?= $rec_total ?> record(s)
              </div>
              <?php if ($rec_totalPages > 1): ?>
                <nav class="ad-pagination">
                  <?php if ($rec_page > 1): ?>
                    <a href="<?= h(paginationUrl($rec_page - 1)) ?>" class="ad-page-btn" title="Previous">
                      <i class="bi bi-chevron-left"></i>
                    </a>
                  <?php endif; ?>

                  <?php
                    $startPage = max(1, $rec_page - 2);
                    $endPage = min($rec_totalPages, $rec_page + 2);
                    if ($startPage > 1) echo '<span class="ad-page-ellipsis">…</span>';
                    for ($p = $startPage; $p <= $endPage; $p++):
                  ?>
                    <a href="<?= h(paginationUrl($p)) ?>" class="ad-page-btn <?= ($p === $rec_page) ? 'active' : '' ?>">
                      <?= $p ?>
                    </a>
                  <?php endfor;
                    if ($endPage < $rec_totalPages) echo '<span class="ad-page-ellipsis">…</span>';
                  ?>

                  <?php if ($rec_page < $rec_totalPages): ?>
                    <a href="<?= h(paginationUrl($rec_page + 1)) ?>" class="ad-page-btn" title="Next">
                      <i class="bi bi-chevron-right"></i>
                    </a>
                  <?php endif; ?>
                </nav>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- /panel-records -->

    </div>
  </main>
</div>

<!-- CREATE AID TYPE MODAL -->
<div class="modal fade" id="aidTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:620px;">
    <div class="modal-content" style="border-radius:12px;">
      <form method="post" action="type_save.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Create Aid Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label small fw-semibold">Aid Name</label>
              <input type="text" name="aid_name" class="form-control" required placeholder="e.g., School Supplies 2026">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label small fw-semibold">Beneficiary Category</label>
              <select name="beneficiary_category" class="form-select" required>
                <option value="Resident">Resident</option>
                <option value="Student">Student</option>
                <option value="Senior">Senior</option>
                <option value="PWD">PWD</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label small fw-semibold">Active</label>
              <select name="is_active" class="form-select" required>
                <option value="1" selected>Yes</option>
                <option value="0">No</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Description (optional)</label>
              <input type="text" name="description" class="form-control" placeholder="Short details">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="ad-btn ad-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="ad-btn ad-btn-primary">
            <i class="bi bi-check2-circle"></i> Save Aid Type
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT REMARKS MODAL -->
<div class="modal fade" id="editRemarksModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Remarks</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editRemarksId">
        <label class="form-label small fw-semibold">Remarks for Record #<span id="editRemarksLabel"></span></label>
        <textarea class="form-control" id="editRemarksText" rows="3" maxlength="255" placeholder="Enter remarks..."></textarea>
        <div class="small mt-1" style="color:var(--ad-text-muted)"><span id="remarksCharCount">0</span>/255 characters</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="ad-btn ad-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="ad-btn ad-btn-primary" id="saveRemarksBtn">
          <i class="bi bi-check2-circle"></i> Save Remarks
        </button>
      </div>
    </div>
  </div>
</div>

<!-- VIEW DETAIL MODAL -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:600px;">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header" style="background:linear-gradient(135deg,#1B2B3A,#243B53);color:#fff;border-radius:12px 12px 0 0;">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-text me-2"></i>Record Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <table class="table table-borderless mb-0" style="font-size:.9rem;">
          <tbody id="detailBody"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="ad-btn ad-btn-outline" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = '<?= h($csrfToken) ?>';

/* ---- Tab Switching ---- */
function switchTab(tab) {
  document.querySelectorAll('.ad-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.ad-tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
}

/* ---- Select All (Create Tab) ---- */
function selectAll(on) {
  document.querySelectorAll('.js-pick').forEach(cb => cb.checked = !!on);
}

/* ---- Records: Select All Checkbox ---- */
const selectAllCb = document.getElementById('selectAllRecords');
if (selectAllCb) {
  selectAllCb.addEventListener('change', function() {
    document.querySelectorAll('.js-rec-check').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
  });
}
document.querySelectorAll('.js-rec-check').forEach(cb => {
  cb.addEventListener('change', updateBulkBar);
});

function updateBulkBar() {
  const checked = document.querySelectorAll('.js-rec-check:checked');
  const bar = document.getElementById('bulkActionsBar');
  const countEl = document.getElementById('selectedCount');
  if (checked.length > 0) {
    bar.style.display = 'flex !important';
    bar.style.cssText = 'display:flex !important;';
    countEl.textContent = checked.length + ' selected';
  } else {
    bar.style.cssText = 'display:none !important;';
  }
}

/* ---- Bulk Lock/Unlock ---- */
async function bulkLockAction(action) {
  const checked = document.querySelectorAll('.js-rec-check:checked');
  if (checked.length === 0) { alert('No records selected.'); return; }
  const label = action === 'lock' ? 'Lock' : 'Unlock';
  if (!confirm(label + ' ' + checked.length + ' record(s)? This action will be logged.')) return;

  const formData = new FormData();
  formData.append('action', action);
  formData.append('csrf_token', CSRF);
  checked.forEach(cb => formData.append('record_ids[]', cb.value));

  try {
    const resp = await fetch('bulk_lock.php', { method: 'POST', body: formData });
    const json = await resp.json();
    if (json.success) {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', 'records');
      url.searchParams.set('success', json.message);
      window.location.href = url.toString();
    } else {
      alert('Error: ' + json.message);
    }
  } catch (e) {
    alert('Network error. Please try again.');
  }
}

/* ---- Lock/Unlock via AJAX ---- */
document.querySelectorAll('.js-lock').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.dataset.id;
    const action = this.dataset.action;
    const label = action === 'lock' ? 'Lock' : 'Unlock';

    if (!confirm(label + ' record #' + id + '? This action will be logged.')) return;

    this.disabled = true;
    this.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    try {
      const formData = new FormData();
      formData.append('record_id', id);
      formData.append('action', action);
      formData.append('csrf_token', CSRF);

      const resp = await fetch('lock.php', { method: 'POST', body: formData });
      const json = await resp.json();

      if (json.success) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'records');
        url.searchParams.set('success', json.message);
        window.location.href = url.toString();
      } else {
        alert('Error: ' + json.message);
        this.disabled = false;
        this.innerHTML = action === 'lock'
          ? '<i class="bi bi-lock-fill"></i>'
          : '<i class="bi bi-unlock-fill"></i>';
      }
    } catch (e) {
      alert('Network error. Please try again.');
      this.disabled = false;
      this.innerHTML = action === 'lock'
        ? '<i class="bi bi-lock-fill"></i>'
        : '<i class="bi bi-unlock-fill"></i>';
    }
  });
});

/* ---- Inline Status Change ---- */
document.querySelectorAll('.js-status-change').forEach(sel => {
  sel.addEventListener('change', async function() {
    const id = this.dataset.id;
    const newStatus = this.value;
    if (!confirm('Change status of record #' + id + ' to "' + newStatus + '"?')) {
      this.value = this.dataset.original || this.querySelector('[selected]')?.value || 'Received';
      return;
    }
    const formData = new FormData();
    formData.append('record_id', id);
    formData.append('field', 'status');
    formData.append('value', newStatus);
    formData.append('csrf_token', CSRF);
    try {
      const resp = await fetch('update_record.php', { method: 'POST', body: formData });
      const json = await resp.json();
      if (json.success) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'records');
        url.searchParams.set('success', json.message);
        window.location.href = url.toString();
      } else {
        alert('Error: ' + json.message);
      }
    } catch (e) { alert('Network error.'); }
  });
});

/* ---- Edit Remarks Modal ---- */
const editRemarksModal = document.getElementById('editRemarksModal');
const editRemarksText = document.getElementById('editRemarksText');
const editRemarksId = document.getElementById('editRemarksId');
const editRemarksLabel = document.getElementById('editRemarksLabel');
const remarksCharCount = document.getElementById('remarksCharCount');

if (editRemarksText) {
  editRemarksText.addEventListener('input', function() {
    remarksCharCount.textContent = this.value.length;
  });
}

document.querySelectorAll('.js-edit-remarks').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    const remarks = this.dataset.remarks || '';
    editRemarksId.value = id;
    editRemarksLabel.textContent = id;
    editRemarksText.value = remarks;
    remarksCharCount.textContent = remarks.length;
    new bootstrap.Modal(editRemarksModal).show();
  });
});

document.getElementById('saveRemarksBtn')?.addEventListener('click', async function() {
  const id = editRemarksId.value;
  const remarks = editRemarksText.value.trim();
  this.disabled = true;
  this.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
  const formData = new FormData();
  formData.append('record_id', id);
  formData.append('field', 'remarks');
  formData.append('value', remarks);
  formData.append('csrf_token', CSRF);
  try {
    const resp = await fetch('update_record.php', { method: 'POST', body: formData });
    const json = await resp.json();
    if (json.success) {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', 'records');
      url.searchParams.set('success', json.message);
      window.location.href = url.toString();
    } else {
      alert('Error: ' + json.message);
      this.disabled = false;
      this.innerHTML = '<i class="bi bi-check2-circle"></i> Save Remarks';
    }
  } catch (e) {
    alert('Network error.');
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check2-circle"></i> Save Remarks';
  }
});

/* ---- View Detail Modal ---- */
document.querySelectorAll('.js-view-detail').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    const isLocked = d.locked === '1';
    const statusBadge = {
      'Received': 'ad-badge-received',
      'Pending': 'ad-badge-pending',
      'Cancelled': 'ad-badge-cancelled'
    };
    const rows = [
      ['Record ID', '<strong>#' + d.id + '</strong>'],
      ['Beneficiary', d.name],
      ['Type', '<span class="ad-badge ad-badge-type">' + d.type + '</span>'],
      ['Aid Program', d.aid],
      ['Address', d.address],
      ['Zone', d.zone || '—'],
      ['Contact', d.contact || '—'],
      ['Status', '<span class="ad-badge ' + (statusBadge[d.status] || '') + '">' + d.status + '</span>'],
      ['Distributed At', d.date],
      ['Remarks', d.remarks || '<span style="color:var(--ad-text-muted)">No remarks</span>'],
      ['Lock Status', isLocked
        ? '<span class="ad-badge ad-badge-locked"><i class="bi bi-lock-fill"></i> Locked</span> by ' + (d.lockedBy || '—') + ' on ' + d.lockedAt
        : '<span class="ad-badge ad-badge-unlocked"><i class="bi bi-unlock-fill"></i> Open</span>']
    ];
    const tbody = document.getElementById('detailBody');
    tbody.innerHTML = rows.map(r =>
      '<tr><td style="font-weight:600;width:140px;color:var(--ad-text-muted);padding:10px 16px;">' + r[0] + '</td>' +
      '<td style="padding:10px 16px;">' + r[1] + '</td></tr>'
    ).join('');
    new bootstrap.Modal(document.getElementById('detailModal')).show();
  });
});

/* ---- Auto-dismiss flash messages ---- */
setTimeout(() => {
  document.querySelectorAll('.ad-flash').forEach(el => {
    el.style.transition = 'opacity .5s ease, transform .5s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-8px)';
    setTimeout(() => el.remove(), 500);
  });
}, 5000);

function filterBeneficiaryTable(val) {
  const rows = document.querySelectorAll("#beneficiaryTableBody tr");
  rows.forEach(row => {
    if (row.querySelector(".ad-empty")) return;
    const statusBadge = row.querySelector(".ad-badge");
    if (!statusBadge) return;
    
    const statusText = statusBadge.textContent.trim();
    
    if (val === "All") {
      row.style.display = "";
    } else if (val === "Given" && statusText === "Given") {
      row.style.display = "";
    } else if (val === "Not Yet" && statusText === "Not Yet") {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}
</script>
</body>
</html>