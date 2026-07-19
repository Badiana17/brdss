<?php
require_once "../../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../../config/db.php";
?>

<form method="post" action="create.php" id="addResidentForm">
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
        <input class="form-check-input" type="radio" name="beneficiary_category" id="cat_none" value="None" checked>
        <label class="form-check-label" for="cat_none">None / Standard Resident</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="cat_senior" value="Senior">
        <label class="form-check-label" for="cat_senior">Senior Citizen</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="cat_pwd" value="PWD">
        <label class="form-check-label" for="cat_pwd">PWD (Person with Disability)</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="beneficiary_category" id="cat_student" value="Student">
        <label class="form-check-label" for="cat_student">Student</label>
      </div>
    </div>

    <!-- SENIOR FIELDS -->
    <div class="col-12" id="a_senior_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#f0f6ff;">
        <div class="fw-semibold text-primary mb-2"><i class="bi bi-person-badge me-1"></i>Senior Citizen Details</div>
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label form-label-sm">OSCA ID No.</label>
            <input class="form-control form-control-sm" name="osca_id_no" placeholder="OSCA-XXXX-XXXX">
          </div>
          <div class="col-md-5">
            <label class="form-label form-label-sm">OSCA Issued Date</label>
            <input type="date" class="form-control form-control-sm" name="osca_issued_date">
          </div>
        </div>
      </div>
    </div>

    <!-- PWD FIELDS -->
    <div class="col-12" id="a_pwd_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#fff6ed;">
        <div class="fw-semibold text-warning mb-2"><i class="bi bi-universal-access me-1"></i>PWD Details</div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label form-label-sm">Disability Type</label>
            <input class="form-control form-control-sm" name="disability_type" placeholder="e.g. Visual, Hearing, Orthopedic">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">PWD ID No.</label>
            <input class="form-control form-control-sm" name="pwd_id_no" placeholder="PWD-XXXX-XXXX">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Guardian Name</label>
            <input class="form-control form-control-sm" name="guardian_name" placeholder="Full name">
          </div>
          <div class="col-12">
            <label class="form-label form-label-sm">Remarks</label>
            <textarea class="form-control form-control-sm" name="pwd_remarks" rows="2" placeholder="Additional remarks..."></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- STUDENT FIELDS -->
    <div class="col-12" id="a_student_fields" style="display:none;">
      <div class="p-3 border rounded" style="background:#eefaf1;">
        <div class="fw-semibold text-success mb-2"><i class="bi bi-mortarboard me-1"></i>Student Details</div>
        <div class="row g-2">
          <div class="col-md-5">
            <label class="form-label form-label-sm">Grade / Year Level</label>
            <input class="form-control form-control-sm" name="grade_level" placeholder="e.g. Grade 10, 3rd Year College">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Enrollment Status</label>
            <select class="form-select form-select-sm" name="student_is_active">
              <option value="1">Currently Enrolled</option>
              <option value="0">Not Enrolled</option>
            </select>
          </div>
        </div>
      </div>
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

  // Category show/hide
  const radios = document.querySelectorAll("input[name='beneficiary_category']");
  const seniorBox  = document.getElementById("a_senior_fields");
  const pwdBox     = document.getElementById("a_pwd_fields");
  const studentBox = document.getElementById("a_student_fields");

  function toggleCatFields() {
    const val = document.querySelector("input[name='beneficiary_category']:checked")?.value || "None";
    seniorBox.style.display  = val === "Senior"  ? "" : "none";
    pwdBox.style.display     = val === "PWD"     ? "" : "none";
    studentBox.style.display = val === "Student" ? "" : "none";
  }

  radios.forEach(r => r.addEventListener("change", toggleCatFields));
  toggleCatFields();
};
</script>