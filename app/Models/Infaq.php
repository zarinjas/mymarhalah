<?php

namespace App\Models;

use App\Support\NormalizesStoragePath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Infaq extends Model
{
    use NormalizesStoragePath;

    protected $table = 'infaq';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'image_path',
        'type',
        'target_amount',
        'collected_amount',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'target_amount'    => 'float',
        'collected_amount' => 'float',
        'display_order'    => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(InfaqDonation::class);
    }

    /**
     * Progress percentage capped at 100 (for 'progress' type infaq).
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->type !== 'progress' || ! $this->target_amount || $this->target_amount <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->collected_amount / $this->target_amount) * 100));
    }

    public function getImagePathAttribute($value): ?string
    {
        return $this->normalizeStoragePath($value);
    }
}
