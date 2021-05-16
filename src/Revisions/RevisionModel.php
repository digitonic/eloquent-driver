<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Database\Eloquent\Model as Eloquent;

class RevisionModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'revisions';

    protected $casts = [
        'date' => 'datetime',
        'working' => 'bool',
        'attributes' => 'json',
    ];
}
