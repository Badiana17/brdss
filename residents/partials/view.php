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

if (!$r) { http_response_code(404); echo "Resident not found"; exit; }
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
</style>

<div class="row g-3">
  <div class="col-12"><div class="modal-section-title">Personal Identity</div></div>

  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Last Name</div><div class="modal-value fw-semibold"><?= htmlspecialchars($r["last_name"]??"") ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">First Name</div><div class="modal-value fw-semibold"><?= htmlspecialchars($r["first_name"]??"") ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Middle Name</div><div class="modal-value"><?= htmlspecialchars($r["middle_name"]??"") ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Suffix</div><div class="modal-value"><?= htmlspecialchars($r["suffix"]??"") ?></div></div></div>

  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Birth Date</div><div class="modal-value"><?= htmlspecialchars($r["birthday"]??"") ?></div></div></div>
  <div class="col-md-2"><div class="modal-field"><div class="modal-label">Age</div><div class="modal-value"><?= htmlspecialchars($r["age"]??"") ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Demographics</div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Gender</div><div class="modal-value"><?= htmlspecialchars($r["gender"]??"") ?></div></div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Civil Status</div><div class="modal-value"><?= htmlspecialchars($r["civil_status"]??"") ?></div></div></div>
  <div class="col-md-4"><div class="modal-field"><div class="modal-label">Voter</div><div class="modal-value"><?= ((int)($r["is_voter"]??0)===1)?"Yes":"No" ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Address &amp; Contact</div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Address</div><div class="modal-value"><?= htmlspecialchars($r["address"]??"") ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Barangay</div><div class="modal-value"><?= htmlspecialchars($r["barangay"]??"") ?></div></div></div>
  <div class="col-md-3"><div class="modal-field"><div class="modal-label">Zone</div><div class="modal-value"><?= htmlspecialchars($r["zone"]??"") ?></div></div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Contact No.</div><div class="modal-value"><?= htmlspecialchars($r["contact_no"]??"") ?></div></div></div>

  <div class="col-12 mt-2"><div class="modal-section-title">Classification</div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Beneficiary Category</div><div class="modal-value"><?= htmlspecialchars($r["beneficiary_category"]??"") ?></div></div></div>
  <div class="col-md-6"><div class="modal-field"><div class="modal-label">Status</div><div class="modal-value"><?= htmlspecialchars($r["status"]??"") ?></div></div></div>

  <div class="col-12 d-flex justify-content-end gap-2 mt-2">
    <button class="btn btn-soft btn-sm" data-bs-dismiss="modal">Close</button>
    <a class="btn brdss-btn btn-sm js-open-modal"
       data-title="Edit Resident"
       data-url="partials/edit.php?id=<?= (int)$r["resident_id"] ?>">
      Edit
    </a>
  </div>
</div>