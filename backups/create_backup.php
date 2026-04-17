<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

date_default_timezone_set("Asia/Manila");

$dbHost = "127.0.0.1";
$dbUser = "root";
$dbPass = "";
$dbName = "brdss_db";

$backupFolder = __DIR__ . "/files/";

if (!is_dir($backupFolder)) {
    mkdir($backupFolder, 0777, true);
}

$timestamp = date("Y-m-d_H-i-s");
$fileName = "backup_" . $timestamp . ".sql";
$filePath = $backupFolder . $fileName;

$tables = [];
$tableResult = mysqli_query($conn, "SHOW TABLES");

while ($row = mysqli_fetch_row($tableResult)) {
    $tables[] = $row[0];
}

$sqlScript = "";
foreach ($tables as $table) {
    $createTableResult = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    $createTableRow = mysqli_fetch_row($createTableResult);

    $sqlScript .= "\n\n" . $createTableRow[1] . ";\n\n";

    $dataResult = mysqli_query($conn, "SELECT * FROM `$table`");
    while ($dataRow = mysqli_fetch_assoc($dataResult)) {
        $columns = array_map(function($col) {
            return "`" . $col . "`";
        }, array_keys($dataRow));

        $values = array_map(function($value) use ($conn) {
            if (is_null($value)) {
                return "NULL";
            }
            return "'" . mysqli_real_escape_string($conn, $value) . "'";
        }, array_values($dataRow));

        $sqlScript .= "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
    }
}

if (file_put_contents($filePath, $sqlScript) !== false) {
    $fileSize = filesize($filePath);
    $relativePath = "backups/files/" . $fileName;
    $userId = (int)$_SESSION['user_id'];
    $location = "External";

    $stmt = mysqli_prepare($conn, "INSERT INTO backup_history (user_id, file_name, file_location, file_size, backup_date) VALUES (?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "issi", $userId, $fileName, $location, $fileSize);
    mysqli_stmt_execute($stmt);

    $_SESSION['backup_success'] = "Backup created successfully.";
} else {
    $_SESSION['backup_error'] = "Failed to create backup file.";
}

header("Location: index.php");
exit;