<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteApplicationNoteAction
{
    public function handle(Application $application, ApplicationNote $note, User $actor): void
    {
        DB::transaction(function () use ($application, $note, $actor) {
            /** @var Application $lockedApplication */
            $lockedApplication = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            /** @var ApplicationNote $lockedNote */
            $lockedNote = ApplicationNote::whereKey($note->id)
                ->where('application_id', $lockedApplication->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedNote->setRelation('application', $lockedApplication);
            Gate::forUser($actor)->authorize('delete', $lockedNote);

            $lockedNote->delete();
        });
    }
}
