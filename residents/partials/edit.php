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

$cat = $r["beneficiary_category"] ?? "None";
?>

<form method="post" action="/brdss/residents/update.php" id="editResidentForm">
  <input type="hidden" name="resident_id" value="<?= (int)$r["resident_id"] ?>">

  <div class="row g-3">
    <div class="col-12"><div class="fw-bold text-primary">Personal Identity</div></div>

    <div class="col-md-4">
      <label class="form-label">Last Name *</label>
      <input class="form-control form-control-sm" name="last_name" value="<?= htmlspecialchars($r["last_name"]??"") ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">First Name *</label>
      <input class="form-control form-control-sm" name="first_name" value="<?= htmlspecialchars($r["first_name"]??"") ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Middle Name *</label>
      <input class="form-control form-control-sm" name="middle_name" value="<?= htmlspecialchars($r["middle_name"]??"") ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Suffix</label>
      <input class="form-control form-control-sm" name="suffix" value="<?= htmlspecialchars($r["suffix"]??"") ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Birth Date *</label>
      <input type="date" class="form-control form-control-sm" name="birthday" id="e_birthday" value="<?= htmlspecialchars($r["birthday"]??"") ?>" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Age</label>
      <input class="form-control form-control-sm" name="age" id="e_age" value="<?= htmlspecialchars($r["age"]??"") ?>" readonly>
    </div>

    <div class="col-md-4">
      <label class="form-label">Address *</label>
      <input class="form-control form-control-sm" name="address" value="<?= htmlspecialchars($r["address"]??"") ?>" required>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Address &amp; Contact</div></div>

    <div class="col-md-3">
      <label class="form-label">Barangay *</label>
      <input class="form-control form-control-sm" name="barangay" value="<?= htmlspecialchars($r["barangay"]??"") ?>" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Zone *</label>
      <input class="form-control form-control-sm" name="zone" value="<?= htmlspecialchars($r["zone"]??"") ?>" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Contact No. *</label>
      <input class="form-control form-control-sm" name="contact_no" value="<?= htmlspecialchars($r["contact_no"]??"") ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender *</label>
      <select class="form-select form-select-sm" name="gender" required>
        <option value="">-Select-</option>
        <?php foreach (["Male","Female","Other"] as $g): ?>
          <option <?= (($r["gender"]??"") === $g) ? "selected" : "" ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Demographic Classification</div></div>

    <div class="col-md-4">
      <label class="form-label">Civil Status *</label>
      <select class="form-select form-select-sm" name="civil_status" required>
        <option value="">-Select-</option>
        <?php foreach (["Single","Married","Widowed","Separated"] as $cs): ?>
          <option <?= (($r["civil_status"]??"") === $cs) ? "selected" : "" ?>><?= $cs ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Voter *</label>
      <select class="form-select form-select-sm" name="is_voter" required>
        <option value="">-Select-</option>
        <option value="1" <?= ((int)($r["is_voter"]??0)===1) ? "selected" : "" ?>>Yes</option>
        <option value="0" <?= ((int)($r["is_voter"]??0)===0) ? "selected" : "" ?>>No</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Status *</label>
      <select class="form-select form-select-sm" name="status" required>
        <option value="">-Select-</option>
        <option <?= (($r["status"]??"") === "Active") ? "selected" : "" ?>>Active</option>
        <option <?= (($r["status"]??"") === "Inactive") ? "selected" : "" ?>>Inactive</option>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Beneficiary Category</div></div>

    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input e-ben-cat" type="checkbox" id="e_cat_senior" value="Senior Citizen" <?= ($cat==="Senior Citizen")?"checked":"" ?>>
        <label class="form-check-label" for="e_cat_senior">Senior Citizen</label>
      </div>
      <div class="form-check">
        <input class="form-check-input e-ben-cat" type="checkbox" id="e_cat_pwd" value="PWD" <?= ($cat==="PWD")?"checked":"" ?>>
        <label class="form-check-label" for="e_cat_pwd">PWD</label>
      </div>
      <div class="form-check">
        <input class="form-check-input e-ben-cat" type="checkbox" id="e_cat_student" value="Student" <?= ($cat==="Student")?"checked":"" ?>>
        <label class="form-check-label" for="e_cat_student">Student</label>
      </div>

      <input type="hidden" name="beneficiary_category" id="e_beneficiary_category" value="<?= htmlspecialchars($cat) ?>">
      <div class="small text-muted mt-2">*Checkboxes behave like single-select (one category only).</div>
    </div>

    <div class="col-12 d-flex justify-content-between align-items-center mt-2">
      <button type="submit"
              class="btn btn-outline-danger btn-sm"
              formaction="../delete.php"
              onclick="return confirm('Are you sure you want to delete this resident record?');">
        <i class="bi bi-trash me-1"></i>Delete
      </button>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-soft btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn brdss-btn btn-sm">
          <i class="bi bi-save2 me-1"></i>Save Changes
        </button>
      </div>
    </div>
  </div>
</form>

<script>
window.initResidentsPartial = function () {
  // Age calc
  const eBirthInput = document.getElementById("e_birthday");
  const eAgeInput   = document.getElementById("e_age");
  if (eBirthInput && eAgeInput) {
    eBirthInput.addEventListener("change", () => {
      if (!eBirthInput.value) { eAgeInput.value = ""; return; }
      const b = new Date(eBirthInput.value);
      const t = new Date();
      let age = t.getFullYear() - b.getFullYear();
      const m = t.getMonth() - b.getMonth();
      if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
      eAgeInput.value = (age >= 0 ? age : "");
    });
  }

  // Beneficiary single-select
  const eHiddenCat = document.getElementById("e_beneficiary_category");
  document.querySelectorAll(".e-ben-cat").forEach(cb => {
    cb.addEventListener("change", function(){
      if (this.checked) {
        document.querySelectorAll(".e-ben-cat").forEach(other => { if (other !== this) other.checked = false; });
        eHiddenCat.value = this.value;
      } else {
        eHiddenCat.value = "None";
      }
    });
  });
};
</script>