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

// Fetch category-specific data
$senior = $pwd = $student = null;

if ($cat === "Senior") {
  $s = $conn->prepare("SELECT * FROM senior_citizens WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $senior = $s->get_result()->fetch_assoc();
  $s->close();
} elseif ($cat === "PWD") {
  $s = $conn->prepare("SELECT * FROM persons_with_disabilities WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $pwd = $s->get_result()->fetch_assoc();
  $s->close();
} elseif ($cat === "Student") {
  $s = $conn->prepare("SELECT * FROM students WHERE resident_id=? LIMIT 1");
  $s->bind_param("i", $id); $s->execute();
  $student = $s->get_result()->fetch_assoc();
  $s->close();
}

function hv($v): string { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
?>

<form method="post" action="update.php" id="editResidentForm">
  <input type="hidden" name="csrf_token" value="<?= hv(generate_csrf()) ?>">
  <input type="hidden" name="resident_id" value="<?= (int)$r["resident_id"] ?>">

  <div class="row g-3">
    <div class="col-12"><div class="fw-bold text-primary">Personal Identity</div></div>

    <div class="col-md-4">
      <label class="form-label">Last Name *</label>
      <input class="form-control form-control-sm" name="last_name" value="<?= hv($r["last_name"]) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">First Name *</label>
      <input class="form-control form-control-sm" name="first_name" value="<?= hv($r["first_name"]) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Middle Name *</label>
      <input class="form-control form-control-sm" name="middle_name" value="<?= hv($r["middle_name"]) ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Suffix</label>
      <input class="form-control form-control-sm" name="suffix" value="<?= hv($r["suffix"]) ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Birth Date *</label>
      <input type="date" class="form-control form-control-sm" name="birthday" id="e_birthday" value="<?= hv($r["birthday"]) ?>" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Age</label>
      <input class="form-control form-control-sm" name="age" id="e_age" value="<?= hv($r["age"]) ?>" readonly>
    </div>

    <div class="col-md-4">
      <label class="form-label">Address *</label>
      <input class="form-control form-control-sm" name="address" value="<?= hv($r["address"]) ?>" required>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Address &amp; Contact</div></div>

    <div class="col-md-3">
      <label class="form-label">Barangay *</label>
      <input class="form-control form-control-sm" name="barangay" value="<?= hv($r["barangay"]) ?>" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Zone *</label>
      <input class="form-control form-control-sm" name="zone" value="<?= hv($r["zone"]) ?>" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Contact No. *</label>
      <input class="form-control form-control-sm" name="contact_no" value="<?= hv($r["contact_no"]) ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender *</label>
      <select class="form-select form-select-sm" name="gender" required>
        <option value="">-Select-</option>
        <?php foreach (["Male","Female","Other"] as $g): ?>
          <option <?= ($r["gender"] === $g) ? "selected" : "" ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Demographic Classification</div></div>

    <div class="col-md-4">
      <label class="form-label">Civil Status *</label>
      <select class="form-select form-select-sm" name="civil_status" required>
        <option value="">-Select-</option>
        <?php foreach (["Single","Married","Widowed","Separated"] as $cs): ?>
          <option <?= ($r["civil_status"] === $cs) ? "selected" : "" ?>><?= $cs ?></option>
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
        <option <?= ($r["status"]==="Active") ? "selected" : "" ?>>Active</option>
        <option <?= ($r["status"]==="Inactive") ? "selected" : "" ?>>Inactive</option>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Beneficiary Category</div></div>

    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="e_cat_none" value="None" <?= in_array($cat,["None","","Resident"]) ? "checked" : "" ?>>
        <label class="form-check-label" for="e_cat_none">None / Standard Resident</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="e_cat_senior" value="Senior" <?= ($cat==="Senior") ? "checked" : "" ?>>
        <label class="form-check-label" for="e_cat_senior">Senior Citizen</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="e_cat_pwd" value="PWD" <?= ($cat==="PWD") ? "checked" : "" ?>>
        <label class="form-check-label" for="e_cat_pwd">PWD (Person with Disability)</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="e_cat_student" value="Student" <?= ($cat==="Student") ? "checked" : "" ?>>
        <label class="form-check-label" for="e_cat_student">Student</label>
      </div>
    </div>

    <!-- SENIOR FIELDS -->
    <div class="col-12" id="e_senior_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#f0f6ff;">
        <div class="fw-semibold text-primary mb-2"><i class="bi bi-person-badge me-1"></i>Senior Citizen Details</div>
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label form-label-sm">OSCA ID No.</label>
            <input class="form-control form-control-sm" name="osca_id_no" value="<?= hv($senior["osca_id_no"] ?? "") ?>" placeholder="OSCA-XXXX-XXXX">
          </div>
          <div class="col-md-5">
            <label class="form-label form-label-sm">OSCA Issued Date</label>
            <input type="date" class="form-control form-control-sm" name="osca_issued_date" value="<?= hv($senior["osca_issued_date"] ?? "") ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- PWD FIELDS -->
    <div class="col-12" id="e_pwd_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#fff6ed;">
        <div class="fw-semibold text-warning mb-2"><i class="bi bi-universal-access me-1"></i>PWD Details</div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Disability Type</label>
            <input class="form-control form-control-sm" name="disability_type" value="<?= hv($pwd["disability_type"] ?? "") ?>" placeholder="e.g. Visual, Hearing">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">PWD ID No.</label>
            <input class="form-control form-control-sm" name="pwd_id_no" value="<?= hv($pwd["pwd_id_no"] ?? "") ?>" placeholder="PWD-XXXX-XXXX">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Guardian Name</label>
            <input class="form-control form-control-sm" name="guardian_name" value="<?= hv($pwd["guardian_name"] ?? "") ?>">
          </div>
          <div class="col-12">
            <label class="form-label form-label-sm">Remarks</label>
            <textarea class="form-control form-control-sm" name="pwd_remarks" rows="2"><?= hv($pwd["remarks"] ?? "") ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- STUDENT FIELDS -->
    <div class="col-12" id="e_student_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#eefaf1;">
        <div class="fw-semibold text-success mb-2"><i class="bi bi-mortarboard me-1"></i>Student Details</div>
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label form-label-sm">Grade / Year Level</label>
            <input class="form-control form-control-sm" name="grade_level" value="<?= hv($student["grade_level"] ?? "") ?>" placeholder="e.g. Grade 10, 3rd Year College">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Enrollment Status</label>
            <select class="form-select form-select-sm" name="student_is_active">
              <option value="1" <?= ((int)($student["is_active"] ?? 1) === 1) ? "selected" : "" ?>>Currently Enrolled</option>
              <option value="0" <?= ((int)($student["is_active"] ?? 1) === 0) ? "selected" : "" ?>>Not Enrolled</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 d-flex justify-content-between align-items-center mt-2">
      <button type="submit"
              class="btn btn-outline-danger btn-sm"
              formaction="delete.php"
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

  // Category show/hide
  const radios = document.querySelectorAll("input[name='beneficiary_category']");
  const seniorBox  = document.getElementById("e_senior_fields");
  const pwdBox     = document.getElementById("e_pwd_fields");
  const studentBox = document.getElementById("e_student_fields");

  function toggleCatFields() {
    const val = document.querySelector("input[name='beneficiary_category']:checked")?.value || "None";
    seniorBox.style.display  = val === "Senior"  ? "" : "none";
    pwdBox.style.display     = val === "PWD"     ? "" : "none";
    studentBox.style.display = val === "Student" ? "" : "none";
  }

  radios.forEach(r => r.addEventListener("change", toggleCatFields));
  toggleCatFields(); // run on load to show existing data
};
</script>