<?php
require_once "../config/auth.php";
require_role(["admin_staff","super_admin"]);
require_once "../config/db.php";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Add Resident</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Add New Resident</h4>
    <a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <form method="post" action="create.php" class="card shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Last Name *</label>
          <input class="form-control" name="last_name" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">First Name *</label>
          <input class="form-control" name="first_name" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name *</label>
          <input class="form-control" name="middle_name" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Suffix</label>
          <input class="form-control" name="suffix" placeholder="Jr., Sr., III">
        </div>

        <div class="col-md-3">
          <label class="form-label">Birth Date *</label>
          <input type="date" class="form-control" name="birthday" id="a_birthday" required>
        </div>

        <div class="col-md-2">
          <label class="form-label">Age</label>
          <input class="form-control" name="age" id="a_age" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Address *</label>
          <input class="form-control" name="address" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Barangay *</label>
          <input class="form-control" name="barangay" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Zone *</label>
          <input class="form-control" name="zone" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Contact No. *</label>
          <input class="form-control" name="contact_no" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Gender *</label>
          <select class="form-select" name="gender" required>
            <option value="">-Select-</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Civil Status *</label>
          <select class="form-select" name="civil_status" required>
            <option value="">-Select-</option>
            <option>Single</option>
            <option>Married</option>
            <option>Widowed</option>
            <option>Separated</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Voter *</label>
          <select class="form-select" name="is_voter" required>
            <option value="">-Select-</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Status *</label>
          <select class="form-select" name="status" required>
            <option value="">-Select-</option>
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Beneficiary Category</label>

          <div class="form-check">
            <input class="form-check-input ben-cat" type="checkbox" id="cat_senior" value="Senior Citizen">
            <label class="form-check-label" for="cat_senior">Senior Citizen</label>
          </div>
          <div class="form-check">
            <input class="form-check-input ben-cat" type="checkbox" id="cat_pwd" value="PWD">
            <label class="form-check-label" for="cat_pwd">PWD</label>
          </div>
          <div class="form-check">
            <input class="form-check-input ben-cat" type="checkbox" id="cat_student" value="Student">
            <label class="form-check-label" for="cat_student">Student</label>
          </div>

          <input type="hidden" name="beneficiary_category" id="a_beneficiary_category" value="None">
          <div class="text-muted small mt-1">*Single-select only.</div>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2 bg-white">
      <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
      <button class="btn brdss-btn" type="submit"><i class="bi bi-save2 me-1"></i>Save</button>
    </div>
  </form>
</div>

<script>
const birthInput = document.getElementById("a_birthday");
const ageInput   = document.getElementById("a_age");

birthInput.addEventListener("change", () => {
  if (!birthInput.value) { ageInput.value = ""; return; }
  const b = new Date(birthInput.value);
  const t = new Date();
  let age = t.getFullYear() - b.getFullYear();
  const m = t.getMonth() - b.getMonth();
  if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
  ageInput.value = (age >= 0 ? age : "");
});

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
</script>

</body>
</html>