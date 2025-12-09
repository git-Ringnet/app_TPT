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

            // Export trực tiếp qua PHP
            return $this->exportViaPhp($filename, $database);

        } catch (\Exception $e) {
            return redirect()->route('database.index')->with('warning', 'Lỗi export: ' . $e->getMessage());
        }
    }

    /**
     * Export database qua PHP - Format đơn giản, mỗi INSERT một dòng
     */
    private function exportViaPhp($filename, $database)
    {
        try {
            // Tăng thời gian thực thi cho database lớn
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . $database . "\n";
            $sql .= "-- ----------------------------------------------------\n\n";

            $sql .= "SET NAMES utf8mb4;\n";
            $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;

                // Get create table statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "-- ----------------------------\n";
                $sql .= "-- Table structure for `{$tableName}`\n";
                $sql .= "-- ----------------------------\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

                // Get table data - INSERT từng dòng một để đơn giản
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- ----------------------------\n";
                    $sql .= "-- Records of `{$tableName}`\n";
                    $sql .= "-- ----------------------------\n";

                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_keys($rowArray);
                        $values = array_map(function ($value) {
                            if (is_null($value)) {
                                return 'NULL';
                            }
                            // Escape đúng cách cho MySQL
                            $value = str_replace("\\", "\\\\", $value);
                            $value = str_replace("'", "\\'", $value);
                            $value = str_replace("\r\n", "\\r\\n", $value);
                            $value = str_replace("\n", "\\n", $value);
                            $value = str_replace("\r", "\\r", $value);
                            return "'" . $value . "'";
                        }, array_values($rowArray));

                        $columnList = '`' . implode('`, `', $columns) . '`';
                        $valueList = implode(', ', $values);
                        $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES ({$valueList});\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            $sql .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

            return response($sql)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($sql));

        } catch (\Exception $e) {
            return redirect()->route('database.index')->with('warning', 'Lỗi export: ' . $e->getMessage());
        }
    }

    /**
     * Import database từ file SQL (Restore - xóa sạch dữ liệu cũ rồi import)
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
            // Tăng thời gian thực thi và memory cho database lớn
            set_time_limit(0); // Không giới hạn thời gian
            ini_set('memory_limit', '512M');

            $file = $request->file('sql_file');
            $filename = $file->getClientOriginalName();
            $sql = file_get_contents($file->getRealPath());
            $database = config('database.connections.mysql.database');

            // Tắt foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Xóa tất cả các bảng hiện tại
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            }

            // Parse và thực thi từng câu lệnh SQL
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Tách SQL thành các dòng
            $lines = explode("\n", $sql);
            $currentStatement = '';

            foreach ($lines as $line) {
                $line = trim($line);

                // Bỏ qua dòng trống và comments
                if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0) {
                    continue;
                }

                $currentStatement .= $line . ' ';

                // Nếu dòng kết thúc bằng ; thì thực thi
                if (substr(rtrim($line), -1) === ';') {
                    $stmt = trim($currentStatement);
                    $currentStatement = '';

                    if (!empty($stmt)) {
                        try {
                            DB::unprepared($stmt);
                            $successCount++;
                        } catch (\Exception $e) {
                            $errorCount++;
                            $errors[] = $e->getMessage();
                            \Log::error('SQL Import Error: ' . $e->getMessage() . ' - Statement: ' . substr($stmt, 0, 200));
                        }
                    }
                }
            }

            // Bật lại foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            if ($errorCount > 0) {
                \Log::error('SQL Import completed with errors', ['success' => $successCount, 'errors' => $errorCount, 'sample_errors' => array_slice($errors, 0, 5)]);
                return redirect()->route('database.index')
                    ->with('msg', "Restore hoàn tất! ({$successCount} câu lệnh thành công, {$errorCount} lỗi)");
            }

            return redirect()->route('database.index')->with('msg', "Restore database thành công! ({$successCount} câu lệnh) - File: " . $filename);
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \Log::error('SQL Import Fatal Error: ' . $e->getMessage());
            return redirect()->route('database.index')->with('warning', 'Lỗi restore: ' . $e->getMessage());
        }
    }
}
