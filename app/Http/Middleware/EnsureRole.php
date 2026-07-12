<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || ! $this->matchesAnyRole($user, $roles)) {
            abort(403, 'Bạn không có quyền truy cập');
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
