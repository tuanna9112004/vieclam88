<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * ADR-067: password_changed_at=null (mật khẩu tạm chưa đổi) chặn toàn bộ route HR
     * khác ngoài hr.password.change/update và hr.logout (đăng ký ở group không bọc
     * middleware này trong routes/hr.php).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_changed_at === null) {
            return redirect()->route('hr.password.change');
        }

        return $next($request);
    }
}
