<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        $branches = Branch::query()
            ->where('status', 'active')
            ->whereHas('administrativeUnit', fn (Builder $query) => $query->where('is_active', true))
            ->where(fn (Builder $query) => $query->whereNotNull('phone')->orWhereNotNull('zalo'))
            ->with('administrativeUnit:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'zalo', 'administrative_unit_id', 'address_detail']);

        return view('public.contact.show', ['branches' => $branches]);
    }
}
