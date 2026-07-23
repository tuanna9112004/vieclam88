<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\RecordContactAttemptAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\StoreContactAttemptRequest;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;

class ContactAttemptController extends Controller
{
    public function store(StoreContactAttemptRequest $request, Application $application, RecordContactAttemptAction $action): RedirectResponse
    {
        $action->handle($application, $request->validated(), $request->user());

        return redirect()->route('hr.applications.show', $application)->with('status', 'Đã ghi nhận liên hệ.');
    }
}
