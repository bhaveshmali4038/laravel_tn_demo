<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Services\BusinessRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BusinessController extends Controller
{
    public function index(): View
    {
        return view('businesses.index');
    }

    public function data(): JsonResponse
    {
        return DataTables::eloquent(Business::query()->withCount('branches'))
            ->addColumn('logo', function (Business $business) {
                $url = e(Storage::disk('public')->url($business->logo_path));
                return '<img class="table-logo" src="'.$url.'" width="44" height="44" alt="Business logo" style="object-fit:cover;border-radius:10px">';
            })
            ->addColumn('actions', function (Business $business) {
                return '<a class="btn btn-sm btn-outline-primary me-2" href="'.e(route('businesses.edit', $business)).'">Edit</a><form class="delete-form d-inline" data-confirm="Delete this business and all of its branches?" method="POST" action="'.e(route('businesses.destroy', $business)).'">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>';
            })
            ->rawColumns(['logo', 'actions'])->toJson();
    }

    public function create(): View
    {
        return view('businesses.create');
    }

    public function store(StoreBusinessRequest $request, BusinessRecordService $records): RedirectResponse
    {
        $records->createBusiness($request->validated());
        return redirect()->route('businesses.index')->with('success', 'Business created successfully.');
    }

    public function destroy(Business $business, BusinessRecordService $records): RedirectResponse
    {
        $records->deleteBusiness($business);
        return redirect()->route('businesses.index')->with('success', 'Business and its branches deleted successfully.');
    }

    public function edit(Business $business): View
    {
        return view('businesses.create', ['businessRecord' => $business]);
    }

    public function update(UpdateBusinessRequest $request, Business $business, BusinessRecordService $records): RedirectResponse
    {
        $records->updateBusiness($business, $request->validated());
        return redirect()->route('businesses.index')->with('success', 'Business updated successfully.');
    }
}
