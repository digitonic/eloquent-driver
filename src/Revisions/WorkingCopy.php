<?php

namespace Statamic\Eloquent\Revisions;

use Statamic\Facades\Revision as Revisions;
use Statamic\Revisions\Revision;

class WorkingCopy extends Revision
{
    public static function fromRevision(Revision $revision)
    {
        return (new self)
            ->id($revision->id() ?? false)
            ->key($revision->key())
            ->date($revision->date())
            ->user($revision->user() ?? false)
            ->message($revision->message() ?? false)
            ->attributes($revision->attributes());
    }

    public static function fromModel(RevisionModel $revision)
    {
        return (new self)
            ->id(false)
            ->key($revision->key)
            ->date($revision->date)
            ->user($revision->user ?? '')
            ->message($revision->message ?? '')
            ->attributes($revision->attributes);
    }
}
