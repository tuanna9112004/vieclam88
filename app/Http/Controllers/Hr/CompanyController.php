<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Company\SaveCompanyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Company\StoreCompanyRequest;
use App\Http\Requests\Hr\Company\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()->orderBy('name')->paginate(20);
        $trashedCompanies = Company::onlyTrashed()->orderBy('name')->get();

        return view('hr.companies.index', compact('companies', 'trashedCompanies'));
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('hr.companies.create');
    }

    public function store(StoreCompanyRequest $request, SaveCompanyAction $action): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $company = $action->handle($data, $request->user());

        // Quick Create tu form Job (AJAX, khong roi man hinh) goi lai dung route/Policy/Action
        // nay — chi khac dinh dang response, khong duplicate logic nghiep vu.
        if ($request->wantsJson()) {
            return response()->json(['id' => $company->id, 'name' => $company->name]);
        }

        return redirect()->route('hr.companies.index')
            ->with('status', 'Đã tạo công ty.')
            ->with('duplicate_warning', $this->duplicateWarning($action, $data['name'], $company->id));
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('hr.companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company, SaveCompanyAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->handle($data, $request->user(), $company);

        return redirect()->route('hr.companies.index')
            ->with('status', 'Đã cập nhật công ty.')
            ->with('duplicate_warning', $this->duplicateWarning($action, $data['name'], $company->id));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return redirect()->route('hr.companies.index')->with('status', 'Đã xóa công ty.');
    }

    public function restore(Company $company): RedirectResponse
    {
        $this->authorize('restore', $company);

        $company->restore();

        return redirect()->route('hr.companies.index')->with('status', 'Đã khôi phục công ty.');
    }

    protected function duplicateWarning(SaveCompanyAction $action, string $name, int $excludeId): ?string
    {
        $count = $action->countDuplicateNames($name, $excludeId);

        if ($count === 0) {
            return null;
        }

        return "Có {$count} công ty khác đang trùng tên \"{$name}\" — kiểm tra tránh trùng lặp trước khi tạo Job.";
    }
}
