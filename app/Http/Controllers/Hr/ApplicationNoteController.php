<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\SaveApplicationNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\StoreApplicationNoteRequest;
use App\Http\Requests\Hr\Application\UpdateApplicationNoteRequest;
use App\Models\Application;
use App\Models\ApplicationNote;
use Illuminate\Http\RedirectResponse;

class ApplicationNoteController extends Controller
{
    public function store(StoreApplicationNoteRequest $request, Application $application, SaveApplicationNoteAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $application, $request->user());

        return redirect()->route('hr.applications.index')->with('status', 'Đã thêm ghi chú.');
    }

    public function update(
        UpdateApplicationNoteRequest $request,
        Application $application,
        ApplicationNote $note,
        SaveApplicationNoteAction $action
    ): RedirectResponse {
        abort_unless($note->application_id === $application->id, 404);

        $action->handle($request->validated(), $application, $request->user(), $note);

        return redirect()->route('hr.applications.index')->with('status', 'Đã cập nhật ghi chú.');
    }

    public function destroy(Application $application, ApplicationNote $note): RedirectResponse
    {
        abort_unless($note->application_id === $application->id, 404);

        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->route('hr.applications.index')->with('status', 'Đã xóa ghi chú.');
    }
}
