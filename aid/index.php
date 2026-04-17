<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username = $_SESSION["username"] ?? "User";
$role     = $_SESSION["role"] ?? "admin_staff";
$dash     = ($role === "super_admin") ? "../dashboard/super.php" : "../dashboard/super.php";

/* Flash messages */
$flashSuccess = trim($_GET["success"] ?? "");
$flashError   = trim($_GET["error"] ?? "");

/* Filters */
$category = trim($_GET["category"] ?? "Student");   // Resident | Student | Senior | PWD | All
$aid_id   = isset($_GET["aid_id"]) ? (int)$_GET["aid_id"] : 0;
$search   = trim($_GET["q"] ?? "");

/**
 * IMPORTANT:
 * We will source beneficiaries from RESIDENTS table using residents.beneficiary_category
 * because your extension tables (students/seniors/pwd) may be empty.
 *
 * Mapping:
 * - UI Category "Senior" should match residents.beneficiary_category = "Senior Citizen" (common in your residents module)
 */
$allowedCategories = ["All","Resident","Student","Senior","PWD"];
if (!in_array($category, $allowedCategories, true)) $category = "Student";

/* 1) Load Aid Types for dropdown */
$aids = [];
if ($category === "All") {
  // show all aids if All
  $stmt = $conn->prepare("
    SELECT id, aid_name, beneficiary_category
    FROM aid_types
    WHERE is_active = 1
    ORDER BY created_at DESC
  ");
  $stmt->execute();
} else {
  $stmt = $conn->prepare("
    SELECT id, aid_name, beneficiary_category
    FROM aid_types
    WHERE is_active = 1 AND beneficiary_category = ?
    ORDER BY created_at DESC
  ");
  $stmt->bind_param("s", $category);
  $stmt->execute();
}
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $aids[] = $row;
$stmt->close();

/* Auto-select latest aid */
if ($aid_id === 0 && count($aids) > 0) $aid_id = (int)$aids[0]["id"];

/* 2) Load beneficiaries from RESIDENTS table */
$where = [];
$params = [];
$types  = "";

/* Category filter -> residents.beneficiary_category */
if ($category !== "All") {
  if ($category === "Student") {
    $where[] = "r.beneficiary_category = 'Student'";
  } elseif ($category === "Senior") {
    // match your residents module value
    $where[] = "r.beneficiary_category IN ('Senior Citizen','Senior')";
  } elseif ($category === "PWD") {
    $where[] = "r.beneficiary_category = 'PWD'";
  } elseif ($category === "Resident") {
    // household head / normal resident category possibilities
    $where[] = "r.beneficiary_category IN ('Household Head','Resident','None')";
  }
}

/* Search (same style as residents module) */
if ($search !== "") {
  $where[] = "(r.last_name LIKE ? OR r.first_name LIKE ? OR r.middle_name LIKE ? OR r.address LIKE ? OR r.barangay LIKE ? OR r.zone LIKE ? OR r.contact_no LIKE ?)";
  $like = "%{$search}%";
  array_push($params, $like, $like, $like, $like, $like, $like, $like);
  $types .= "sssssss";
}

$whereSql = (count($where) > 0) ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "
  SELECT
    r.resident_id AS beneficiary_id,
    r.last_name, r.first_name, r.middle_name, r.suffix,
    r.address, r.barangay, r.zone, r.contact_no,
    r.beneficiary_category
  FROM residents r
  $whereSql
  ORDER BY r.last_name ASC, r.first_name ASC
  LIMIT 800
";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$beneficiaries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* 3) Preload last received dates for selected aid */
$lastMap = []; // beneficiary_id => last_received_at
if ($aid_id > 0 && count($beneficiaries) > 0) {
  $ids = array_map(fn($b) => (int)$b["beneficiary_id"], $beneficiaries);
  $placeholders = implode(",", array_fill(0, count($ids), "?"));
  $inTypes = str_repeat("i", count($ids));

  $sql = "
    SELECT beneficiary_id, MAX(distributed_at) AS last_received_at
    FROM aid_distribution
    WHERE beneficiary_type = ?
      AND aid_type_id = ?
      AND status = 'Received'
      AND beneficiary_id IN ($placeholders)
    GROUP BY beneficiary_id
  ";

  // beneficiary_type MUST match aid_distribution enum
  // We'll store distribution under the selected category label,
  // but since beneficiaries are residents, we can record:
  // Student/Senior/PWD/Resident based on chosen category (or based on resident's category if All).
  $benefType = ($category === "All") ? "Resident" : $category;

  $stmt = $conn->prepare($sql);
  $bindTypes = "si".$inTypes;
  $bindValues = array_merge([$benefType, $aid_id], $ids);
  $stmt->bind_param($bindTypes, ...$bindValues);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $lastMap[(int)$r["beneficiary_id"]] = $r["last_received_at"];
  }
  $stmt->close();
}

/* UI helper label */
function cat_label($c){
  if ($c === "Senior") return "Senior Citizen";
  return $c;
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
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    .layout { display:flex; height:100vh; }
    .side{
      width: 280px;
      background: linear-gradient(180deg, #1B2B3A 0%, #243B53 100%);
      color:#fff;
      position: sticky;
      top:0;
      height:100vh;
      overflow-y:auto;
    }
    .side a{ color: rgba(255,255,255,.85); text-decoration:none; }
    .side a:hover{ color:#fff; }
    .side .nav-link{ border-radius:10px; padding:10px 12px; }
    .side .nav-link.active, .side .nav-link:hover{ background: rgba(255,255,255,.10); }

    .content{ flex:1; height:100vh; overflow-y:auto; background:#f6f7fb; }
    .topbar{ background:#fff; border-bottom: 1px solid rgba(0,0,0,.06); position: sticky; top: 0; z-index: 5; }

    .card-box{ border-radius:10px; border:1px solid rgba(0,0,0,.06); }
    .filter-pill{ border-radius:10px; border:1px solid rgba(0,0,0,.10); background:#fff; padding:10px; }
    .btn-soft{ border-radius:10px; border:1px solid rgba(0,0,0,.10); background:#fff; }
    .table thead th{ background:#f3f4f6; font-weight:700; font-size:.85rem; white-space: nowrap; }
    .table td{ vertical-align: middle; white-space: nowrap; }
    .table-hscroll{ overflow-x:auto; }
    .table-vscroll{ max-height: 65vh; overflow-y:auto; }
    .muted{ color:#6b7280; }
    .badge-given { background: #e6f4ea; color: #137333; border: 1px solid rgba(19,115,51,.25); }
    .badge-notyet { background: #fff4e5; color: #8a5a00; border: 1px solid rgba(138,90,0,.25); }
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

      <?php if ($role === "super_admin"): ?>
        <a class="nav-link" href="../backups/index.php"><i class="bi bi-database-fill-gear me-2"></i>Backups</a>
        <a class="nav-link" href="../users/index.php"><i class="bi bi-shield-lock-fill me-2"></i>Users</a>
      <?php endif; ?>
    </nav>

    <hr class="border-light opacity-25 my-3">

    <a class="btn btn-outline-light w-100" href="../auth/logout.php">
      <i class="bi bi-box-arrow-right me-2"></i>Logout
    </a>
  </aside>

  <!-- CONTENT -->
  <main class="content">
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-bold fs-4">Aid Distribution</div>
        <div class="small muted">Manage and track aid distribution records for Residents, Students, Seniors, and PWDs.</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-soft" type="button" data-bs-toggle="modal" data-bs-target="#aidTypeModal">
          <i class="bi bi-plus-circle me-1"></i>Create Aid Type
        </button>
        <a class="btn btn-soft" href="<?= h($dash) ?>">
          <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">

      <?php if ($flashSuccess !== ""): ?>
        <div class="alert alert-success py-2"><?= h($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError !== ""): ?>
        <div class="alert alert-danger py-2"><?= h($flashError) ?></div>
      <?php endif; ?>

      <!-- FILTERS + SEARCH -->
      <form class="filter-pill mb-3" method="get" action="index.php">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach ($allowedCategories as $c): ?>
                <option value="<?= h($c) ?>" <?= ($category === $c) ? "selected" : "" ?>><?= h(cat_label($c)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold mb-1">Aid</label>
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

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" class="form-control form-control-sm" name="q"
                   placeholder="Search name, address, barangay, zone..."
                   value="<?= h($search) ?>">
          </div>

          <div class="col-12 col-md-2 d-grid">
            <label class="form-label small fw-semibold mb-1 d-none d-md-block">&nbsp;</label>
            <button type="submit" class="btn brdss-btn btn-sm">
              <i class="bi bi-funnel-fill me-1"></i>Apply
            </button>
          </div>
        </div>
      </form>

      <!-- TABLE -->
      <div class="card card-box shadow-sm">
        <div class="card-body p-0">

          <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
            <div class="fw-semibold">Beneficiary Information</div>
            <div class="small muted"><?= count($beneficiaries) ?> record(s) found</div>
          </div>

          <form method="post" action="save.php" class="m-0">
            <input type="hidden" name="category" value="<?= h($category) ?>">
            <input type="hidden" name="aid_type_id" value="<?= (int)$aid_id ?>">

            <div class="px-3 py-3 border-bottom d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between">
              <div class="d-flex gap-2 align-items-center">
                <button type="submit" class="btn brdss-btn btn-sm" <?= ($aid_id === 0) ? "disabled" : "" ?>
                        onclick="return confirm('Save distribution for selected beneficiaries?')">
                  <i class="bi bi-save2-fill me-1"></i>Save
                </button>

                <button type="button" class="btn btn-soft btn-sm" onclick="selectAll(true)">
                  <i class="bi bi-check2-square me-1"></i>Select All
                </button>
                <button type="button" class="btn btn-soft btn-sm" onclick="selectAll(false)">
                  <i class="bi bi-square me-1"></i>Clear
                </button>
              </div>

              <div style="min-width: 320px; width: 100%;">
                <input type="text" class="form-control form-control-sm"
                       name="remarks" placeholder="Remarks (optional, same remark for all selected)">
              </div>
            </div>

            <div class="table-hscroll">
              <div class="table-vscroll">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th style="width:60px;">Select</th>
                      <th>Last Name</th>
                      <th>First Name</th>
                      <th>Middle</th>
                      <th>Suffix</th>
                      <th>Address</th>
                      <th>Barangay</th>
                      <th>Zone</th>
                      <th>Contact No.</th>
                      <th style="width:140px;">Status</th>
                      <th style="width:200px;">Last Received</th>
                    </tr>
                  </thead>

                  <tbody>
                  <?php if (count($beneficiaries) === 0): ?>
                    <tr>
                      <td colspan="11" class="text-center py-4 muted">No records found.</td>
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
                            <span class="badge badge-given">Given</span>
                          <?php else: ?>
                            <span class="badge badge-notyet">Not Yet</span>
                          <?php endif; ?>
                        </td>

                        <td><?= $last ? h($last) : "<span class='muted'>—</span>" ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </form>

          <div class="px-3 py-3 d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between border-top">
            <div class="small muted">
              Tip: “Save” inserts a new distribution record (history-based) so pwede bigyan ulit anytime.
            </div>
            <div class="small muted">
              Selected Aid ID: <strong><?= (int)$aid_id ?></strong>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<!-- CREATE AID TYPE MODAL -->
<div class="modal fade" id="aidTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:620px;">
    <div class="modal-content" style="border-radius:12px;">
      <form method="post" action="type_save.php">
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
                <option value="Senior">Senior Citizen</option>
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
          <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn brdss-btn">
            <i class="bi bi-check2-circle me-1"></i>Save Aid Type
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectAll(on){
  document.querySelectorAll(".js-pick").forEach(cb => cb.checked = !!on);
}
</script>
</body>
</html>