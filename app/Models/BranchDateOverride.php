<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchDateOverride extends Model
{
    protected $fillable = ['date', 'is_available'];

    protected function casts(): array
    {
        return ['date' => 'immutable_date', 'is_available' => 'boolean'];
    }

    /** Keep DATE values portable: SQLite must not receive a midnight timestamp. */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = CarbonImmutable::parse($value)->format('Y-m-d');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

}
