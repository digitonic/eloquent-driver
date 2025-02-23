<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Entries\CollectionRepository;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Revisions\RevisionRepository;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;
use function Couchbase\defaultDecoder;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent-driver.php', 'statamic-eloquent-driver');

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('statamic-eloquent-driver.php')]);

            $this->commands([ImportEntries::class]);
        }
    }

    public function register()
    {
        $this->registerEntries();
    }

    protected function registerEntries()
    {
        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic-eloquent-driver.entries.model');
        });

        $this->app->bind(\Statamic\Revisions\RevisionRepository::class, function () {
            return new RevisionRepository;
        });

        $this->app->singleton(
            \Statamic\Http\Controllers\CP\Collections\EntryRevisionsController::class,
            \Statamic\Eloquent\Http\Controllers\CP\Collections\EntryRevisionsController::class
        );

        $this->app->singleton(
            \Statamic\Http\Controllers\CP\Collections\RestoreEntryRevisionController::class,
            \Statamic\Eloquent\Http\Controllers\CP\Collections\RestoreEntryRevisionController::class
        );
    }
}
