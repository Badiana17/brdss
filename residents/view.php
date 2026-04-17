<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  header("Location: index.php?error=" . urlencode("Invalid resident ID."));
  exit;
}

$stmt = $conn->prepare("SELECT * FROM residents WHERE resident_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$r = $res->fetch_assoc();

if (!$r) {
  header("Location: index.php?error=" . urlencode("Resident not found."));
  exit;
}

$username = $_SESSION["username"] ?? "User";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | View Resident</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .card-box{ border-radius:12px; border:1px solid rgba(0,0,0,.06); }
    .modal-field{
      background:#f9fafb; border:1px solid rgba(0,0,0,.06);
      border-radius:10px; padding:10px 12px; min-height:56px;
    }
    .modal-label{ font-size:.72rem; color:#6b7280; margin-bottom:2px; letter-spacing:.2px; }
    .modal-value{ font-size:.95rem; color:#1B2B3A; line-height:1.15; word-break:break-word; }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Resident Profile</h4>
      <div class="text-muted small">Viewing as <?= htmlspecialchars($username) ?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
      <a class="btn brdss-btn" href="edit.php?id=<?= (int)$r["resident_id"] ?>"><i class="bi bi-pencil-square me-1"></i>Edit</a>
    </div>
  </div>

  <div class="card card-box shadow-sm">
    <div class="card-body">
      <div class="row g-3">

        <div class="col-12"><div class="fw-bold text-primary">Personal Identity</div></div>

        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Last Name</div><div class="modal-value fw-semibold"><?= htmlspecialchars($r["last_name"] ?? "") ?></div></div></div>
        <div class="col-md-3"><div class="modal-field"><div class="modal-label">First Name</div><div class="modal-value fw-semibold"><?= htmlspecialchars($r["first_name"] ?? "") ?></div></div></div>
        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Middle Name</div><div class="modal-value"><?= htmlspecialchars($r["middle_name"] ?? "") ?></div></div></div>
        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Suffix</div><div class="modal-value"><?= htmlspecialchars($r["suffix"] ?? "") ?></div></div></div>

        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Birth Date</div><div class="modal-value"><?= htmlspecialchars($r["birthday"] ?? "") ?></div></div></div>
        <div class="col-md-2"><div class="modal-field"><div class="modal-label">Age</div><div class="modal-value"><?= htmlspecialchars($r["age"] ?? "") ?></div></div></div>

        <div class="col-12 mt-2"><div class="fw-bold text-primary">Demographics</div></div>

        <div class="col-md-4"><div class="modal-field"><div class="modal-label">Gender</div><div class="modal-value"><?= htmlspecialchars($r["gender"] ?? "") ?></div></div></div>
        <div class="col-md-4"><div class="modal-field"><div class="modal-label">Civil Status</div><div class="modal-value"><?= htmlspecialchars($r["civil_status"] ?? "") ?></div></div></div>
        <div class="col-md-4"><div class="modal-field"><div class="modal-label">Voter</div><div class="modal-value"><?= ((int)($r["is_voter"] ?? 0) === 1) ? "Yes" : "No" ?></div></div></div>

        <div class="col-12 mt-2"><div class="fw-bold text-primary">Address & Contact</div></div>

        <div class="col-md-6"><div class="modal-field"><div class="modal-label">Address</div><div class="modal-value"><?= htmlspecialchars($r["address"] ?? "") ?></div></div></div>
        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Barangay</div><div class="modal-value"><?= htmlspecialchars($r["barangay"] ?? "") ?></div></div></div>
        <div class="col-md-3"><div class="modal-field"><div class="modal-label">Zone</div><div class="modal-value"><?= htmlspecialchars($r["zone"] ?? "") ?></div></div></div>

        <div class="col-md-6"><div class="modal-field"><div class="modal-label">Contact No.</div><div class="modal-value"><?= htmlspecialchars($r["contact_no"] ?? "") ?></div></div></div>

        <div class="col-12 mt-2"><div class="fw-bold text-primary">Classification</div></div>

        <div class="col-md-6"><div class="modal-field"><div class="modal-label">Beneficiary Category</div><div class="modal-value"><?= htmlspecialchars($r["beneficiary_category"] ?? "") ?></div></div></div>
        <div class="col-md-6"><div class="modal-field"><div class="modal-label">Status</div><div class="modal-value"><?= htmlspecialchars($r["status"] ?? "") ?></div></div></div>

      </div>
    </div>
  </div>
</div>

</body>
</html>