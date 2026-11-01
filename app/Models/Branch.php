<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['business_id', 'name'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BranchImage::class);
    }

    public function timings(): HasMany
    {
        return $this->hasMany(BranchTiming::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function dateOverrides(): HasMany
    {
        return $this->hasMany(BranchDateOverride::class)->orderBy('date');
    }
}
