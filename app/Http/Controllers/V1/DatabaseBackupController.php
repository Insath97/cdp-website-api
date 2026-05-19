<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ActivityLogTrait;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DatabaseBackupController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Database Export', only: ['export']),
        ];
    }

    /**
     * Export the database as a SQL file.
     */
    public function export()
    {
        try {
            $dbConfig = config('database.connections.mysql');
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];

            $filename = "backup-" . $database . "-" . date('Y-m-d-H-i-s') . ".sql";
            $path = storage_path('app/' . $filename);

            // 1. Find mysqldump path (especially for Laragon)
            $mysqldump = 'mysqldump';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $paths = glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe');
                if (!empty($paths)) {
                    $mysqldump = '"' . str_replace('/', '\\', $paths[0]) . '"';
                }
            }

            // 2. Handle empty password correctly for Windows/MySQL
            $passwordPart = $password ? '--password=' . escapeshellarg($password) : '';

            // 3. Construct command and REDIRECT errors to output so we can read them
            $command = sprintf(
                '%s --user=%s %s --host=%s --port=%s %s > %s 2>&1',
                $mysqldump,
                escapeshellarg($username),
                $passwordPart,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            // Execute the command
            $returnVar = null;
            $output = [];
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // If it fails, we now have the ACTUAL error in $output
                $errorMessage = !empty($output) ? implode("\n", $output) : 'Unknown error';
                throw new \Exception('Database backup failed: ' . $errorMessage);
            }

            if (!file_exists($path) || filesize($path) === 0) {
                throw new \Exception('Database backup file was not created or is empty.');
            }

            $this->logActivity('SYSTEM', 'Database Export', "Exported database backup: {$filename}");

            return response()->download($path)->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export database',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
