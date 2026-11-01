<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BranchTiming;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditModulesTest extends TestCase
{
    use RefreshDatabase;

    private function business(): Business
    {
        Storage::fake('public');
        Storage::disk('public')->put('logo.png', 'original');
        return Business::create(['name' => 'Original', 'email' => 'test@example.com', 'phone_number' => '9876543210', 'logo_path' => 'logo.png']);
    }

    public function test_business_edit_keeps_logo_and_replacement_removes_old_file(): void
    {
        $business = $this->business();
        $data = ['name' => 'Updated', 'email' => 'updated@example.com', 'phone_number' => '9876543211'];
        $this->get(route('businesses.edit', $business))->assertOk()->assertSee('Original');
        $this->put(route('businesses.update', $business), $data)->assertSessionHasNoErrors()->assertRedirect(route('businesses.index'));
        $this->assertSame('Updated', $business->fresh()->name);
        Storage::disk('public')->assertExists('logo.png');
        $this->put(route('businesses.update', $business), $data + ['logo' => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('logo.png');
        Storage::disk('public')->assertExists($business->fresh()->logo_path);
    }

    public function test_branch_edit_replaces_schedule_and_dates_while_retaining_images(): void
    {
        $business = $this->business();
        $branch = $business->branches()->create(['name' => 'Original branch']);
        Storage::disk('public')->put('branch.png', 'original');
        $branch->images()->create(['path' => 'branch.png']);
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);
        $branch->dateOverrides()->create(['date' => '2026-12-25', 'is_available' => false]);
        $this->get(route('branches.edit', $branch))->assertOk()->assertSee('09:00')->assertSee('2026-12-25');
        $data = ['business_id' => $business->id, 'name' => 'Updated branch', 'weekly_hours' => [2 => ['enabled' => 1, 'slots' => [['start_time' => '13:00', 'end_time' => '17:00']]]], 'overrides' => [['date' => '2026-12-31', 'is_available' => 1]]];
        $this->put(route('branches.update', $branch), $data)->assertSessionHasNoErrors()->assertRedirect(route('branches.show', $branch));
        $this->assertSame('Updated branch', $branch->fresh()->name);
        $this->assertSame(2, $branch->timings()->sole()->weekday);
        $this->assertSame('2026-12-31', $branch->dateOverrides()->sole()->date->format('Y-m-d'));
        Storage::disk('public')->assertExists('branch.png');
        $this->put(route('branches.update', $branch), ['business_id' => $business->id, 'name' => 'Closed branch', 'images' => [UploadedFile::fake()->image('replacement.png')]])->assertSessionHasNoErrors();
        $this->assertSame(0, $branch->timings()->count());
        $this->assertSame(0, $branch->dateOverrides()->count());
        Storage::disk('public')->assertMissing('branch.png');
        Storage::disk('public')->assertExists($branch->images()->sole()->path);
    }

    public function test_failed_branch_update_rolls_back_schedule_and_preserves_existing_files(): void
    {
        $business = $this->business();
        $branch = $business->branches()->create(['name' => 'Original']);
        Storage::disk('public')->put('branch.png', 'original');
        $branch->images()->create(['path' => 'branch.png']);
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);
        BranchTiming::creating(function () { throw new \RuntimeException('Simulated write failure'); });
        try {
            app(\App\Services\BusinessRecordService::class)->updateBranch($branch, ['business_id' => $business->id, 'name' => 'Changed', 'images' => [UploadedFile::fake()->image('new.png')], 'weekly_hours' => [2 => ['enabled' => 1, 'slots' => [['start_time' => '13:00', 'end_time' => '17:00']]]]]);
            $this->fail('Expected write failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated write failure', $exception->getMessage());
        } finally {
            BranchTiming::flushEventListeners();
        }
        $this->assertSame('Original', $branch->fresh()->name);
        $this->assertSame(1, $branch->timings()->sole()->weekday);
        $this->assertSame(['branch.png', 'logo.png'], Storage::disk('public')->allFiles());
    }

    public function test_invalid_updates_leave_existing_contacts_hours_dates_and_images_unchanged(): void
    {
        $business = $this->business();
        $branch = $business->branches()->create(['name' => 'Original branch']);
        Storage::disk('public')->put('branch.png', 'original');
        $branch->images()->create(['path' => 'branch.png']);
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);
        $branch->dateOverrides()->create(['date' => '2026-12-25', 'is_available' => false]);

        $this->patch(route('businesses.update', $business), [
            'name' => '', 'email' => 'invalid', 'phone_number' => 'invalid',
            'logo' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHasErrors(['name', 'email', 'phone_number']);
        $this->put(route('branches.update', $branch), [
            'business_id' => $business->id, 'name' => 'Changed',
            'images' => [UploadedFile::fake()->image('new.png')],
            'weekly_hours' => [1 => ['enabled' => 1, 'slots' => [
                ['start_time' => '09:00', 'end_time' => '12:00'],
                ['start_time' => '11:00', 'end_time' => '14:00'],
            ]]],
        ])->assertSessionHasErrors();

        $this->assertSame('Original', $business->fresh()->name);
        $this->assertSame('test@example.com', $business->fresh()->email);
        $this->assertSame('Original branch', $branch->fresh()->name);
        $this->assertSame(1, $branch->timings()->sole()->weekday);
        $this->assertSame('2026-12-25', $branch->dateOverrides()->sole()->date->format('Y-m-d'));
        $this->assertSame(['branch.png', 'logo.png'], Storage::disk('public')->allFiles());
    }

    public function test_edit_validation_retry_does_not_restore_removed_dates_or_disabled_days(): void
    {
        $business = $this->business();
        $branch = $business->branches()->create(['name' => 'Original branch']);
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);
        $branch->dateOverrides()->create(['date' => '2026-12-25', 'is_available' => false]);
        $response = $this->withSession(['_old_input' => [
            'name' => '', 'business_id' => $business->id, 'weekly_hours' => [],
        ]])->get(route('branches.edit', $branch))->assertOk();

        $response->assertDontSee('2026-12-25');
        $this->assertDoesNotMatchRegularExpression('/id="weekday-1"[^>]*checked/', $response->getContent());
    }

    public function test_branch_can_move_to_another_business_without_changing_its_identity(): void
    {
        $business = $this->business();
        $other = Business::create(['name' => 'Other', 'email' => 'other@example.com', 'phone_number' => '9876543211', 'logo_path' => 'other.png']);
        $branch = $business->branches()->create(['name' => 'Location']);
        $this->patch(route('branches.update', $branch), [
            'business_id' => $other->id, 'name' => 'Relocated',
        ])->assertSessionHasNoErrors()->assertRedirect(route('branches.show', $branch));
        $this->assertSame($other->id, $branch->fresh()->business_id);
        $this->assertSame('Relocated', $branch->fresh()->name);
        $this->assertModelExists($business);
        $this->assertDatabaseCount('branches', 1);
    }
}
