<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

$dbHost = "127.0.0.1";
$dbUser = "root";
$dbPass = "";
$dbName = "brdss";

$tables = [];
$tableResult = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($tableResult)) {
    $tables[] = $row[0];
}

$sqlScript = "";
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT * FROM `$table`");
    $columnCount = mysqli_num_fields($result);

    $sqlScript .= "\n-- Table structure for `$table`\n";
    $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
    $sqlScript .= $row2[1] . ";\n\n";

    $sqlScript .= "-- Dumping data for table `$table`\n";
    for ($i = 0; $i < $columnCount; $i++) {
        while ($row = mysqli_fetch_row($result)) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                $row[$j] = $row[$j] ?? '';
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $sqlScript .= '"' . $row[$j] . '"';
                } else {
                    $sqlScript .= '""';
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
    }
    $sqlScript .= "\n";
}

$backupFolder = __DIR__ . '/files/';
if (!is_dir($backupFolder)) {
    mkdir($backupFolder, 0777, true);
}

$timestamp = date("Y-m-d_H-i-s");
$fileName = "backup_" . $timestamp . ".sql";
$filePath = $backupFolder . $fileName;

if (file_put_contents($filePath, $sqlScript) !== false) {
    $fileSize = filesize($filePath);
    $userId = (int)$_SESSION['user_id'];
    $location = "External";

    $stmt = mysqli_prepare($conn, "INSERT INTO backup_history (user_id, file_name, file_location, file_size, backup_date) VALUES (?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "issi", $userId, $fileName, $location, $fileSize);
    mysqli_stmt_execute($stmt);

    $_SESSION['backup_success'] = "Database backup created successfully.";
} else {
    $_SESSION['backup_error'] = "Failed to create backup file.";
}

header("Location: index.php");
exit;