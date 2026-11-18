<?php

namespace App\Services;

use App\Models\AccessEvent;
use App\Models\AuditLog;
use App\Models\DigitalId;
use App\Models\GatePass;
use App\Models\User;
use App\Models\UserZoneAuthorization;
use App\Models\VisitorRegistration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseBackupService
{
    public function create(?User $actor = null, ?string $ipAddress = null): string
    {
        AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $actor ? 'backup.created' : 'backup.scheduled_created',
            'ip_address' => $ipAddress,
        ]);

        $snapshot = [
            'application' => 'GatePassQR',
            'schema_version' => 1,
            'created_at' => now()->toIso8601String(),
            'tables' => [
                'users' => User::all()->toArray(),
                'digital_ids' => DigitalId::all()->toArray(),
                'user_zone_authorizations' => UserZoneAuthorization::all()->toArray(),
                'visitor_registrations' => VisitorRegistration::all()->toArray(),
                'gate_passes' => GatePass::all()->toArray(),
                'access_events' => AccessEvent::all()->toArray(),
                'audit_logs' => AuditLog::all()->toArray(),
            ],
        ];
        $serializedSnapshot = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $filename = 'backups/gatepassqr-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json';

        Storage::disk('local')->put($filename, json_encode([
            'checksum_sha256' => hash('sha256', $serializedSnapshot),
            'snapshot' => $snapshot,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $filename;
    }
}
