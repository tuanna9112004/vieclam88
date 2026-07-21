<?php

namespace App\Http\Controllers\Hr;

use App\Actions\CompanyContact\SaveCompanyContactAction;
use App\Enums\CompanyContactStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\CompanyContact\StoreCompanyContactRequest;
use App\Http\Requests\Hr\CompanyContact\UpdateCompanyContactRequest;
use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyContactController extends Controller
{
    public function index(Request $request, Company $company): View|JsonResponse
    {
        $this->authorize('viewAny', CompanyContact::class);

        $contacts = $company->companyContacts()->orderBy('name')->get();

        // Quick Create tu form Job: dropdown Contact loc theo Company qua AJAX goi lai dung
        // route nay, chi tra ve contact active (Job chi duoc gan contact active — CORE-FLOWS 392).
        if ($request->wantsJson()) {
            return response()->json(
                $contacts->filter(fn ($contact) => $contact->status === CompanyContactStatus::Active)
                    ->values()
                    ->map(fn ($contact) => ['id' => $contact->id, 'name' => $contact->name])
            );
        }

        return view('hr.companies.contacts.index', compact('company', 'contacts'));
    }

    public function store(StoreCompanyContactRequest $request, Company $company, SaveCompanyContactAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $company);

        return redirect()->route('hr.company-contacts.index', $company)->with('status', 'Đã tạo đầu mối.');
    }

    public function update(
        UpdateCompanyContactRequest $request,
        Company $company,
        CompanyContact $contact,
        SaveCompanyContactAction $action
    ): RedirectResponse {
        abort_unless($contact->company_id === $company->id, 404);

        $action->handle($request->validated(), $company, $contact);

        return redirect()->route('hr.company-contacts.index', $company)->with('status', 'Đã cập nhật đầu mối.');
    }

    public function destroy(Company $company, CompanyContact $contact): RedirectResponse
    {
        abort_unless($contact->company_id === $company->id, 404);

        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->route('hr.company-contacts.index', $company)->with('status', 'Đã xóa đầu mối.');
    }

    public function restore(Company $company, CompanyContact $contact): RedirectResponse
    {
        abort_unless($contact->company_id === $company->id, 404);

        $this->authorize('restore', $contact);

        $contact->restore();

        return redirect()->route('hr.company-contacts.index', $company)->with('status', 'Đã khôi phục đầu mối.');
    }
}
