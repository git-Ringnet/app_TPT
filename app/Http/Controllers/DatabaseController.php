<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    /**
     * Hiển thị trang quản lý database
     */
    public function index()
    {
        return view('database.index', [
            'title' => 'Quản lý Database',
            'activeGroup' => 'systemFirst',
            'activeName' => 'database',
            'dbName' => config('database.connections.mysql.database')
        ]);
    }

    /**
     * Export database ra file SQL và download
     */
    public function export(Request $request)
    {
        try {
            $database = config('database.connections.mysql.database');

            // Tên file backup
            $filename = 'backup_' . $database . '_' . date('d-m-Y_His') . '.sql';

            // Export trực tiếp qua PHP (không cần mysqldump)
            return $this->exportViaPhp($filename, $database);

        } catch (\Exception $e) {
            return redirect()->route('database.index')->with('warning', 'Lỗi export: ' . $e->getMessage());
        }
    }

    /**
     * Export database qua PHP
     */
    private function exportViaPhp($filename, $database)
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . $database . "\n";
            $sql .= "-- ----------------------------------------------------\n\n";

            $sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
            $sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
            $sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
            $sql .= "/*!40101 SET NAMES utf8mb4 */;\n";
            $sql .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
            $sql .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
            $sql .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
            $sql .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
            $sql .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
            $sql .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Get create table statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "--\n";
                $sql .= "-- Table structure for table `{$tableName}`\n";
                $sql .= "--\n\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
                $sql .= "/*!40101 SET character_set_client = utf8 */;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n";
                $sql .= "/*!40101 SET character_set_client = @saved_cs_client */;\n\n";

                // Get table data
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "--\n";
                    $sql .= "-- Dumping data for table `{$tableName}`\n";
                    $sql .= "--\n\n";
                    $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` DISABLE KEYS */;\n";

                    // Batch insert để tối ưu
                    $batchSize = 100;
                    $batches = $rows->chunk($batchSize);

                    foreach ($batches as $batch) {
                        $firstRow = true;
                        $insertSql = "INSERT INTO `{$tableName}` VALUES ";

                        foreach ($batch as $row) {
                            $rowArray = (array) $row;
                            $values = array_map(function ($value) {
                                if (is_null($value)) {
                                    return 'NULL';
                                }
                                // Escape special characters
                                $value = str_replace(['\\', "\x00", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $value);
                                return "'" . $value . "'";
                            }, $rowArray);

                            if (!$firstRow) {
                                $insertSql .= ",";
                            }
                            $insertSql .= "(" . implode(',', $values) . ")";
                            $firstRow = false;
                        }
                        $sql .= $insertSql . ";\n";
                    }

                    $sql .= "/*!40000 ALTER TABLE `{$tableName}` ENABLE KEYS */;\n";
                    $sql .= "UNLOCK TABLES;\n\n";
                }
            }

            $sql .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
            $sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
            $sql .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
            $sql .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
            $sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
            $sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
            $sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
            $sql .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n\n";
            $sql .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

            // Tạo response download trực tiếp (không lưu file)
            return response($sql)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($sql));

        } catch (\Exception $e) {
            return redirect()->route('database.index')->with('warning', 'Lỗi export: ' . $e->getMessage());
        }
    }

    /**
     * Import database từ file SQL
     */
    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|max:102400', // Max 100MB
        ], [
            'sql_file.required' => 'Vui lòng chọn file SQL để import.',
            'sql_file.file' => 'File không hợp lệ.',
            'sql_file.max' => 'File không được vượt quá 100MB.',
        ]);

        try {
            $file = $request->file('sql_file');
            $filename = $file->getClientOriginalName();
            $sql = file_get_contents($file->getRealPath());

            // Tắt foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Tách các câu lệnh SQL
            $statements = $this->parseSqlStatements($sql);
            $successCount = 0;
            $errorCount = 0;

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !$this->isComment($statement)) {
                    try {
                        DB::unprepared($statement);
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        \Log::warning('SQL Import Warning: ' . $e->getMessage() . ' - Statement: ' . substr($statement, 0, 100));
                    }
                }
            }

            // Bật lại foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            if ($errorCount > 0) {
                return redirect()->route('database.index')
                    ->with('msg', "Import thành công! ({$successCount} câu lệnh). Có {$errorCount} lỗi nhỏ đã bỏ qua.");
            }

            return redirect()->route('database.index')->with('msg', 'Import database thành công từ file: ' . $filename);
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return redirect()->route('database.index')->with('warning', 'Lỗi import: ' . $e->getMessage());
        }
    }

    /**
     * Tách các câu lệnh SQL
     */
    private function parseSqlStatements($sql)
    {
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $escaped = false;

        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            // Handle escape character
            if ($escaped) {
                $currentStatement .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $currentStatement .= $char;
                continue;
            }

            if (!$inString) {
                if ($char === '"' || $char === "'" || $char === '`') {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === ';') {
                    $trimmed = trim($currentStatement);
                    if (!empty($trimmed)) {
                        $statements[] = $trimmed;
                    }
                    $currentStatement = '';
                    continue;
                }
            } else {
                if ($char === $stringChar) {
                    $inString = false;
                }
            }

            $currentStatement .= $char;
        }

        // Add last statement if exists
        $trimmed = trim($currentStatement);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /**
     * Kiểm tra xem dòng có phải là comment không
     */
    private function isComment($statement)
    {
        $statement = trim($statement);

        // Skip empty statements
        if (empty($statement)) {
            return true;
        }

        // Skip comment lines
        if (strpos($statement, '--') === 0) {
            return true;
        }
        if (strpos($statement, '#') === 0) {
            return true;
        }

        // Skip MySQL conditional comments that are just settings
        if (preg_match('/^\/\*!\d+\s*(SET|SELECT)\s/i', $statement)) {
            return false; // These are valid SQL
        }

        return false;
    }
}
