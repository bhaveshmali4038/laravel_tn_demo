<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchDateOverrideRequest;
use App\Models\Branch;
use App\Models\BranchDateOverride;
use App\Services\BusinessRecordService;
use Illuminate\Http\RedirectResponse;

class BranchDateOverrideController extends Controller
{
    public function store(StoreBranchDateOverrideRequest $request, Branch $branch, BusinessRecordService $records): RedirectResponse
    {
        $records->createDateOverride($branch, $request->validated());
        return redirect()->route('branches.show', $branch)->with('success', 'Date-specific availability added successfully.');
    }

    public function destroy(Branch $branch, BranchDateOverride $dateOverride): RedirectResponse
    {
        abort_unless($dateOverride->branch_id === $branch->id, 404);
        $dateOverride->delete();
        return redirect()->route('branches.show', $branch)->with('success', 'Date-specific availability deleted successfully.');
    }
}
