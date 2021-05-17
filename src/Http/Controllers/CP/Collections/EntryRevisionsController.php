<?php

namespace Statamic\Eloquent\Http\Controllers\CP\Collections;

use Illuminate\Http\Request;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Resources\CP\Entries\Entry as EntryResource;
use Statamic\Revisions\Revision;

class EntryRevisionsController extends CpController
{
    public function index(Request $request, $collection, $entry)
    {
        $revisions = RevisionModel::where('key', $entry->id())
            ->orderBy('created_at', 'DESC')
            ->get();

        $revisionCollection = collect([]);
        $revisions->each(function ($revision) use ($revisionCollection) {
            $revisionCollection->push($this->makeRevisionFromModel($revision));
        });

        // The first non manually created revision would be considered the "current"
        // version. It's what corresponds to what's in the content directory.
        optional($revisionCollection->first(function ($revision) {
            return $revision->action() != 'revision';
        }))->attribute('current', true);

        optional($revisionCollection->first(function ($revision) {
            return $revision->action() == 'revision';
        }))->attribute('working', true);

        $results = $revisionCollection->groupBy(function ($revision) {
            return $revision->date()->clone()->startOfDay()->format('U');
        })->map(function ($revisions, $day) {
            return compact('day', 'revisions');
        })->reverse()->values();

        return $results;
    }

    public function store(Request $request, $collection, $entry)
    {
        $entry->createRevision([
            'message' => $request->message,
            'user' => User::fromUser($request->user()),
        ]);

        return new EntryResource($entry);
    }

    public function show(Request $request, $collection, $entry, $slug, $revision)
    {
        $entry = $entry->makeFromRevision($revision);

        // TODO: Most of this is duplicated with EntriesController@edit. DRY it off.

        $blueprint = $entry->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($entry->data()->all())
            ->preProcess();

        $values = array_merge($fields->values()->all(), [
            'title' => $entry->get('title'),
            'slug' => $entry->slug(),
        ]);

        if ($entry->collection()->dated()) {
            $datetime = substr($entry->date()->toDateTimeString(), 0, 16);
            $datetime = ($entry->hasTime()) ? $datetime : substr($datetime, 0, 10);
            $values['date'] = $datetime;
        }

        return [
            'title' => $entry->value('title'),
            'editing' => true,
            'actions' => [
                'save' => $entry->updateUrl(),
                'publish' => $entry->publishUrl(),
                'unpublish' => $entry->unpublishUrl(),
                'revisions' => $entry->revisionsUrl(),
                'restore' => $entry->restoreRevisionUrl(),
                'createRevision' => $entry->createRevisionUrl(),
            ],
            'values' => $values,
            'meta' => $fields->meta(),
            'collection' => $this->collectionToArray($entry->collection()),
            'blueprint' => $blueprint->toPublishArray(),
            'readOnly' => true,
            'published' => $entry->published(),
            'locale' => $entry->locale(),
            'localizations' => $entry->collection()->sites()->map(function ($handle) use ($entry) {
                $localized = $entry->in($handle);
                $exists = $localized !== null;

                return [
                    'handle' => $handle,
                    'name' => Site::get($handle)->name(),
                    'active' => $handle === $entry->locale(),
                    'exists' => $exists,
                    'root' => $exists ? $localized->isRoot() : false,
                    'origin' => $exists ? $localized->id() === optional($entry->origin())->id() : null,
                    'published' => $exists ? $localized->published() : false,
                    'url' => $exists ? $localized->editUrl() : null,
                ];
            })->all(),
        ];
    }

    protected function workingCopy($entry)
    {
        if ($entry->published()) {
            return $entry->workingCopy();
        }

        return $entry
            ->makeWorkingCopy()
            ->date($entry->lastModified())
            ->user($entry->lastModifiedBy());
    }

    protected function collectionToArray($collection)
    {
        return [
            'title' => $collection->title(),
            'url' => cp_route('collections.show', $collection->handle()),
        ];
    }

    protected function makeRevisionFromModel($revision)
    {
        $user = \Statamic\Facades\User::find($revision->user ?: null);

        return (new Revision)
            ->key($revision->key)
            ->action($revision->action ?? false)
            ->id($revision->id)
            ->date($revision->date)
            ->user($user)
            ->message($revision->message ?? '')
            ->attributes($revision->attributes);
    }
}
