<?php

namespace App\Actions\Application;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;

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
        if ($note !== null) {
            $note->update([
                'content' => $data['content'],
                'edited_at' => now(),
            ]);

            return $note;
        }

        return ApplicationNote::create([
            'application_id' => $application->id,
            'user_id' => $actor->id,
            'content' => $data['content'],
        ]);
    }
}
