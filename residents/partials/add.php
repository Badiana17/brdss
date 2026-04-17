<?php
require_once "../../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../../config/db.php";
?>

<form method="post" action="../create.php" id="addResidentForm">
  <div class="row g-3">

    <div class="col-12"><div class="fw-bold text-primary">Personal Identity</div></div>

    <div class="col-md-4">
      <label class="form-label">Last Name *</label>
      <input class="form-control form-control-sm" name="last_name" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">First Name *</label>
      <input class="form-control form-control-sm" name="first_name" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Middle Name *</label>
      <input class="form-control form-control-sm" name="middle_name" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Suffix</label>
      <input class="form-control form-control-sm" name="suffix" placeholder="Jr., Sr., III">
    </div>

    <div class="col-md-3">
      <label class="form-label">Birth Date *</label>
      <input type="date" class="form-control form-control-sm" name="birthday" id="a_birthday" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Age</label>
      <input class="form-control form-control-sm" name="age" id="a_age" readonly>
    </div>

    <div class="col-md-4">
      <label class="form-label">Address *</label>
      <input class="form-control form-control-sm" name="address" required>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Address &amp; Contact</div></div>

    <div class="col-md-3">
      <label class="form-label">Barangay *</label>
      <input class="form-control form-control-sm" name="barangay" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Zone *</label>
      <input class="form-control form-control-sm" name="zone" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Contact No. *</label>
      <input class="form-control form-control-sm" name="contact_no" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender *</label>
      <select class="form-select form-select-sm" name="gender" required>
        <option value="">-Select-</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Demographic Classification</div></div>

    <div class="col-md-4">
      <label class="form-label">Civil Status *</label>
      <select class="form-select form-select-sm" name="civil_status" required>
        <option value="">-Select-</option>
        <option>Single</option>
        <option>Married</option>
        <option>Widowed</option>
        <option>Separated</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Voter *</label>
      <select class="form-select form-select-sm" name="is_voter" required>
        <option value="">-Select-</option>
        <option value="1">Yes</option>
        <option value="0">No</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Status *</label>
      <select class="form-select form-select-sm" name="status" required>
        <option value="">-Select-</option>
        <option>Active</option>
        <option>Inactive</option>
      </select>
    </div>

    <div class="col-12"><div class="fw-bold text-primary mt-2">Beneficiary Category</div></div>

    <div class="col-12">
      <div class="form-check">
        <input class="form-check-input ben-cat" type="checkbox" id="cat_senior" value="Senior Citizen">
        <label class="form-check-label" for="cat_senior">Senior Citizen</label>
      </div>
      <div class="form-check">
        <input class="form-check-input ben-cat" type="checkbox" id="cat_pwd" value="PWD">
        <label class="form-check-label" for="cat_pwd">PWD (Person with Disability)</label>
      </div>
      <div class="form-check">
        <input class="form-check-input ben-cat" type="checkbox" id="cat_student" value="Student">
        <label class="form-check-label" for="cat_student">Student</label>
      </div>

      <input type="hidden" name="beneficiary_category" id="a_beneficiary_category" value="None">
      <div class="small text-muted mt-2">*Checkboxes behave like single-select (one category only).</div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
      <button type="button" class="btn btn-soft btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn brdss-btn btn-sm"><i class="bi bi-save2 me-1"></i>Save Resident</button>
    </div>
  </div>
</form>

<script>
window.initResidentsPartial = function () {
  // Age calc
  const birthInput = document.getElementById("a_birthday");
  const ageInput   = document.getElementById("a_age");
  if (birthInput && ageInput) {
    birthInput.addEventListener("change", () => {
      if (!birthInput.value) { ageInput.value = ""; return; }
      const b = new Date(birthInput.value);
      const t = new Date();
      let age = t.getFullYear() - b.getFullYear();
      const m = t.getMonth() - b.getMonth();
      if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
      ageInput.value = (age >= 0 ? age : "");
    });
  }

  // Beneficiary single-select
  const hiddenCat = document.getElementById("a_beneficiary_category");
  document.querySelectorAll(".ben-cat").forEach(cb => {
    cb.addEventListener("change", function(){
      if (this.checked) {
        document.querySelectorAll(".ben-cat").forEach(other => { if (other !== this) other.checked = false; });
        hiddenCat.value = this.value;
      } else {
        hiddenCat.value = "None";
      }
    });
  });
};
</script>