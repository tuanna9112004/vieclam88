<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * ADR-077: users.status != active mất quyền ở request kế tiếp — logout/invalidate
     * session ngay, không chỉ chặn ở lần login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->isActive() || ! $user->hasValidBranchAssignment())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('hr.login');
        }

        return $next($request);
    }
}
