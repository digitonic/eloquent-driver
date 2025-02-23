<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Support\Carbon;
use Statamic\Facades\Revision as Revisions;
use Statamic\Statamic;
use Statamic\Revisions\Revision;

trait Revisable
{
    public function revision(string $reference)
    {
        $revision = RevisionModel::findOrFail($reference);

        $user = \Statamic\Facades\User::find($revision->user ?: null);

        return (new Revision)
            ->key($revision->key)
            ->action($revision->action ?? '')
            ->id($revision->id)
            ->date($revision->date)
            ->user($user)
            ->message($revision->message ?? '')
            ->attributes($revision->attributes);
    }

    public function makeRevision()
    {
        return (new Revision)
            ->date(Carbon::now())
            ->key($this->revisionKey())
            ->attributes($this->revisionAttributes());
    }

    public function publishWorkingCopy($options = [])
    {
        $item = $this->fromWorkingCopy();

        $item
            ->published(true)
            ->updateLastModified($user = $options['user'] ?? false)
            ->save();

        $item
            ->makeRevision()
            ->user($user)
            ->message($options['message'] ?? '')
            ->action('publish')
            ->save();

        return $item;
    }

    public function unpublishWorkingCopy($options = [])
    {
        $item = $this->fromWorkingCopy();

        $item
            ->published(false)
            ->updateLastModified($user = $options['user'] ?? false)
            ->save();

        $item
            ->makeRevision()
            ->user($user)
            ->message($options['message'] ?? '')
            ->action('unpublish')
            ->save();

        return $item;
    }

    public function store($options = [])
    {
        $this
            ->published(false)
            ->updateLastModified($user = $options['user'] ?? false)
            ->save();

        $this
            ->makeRevision()
            ->user($user)
            ->message($options['message'] ?? '')
            ->save();
    }

    public function createRevision($options = [])
    {
        $this
            ->fromWorkingCopy()
            ->makeRevision()
            ->user($options['user'] ?? false)
            ->message($options['message'] ?? '')
            ->save();
    }

    public function revisionsEnabled()
    {
        return config('statamic.revisions.enabled') || ! Statamic::pro();
    }

    abstract protected function revisionKey();

    abstract protected function revisionAttributes();

    abstract public function makeFromRevision($revision);
}
