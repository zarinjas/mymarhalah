<?php

namespace App\Models;

use App\Support\NormalizesStoragePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;
    use NormalizesStoragePath;

    protected $fillable = [
        'system_logo_path',
    ];

    public static function singleton(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'system_logo_path' => null,
        ]);
    }

    public function getSystemLogoPathAttribute($value): ?string
    {
        return $this->normalizeStoragePath($value);
    }
}
