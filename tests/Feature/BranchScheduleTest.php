<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Services\BranchStatusService;
use App\Services\BusinessRecordService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BranchScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['app.timezone' => 'Asia/Kolkata']);
    }

    public function test_branch_creation_persists_multiple_images_weekday_slots_and_date_overrides(): void
    {
        $business = $this->business();
        $this->post(route('branches.store'), $this->payload($business, [
            'weekly_hours' => [
                1 => ['enabled' => 1, 'slots' => [$this->slot('12:00', '14:00'), $this->slot('19:00', '22:00')]],
                2 => ['enabled' => 1, 'slots' => [$this->slot('09:00', '11:00'), $this->slot('12:45', '14:30'), $this->slot('19:00', '22:00')]],
                7 => ['enabled' => 0],
            ],
            'overrides' => [
                ['date' => '2026-12-25', 'is_available' => 0],
                ['date' => '2026-12-27', 'is_available' => 1],
            ],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $branch = Branch::query()->sole();
        $this->assertSame($business->id, $branch->business_id);
        $this->assertSame('Riverside', $branch->name);
        $this->assertCount(2, $branch->images);
        foreach ($branch->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
        $this->assertSame(2, $branch->timings()->where('weekday', 1)->count());
        $this->assertSame(3, $branch->timings()->where('weekday', 2)->count());
        $this->assertSame(0, $branch->timings()->where('weekday', 7)->count());
        $this->assertCount(2, $branch->dateOverrides);
        $this->assertDatabaseHas('branch_date_overrides', ['branch_id' => $branch->id, 'date' => '2026-12-25', 'is_available' => 0]);
    }

    public function test_branch_requires_an_existing_business_name_and_images(): void
    {
        $this->post(route('branches.store'), [
            'business_id' => 999999,
            'name' => '',
        ])->assertSessionHasErrors(['business_id', 'name', 'images']);

        $this->assertDatabaseCount('branches', 0);
    }

    public function test_branch_rejects_nonimage_uploads_without_creating_records(): void
    {
        $this->post(route('branches.store'), $this->payload($this->business(), [
            'images' => [UploadedFile::fake()->create('picture.php', 5, 'text/plain')],
        ]))->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('branches', 0);
        $this->assertDatabaseCount('branch_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_branch_upload_limits_and_actual_image_content_are_validated(): void
    {
        $business = $this->business();
        $disguisedContent = UploadedFile::fake()->createWithContent('disguised.jpg', '<?php echo "This is not an image";');
        $invalidUploads = [
            ['files' => array_map(fn ($index) => UploadedFile::fake()->image('image-'.$index.'.jpg'), range(1, 11)), 'error' => 'images'],
            ['files' => [UploadedFile::fake()->image('large.jpg')->size(4097)], 'error' => 'images.0'],
            ['files' => [new UploadedFile($disguisedContent->getPathname(), 'disguised.jpg', 'image/jpeg', null, true)], 'error' => 'images.0'],
        ];

        foreach ($invalidUploads as $upload) {
            $this->post(route('branches.store'), $this->payload($business, [
                'images' => $upload['files'],
            ]))->assertSessionHasErrors($upload['error']);

            $this->assertDatabaseCount('branches', 0);
            $this->assertDatabaseCount('branch_images', 0);
            $this->assertDatabaseCount('branch_timings', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_a_database_failure_rolls_back_branch_children_and_compensates_all_uploaded_images(): void
    {
        $business = $this->business();

        try {
            // Bypass request validation to force the database UNIQUE constraint to
            // fail after images, weekly timings and the first override are saved.
            app(BusinessRecordService::class)->createBranch($this->payload($business, [
                'overrides' => [
                    ['date' => '2026-12-25', 'is_available' => 0],
                    ['date' => '2026-12-25', 'is_available' => 1],
                ],
            ]));
            $this->fail('The duplicate date should cause an actual database exception.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', (string) $exception->getCode());
        }

        $this->assertModelExists($business);
        foreach (['branches', 'branch_images', 'branch_timings', 'branch_date_overrides'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_invalid_incomplete_equal_reversed_and_overlapping_weekly_slots_are_rejected(): void
    {
        $business = $this->business();
        $invalidSchedules = [
            [$this->slot('25:00', '26:00')],
            [['start_time' => '09:00']],
            [$this->slot('09:00', '09:00')],
            [$this->slot('22:00', '02:00')],
            [$this->slot('09:00', '12:00'), $this->slot('11:59', '14:00')],
            [$this->slot('09:00', '12:00'), $this->slot('09:00', '12:00')],
            [$this->slot('10:00', '11:00'), $this->slot('09:00', '17:00'), $this->slot('12:00', '13:00')],
            [],
        ];

        foreach ($invalidSchedules as $slots) {
            $this->post(route('branches.store'), $this->payload($business, [
                'weekly_hours' => [1 => ['enabled' => 1, 'slots' => $slots]],
            ]))->assertSessionHasErrors();
            $this->assertDatabaseCount('branches', 0);
            $this->assertDatabaseCount('branch_timings', 0);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_touching_slots_are_allowed_but_duplicate_date_overrides_are_rejected(): void
    {
        $business = $this->business();
        $this->post(route('branches.store'), $this->payload($business, [
            'weekly_hours' => [1 => ['enabled' => 1, 'slots' => [$this->slot('09:00', '12:00'), $this->slot('12:00', '17:00')]]],
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('branch_timings', 2);

        $this->post(route('branches.store'), $this->payload($business, [
            'overrides' => [
                ['date' => '2026-12-25', 'is_available' => 0],
                ['date' => '2026-12-25', 'is_available' => 0],
            ],
        ]))->assertSessionHasErrors();
        $this->assertDatabaseCount('branches', 1);
    }

    public function test_open_status_respects_all_slot_boundaries_gaps_and_closed_weekdays(): void
    {
        $branch = $this->branch();
        $branch->timings()->createMany([
            ['weekday' => 1, 'start_time' => '12:00', 'end_time' => '14:00'],
            ['weekday' => 1, 'start_time' => '19:00', 'end_time' => '22:00'],
            ['weekday' => 2, 'start_time' => '09:00', 'end_time' => '11:00'],
        ]);

        foreach ([
            '2026-10-26 11:59:59' => false,
            '2026-10-26 12:00:00' => true,
            '2026-10-26 13:59:59' => true,
            '2026-10-26 14:00:00' => false,
            '2026-10-26 18:59:59' => false,
            '2026-10-26 19:00:00' => true,
            '2026-10-26 22:00:00' => false,
            '2026-10-27 09:00:00' => true,
            '2026-10-27 12:00:00' => false,
            '2026-11-01 12:00:00' => false,
            '2026-11-02 12:00:00' => true,
        ] as $time => $isOpen) {
            $status = $this->branchStatus($branch, $time);
            $this->assertSame($isOpen, $status['is_open'], $time);
            $this->assertSame($isOpen ? 'Open' : 'Closed', $status['label']);
            $this->assertSame('weekly', $status['source']);
        }
    }

    public function test_a_date_closure_replaces_weekly_hours_for_only_that_date(): void
    {
        $branch = $this->branch();
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);
        $branch->dateOverrides()->create(['date' => '2026-10-26', 'is_available' => false]);

        $status = $this->branchStatus($branch, '2026-10-26 12:00:00');
        $this->assertFalse($status['is_open']);
        $this->assertSame('date_override', $status['source']);
        $this->assertSame([], $status['slots']);
        $this->assertTrue($this->branchStatus($branch, '2026-11-02 12:00:00')['is_open']);
    }

    public function test_a_date_opening_keeps_a_normally_closed_day_open_for_the_entire_local_date(): void
    {
        $branch = $this->branch();
        $branch->dateOverrides()->create(['date' => '2026-11-01', 'is_available' => true]);

        foreach (['00:00:00', '09:59:59', '12:00:00', '23:59:59'] as $time) {
            $status = $this->branchStatus($branch, '2026-11-01 '.$time);
            $this->assertTrue($status['is_open']);
            $this->assertSame('date_override', $status['source']);
        }
        $this->assertFalse($this->branchStatus($branch, '2026-11-02 00:00:00')['is_open']);
        $this->assertFalse($this->branchStatus($branch, '2026-11-08 10:00:00')['is_open']);
    }

    public function test_status_converts_the_supplied_instant_to_the_application_timezone(): void
    {
        $branch = $this->branch();
        $branch->timings()->create(['weekday' => 1, 'start_time' => '00:00', 'end_time' => '01:00']);

        $status = app(BranchStatusService::class)->status($branch, CarbonImmutable::parse('2026-10-25 18:30:00', 'UTC'));

        $this->assertTrue($status['is_open']);
        $this->assertSame('2026-10-26', $status['date']);
        $this->assertSame('Asia/Kolkata', $status['time_zone']);
    }

    public function test_page_visits_and_the_uncached_status_endpoint_recalculate_the_current_status(): void
    {
        $branch = $this->branch();
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);

        try {
            $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'Asia/Kolkata'));
            $this->get(route('branches.show', $branch))->assertOk()
                ->assertViewHas('status', fn ($status) => $status['is_open'] === true);
            $response = $this->getJson(route('branches.status', $branch))->assertOk()
                ->assertJsonPath('is_open', true)->assertJsonPath('source', 'weekly');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

            $this->travelTo(CarbonImmutable::parse('2026-11-02 17:00:00', 'Asia/Kolkata'));
            $this->get(route('branches.show', $branch))->assertOk()
                ->assertViewHas('status', fn ($status) => $status['is_open'] === false);
            $this->getJson(route('branches.status', $branch))->assertOk()->assertJsonPath('is_open', false);
        } finally {
            $this->travelBack();
        }
    }

    public function test_disabled_weekdays_and_unknown_weekday_keys_cannot_contain_hours(): void
    {
        $business = $this->business();
        foreach ([
            [1 => ['enabled' => 0, 'slots' => [$this->slot('09:00', '17:00')]]],
            [8 => ['enabled' => 1, 'slots' => [$this->slot('09:00', '17:00')]]],
            [1 => ['enabled' => 1, 'slots' => 'invalid']],
        ] as $weeklyHours) {
            $this->post(route('branches.store'), $this->payload($business, [
                'weekly_hours' => $weeklyHours,
            ]))->assertSessionHasErrors();
            $this->assertDatabaseCount('branches', 0);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_date_overrides_can_be_created_and_deleted_without_editing_the_branch(): void
    {
        $branch = $this->branch();
        $this->post(route('branches.date-overrides.store', $branch), [
            'date' => '2026-12-25', 'is_available' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $override = $branch->dateOverrides()->sole();
        $this->assertTrue($override->is_available);

        $this->delete(route('branches.date-overrides.destroy', [$branch, $override]))->assertRedirect();
        $this->assertModelExists($branch);
        $this->assertDatabaseCount('branch_date_overrides', 0);
    }

    public function test_date_override_validation_rejects_invalid_duplicate_and_inconsistent_dates_or_slots(): void
    {
        $branch = $this->branch();
        $branch->dateOverrides()->create(['date' => '2026-12-25', 'is_available' => false]);

        foreach ([
            ['date' => '2026-02-30', 'is_available' => 0],
            ['date' => '2026-12-25', 'is_available' => 0],
            ['date' => '2026-12-26', 'is_available' => 2],
            ['date' => '2026-12-26', 'is_available' => 1, 'slots' => [$this->slot('10:00', '12:00')]],
            ['date' => '2026-12-26', 'is_available' => 0, 'slots' => [$this->slot('10:00', '12:00')]],
        ] as $payload) {
            $this->post(route('branches.date-overrides.store', $branch), $payload)->assertSessionHasErrors();
            $this->assertDatabaseCount('branch_date_overrides', 1);
        }
    }

    public function test_a_date_override_cannot_be_deleted_through_a_different_branch(): void
    {
        $first = $this->branch();
        $second = $first->business->branches()->create(['name' => 'Hilltop']);
        $override = $first->dateOverrides()->create(['date' => '2026-12-25', 'is_available' => false]);

        $this->delete(route('branches.date-overrides.destroy', [$second, $override]))->assertNotFound();
        $this->assertModelExists($override);
    }

    private function branchStatus(Branch $branch, string $time): array
    {
        return app(BranchStatusService::class)->status($branch->fresh(), CarbonImmutable::parse($time, 'Asia/Kolkata'));
    }

    private function business(): Business
    {
        return Business::query()->create([
            'name' => 'Harbour Coffee', 'email' => 'hello@harbour.example',
            'phone_number' => '9876543210', 'logo_path' => 'businesses/logo.png',
        ]);
    }

    private function branch(): Branch
    {
        return $this->business()->branches()->create(['name' => 'Riverside']);
    }

    private function payload(Business $business, array $attributes = []): array
    {
        return array_replace([
            'business_id' => $business->id,
            'name' => 'Riverside',
            'images' => [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('interior.jpg')],
            'weekly_hours' => [1 => ['enabled' => 1, 'slots' => [$this->slot('09:00', '17:00')]]],
        ], $attributes);
    }

    private function slot(string $start, string $end): array
    {
        return ['start_time' => $start, 'end_time' => $end];
    }
}
