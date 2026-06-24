<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = Schema::hasTable('audit_logs')
            ? AuditLog::with('user')->latest('created_at')->paginate(20)
            : collect();

        return view('audit_logs.index', compact('logs'));
    }
}
