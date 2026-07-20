<?php

namespace App\Http\Controllers\Hr\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Auth\HrLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HrAuthController extends Controller
{
    public function create(): View
    {
        return view('hr.auth.login');
    }

    public function store(HrLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('hr.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('hr.login');
    }
}
