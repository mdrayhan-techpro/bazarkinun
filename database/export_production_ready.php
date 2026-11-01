<?php
// database/export_production_ready.php
// Generates a complete, standalone, TiDB & MySQL 8.0 compliant SQL dump file

require_once __DIR__ . '/../config/db.php';

$outputFile = __DIR__ . '/tidb_production_ready.sql';
$fp = fopen($outputFile, 'w');

if (!$fp) {
    die("Failed to open output file: {$outputFile}\n");
}

fwrite($fp, "-- ========================================================\n");
fwrite($fp, "-- BazarKinun.com — Complete TiDB Cloud & MySQL 8.0 Schema & Seed\n");
fwrite($fp, "-- Generated for Production Deployment\n");
fwrite($fp, "-- ========================================================\n\n");

fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($fp, "SET NAMES utf8mb4;\n\n");
fwrite($fp, "CREATE DATABASE IF NOT EXISTS `bazarkinun_db`;\n");
fwrite($fp, "USE `bazarkinun_db`;\n\n");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Processing table: {$table}...\n";

    // 1. Drop Table IF EXISTS
    fwrite($fp, "-- --------------------------------------------------------\n");
    fwrite($fp, "-- Table structure for `{$table}`\n");
    fwrite($fp, "-- --------------------------------------------------------\n");
    fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

    // 2. Create Table
    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $createSql = $createStmt['Create Table'] ?? '';

    // Normalize for TiDB Cloud compatibility
    // TiDB supports standard InnoDB with utf8mb4
    $createSql = preg_replace('/AUTO_INCREMENT=\d+\s*/i', '', $createSql);
    fwrite($fp, $createSql . ";\n\n");

    // 3. Export Data (Skip test logs or excessive ephemeral records, but keep all master catalog, settings, rules, users)
    $skipDataTables = ['login_attempts', 'otp_verifications'];
    if (in_array($table, $skipDataTables)) {
        fwrite($fp, "-- Ephemeral logs table `{$table}` structure only\n\n");
        continue;
    }

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    $rowCount = count($rows);

    if ($rowCount > 0) {
        fwrite($fp, "-- Dumping data for table `{$table}` ({$rowCount} rows)\n");
        
        // Chunk inserts for efficiency and memory friendliness
        $chunks = array_chunk($rows, 50);
        foreach ($chunks as $chunk) {
            $insertSql = "INSERT INTO `{$table}` VALUES \n";
            $valRows = [];
            foreach ($chunk as $row) {
                $escapedVals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $escapedVals[] = 'NULL';
                    } else {
                        $escapedVals[] = $pdo->quote($val);
                    }
                }
                $valRows[] = "(" . implode(', ', $escapedVals) . ")";
            }
            $insertSql .= implode(",\n", $valRows) . ";\n";
            fwrite($fp, $insertSql);
        }
        fwrite($fp, "\n");
    }
}

fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($fp, "-- ========================================================\n");
fwrite($fp, "-- Database dump completed successfully\n");
fwrite($fp, "-- ========================================================\n");

fclose($fp);

$sizeKb = round(filesize($outputFile) / 1024, 2);
echo "\n✅ Successfully generated TiDB Production Dump: {$outputFile} ({$sizeKb} KB)\n";

