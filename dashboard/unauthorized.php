<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Unauthorized Access</title>
  <style>
    body { font-family: sans-serif; text-align: center; margin-top: 50px; background: #f6f7fb; color: #333; }
    h1 { color: #8b1e2d; }
    a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2F5D8A; color: #fff; text-decoration: none; border-radius: 5px; }
  </style>
</head>
<body>
  <h1>403 Unauthorized</h1>
  <p>You do not have permission to access this page.</p>
  <a href="../dashboard/super.php">Return to Dashboard</a>
</body>
</html>
