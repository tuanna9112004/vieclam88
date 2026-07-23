<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * docs/CORE-FLOWS.md muc 7.3.1: Note la ghi chu lam viec, khong giu lich su day du cac lan sua —
 * chi cap nhat edited_at (moc gan nhat). user_id chi gan khi tao (nguoi tao), khong doi khi sua.
 */
class SaveApplicationNoteAction
{
    /**
     * @param  array{content: string}  $data
     */
    public function handle(array $data, Application $application, User $actor, ?ApplicationNote $note = null): ApplicationNote
    {
        return DB::transaction(function () use ($data, $application, $actor, $note) {
            /** @var Application $lockedApplication */
            $lockedApplication = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($note !== null) {
                /** @var ApplicationNote $lockedNote */
                $lockedNote = ApplicationNote::whereKey($note->id)
                    ->where('application_id', $lockedApplication->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedNote->setRelation('application', $lockedApplication);
                Gate::forUser($actor)->authorize('update', $lockedNote);

                $lockedNote->update([
                    'content' => $data['content'],
                    'edited_at' => now(),
                ]);

                return $lockedNote;
            }

            Gate::forUser($actor)->authorize('create', [ApplicationNote::class, $lockedApplication]);

            return ApplicationNote::create([
                'application_id' => $lockedApplication->id,
                'user_id' => $actor->id,
                'content' => $data['content'],
            ]);
        });
    }
}
