<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VisitorRegistration extends Model
{
    protected $fillable = [
        'tracking_code', 'name', 'contact_number', 'id_type', 'id_number', 'purpose', 'destination',
        'visit_date', 'status', 'remarks', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'reviewed_at' => 'datetime'];
    }

    public function gatePass(): HasOne
    {
        return $this->hasOne(GatePass::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
