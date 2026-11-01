<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchDateOverride;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BusinessRecordService
{
    /** Compensate uploaded files if any database write fails. */
    public function createBusiness(array $data): Business
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($data, &$paths) {
                $path = $this->storeImage($data['logo'], 'business-logos');
                $paths[] = $path;
                return Business::create([
                    'name' => $data['name'], 'email' => $data['email'],
                    'phone_number' => $data['phone_number'], 'logo_path' => $path,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }
    }

    public function createBranch(array $data): Branch
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($data, &$paths) {
                $branch = Branch::create(['business_id' => $data['business_id'], 'name' => $data['name']]);
                foreach ($data['images'] as $image) {
                    $path = $this->storeImage($image, 'branch-images');
                    $paths[] = $path;
                    $branch->images()->create(['path' => $path]);
                }
                foreach ($data['weekly_hours'] ?? [] as $weekday => $day) {
                    if (! in_array($day['enabled'] ?? null, [true, 1, '1'], true)) {
                        continue;
                    }
                    foreach ($day['slots'] as $slot) {
                        $branch->timings()->create(['weekday' => $weekday] + $slot);
                    }
                }
                foreach ($data['overrides'] ?? [] as $override) {
                    $this->createDateOverride($branch, $override);
                }
                return $branch;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }
    }

    public function createDateOverride(Branch $branch, array $data): BranchDateOverride
    {
        return DB::transaction(function () use ($branch, $data) {
            $override = $branch->dateOverrides()->create([
                'date' => $data['date'], 'is_available' => $data['is_available'],
            ]);
            return $override;
        });
    }

    /** Retain the existing logo unless a replacement is successfully committed. */
    public function updateBusiness(Business $business, array $data): void
    {
        $newPath = null;
        $oldPath = $business->logo_path;
        try {
            DB::transaction(function () use ($business, $data, &$newPath) {
                $values = array_intersect_key($data, array_flip(['name', 'email', 'phone_number']));
                if (! empty($data['logo'])) {
                    $newPath = $this->storeImage($data['logo'], 'business-logos');
                    $values['logo_path'] = $newPath;
                }
                $business->update($values);
            });
        } catch (Throwable $exception) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $exception;
        }
        if ($newPath) Storage::disk('public')->delete($oldPath);
    }

    /** Replace the submitted schedule atomically; optional new images replace the gallery. */
    public function updateBranch(Branch $branch, array $data): void
    {
        $newPaths = [];
        $oldPaths = [];
        try {
            DB::transaction(function () use ($branch, $data, &$newPaths, &$oldPaths) {
                $branch->update(['business_id' => $data['business_id'], 'name' => $data['name']]);
                if (! empty($data['images'])) {
                    $oldPaths = $branch->images()->pluck('path')->all();
                    $branch->images()->delete();
                    foreach ($data['images'] as $image) {
                        $path = $this->storeImage($image, 'branch-images');
                        $newPaths[] = $path;
                        $branch->images()->create(['path' => $path]);
                    }
                }
                $branch->timings()->delete();
                foreach ($data['weekly_hours'] ?? [] as $weekday => $day) {
                    if (! in_array($day['enabled'] ?? null, [true, 1, '1'], true)) continue;
                    foreach ($day['slots'] as $slot) {
                        $branch->timings()->create(['weekday' => $weekday] + $slot);
                    }
                }
                $branch->dateOverrides()->delete();
                foreach ($data['overrides'] ?? [] as $override) {
                    $this->createDateOverride($branch, $override);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }
        Storage::disk('public')->delete($oldPaths);
    }

    public function deleteBusiness(Business $business): void
    {
        $business->load('branches.images');
        $paths = [$business->logo_path];
        foreach ($business->branches as $branch) {
            array_push($paths, ...$branch->images->pluck('path')->all());
        }
        // Foreign-key cascades remove child records. Remove files only after commit.
        DB::transaction(fn () => $business->delete());
        Storage::disk('public')->delete($paths);
    }

    public function deleteBranch(Branch $branch): void
    {
        $paths = $branch->images()->pluck('path')->all();
        DB::transaction(fn () => $branch->delete());
        Storage::disk('public')->delete($paths);
    }

    private function storeImage($image, string $directory): string
    {
        $path = $image->store($directory, 'public');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The image could not be saved. Please try again.');
        }
        return $path;
    }
}
