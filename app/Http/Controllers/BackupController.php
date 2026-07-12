<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    private string $directory = 'backups';

    public function index()
    {
        Storage::disk('local')->makeDirectory($this->directory);

        $files = collect(Storage::disk('local')->files($this->directory))
            ->filter(fn ($path) => str_ends_with($path, '.sql'))
            ->map(function ($path) {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => Storage::disk('local')->size($path),
                    'updated_at' => Storage::disk('local')->lastModified($path),
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return view('system.backups', compact('files'));
    }

    public function store()
    {
        Storage::disk('local')->makeDirectory($this->directory);

        $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
        $path = $this->directory . '/' . $filename;

        Storage::disk('local')->put($path, $this->databaseDump());

        AuditLogger::log('database_backup_created', null, null, 'Tạo bản sao lưu database ' . $filename);

        return back()->with('success', 'Đã tạo bản sao lưu database.');
    }

    public function download(string $filename): StreamedResponse
    {
        abort_unless($filename === basename($filename) && str_ends_with($filename, '.sql'), 404);

        $path = storage_path('app/' . $this->directory . '/' . $filename);
        abort_unless(File::exists($path), 404);

        return response()->streamDownload(function () use ($path) {
            echo File::get($path);
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    private function databaseDump(): string
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::getDatabaseName();
        $tables = collect(DB::select('SHOW FULL TABLES'))
            ->map(fn ($row) => array_values((array) $row))
            ->filter(fn ($values) => ($values[1] ?? '') === 'BASE TABLE')
            ->pluck(0)
            ->values();

        $sql = "-- School Manager database backup\n";
        $sql .= '-- Database: ' . $database . "\n";
        $sql .= '-- Created at: ' . now()->format('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $createRow = (array) DB::selectOne('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';

            $sql .= "DROP TABLE IF EXISTS `" . str_replace('`', '``', $table) . "`;\n";
            $sql .= $createSql . ";\n\n";

            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use (&$sql, $table, $pdo) {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $columns = collect(array_keys($data))
                        ->map(fn ($column) => '`' . str_replace('`', '``', $column) . '`')
                        ->implode(', ');
                    $values = collect(array_values($data))
                        ->map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value))
                        ->implode(', ');

                    $sql .= 'INSERT INTO `' . str_replace('`', '``', $table) . "` ({$columns}) VALUES ({$values});\n";
                }
            });

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
