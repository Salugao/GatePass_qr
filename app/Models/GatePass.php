<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatePass extends Model
{
    protected $fillable = [
        'visitor_registration_id', 'pass_uuid', 'status', 'allowed_gate', 'valid_from', 'expires_at', 'entered_at', 'exited_at',
    ];

    protected function casts(): array
    {
        return ['valid_from' => 'datetime', 'expires_at' => 'datetime', 'entered_at' => 'datetime', 'exited_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(VisitorRegistration::class, 'visitor_registration_id');
    }
}
