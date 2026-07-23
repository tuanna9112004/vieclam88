<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\ChangeApplicationStageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\ChangeApplicationStageRequest;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;

class ApplicationStageController extends Controller
{
    public function store(ChangeApplicationStageRequest $request, Application $application, ChangeApplicationStageAction $action): RedirectResponse
    {
        $action->handle($application, $request->validated(), $request->user());

        return redirect()->route('hr.applications.show', $application)->with('status', 'Đã cập nhật giai đoạn.');
    }
}
