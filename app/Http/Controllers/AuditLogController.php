<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'user_id', 'module', 'action', 'date_from', 'date_to']);
        $users = Schema::hasTable('users')
            ? User::query()->orderBy('username')->get()
            : collect();
        $modules = collect();
        $actions = collect();

        $logs = collect();

        if (Schema::hasTable('audit_logs')) {
            $modules = AuditLog::query()
                ->select('entity_type')
                ->whereNotNull('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type');

            $actions = AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action');

            $query = AuditLog::with('user')->latest('created_at');

            $query->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($search) use ($keyword) {
                    $search->where('action', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhere('entity_type', 'like', '%' . $keyword . '%')
                        ->orWhere('ip_address', 'like', '%' . $keyword . '%')
                        ->orWhereHas('user', function ($userQuery) use ($keyword) {
                            $userQuery->where('username', 'like', '%' . $keyword . '%');
                        });
                });
            });

            $query->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId));
            $query->when($filters['module'] ?? null, fn ($query, $module) => $query->where('entity_type', $module));
            $query->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action));
            $query->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date));
            $query->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

            $logs = $query->paginate(20)->withQueryString();
        }

        return view('audit_logs.index', compact('logs', 'users', 'modules', 'actions', 'filters'));
    }
}
