<?php

namespace Markkravchuk\BackupVcs;

use Statamic\Facades\Utility;
use Illuminate\Routing\Router;
use Statamic\Providers\AddonServiceProvider;

use Markkravchuk\BackupVcs\Listeners\BackupVcsEventSubscriber;

class ServiceProvider extends AddonServiceProvider
{
    protected $subscribe = [
        BackupVcsEventSubscriber::class,
    ];

    /**
     * Main function that runs after the Statamic and addon were installed
     * All the processes are initialized here
     *
     * @return void
     */
    public function bootAddon()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'backup-vcs');

//        $this->mergeConfigFrom(__DIR__.'/../config/activity-logger.php', 'admin-log');

        // Publishing to project's structure
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../artisan/' => app_path('/Console/Commands'),
                __DIR__.'/../config/' => config_path()
            ], 'backup-vcs-config');
        }

        // Create UI page for addon, under Utilities tab
        Utility::make('backup-vcs')
            ->title(__('Backup VCS'))
            ->icon('book-pages')
            ->description(__('See the activity history and revert content changes to the point where everything works fine'))
            ->routes(function (Router $router) {
                // Only 1 page to be displayed
                $router->get('/', [BackupVcsViewController::class, 'show'])->name('show');
            })
            ->register();
    }
}
