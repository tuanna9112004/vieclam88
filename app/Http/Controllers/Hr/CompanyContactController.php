<?php

namespace App\Http\Controllers\Hr;

use App\Actions\CompanyContact\SaveCompanyContactAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\CompanyContact\StoreCompanyContactRequest;
use App\Http\Requests\Hr\CompanyContact\UpdateCompanyContactRequest;
use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyContactController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorize('viewAny', CompanyContact::class);

        $contacts = $company->companyContacts()->orderBy('name')->get();

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
