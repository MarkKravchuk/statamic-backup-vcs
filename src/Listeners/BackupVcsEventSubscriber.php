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
        $this->addEntry("Created/edited asset container '" . $event->container->title() . "' (id: '" . $event->container->id() . "')");
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
        $this->addEntry("Created/edited asset folder '" . $event->folder->path() . "'");
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
        $this->addEntry("Created/edited asset '" . $event->asset->url() . "'");
    }

    /**
     * Handle the BlueprintDeleted event.
     *
     * @param BlueprintDeleted $event
     *
     * @return void
     */
    public function handleBlueprintDeleted(BlueprintDeleted $event)
    {
        $this->addEntry("Deleted blueprint '" . $event->blueprint->title() . "' (handle: '" . $event->blueprint->handle() . "') for '" . $event->blueprint->namespace() . "'");
    }

    /**
     * Handle the BlueprintSaved event.
     *
     * @param BlueprintSaved $event
     *
     * @return void
     */
    public function handleBlueprintSaved(BlueprintSaved $event)
    {
        $this->addEntry("Created/edited blueprint '" . $event->blueprint->title() . "' (handle: '" . $event->blueprint->handle() . "') for '" . $event->blueprint->namespace() . "'");
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
        $this->addEntry("Created/edited collection '" . $event->collection->title() . "' (handle '" . $event->collection->handle() . "')");
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
     * Handle the FieldsetDeleted event.
     *
     * @param FieldsetDeleted $event
     *
     * @return void
     */
    public function handleFieldsetDeleted(FieldsetDeleted $event)
    {
        $this->addEntry("Deleted fieldset '" . $event->fieldset->title() . "' (handle: '" . $event->fieldset->handle() . "')");
    }

    /**
     * Handle the FieldsetSaved event.
     *
     * @param FieldsetSaved $event
     *
     * @return void
     */
    public function handleFieldsetSaved(FieldsetSaved $event)
    {
        $this->addEntry("Created/edited fieldset '" . $event->fieldset->title() . "' (handle: '" . $event->fieldset->handle() . "')");
    }

    /**
     * Handle the FormDeleted event.
     *
     * @param FormDeleted $event
     *
     * @return void
     */
    public function handleFormDeleted(FormDeleted $event)
    {
        $this->addEntry("Deleted form '" . $event->form->title() . "' (handle: '" . $event->form->handle() . "')");
    }

    /**
     * Handle the FormSaved event.
     *
     * @param FormSaved $event
     *
     * @return void
     */
    public function handleFormSaved(FormSaved $event)
    {
        $this->addEntry("Created/edited form '" . $event->form->title() . "' (handle: '" . $event->form->handle() . "')");
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
        $this->addEntry("Celeted global set '" . $event->globals->title() . "' (handle: '" . $event->globals->handle() . "')");
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
        $this->addEntry("Created/edited global set '" . $event->globals->title() . "' (handle: '" . $event->globals->handle() . "')");
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
        $this->addEntry("Created/edited navigation '" . $event->nav->title() . "' (handle: '" . $event->nav->handle() . "')");
    }

    /**
     * Handle the RoleDeleted event.
     *
     * @param RoleDeleted $event
     *
     * @return void
     */
    public function handleRoleDeleted(RoleDeleted $event)
    {
        $this->addEntry("Celeted role '" . $event->role->title() . "' (handle: '" . $event->role->handle() . "')");
    }

    /**
     * Handle the RoleSaved event.
     *
     * @param RoleSaved $event
     *
     * @return void
     */
    public function handleRoleSaved(RoleSaved $event)
    {
        $this->addEntry("Created/edited role '" . $event->role->title() . "' (handle: '" . $event->role->handle() . "')");
    }

    /**
     * Handle the SubmissionDeleted event.
     *
     * @param SubmissionDeleted $event
     *
     * @return void
     */
    public function handleSubmissionDeleted(SubmissionDeleted $event)
    {
        $this->addEntry("Deleted submission with id '" . $event->submission->id() . "' for form '" . $event->submission->form()->title() . "' (handle: '" . $event->submission->form()->handle() . "')");
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
        $this->addEntry("Created/edited term '" . $event->term->title() . "' (id: '" . $event->term->id() . "') in taxonomy '" . $event->term->taxonomy()->title() . "'");
    }

    /**
     * Handle the UserDeleted event.
     *
     * @param UserDeleted $event
     *
     * @return void
     */
    public function handleUserDeleted(UserDeleted $event)
    {
        $this->addEntry("Deleted user '" . $event->user->name . "' (e-mail: '" . $event->user->email() . "', id: '" . $event->user->id() . "')");
    }

    /**
     * Handle the UserGroupDeleted event.
     *
     * @param UserGroupDeleted $event
     *
     * @return void
     */
    public function handleUserGroupDeleted(UserGroupDeleted $event)
    {
        $this->addEntry("Deleted user group '" . $event->group->title() . "' (handle: '" . $event->group->handle() . "')");
    }

    /**
     * Handle the UserGroupSaved event.
     *
     * @param UserGroupSaved $event
     *
     * @return void
     */
    public function handleUserGroupSaved(UserGroupSaved $event)
    {
        $this->addEntry("Created/edited user group '" . $event->group->title() . "' (handle: '" . $event->group->handle() . "')");
    }

    /**
     * Handle the UserSaved event.
     *
     * @param UserSaved $event
     *
     * @return void
     */
    public function handleUserSaved(UserSaved $event)
    {
        $this->addEntry("Created/edited user '" . $event->user->name . "' (e-mail: '" . $event->user->email() . "', id: '" . $event->user->id() . "')");
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
            BlueprintDeleted::class => 'handleBlueprintDeleted',
            BlueprintSaved::class => 'handleBlueprintSaved',
            CollectionDeleted::class => 'handleCollectionDeleted',
            CollectionSaved::class => 'handleCollectionSaved',
            EntryDeleted::class => 'handleEntryDeleted',
            EntrySaved::class => 'handleEntrySaved',
            FieldsetDeleted::class => 'handleFieldsetDeleted',
            FieldsetSaved::class => 'handleFieldsetSaved',
            FormDeleted::class => 'handleFormDeleted',
            FormSaved::class => 'handleFormSaved',
            GlobalSetDeleted::class => 'handleGlobalSetDeleted',
            GlobalSetSaved::class => 'handleGlobalSetSaved',
            NavDeleted::class => 'handleNavDeleted',
            NavSaved::class => 'handleNavSaved',
            RoleDeleted::class => 'handleRoleDeleted',
            RoleSaved::class => 'handleRoleSaved',
            SubmissionDeleted::class => 'handleSubmissionDeleted',
            TaxonomyDeleted::class => 'handleTaxonomyDeleted',
            TaxonomySaved::class => 'handleTaxonomySaved',
            TermDeleted::class => 'handleTermDeleted',
            TermSaved::class => 'handleTermSaved',
            UserDeleted::class => 'handleUserDeleted',
            UserGroupDeleted::class => 'handleUserGroupDeleted',
            UserGroupSaved::class => 'handleUserGroupSaved',
            UserSaved::class => 'handleUserSaved',
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
