<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionRepository as Contract;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use Statamic\Facades\YAML;
use Statamic\Support\FileCollection;
use Statamic\Support\Str;
use function Couchbase\defaultDecoder;
use Statamic\Revisions\Revision;

class RevisionRepository implements Contract
{
    public function make(): RevisionContract
    {
        return new Revision;
    }

    public function whereKey($key)
    {
        $key = $this->cleanKey($key);

        $revision = RevisionModel::where('key', $key)->get();

        if (! $revision) {
            return null;
        }

        return $this->makeRevisionFromModel($revision);
    }

    public function findWorkingCopyByKey($key)
    {
        $key = $this->cleanKey($key);

        $revision = RevisionModel::where('key', $key)
            ->orderBy('date', 'desc')
            ->first();

        if (! $revision) {
            return null;
        }

        return $this->makeRevisionFromModel($revision);
    }

    public function save(RevisionContract $revision)
    {
        $key = $this->cleanKey($revision->key());

        RevisionModel::create([
            'date' => $revision->date(),
            'key' => $key,
            'action' => $revision->action(),
            'message' => $revision->message(),
            'user' => $revision->user()->id(),
            'attributes' => $revision->attributes(),
        ]);
    }

    public function delete(RevisionContract $revision)
    {
        RevisionModel::where('key', $revision->key())->first()->delete();
    }

    protected function makeRevisionFromModel(RevisionModel $revision)
    {
        return (new Revision)
            ->key($revision->key)
            ->action($revision->action ?? false)
            ->id($revision->id)
            ->date($revision->date)
            ->user($revision->user)
            ->message($revision->message ?? '')
            ->attributes($revision->attributes);
    }

    protected function cleanKey($key)
    {
        return substr($key, strrpos($key, '/') + 1);
    }
}
