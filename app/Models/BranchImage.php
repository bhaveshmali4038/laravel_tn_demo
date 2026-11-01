<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchImage extends Model
{
    protected $fillable = ['path'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
