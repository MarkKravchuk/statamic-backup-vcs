<?php

namespace Markkravchuk\BackupVcs;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Statamic\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;

class BackupVcsViewController extends Controller
{
    /**
     * Function that performs filtering based on whether the search query is provided from the UI
     *
     * @param Request $request
     *
     * @return Application|Factory|View
     */
    public function show(Request $request): View|Factory|Application
    {
        $revertToBackup = $request->query('rebase-to-backup-id');
        $backups = $this->getAllBackups();

        if ($revertToBackup) {
            for ($i = 0; $i < $revertToBackup; $i++) {
                Storage::disk('gcs-backup')->delete($backups[$i]['file']);
            }

            $backups = $this->getAllBackups();

            Artisan::call('content:get');
        }

        // Defining the view to display and data to be shown
        return view('backup-vcs::show', [
            'backups' => $backups,
        ]);
    }

    private function getAllBackups(): array
    {
        $files = Storage::disk('gcs-backup')->files('backup/');

        $fileData = collect();
        foreach ($files as $file) {
            $fileData->push([
                'file' => $file,
                'date' => Storage::disk('gcs-backup')->lastModified($file),
                'name' => explode('/', $file)[1],
            ]);
        }
        $fileData = $fileData->sortByDesc('date')->toArray();

        $updated = [];
        foreach ($fileData as $backup) {
            array_push($updated, $backup);
        }

        return $updated;
    }
}
