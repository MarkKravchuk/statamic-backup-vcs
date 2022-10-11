<?php

namespace Markkravchuk\BackupVcs\Listeners;

use Statamic\Events\NavSaved;
use Statamic\Events\FormSaved;
use Statamic\Events\RoleSaved;
use Statamic\Events\TermSaved;
use Statamic\Events\UserSaved;
use Statamic\Events\AssetSaved;
use Statamic\Events\EntrySaved;
use Statamic\Events\NavDeleted;
use Statamic\Events\FormDeleted;
use Statamic\Events\RoleDeleted;
use Statamic\Events\TermDeleted;
use Statamic\Events\UserDeleted;
use Statamic\Events\AssetDeleted;
use Statamic\Events\EntryDeleted;
use League\Flysystem\MountManager;
use Statamic\Events\FieldsetSaved;
use Statamic\Events\TaxonomySaved;
use Statamic\Events\BlueprintSaved;
use Statamic\Events\GlobalSetSaved;
use Statamic\Events\UserGroupSaved;
use Statamic\Events\CollectionSaved;
use Statamic\Events\FieldsetDeleted;
use Statamic\Events\TaxonomyDeleted;
use Statamic\Events\AssetFolderSaved;
use Statamic\Events\BlueprintDeleted;
use Statamic\Events\GlobalSetDeleted;
use Statamic\Events\UserGroupDeleted;
use Statamic\Events\CollectionDeleted;
use Statamic\Events\SubmissionDeleted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Statamic\Events\AssetFolderDeleted;
use Statamic\Events\AssetContainerSaved;
use Statamic\Events\AssetContainerDeleted;

