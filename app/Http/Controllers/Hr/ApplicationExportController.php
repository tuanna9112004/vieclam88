<?php

namespace App\Http\Controllers\Hr;

use App\Actions\Application\ExportApplicationsCsvAction;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationExportController extends Controller
{
    public function index(Request $request, ExportApplicationsCsvAction $action): StreamedResponse
    {
        $this->authorize('export', Application::class);

        $filters = $request->only([
            'q',
            'stage',
            'owner_branch_id',
            'date_from',
            'date_to',
            'uncontacted',
            'needs_duplicate_review',
        ]);

        return $action->handle($filters, $request->user());
    }
}
