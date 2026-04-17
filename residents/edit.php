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
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
  header("Location: index.php?error=" . urlencode("Resident not found."));
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Edit Resident</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Edit Resident</h4>
    <a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <form method="post" action="update.php" class="card shadow-sm" id="editForm">
    <input type="hidden" name="resident_id" value="<?= (int)$r["resident_id"] ?>">

    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Last Name *</label>
          <input class="form-control" name="last_name" value="<?= htmlspecialchars($r["last_name"] ?? "") ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">First Name *</label>
          <input class="form-control" name="first_name" value="<?= htmlspecialchars($r["first_name"] ?? "") ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name *</label>
          <input class="form-control" name="middle_name" value="<?= htmlspecialchars($r["middle_name"] ?? "") ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Suffix</label>
          <input class="form-control" name="suffix" value="<?= htmlspecialchars($r["suffix"] ?? "") ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label">Birth Date *</label>
          <input type="date" class="form-control" name="birthday" id="e_birthday" value="<?= htmlspecialchars($r["birthday"] ?? "") ?>" required>
        </div>

        <div class="col-md-2">
          <label class="form-label">Age</label>
          <input class="form-control" name="age" id="e_age" value="<?= htmlspecialchars($r["age"] ?? "") ?>" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Address *</label>
          <input class="form-control" name="address" value="<?= htmlspecialchars($r["address"] ?? "") ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Barangay *</label>
          <input class="form-control" name="barangay" value="<?= htmlspecialchars($r["barangay"] ?? "") ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Zone *</label>
          <input class="form-control" name="zone" value="<?= htmlspecialchars($r["zone"] ?? "") ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Contact No. *</label>
          <input class="form-control" name="contact_no" value="<?= htmlspecialchars($r["contact_no"] ?? "") ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Gender *</label>
          <select class="form-select" name="gender" required>
            <option value="">-Select-</option>
            <?php foreach (["Male","Female","Other"] as $g): ?>
              <option <?= (($r["gender"] ?? "") === $g) ? "selected" : "" ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Civil Status *</label>
          <select class="form-select" name="civil_status" required>
            <option value="">-Select-</option>
            <?php foreach (["Single","Married","Widowed","Separated"] as $cs): ?>
              <option <?= (($r["civil_status"] ?? "") === $cs) ? "selected" : "" ?>><?= $cs ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Voter *</label>
          <select class="form-select" name="is_voter" required>
            <option value="">-Select-</option>
            <option value="1" <?= ((int)($r["is_voter"] ?? 0) === 1) ? "selected" : "" ?>>Yes</option>
            <option value="0" <?= ((int)($r["is_voter"] ?? 0) === 0) ? "selected" : "" ?>>No</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Status *</label>
          <select class="form-select" name="status" required>
            <option value="">-Select-</option>
            <option <?= (($r["status"] ?? "") === "Active") ? "selected" : "" ?>>Active</option>
            <option <?= (($r["status"] ?? "") === "Inactive") ? "selected" : "" ?>>Inactive</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Beneficiary Category</label>
          <?php $cat = $r["beneficiary_category"] ?? "None"; ?>
          <div class="form-check">
            <input class="form-check-input e-ben-cat" type="checkbox" value="Senior Citizen" id="e_cat_senior" <?= ($cat==="Senior Citizen")?"checked":"" ?>>
            <label class="form-check-label" for="e_cat_senior">Senior Citizen</label>
          </div>
          <div class="form-check">
            <input class="form-check-input e-ben-cat" type="checkbox" value="PWD" id="e_cat_pwd" <?= ($cat==="PWD")?"checked":"" ?>>
            <label class="form-check-label" for="e_cat_pwd">PWD</label>
          </div>
          <div class="form-check">
            <input class="form-check-input e-ben-cat" type="checkbox" value="Student" id="e_cat_student" <?= ($cat==="Student")?"checked":"" ?>>
            <label class="form-check-label" for="e_cat_student">Student</label>
          </div>

          <input type="hidden" name="beneficiary_category" id="e_beneficiary_category" value="<?= htmlspecialchars($cat) ?>">
          <div class="text-muted small mt-1">*Single-select only.</div>
        </div>

      </div>
    </div>

    <div class="card-footer d-flex justify-content-between bg-white">
      <button type="submit"
              class="btn btn-outline-danger"
              formaction="delete.php"
              onclick="return confirm('Are you sure you want to delete this resident record?');">
        <i class="bi bi-trash me-1"></i>Delete
      </button>

      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
        <button class="btn brdss-btn" type="submit"><i class="bi bi-save2 me-1"></i>Save Changes</button>
      </div>
    </div>
  </form>
</div>

<script>
const eBirthInput = document.getElementById("e_birthday");
const eAgeInput   = document.getElementById("e_age");
eBirthInput.addEventListener("change", () => {
  if (!eBirthInput.value) { eAgeInput.value = ""; return; }
  const b = new Date(eBirthInput.value);
  const t = new Date();
  let age = t.getFullYear() - b.getFullYear();
  const m = t.getMonth() - b.getMonth();
  if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
  eAgeInput.value = (age >= 0 ? age : "");
});

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
</script>

</body>
</html>