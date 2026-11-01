<?php

namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_business_creation_stores_contact_details_and_its_logo(): void
    {
        $response = $this->post(route('businesses.store'), [
            'name' => 'Harbour Coffee',
            'email' => 'hello@harbour.example',
            'phone_number' => '+91 98765 43210',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $business = Business::query()->sole();
        $this->assertSame('Harbour Coffee', $business->name);
        $this->assertSame('hello@harbour.example', $business->email);
        $this->assertSame('+91 98765 43210', $business->phone_number);
        Storage::disk('public')->assertExists($business->logo_path);
    }

    public function test_invalid_business_input_cannot_create_a_partial_record_or_upload(): void
    {
        $this->post(route('businesses.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'phone_number' => 'letters-only',
            'logo' => UploadedFile::fake()->create('logo.txt', 5, 'text/plain'),
        ])->assertSessionHasErrors(['name', 'email', 'phone_number', 'logo']);

        $this->assertDatabaseCount('businesses', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_business_logo_is_required_and_must_be_an_image(): void
    {
        $this->post(route('businesses.store'), [
            'name' => 'Harbour Coffee',
            'email' => 'hello@harbour.example',
            'phone_number' => '9876543210',
        ])->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_malformed_phone_array_returns_validation_errors(): void
    {
        $this->post(route('businesses.store'), [
            'name' => 'Sample', 'email' => 'sample@example.com',
            'phone_number' => ['1234567890'],
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertSessionHasErrors('phone_number');
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_deleting_a_business_removes_all_related_rows_and_uploaded_files(): void
    {
        $business = $this->business();
        $branch = $business->branches()->create(['name' => 'Riverside']);
        Storage::disk('public')->put('branches/front.jpg', 'image');
        $branch->images()->create(['path' => 'branches/front.jpg']);
        $branch->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);
        $branch->dateOverrides()->create(['date' => '2026-10-26', 'is_available' => true]);

        $this->delete(route('businesses.destroy', $business))->assertRedirect();

        foreach (['businesses', 'branches', 'branch_images', 'branch_timings', 'branch_date_overrides'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        Storage::disk('public')->assertMissing(['businesses/logo.png', 'branches/front.jpg']);
    }

    public function test_branch_deletion_preserves_its_business_and_other_branches(): void
    {
        $business = $this->business();
        $first = $business->branches()->create(['name' => 'First']);
        $second = $business->branches()->create(['name' => 'Second']);
        Storage::disk('public')->put('branches/first.jpg', 'image');
        $first->images()->create(['path' => 'branches/first.jpg']);
        $first->timings()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);
        $first->dateOverrides()->create(['date' => '2026-10-26', 'is_available' => true]);

        $this->delete(route('branches.destroy', $first))->assertRedirect();

        $this->assertModelExists($business);
        $this->assertModelExists($second);
        $this->assertModelMissing($first);
        $this->assertDatabaseCount('branch_images', 0);
        $this->assertDatabaseCount('branch_timings', 0);
        $this->assertDatabaseCount('branch_date_overrides', 0);
        Storage::disk('public')->assertMissing('branches/first.jpg');
        Storage::disk('public')->assertExists($business->logo_path);
    }

    public function test_business_datatable_supports_search(): void
    {
        $this->business(['name' => 'First business', 'email' => 'first@example.com']);
        $this->business(['name' => 'Harbour Coffee', 'email' => 'second@example.com']);
        $this->business(['name' => 'Mountain Bakery', 'email' => 'third@example.com']);
        $columns = collect(['id', 'name', 'email', 'phone_number', 'branches_count', 'logo', 'actions'])
            ->map(fn ($name) => ['data' => $name, 'name' => $name, 'searchable' => in_array($name, ['name', 'email', 'phone_number']) ? 'true' : 'false', 'orderable' => 'false'])
            ->all();

        $this->getJson(route('businesses.data', [
            'draw' => 1, 'start' => 0, 'length' => 1, 'columns' => $columns,
            'search' => ['value' => 'Harbour', 'regex' => 'false'],
        ]))->assertOk()->assertJsonPath('recordsTotal', 3)->assertJsonPath('recordsFiltered', 1)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Harbour Coffee');
    }

    public function test_business_datatable_returns_only_the_requested_page(): void
    {
        foreach (['First', 'Second', 'Third'] as $index => $name) {
            $this->business(['name' => $name, 'email' => 'business'.$index.'@example.com']);
        }

        $this->getJson(route('businesses.data', [
            'draw' => 1, 'start' => 1, 'length' => 1,
        ]))->assertOk()->assertJsonPath('recordsTotal', 3)->assertJsonPath('recordsFiltered', 3)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Second');
    }

    public function test_business_datatable_escapes_user_supplied_names(): void
    {
        $this->business(['name' => '<script>alert(1)</script>', 'email' => 'first@example.com']);
        $response = $this->getJson(route('businesses.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonCount(1, 'data');

        $name = collect($response->json('data'))->firstWhere('email', 'first@example.com')['name'];
        $this->assertStringContainsString('&lt;script&gt;', $name);
        $this->assertStringNotContainsString('<script>', $name);
    }

    public function test_branch_datatable_shows_business_and_computed_status(): void
    {
        $business = $this->business();
        $business->branches()->create(['name' => 'Riverside']);
        $business->branches()->create(['name' => 'Hilltop']);
        $columns = collect(['id', 'name', 'business_name', 'status', 'actions'])
            ->map(fn ($name) => ['data' => $name, 'name' => $name, 'searchable' => $name === 'name' ? 'true' : 'false', 'orderable' => 'false'])
            ->all();

        $response = $this->getJson(route('branches.data', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
            'search' => ['value' => 'Riverside', 'regex' => 'false'],
        ]))->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 1)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Riverside')
            ->assertJsonPath('data.0.business_name', 'Harbour Coffee');

        $this->assertStringContainsString('Closed', $response->json('data.0.status'));
    }

    private function business(array $attributes = []): Business
    {
        Storage::disk('public')->put('businesses/logo.png', 'image');

        return Business::query()->create(array_merge([
            'name' => 'Harbour Coffee',
            'email' => 'hello@harbour.example',
            'phone_number' => '9876543210',
            'logo_path' => 'businesses/logo.png',
        ], $attributes));
    }
}