class BackupVcsEventSubscriber
{
    /**
     * Saves the changed entries to Eloquent Model Driver storing the history .
     *
     * @return void
     * @throws \Exception
     */
    public function addEntry($message)
    {
        Artisan::call('backup:run --disable-notifications');

        try {
            $filepath = $this->renameLocalBackup($message);
            $this->moveBackupToCloudStorage($filepath);
            $this->removeLocalBackup();
        } catch (\Exception $e) {
            $this->removeLocalBackup();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Handle the AssetContainerDeleted event.
     *
     * @param AssetContainerDeleted $event
     *
     * @return void
     */
    public function handleAssetContainerDeleted(AssetContainerDeleted $event)
    {
        $this->addEntry("Deleted asset container '" . $event->container->title() . "' (id: '" . $event->container->id() . "')");
    }

    /**
     * Handle the AssetContainerSaved event.
     *
     * @param AssetContainerSaved $event
     *
     * @return void
     */
    public function handleAssetContainerSaved(AssetContainerSaved $event)
    {
        $this->addEntry("Edited asset container '" . $event->container->title() . "' (id: '" . $event->container->id() . "')");
    }

    /**
     * Handle the AssetDeleted event.
     *
     * @param AssetDeleted $event
     *
     * @return void
     */
    public function handleAssetDeleted(AssetDeleted $event)
    {
        $this->addEntry("Deleted asset '" . $event->asset->url() . "'");
    }

    /**
     * Handle the AssetFolderDeleted event.
     *
     * @param AssetFolderDeleted $event
     *
     * @return void
     */
    public function handleAssetFolderDeleted(AssetFolderDeleted $event)
    {
        $this->addEntry("Deleted asset folder '" . $event->folder->path() . "'");
    }

    /**
     * Handle the AssetFolderSaved event.
     *
     * @param AssetFolderSaved $event
     *
     * @return void
     */
    public function handleAssetFolderSaved(AssetFolderSaved $event)
    {
        $this->addEntry("Edited asset folder " . $event->folder->path());
    }

    /**
     * Handle the AssetSaved event.
     *
     * @param AssetSaved $event
     *
     * @return void
     */
    public function handleAssetSaved(AssetSaved $event)
    {
        $this->addEntry("Edited asset " . $event->asset->url() . "");
    }

    /**
     * Handle the CollectionDeleted event.
     *
     * @param CollectionDeleted $event
     *
     * @return void
     */
    public function handleCollectionDeleted(CollectionDeleted $event)
    {
        $this->addEntry("Deleted collection '" . $event->collection->title() . "' (handle: '" . $event->collection->handle() . "')");
    }

    /**
     * Handle the CollectionSaved event.
     *
     * @param CollectionSaved $event
     *
     * @return void
     */
    public function handleCollectionSaved(CollectionSaved $event)
    {
        $this->addEntry("Edited collection '" . $event->collection->title() . "' (handle '" . $event->collection->handle() . "')");
    }

    /**
     * Handle the EntryDeleted event.
     *
     * @param EntryDeleted $event
     *
     * @return void
     */
    public function handleEntryDeleted(EntryDeleted $event)
    {
        $this->addEntry("Deleted page '" . $event->entry->title . " (url " . $event->entry->slug . ")");
    }

    /**
     * Handle the EntrySaved event.
     *
     * @param EntrySaved $event
     *
     * @return void
     */
    public function handleEntrySaved(EntrySaved $event)
    {
        $this->addEntry("Edited page " . $event->entry->title . " (url " . $event->entry->slug . ")");
    }

    /**
     * Handle the GlobalSetDeleted event.
     *
     * @param GlobalSetDeleted $event
     *
     * @return void
     */
    public function handleGlobalSetDeleted(GlobalSetDeleted $event)
    {
        $this->addEntry("Celeted global set '" . $event->globals->title() . "' (handle '" . $event->globals->handle() . "')");
    }

    /**
     * Handle the GlobalSetSaved event.
     *
     * @param GlobalSetSaved $event
     *
     * @return void
     */
    public function handleGlobalSetSaved(GlobalSetSaved $event)
    {
        $this->addEntry("Edited global set '" . $event->globals->title() . "' (handle: '" . $event->globals->handle() . "')");
    }

    /**
     * Handle the NavDeleted event.
     *
     * @param NavDeleted $event
     *
     * @return void
     */
    public function handleNavDeleted(NavDeleted $event)
    {
        $this->addEntry("Deleted navigation '" . $event->nav->title() . "' (handle: '" . $event->nav->handle() . "')");
    }

    /**
     * Handle the NavSaved event.
     *
     * @param NavSaved $event
     *
     * @return void
     */
    public function handleNavSaved(NavSaved $event)
    {
        $this->addEntry("Edited navigation '" . $event->nav->title() . "' (handle: '" . $event->nav->handle() . "')");
    }

    /**
     * Handle the TaxonomyDeleted event.
     *
     * @param TaxonomyDeleted $event
     *
     * @return void
     */
    public function handleTaxonomyDeleted(TaxonomyDeleted $event)
    {
        $this->addEntry("Deleted taxonomy '" . $event->taxonomy->title() . "' (handle: '" . $event->taxonomy->handle() . "')");
    }

    /**
     * Handle the TaxonomySaved event.
     *
     * @param TaxonomySaved $event
     *
     * @return void
     */
    public function handleTaxonomySaved(TaxonomySaved $event)
    {
        $this->addEntry("Created/edited taxonomy '" . $event->taxonomy->title() . "' (handle: '" . $event->taxonomy->handle() . "')");
    }

    /**
     * Handle the TermDeleted event.
     *
     * @param TermDeleted $event
     *
     * @return void
     */
    public function handleTermDeleted(TermDeleted $event)
    {
        $this->addEntry("Deleted term '" . $event->term->title() . "' (id: '" . $event->term->id() . "') in taxonomy '" . $event->term->taxonomy()->title() . "'");
    }

    /**
     * Handle the TermSaved event.
     *
     * @param TermSaved $event
     *
     * @return void
     */
    public function handleTermSaved(TermSaved $event)
    {
        $this->addEntry("Edited term '" . $event->term->title() . "' (id: '" . $event->term->id() . "') in taxonomy '" . $event->term->taxonomy()->title() . "'");
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     *
     * @return void
     */
    public function subscribe($events)
    {
        return [
            AssetContainerDeleted::class => 'handleAssetContainerDeleted',
            AssetContainerSaved::class => 'handleAssetContainerSaved',
            AssetDeleted::class => 'handleAssetDeleted',
            AssetFolderDeleted::class => 'handleAssetFolderDeleted',
            AssetFolderSaved::class => 'handleAssetFolderSaved',
            AssetSaved::class => 'handleAssetSaved',
            CollectionDeleted::class => 'handleCollectionDeleted',
            CollectionSaved::class => 'handleCollectionSaved',
            EntryDeleted::class => 'handleEntryDeleted',
            EntrySaved::class => 'handleEntrySaved',
            GlobalSetDeleted::class => 'handleGlobalSetDeleted',
            GlobalSetSaved::class => 'handleGlobalSetSaved',
            NavDeleted::class => 'handleNavDeleted',
            NavSaved::class => 'handleNavSaved',
            TaxonomyDeleted::class => 'handleTaxonomyDeleted',
            TaxonomySaved::class => 'handleTaxonomySaved',
            TermDeleted::class => 'handleTermDeleted',
            TermSaved::class => 'handleTermSaved',
        ];
    }

    private function renameLocalBackup($message): string
    {
        $relative_dir = '/backup';

        $backupArr = Storage::disk('local')->allFiles($relative_dir);

        if (sizeof($backupArr) != 1) {
            throw new \Exception('The size of local directory with backup does not equal expected 1 file');
        }
        date_default_timezone_set('Europe/Amsterdam');
        $today = date("F j, Y, g-i a");

        $newFileName = $relative_dir . '/' . $today . ' - ' . auth()->user()->name . ' - ' . $message . '.zip';

        Storage::disk('local')->move($backupArr[0], $newFileName);

        return $newFileName;
    }

    private function removeLocalBackup()
    {
        Storage::disk('local')->deleteDirectory('unarchived');
        Storage::disk('local')->deleteDirectory('backup');
    }

    private function moveBackupToCloudStorage($filepath)
    {
        $mountManager = new MountManager([
            'gcs-backup' => \Storage::disk('gcs-backup')->getDriver(),
            'local' => \Storage::disk('local')->getDriver(),
        ]);
        $mountManager->copy('local://'.$filepath, 'gcs-backup://'.$filepath);
    }
}
