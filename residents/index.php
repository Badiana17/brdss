<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

/* Filters + Search (GET) */
$category = trim($_GET["category"] ?? "All");
$gender   = trim($_GET["gender"] ?? "All");
$search   = trim($_GET["q"] ?? "");

/* Build WHERE dynamically */
$where = ["deleted_at IS NULL"];
$params = [];
$types = "";

if ($category !== "All") {
  $where[] = "beneficiary_category = ?";
  $params[] = $category;
  $types .= "s";
}
if ($gender !== "All") {
  $where[] = "gender = ?";
  $params[] = $gender;
  $types .= "s";
}
if ($search !== "") {
  $where[] = "(last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR address LIKE ? OR barangay LIKE ? OR zone LIKE ? OR contact_no LIKE ?)";
  $like = "%{$search}%";
  array_push($params, $like, $like, $like, $like, $like, $like, $like);
  $types .= "sssssss";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$sql = "
  SELECT
    resident_id, first_name, middle_name, last_name, suffix,
    birthday, age, gender, civil_status, is_voter,
    address, barangay, zone, contact_no,
    beneficiary_category, status, created_at
  FROM residents
  $whereSql
  ORDER BY last_name ASC, first_name ASC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Archived residents query */
$archiveStmt = $conn->query("
  SELECT resident_id, first_name, middle_name, last_name, suffix,
         gender, age, barangay, zone, beneficiary_category, deleted_at
  FROM residents
  WHERE deleted_at IS NOT NULL
  ORDER BY deleted_at DESC
");
$archivedRows = $archiveStmt ? $archiveStmt->fetch_all(MYSQLI_ASSOC) : [];
$archivedCount = count($archivedRows);
$csrf = generate_csrf();

$username = $_SESSION["username"] ?? "User";
$role = $_SESSION["role"] ?? "admin_staff";
$dash = ($role === "super_admin") ? "../dashboard/super.php" : "../dashboard/super.php";
$printUrl = "print.php?" . http_build_query([
  "category" => $category,
  "gender" => $gender,
  "q" => $search,
]);

$flashSuccess = trim($_GET["success"] ?? "");
$flashError   = trim($_GET["error"] ?? "");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Residents</title>

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
    .btn-soft{ border-radius:10px; border:1px solid rgba(0,0,0,.10); background:#fff; color:#6b7280; transition:all .2s; font-weight:600; font-size:.85rem; padding:8px 16px; }
    .btn-soft:hover{ background:#eef4fa; color:#2F5D8A; border-color:#2F5D8A; }
    .action-dot{ width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; border:1px solid rgba(0,0,0,.10); background:#fff; }

    .table thead th{ background:#f3f4f6; font-weight:700; font-size:.85rem; white-space: nowrap; }
    .table td{ vertical-align: middle; white-space: nowrap; }
    .table-hscroll{ overflow-x:auto; }
    .table-vscroll{ max-height: 65vh; overflow-y:auto; }

    .muted{ color:#6b7280; }
    .badge-active { background: #e6f4ea; color: #137333; border: 1px solid rgba(19,115,51,.25); }
    .badge-inactive { background: #fdecea; color: #b3261e; border: 1px solid rgba(179,38,30,.25); }

    th.col-identity, td.col-identity { background: #f0f6ff; }
    th.col-demo,     td.col-demo     { background: #f7f2ff; }
    th.col-location, td.col-location { background: #fff6ed; }
    th.col-class,    td.col-class    { background: #eefaf1; }
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
        <div class="small opacity-75"><?= htmlspecialchars($username) ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="<?= $dash ?>"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link active" href="index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link" href="../aid/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>

      <?php if ($role === "super_admin"): ?>
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
        <div class="fw-bold fs-4">Residents</div>
        <div class="small muted">Manage and view the comprehensive database of barangay residents.</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button"
                class="btn brdss-btn btn-sm js-open-modal"
                data-title="Add New Resident"
                data-url="partials/add.php">
          <i class="bi bi-person-plus-fill me-1"></i>Add Resident
        </button>

        <!-- Archive icon button with badge -->
        <button type="button"
                class="btn btn-soft btn-sm position-relative"
                data-bs-toggle="modal"
                data-bs-target="#archiveModal"
                title="View Archived Residents">
          <i class="bi bi-archive-fill"></i>
          <?php if ($archivedCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
              <?= $archivedCount ?>
            </span>
          <?php endif; ?>
        </button>

        <a class="btn btn-soft btn-sm" href="<?= $dash ?>">
          <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">

      <?php if ($flashSuccess !== ""): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError !== ""): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($flashError) ?></div>
      <?php endif; ?>

      <!-- FILTERS + SEARCH -->
      <form class="filter-pill mb-3" method="get" action="index.php">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category" class="form-select form-select-sm">
              <?php
                $cats = ["All","Student","Senior","PWD","None"];
                foreach ($cats as $c) {
                  $sel = ($category === $c) ? "selected" : "";
                  echo "<option value=\"".htmlspecialchars($c)."\" $sel>".htmlspecialchars($c)."</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Gender</label>
            <select name="gender" class="form-select form-select-sm">
              <?php
                $genders = ["All","Male","Female","Other"];
                foreach ($genders as $g) {
                  $sel = ($gender === $g) ? "selected" : "";
                  echo "<option value=\"".htmlspecialchars($g)."\" $sel>".htmlspecialchars($g)."</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" class="form-control form-control-sm" name="q"
                   placeholder="Search by name, address, barangay, zone, contact..."
                   value="<?= htmlspecialchars($search) ?>">
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
            <div class="fw-semibold">Resident Information</div>
            <div class="small muted"><?= count($rows) ?> resident(s) found</div>
          </div>

          <div class="table-hscroll">
            <div class="table-vscroll">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th style="width:70px;">Action</th>
                    <th>Status</th>

                    <th class="col-identity">Last Name</th>
                    <th class="col-identity">First Name</th>
                    <th class="col-identity">Middle</th>
                    <th class="col-identity">Suffix</th>

                    <th class="col-demo">Gender</th>
                    <th class="col-demo">Age</th>
                    <th class="col-demo">Birthday</th>
                    <th class="col-demo">Civil Status</th>
                    <th class="col-demo">Voter</th>

                    <th class="col-location">Contact No.</th>
                    <th class="col-location">Address</th>
                    <th class="col-location">Barangay</th>
                    <th class="col-location">Zone</th>

                    <th class="col-class">Category</th>
                    <th class="col-class">Created At</th>
                  </tr>
                </thead>

                <tbody>
                <?php if (count($rows) === 0): ?>
                  <tr>
                    <td colspan="17" class="text-center py-4 muted">No residents found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td>
                        <div class="dropdown">
                          <button class="action-dot" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                          </button>
                          <ul class="dropdown-menu">
                            <li>
                              <a class="dropdown-item js-open-modal" href="#"
                                 data-title="Resident Profile"
                                 data-url="partials/view.php?id=<?= (int)$r["resident_id"] ?>">
                                View
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item js-open-modal" href="#"
                                 data-title="Edit Resident"
                                 data-url="partials/edit.php?id=<?= (int)$r["resident_id"] ?>">
                                Edit
                              </a>
                            </li>
                          </ul>
                        </div>
                      </td>

                      <td>
                        <?php if (($r["status"] ?? "") === "Active"): ?>
                          <span class="badge badge-active">Active</span>
                        <?php else: ?>
                          <span class="badge badge-inactive">Inactive</span>
                        <?php endif; ?>
                      </td>

                      <td class="col-identity"><?= htmlspecialchars($r["last_name"]) ?></td>
                      <td class="col-identity"><?= htmlspecialchars($r["first_name"]) ?></td>
                      <td class="col-identity"><?= htmlspecialchars($r["middle_name"] ?? "") ?></td>
                      <td class="col-identity"><?= htmlspecialchars($r["suffix"] ?? "") ?></td>

                      <td class="col-demo"><?= htmlspecialchars($r["gender"] ?? "") ?></td>
                      <td class="col-demo"><?= htmlspecialchars($r["age"] ?? "") ?></td>
                      <td class="col-demo"><?= htmlspecialchars($r["birthday"] ?? "") ?></td>
                      <td class="col-demo"><?= htmlspecialchars($r["civil_status"] ?? "") ?></td>
                      <td class="col-demo"><?= ((int)($r["is_voter"] ?? 0) === 1) ? "Yes" : "No" ?></td>

                      <td class="col-location"><?= htmlspecialchars($r["contact_no"] ?? "") ?></td>
                      <td class="col-location"><?= htmlspecialchars($r["address"] ?? "") ?></td>
                      <td class="col-location"><?= htmlspecialchars($r["barangay"] ?? "") ?></td>
                      <td class="col-location"><?= htmlspecialchars($r["zone"] ?? "") ?></td>

                      <td class="col-class"><?= htmlspecialchars($r["beneficiary_category"] ?? "") ?></td>
                      <td class="col-class"><?= htmlspecialchars($r["created_at"] ?? "") ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="px-3 py-3 d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between border-top">
            <div class="d-flex gap-2">
              <!-- Add Resident now opens modal -->
              <button type="button"
                      class="btn brdss-btn btn-sm js-open-modal"
                      data-title="Add New Resident"
                      data-url="partials/add.php">
                <i class="bi bi-person-plus-fill me-1"></i>Add Resident
              </button>

              <a class="btn btn-soft btn-sm" href="print.php?<?= htmlspecialchars(http_build_query(array_filter(['category' => $category !== 'All' ? $category : null, 'gender' => $gender !== 'All' ? $gender : null, 'q' => $search ?: null]))) ?>" target="_blank">
                <i class="bi bi-printer-fill me-1"></i>Print
              </a>
              <span class="small muted ms-2 align-self-center">Print current filtered residents list</span>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ARCHIVE MODAL -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="archiveModalLabel">
          <i class="bi bi-archive-fill me-2 text-warning"></i>Archive / Recycle Bin
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <?php if (count($archivedRows) === 0): ?>
          <div class="text-center py-5">
            <i class="bi bi-archive" style="font-size:3rem;color:#d1d5db;"></i>
            <div class="mt-2 fw-semibold text-muted">Archive is empty</div>
            <div class="small text-muted mt-1">No deleted residents found.</div>
          </div>
        <?php else: ?>
          <div class="px-3 pt-3 pb-1">
            <div class="small text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Restoring a resident will set their status back to <b>Active</b>. Aid distribution history is preserved.
            </div>
          </div>
          <div style="overflow-x:auto;">
            <div style="max-height:420px;overflow-y:auto;">
              <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead>
                  <tr>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Action</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Last Name</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">First Name</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Middle</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Gender</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Age</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Barangay</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Zone</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Category</th>
                    <th style="background:#f3f4f6;font-weight:700;white-space:nowrap;">Deleted At</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($archivedRows as $ar): ?>
                    <tr>
                      <td style="vertical-align:middle;">
                        <form method="post" action="restore.php"
                              onsubmit="return confirm('Restore <?= htmlspecialchars(addslashes($ar['first_name'] . ' ' . $ar['last_name'])) ?>?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                          <input type="hidden" name="resident_id" value="<?= (int)$ar['resident_id'] ?>">
                          <button type="submit" class="btn btn-success btn-sm" title="Restore">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                          </button>
                        </form>
                      </td>
                      <td style="vertical-align:middle;white-space:nowrap;"><?= htmlspecialchars($ar['last_name'] ?? '') ?></td>
                      <td style="vertical-align:middle;white-space:nowrap;"><?= htmlspecialchars($ar['first_name'] ?? '') ?></td>
                      <td style="vertical-align:middle;white-space:nowrap;"><?= htmlspecialchars($ar['middle_name'] ?? '') ?></td>
                      <td style="vertical-align:middle;white-space:nowrap;"><?= htmlspecialchars($ar['gender'] ?? '') ?></td>
                      <td style="vertical-align:middle;"><?= htmlspecialchars($ar['age'] ?? '') ?></td>
                      <td style="vertical-align:middle;white-space:nowrap;"><?= htmlspecialchars($ar['barangay'] ?? '') ?></td>
                      <td style="vertical-align:middle;"><?= htmlspecialchars($ar['zone'] ?? '') ?></td>
                      <td style="vertical-align:middle;"><?= htmlspecialchars($ar['beneficiary_category'] ?? '') ?></td>
                      <td style="vertical-align:middle;white-space:nowrap;">
                        <span style="background:#fff3cd;color:#856404;border:1px solid rgba(133,100,4,.25);border-radius:6px;padding:2px 8px;font-size:.78rem;font-weight:600;">
                          <i class="bi bi-clock me-1"></i>
                          <?= !empty($ar['deleted_at']) ? date('M d, Y h:i A', strtotime($ar['deleted_at'])) : '—' ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-soft btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- REUSABLE MODAL (loads content from separate files) -->
<div class="modal fade" id="residentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:820px;">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="residentModalTitle">...</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="residentModalBody">Loading...</div>
    </div>
  </div>
</div>

<script>
async function openResidentModal(title, url){
  document.getElementById("residentModalTitle").textContent = title;
  document.getElementById("residentModalBody").innerHTML = "Loading...";
  delete window.initResidentsPartial; // Prevent leftover functions from crashing other views

  const modalEl = document.getElementById("residentModal");
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  try {
    const res = await fetch(url, { headers: { "X-Requested-With": "fetch" } });
    const html = await res.text();
    if (!res.ok) throw new Error(html || "Load failed");

    document.getElementById("residentModalBody").innerHTML = html;

    // Execute scripts inside the dynamically loaded HTML
    const scripts = document.getElementById("residentModalBody").querySelectorAll("script");
    scripts.forEach(oldScript => {
      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });

    // Run initialization if the script defined it
    if (typeof window.initResidentsPartial === "function") {
      window.initResidentsPartial();
    }
  } catch (e) {
    document.getElementById("residentModalBody").innerHTML =
      `<div class="alert alert-danger mb-0">Error loading content.</div>`;
  }
}

document.addEventListener("click", (e) => {
  const btn = e.target.closest(".js-open-modal");
  if(!btn) return;
  e.preventDefault();
  openResidentModal(btn.dataset.title, btn.dataset.url);
});
</script>

</body>
</html>