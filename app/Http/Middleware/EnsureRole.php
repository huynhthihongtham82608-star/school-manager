<?php

namespace App\Http\Middleware;

use App\Support\Rbac\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $this->matchesAnyRole($user, $roles)) {
            abort(403, 'Bạn không có quyền truy cập.');
        }

        if ($user->role === 'staff' && in_array('staff', $roles, true)) {
            $permission = PermissionCatalog::routePermission($request->route()?->getName());

            if ($permission && ! $user->hasPermission($permission)) {
                abort(403, 'Bạn không có quyền thực hiện chức năng này.');
            }
        }

        return $next($request);
    }

    private function matchesAnyRole($user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role === 'teacher' && $user->isTeacher()) {
                return true;
            }

            if ($role === 'homeroom' && $user->isHomeroom()) {
                return true;
            }

            if ($user->role === $role) {
                return true;
            }
        }

        return false;
    }
}
