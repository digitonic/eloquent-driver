<?php

namespace Statamic\Eloquent\Http\Controllers\CP\Collections;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Revisions\Revision;
use Statamic\Eloquent\Revisions\WorkingCopy;
use Statamic\Facades\Revision as Revisions;

class RestoreEntryRevisionController extends CpController
{
    public function __invoke(Request $request, $collection, $entry)
    {
        $date = Carbon::parse($request->revision);
        $dateTime = $date->toDateTimeString();

        $revisionModel = RevisionModel::where('date', $date)->first();

        if (! $target = $entry->revision($revisionModel->id)) {
            dd('no such revision', $request->revision);
            // todo: handle invalid revision reference
        }

        // Update the entry with the targeted revision data.
        if ($entry->published()) {
            $workingCopy = WorkingCopy::fromModel($revisionModel)->date(now());
            $workingCopy->save();

            $this->updateWorkingCopyRevision($workingCopy, $entry);
        } else {
            $entry->makeFromRevision($target)->published(false)->save();
            $this->updateWorkingCopyRevision($target, $entry);
        }

        session()->flash('success', __('Revision restored'));
    }

    /**
     * @param $target
     * @param $entry
     */
    private function updateWorkingCopyRevision($target, $entry): void
    {
        $target->date(now())->save();
        $date = Carbon::parse($target->date());
        $dateTime = $date->toDateTimeString();

        $revision = RevisionModel::where('date', $date)
            ->update([
                'key' => $entry->id(),
                'action' => $target->action()
            ]);
    }
}
