<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfaqDonation extends Model
{
    protected $table = 'infaq_donations';

    protected $fillable = [
        'infaq_id',
        'user_id',
        'amount',
        'reference',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function infaq(): BelongsTo
    {
        return $this->belongsTo(Infaq::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
