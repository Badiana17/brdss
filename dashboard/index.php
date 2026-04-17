<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../auth/login.php");
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">BRDSS Dashboard</span>
    <div class="text-white small">
      <?= htmlspecialchars($_SESSION["username"]) ?> (<?= htmlspecialchars($_SESSION["role"]) ?>)
      <a class="btn btn-sm btn-outline-light ms-2" href="../auth/logout.php">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <div class="alert alert-success">
      Login successful ✅ Welcome to BRDSS.
    </div>
  </main>
</body>
</html>