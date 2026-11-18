<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessEvent extends Model
{
    protected $fillable = [
        'user_id', 'visitor_registration_id', 'gate_pass_id', 'actor_id', 'credential_type', 'zone',
        'direction', 'location', 'result', 'anomaly', 'scanned_at',
    ];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function visitorRegistration(): BelongsTo
    {
        return $this->belongsTo(VisitorRegistration::class);
    }
}
