<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;

class DatabaseBackupController extends Controller
{
    public function __invoke(Request $request, DatabaseBackupService $backupService)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $backup = $backupService->createBackup();

        return response()->download(
            $backup['path'],
            $backup['filename'],
            ['Content-Type' => 'application/sql'],
        )->deleteFileAfterSend(true);
    }
}
