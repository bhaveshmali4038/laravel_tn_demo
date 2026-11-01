<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Business;
use App\Services\BranchStatusService;
use App\Services\BusinessRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('branches.index');
    }

    public function data(BranchStatusService $statuses): JsonResponse
    {
        return DataTables::eloquent(Branch::query()->with(['business', 'timings', 'dateOverrides'])->select('branches.*'))
            ->addColumn('business_name', fn (Branch $branch) => $branch->business->name)
            ->filterColumn('business_name', fn ($query, $keyword) => $query->whereHas('business', fn ($business) => $business->where('name', 'like', '%'.$keyword.'%')))
            ->orderColumn('business_name', function ($query, $direction) {
                $query->orderBy(Business::select('name')->whereColumn('businesses.id', 'branches.business_id'), $direction);
            })
            ->addColumn('status', function (Branch $branch) use ($statuses) {
                $status = $statuses->status($branch);
                return '<span class="status-badge '.($status['is_open'] ? 'is-open' : 'is-closed').'">'.$status['label'].'</span>';
            })
            ->addColumn('actions', function (Branch $branch) {
                return '<div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="'.e(route('branches.edit', $branch)).'">Edit</a><a class="btn btn-sm btn-outline-primary" href="'.e(route('branches.show', $branch)).'">View hours</a>'
                    .'<form class="delete-form d-inline" data-confirm="Delete this branch?" method="POST" action="'.e(route('branches.destroy', $branch)).'">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form></div>';
            })
            ->rawColumns(['status', 'actions'])->toJson();
    }

    public function create(): View
    {
        return view('branches.create', ['businesses' => Business::orderBy('name')->get()]);
    }

    public function store(StoreBranchRequest $request, BusinessRecordService $records): RedirectResponse
    {
        $branch = $records->createBranch($request->validated());
        return redirect()->route('branches.show', $branch)->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch, BranchStatusService $statuses): View
    {
        $branch->load(['business', 'images', 'timings', 'dateOverrides']);
        return view('branches.show', [
            'branch' => $branch, 'status' => $statuses->status($branch),
            'weeklyHours' => $branch->timings->groupBy('weekday'),
        ]);
    }

    public function status(Branch $branch, BranchStatusService $statuses): JsonResponse
    {
        return response()->json($statuses->status($branch))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function destroy(Branch $branch, BusinessRecordService $records): RedirectResponse
    {
        $records->deleteBranch($branch);
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }

    public function edit(Branch $branch): View
    {
        $branch->load(['images', 'timings', 'dateOverrides']);
        $initialHours = $branch->timings->groupBy('weekday')->map(fn ($slots) => [
            'enabled' => 1,
            'slots' => $slots->map(fn ($slot) => ['start_time' => substr($slot->start_time, 0, 5), 'end_time' => substr($slot->end_time, 0, 5)])->values()->all(),
        ])->all();
        $initialOverrides = $branch->dateOverrides->map(fn ($override) => ['date' => $override->date->format('Y-m-d'), 'is_available' => (int) $override->is_available])->all();
        return view('branches.create', compact('branch', 'initialHours', 'initialOverrides') + ['businesses' => Business::orderBy('name')->get()]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, BusinessRecordService $records): RedirectResponse
    {
        $records->updateBranch($branch, $request->validated());
        return redirect()->route('branches.show', $branch)->with('success', 'Branch updated successfully.');
    }
}
