<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalId extends Model
{
    protected $fillable = ['user_id', 'credential_uuid', 'status', 'token_version', 'valid_until', 'issued_by'];

    protected function casts(): array
    {
        return ['valid_until' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
