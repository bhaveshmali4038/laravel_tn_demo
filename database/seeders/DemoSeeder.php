<?php

namespace Database\Seeders;

use App\Models\Business;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Optional, repeatable sample data. The first branch reproduces the PDF hours. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->writeIllustration('demo/harbor-logo.png', 'HARBOR', true);
        $this->writeIllustration('demo/harbor-interior.png', 'HARBOR COFFEE');
        $this->writeIllustration('demo/harbor-counter.png', 'YOUR DAILY PAUSE');

        DB::transaction(function (): void {
            $business = Business::firstOrCreate(['email' => 'hello@harbor.example'], [
                'name' => 'Harbor Coffee',
                'phone_number' => '+91 98765 43210',
                'logo_path' => 'demo/harbor-logo.png',
            ]);
            if (! $business->branches()->where('name', 'Riverside')->exists()) {
                $branch = $business->branches()->create(['name' => 'Riverside']);
                $branch->images()->createMany([
                    ['path' => 'demo/harbor-interior.png'],
                    ['path' => 'demo/harbor-counter.png'],
                ]);
                $hours = [
                    1 => [['12:00', '14:00'], ['19:00', '22:00']],
                    2 => [['09:00', '11:00'], ['12:45', '14:30'], ['19:00', '22:00']],
                    3 => [['09:00', '17:00']],
                    4 => [['09:00', '17:00']],
                ];
                foreach ($hours as $weekday => $slots) {
                    foreach ($slots as [$start, $end]) {
                        $branch->timings()->create(['weekday' => $weekday, 'start_time' => $start, 'end_time' => $end]);
                    }
                }
                $today = CarbonImmutable::now(config('app.timezone'));
                $branch->dateOverrides()->create(['date' => $today->addDays(2)->toDateString(), 'is_available' => false]);
                $branch->dateOverrides()->create(['date' => $today->next('Sunday')->addWeek()->toDateString(), 'is_available' => true]);
            }

            if (! $business->branches()->where('name', 'City Centre')->exists()) {
                $branch = $business->branches()->create(['name' => 'City Centre']);
                // Distinct files ensure deleting one sample branch preserves the other.
                Storage::disk('public')->copy('demo/harbor-interior.png', 'demo/city-centre.png');
                $branch->images()->create(['path' => 'demo/city-centre.png']);
                foreach (range(1, 7) as $weekday) {
                    $branch->timings()->create(['weekday' => $weekday, 'start_time' => '08:00', 'end_time' => '22:00']);
                }
            }
        });
    }

    /** Create clearly illustrative demo assets locally; no remote image dependency. */
    private function writeIllustration(string $path, string $label, bool $logo = false): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }
        $width = $logo ? 400 : 1200;
        $height = $logo ? 400 : 720;
        $image = imagecreatetruecolor($width, $height);
        $navy = imagecolorallocate($image, 24, 39, 66);
        $cream = imagecolorallocate($image, 246, 238, 223);
        $pink = imagecolorallocate($image, 220, 68, 120);
        $muted = imagecolorallocate($image, 201, 173, 141);
        imagefill($image, 0, 0, $cream);
        if ($logo) {
            imagefilledellipse($image, 200, 180, 240, 240, $navy);
            imagestring($image, 5, 172, 165, 'HC', $cream);
            imagestring($image, 5, 169, 325, $label, $navy);
        } else {
            imagefilledrectangle($image, 0, 0, 1200, 150, $navy);
            imagefilledrectangle($image, 100, 220, 1100, 520, $muted);
            foreach ([160, 460, 760] as $x) {
                imagefilledrectangle($image, $x, 260, $x + 220, 460, $navy);
                imagefilledellipse($image, $x + 110, 320, 60, 60, $cream);
            }
            imagefilledrectangle($image, 80, 550, 1120, 600, $pink);
            imagestring($image, 5, 450, 70, $label, $cream);
            imagestring($image, 4, 480, 650, 'DEMO ILLUSTRATION', $navy);
        }
        ob_start();
        imagepng($image);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($image);
    }
}
