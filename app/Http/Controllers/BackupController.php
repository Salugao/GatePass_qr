<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function store(Request $request, DatabaseBackupService $backups): RedirectResponse|StreamedResponse
    {
        $filename = $backups->create($request->user(), $request->ip());

        return Storage::disk('local')->download($filename);
    }
}
