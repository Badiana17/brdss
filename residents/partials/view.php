<?php
require_once "../../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../../config/db.php";

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) { http_response_code(400); echo "Invalid ID"; exit; }

$stmt = $conn->prepare("SELECT * FROM residents WHERE resident_id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$r) { http_response_code(404); echo "Resident not found"; exit; }

$cat = $r["beneficiary_category"] ?? "None";

// Fetch category-specific data
$catData = null;
if ($cat === "Senior") {
  $s = $conn->prepare("SELECT * FROM senior_citizens WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $catData = $s->get_result()->fetch_assoc();
  $s->close();
} elseif ($cat === "PWD") {
  $s = $conn->prepare("SELECT * FROM persons_with_disabilities WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $catData = $s->get_result()->fetch_assoc();
  $s->close();
} elseif ($cat === "Student") {
  $s = $conn->prepare("SELECT * FROM students WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $catData = $s->get_result()->fetch_assoc();
  $s->close();
}

function hv($v): string { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
?>

<style>
  .modal-section-title{
    font-weight:800;font-size:.90rem;color:#2F5D8A;
    border-bottom:1px solid rgba(47,93,138,.15);
    padding-bottom:4px;margin-bottom:6px;
  }
  .modal-field{
    background:#f9fafb;border:1px solid rgba(0,0,0,.06);
    border-radius:10px;padding:10px 12px;min-height:56px;
  }
  .modal-label{font-size:.72rem;color:#6b7280;margin-bottom:2px;letter-spacing:.2px;}
  .modal-value{font-size:.92rem;color:#1B2B3A;line-height:1.15;word-break:break-word;}
  .cat-section{border-radius:10px;padding:12px 16px;border:1px solid;}
  .cat-senior{ background:#f0f6ff; border-color:rgba(47,93,138,.2); }
  .cat-pwd{ background:#fff6ed; border-color:rgba(230,130,0,.2); }
  .cat-student{ background:#eefaf1; border-color:rgba(30,142,62,.2); }
</style>

<div class="row g-3">
  <div class="col-12"><div class="modal-section-title">Personal Identity</div></div>

  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Last Name</div><div class="modal-value fw-semibold"><?= hv($r["last_name"]) ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">First Name</div><div class="modal-value fw-semibold"><?= hv($r["first_name"]) ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Middle Name</div><div class="modal-value"><?= hv($r["middle_name"]) ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Suffix</div><div class="modal-value"><?= hv($r["suffix"]) ?: "—" ?></div></div></div>

  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Birth Date</div><div class="modal-value"><?= hv($r["birthday"]) ?></div></div></div>
  <div class="col-md-2"><div class="modal-field"><div class="modal-label">Age</div><div class="modal-value"><?= hv($r["age"]) ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Demographics</div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Gender</div><div class="modal-value"><?= hv($r["gender"]) ?></div></div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Civil Status</div><div class="modal-value"><?= hv($r["civil_status"]) ?></div></div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Voter</div><div class="modal-value"><?= ((int)($r["is_voter"]??0)===1)?"Yes":"No" ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Address &amp; Contact</div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Address</div><div class="modal-value"><?= hv($r["address"]) ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Barangay</div><div class="modal-value"><?= hv($r["barangay"]) ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Zone</div><div class="modal-value"><?= hv($r["zone"]) ?></div></div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Contact No.</div><div class="modal-value"><?= hv($r["contact_no"]) ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Classification</div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Beneficiary Category</div><div class="modal-value"><?= hv($r["beneficiary_category"]) ?></div></div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Status</div><div class="modal-value"><?= hv($r["status"]) ?></div></div></div>

  <?php if ($cat === "Senior" && $catData): ?>
    <div class="col-12 mt-2"><div class="modal-section-title"><i class="bi bi-person-badge me-1"></i>Senior Citizen Details</div></div>
    <div class="col-12">
      <div class="cat-section cat-senior">
        <div class="row g-2">
          <div class="col-md-5"><div class="modal-label">OSCA ID No.</div><div class="modal-value"><?= hv($catData["osca_id_no"]) ?: "—" ?></div></div>
          <div class="col-md-5"><div class="modal-label">OSCA Issued Date</div><div class="modal-value"><?= hv($catData["osca_issued_date"]) ?: "—" ?></div></div>
        </div>
      </div>
    </div>
  <?php elseif ($cat === "PWD" && $catData): ?>
    <div class="col-12 mt-2"><div class="modal-section-title"><i class="bi bi-universal-access me-1"></i>PWD Details</div></div>
    <div class="col-12">
      <div class="cat-section cat-pwd">
        <div class="row g-2">
          <div class="col-md-4"><div class="modal-label">Disability Type</div><div class="modal-value"><?= hv($catData["disability_type"]) ?: "—" ?></div></div>
          <div class="col-md-4"><div class="modal-label">PWD ID No.</div><div class="modal-value"><?= hv($catData["pwd_id_no"]) ?: "—" ?></div></div>
          <div class="col-md-4"><div class="modal-label">Guardian Name</div><div class="modal-value"><?= hv($catData["guardian_name"]) ?: "—" ?></div></div>
          <?php if (!empty($catData["remarks"])): ?>
            <div class="col-12"><div class="modal-label">Remarks</div><div class="modal-value"><?= hv($catData["remarks"]) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php elseif ($cat === "Student" && $catData): ?>
    <div class="col-12 mt-2"><div class="modal-section-title"><i class="bi bi-mortarboard me-1"></i>Student Details</div></div>
    <div class="col-12">
      <div class="cat-section cat-student">
        <div class="row g-2">
          <div class="col-md-5"><div class="modal-label">Grade / Year Level</div><div class="modal-value"><?= hv($catData["grade_level"]) ?: "—" ?></div></div>
          <div class="col-md-4"><div class="modal-label">Enrollment Status</div><div class="modal-value"><?= ((int)($catData["is_active"]??1)===1) ? "Currently Enrolled" : "Not Enrolled" ?></div></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-12 d-flex justify-content-end gap-2 mt-2">
    <button class="btn btn-soft btn-sm" data-bs-dismiss="modal">Close</button>
    <a class="btn brdss-btn btn-sm js-open-modal"
       data-title="Edit Resident"
       data-url="partials/edit.php?id=<?= (int)$r["resident_id"] ?>">
      Edit
    </a>
  </div>
</div>