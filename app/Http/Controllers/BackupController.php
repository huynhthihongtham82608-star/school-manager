<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        $filename = $this->createBackupFile();

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

    public function destroy(string $filename)
    {
        abort_unless($filename === basename($filename) && str_ends_with($filename, '.sql'), 404);

        $path = $this->directory . '/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        Storage::disk('local')->delete($path);

        AuditLogger::log('database_backup_deleted', null, null, 'Xóa vĩnh viễn bản sao lưu database ' . $filename);

        return back()->with('success', 'Đã xóa tệp sao lưu.');
    }

    public function verifyRestorePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'filename' => ['required', 'string'],
        ]);

        abort_unless($data['filename'] === basename($data['filename']) && str_ends_with($data['filename'], '.sql'), 404);

        $path = $this->directory . '/' . $data['filename'];
        abort_unless(Storage::disk('local')->exists($path), 404);

        $user = $request->user();

        if (! $user?->isSuperAdmin()) {
            return response()->json([
                'message' => 'Chỉ Quản trị tối cao mới được mở cổng khôi phục dữ liệu.',
            ], 403);
        }

        if (! Hash::check($data['password'], $user->getAuthPassword())) {
            return response()->json([
                'message' => 'Mật khẩu admin không chính xác.',
            ], 422);
        }

        $token = Str::random(48);

        $request->session()->put('backup_restore_token_hash', hash('sha256', $token));
        $request->session()->put('backup_restore_unlocked_until', now()->addMinutes(10)->timestamp);
        $request->session()->put('backup_restore_filename', $data['filename']);

        return response()->json([
            'message' => 'Mật khẩu chính xác. Vui lòng chọn lớp bảo vệ sao lưu trước khi khôi phục.',
            'restore_token' => $token,
        ]);
    }

    public function restore(Request $request, string $filename)
    {
        abort_unless($filename === basename($filename) && str_ends_with($filename, '.sql'), 404);

        $data = $request->validate([
            'restore_token' => ['required', 'string'],
            'backup_current' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (! $user?->isSuperAdmin()) {
            return response()->json([
                'message' => 'Chỉ Quản trị tối cao mới được khôi phục dữ liệu.',
            ], 403);
        }

        $expectedHash = $request->session()->get('backup_restore_token_hash');
        $expiresAt = (int) $request->session()->get('backup_restore_unlocked_until', 0);
        $verifiedFilename = $request->session()->get('backup_restore_filename');
        $providedHash = hash('sha256', $data['restore_token']);

        if (! $expectedHash || ! hash_equals($expectedHash, $providedHash) || $verifiedFilename !== $filename || now()->timestamp > $expiresAt) {
            return response()->json([
                'message' => 'Cổng khôi phục đã khóa hoặc phiên xác thực đã hết hạn.',
            ], 403);
        }

        $path = $this->directory . '/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        $sql = Storage::disk('local')->get($path);

        if (! str_starts_with($sql, '-- School Manager database backup')) {
            return response()->json([
                'message' => 'Tệp sao lưu không đúng định dạng của hệ thống.',
            ], 422);
        }

        try {
            @set_time_limit(0);

            $preRestoreBackup = null;

            if ($request->boolean('backup_current')) {
                $preRestoreBackup = $this->createBackupFile('pre_restore_');
                AuditLogger::log('database_backup_created', null, null, 'Tự động tạo bản sao lưu trước khi khôi phục dữ liệu ' . $preRestoreBackup);
            }

            DB::connection()->getPdo()->exec($sql);

            $request->session()->forget([
                'backup_restore_token_hash',
                'backup_restore_unlocked_until',
                'backup_restore_filename',
            ]);

            AuditLogger::log('database_backup_restored', null, null, 'Khôi phục dữ liệu từ bản sao lưu database ' . $filename);

            $message = 'Đã khôi phục dữ liệu từ tệp sao lưu.';

            if ($preRestoreBackup) {
                $message .= ' Hệ thống đã tạo bản sao lưu hiện tại trước khi khôi phục: ' . $preRestoreBackup;
            }

            return response()->json(compact('message'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Không thể khôi phục dữ liệu từ tệp sao lưu. Vui lòng kiểm tra lại tệp hoặc liên hệ kỹ thuật.',
            ], 500);
        }
    }

    private function createBackupFile(string $prefix = 'backup_'): string
    {
        Storage::disk('local')->makeDirectory($this->directory);

        $filename = $prefix . now()->format('Ymd_His') . '.sql';
        $path = $this->directory . '/' . $filename;

        Storage::disk('local')->put($path, $this->databaseDump());

        return $filename;
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
