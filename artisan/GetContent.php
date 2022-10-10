<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Flysystem\MountManager;
use Illuminate\Support\Facades\Storage;

class GetContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the most recent available content from Backup VCS';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle(): int
    {
        try {
            $this->deleteCurrentContent();
            $output = $this->downloadNewContent();
            $this->unzipNewContent($output);
            $this->pasteNewContent();
            $this->removeTemporaryContentFolder();
        } catch (\Exception $e) {
            $this->removeTemporaryContentFolder();
            throw new \Exception($e);
        }
        return 0;
    }

    private function deleteCurrentContent()
    {
        $dirs = Storage::disk('content')->directories('');

        foreach ($dirs as $dir) {
            Storage::disk('content')->deleteDirectory($dir);
        }
    }

    /**
     * @throws \League\Flysystem\FilesystemException
     */
    private function downloadNewContent(): string
    {
        //TODO: Implement here env check (staging or prod)

        $files = Storage::disk('gcs-backup')->files('backup/');

        $fileData = collect();
        foreach ($files as $file) {
            $fileData->push([
                'file' => $file,
                'date' => Storage::disk('gcs-backup')->lastModified($file),
            ]);
        }
        $filepath = $fileData->sortByDesc('date')->first()["file"];

        $mountManager = new MountManager([
            'gcs-backup' => \Storage::disk('gcs-backup')->getDriver(),
            'local' => \Storage::disk('local')->getDriver(),
        ]);
        $mountManager->copy('gcs-backup://'.$filepath, 'local://'.$filepath);

        return $filepath;
    }

    /**
     * @throws \Exception
     */
    private function unzipNewContent($filepath)
    {
        $zip = new \ZipArchive;
        if ($zip->open(Storage::disk('local')->path($filepath))) {
            $zip->extractTo(Storage::disk('local')->path('/unarchived'));
            $zip->close();
        } else {
            throw new \Exception('The backup zip does not exist or is not readable.');
        }
    }

    private function pasteNewContent()
    {
        $pathToUnarchivedContent = Storage::disk('local')->path('/unarchived/*');
        
        
        // php artisan command is run as from cli (on initial fetch of content) as on addon (to update the content)
        exec('cp -r '.$pathToUnarchivedContent.' .');
        exec('cp -r '.$pathToUnarchivedContent.' ../');
        exec('chown -R www-data:www-data ./');
    }

    private function removeTemporaryContentFolder()
    {
        Storage::disk('local')->deleteDirectory('unarchived');
        Storage::disk('local')->deleteDirectory('backup');
    }
}
