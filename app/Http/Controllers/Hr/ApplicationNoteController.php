<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\DeleteApplicationNoteAction;
use App\Actions\Application\SaveApplicationNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\Application\StoreApplicationNoteRequest;
use App\Http\Requests\Hr\Application\UpdateApplicationNoteRequest;
use App\Models\Application;
use App\Models\ApplicationNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicationNoteController extends Controller
{
    public function store(StoreApplicationNoteRequest $request, Application $application, SaveApplicationNoteAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $application, $request->user());

        return redirect()->route('hr.applications.show', $application)->with('status', 'Đã thêm ghi chú.');
    }

    public function update(
        UpdateApplicationNoteRequest $request,
        Application $application,
        ApplicationNote $note,
        SaveApplicationNoteAction $action
    ): RedirectResponse {
        abort_unless($note->application_id === $application->id, 404);

        $action->handle($request->validated(), $application, $request->user(), $note);

        return redirect()->route('hr.applications.show', $application)->with('status', 'Đã cập nhật ghi chú.');
    }

    public function destroy(
        Request $request,
        Application $application,
        ApplicationNote $note,
        DeleteApplicationNoteAction $action
    ): RedirectResponse {
        abort_unless($note->application_id === $application->id, 404);

        $action->handle($application, $note, $request->user());

        return redirect()->route('hr.applications.show', $application)->with('status', 'Đã xóa ghi chú.');
    }
}
