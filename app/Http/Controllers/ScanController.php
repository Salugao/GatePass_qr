<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use App\Models\DigitalId;
use App\Models\GatePass;
use App\Models\User;
use App\Services\CredentialTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function create(): View
    {
        return view('scanner.index');
    }

    public function store(Request $request, CredentialTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'zone' => ['required', 'in:campus,laboratory,office'],
            'location' => ['required', 'string', 'max:120'],
        ]);

        abort_unless($this->canScanZone($request->user(), $data['zone']), 403);

        $payload = $tokens->decode($data['token']);

        if (! $payload || ! Cache::add('scan-nonce:'.$payload['nonce'], true, 45)) {
            $this->recordDenied($request, $data, $payload ? 'replayed_token' : 'invalid_or_expired_token');

            return response()->json(['allowed' => false, 'message' => 'Invalid, expired, or already scanned QR code.'], 422);
        }

        return DB::transaction(function () use ($request, $data, $payload): JsonResponse {
            if ($payload['type'] === 'digital_id') {
                return $this->scanDigitalId($request, $data, $payload);
            }

            if ($payload['type'] === 'gate_pass') {
                return $this->scanGatePass($request, $data, $payload);
            }

            $this->recordDenied($request, $data, 'unknown_credential_type');

            return response()->json(['allowed' => false, 'message' => 'Unrecognized credential.'], 422);
        });
    }

    /** @param array{zone: string, location: string} $data
     * @param  array{type: string, id: int, version?: int, expires: int, nonce: string}  $payload
     */
    private function scanDigitalId(Request $request, array $data, array $payload): JsonResponse
    {
        $digitalId = DigitalId::query()->with('user')->lockForUpdate()->find($payload['id']);
        $user = $digitalId?->user;

        if (! $digitalId || ! $user || $digitalId->status !== 'active' || $digitalId->token_version !== ($payload['version'] ?? 0) || ! $user->is_active || ($digitalId->valid_until && $digitalId->valid_until->isPast())) {
            $this->recordDenied($request, $data, 'inactive_digital_id', $user);

            return response()->json(['allowed' => false, 'message' => 'Access denied: inactive digital ID.'], 422);
        }

        if ($data['zone'] !== 'campus' && ! $user->zoneAuthorizations()->where('zone', $data['zone'])->exists()) {
            $this->recordDenied($request, $data, 'zone_not_authorized', $user);

            return response()->json(['allowed' => false, 'message' => 'No access authorization for this area.', 'person' => $user->name], 422);
        }

        $direction = $this->nextDirection($user, $data['zone']);
        $this->recordEvent($request, $data, 'digital_id', $direction, 'allowed', $user);

        return response()->json(['allowed' => true, 'message' => 'Digital ID verified.', 'person' => $user->name, 'id_number' => $user->id_number, 'direction' => $direction]);
    }

    /** @param array{zone: string, location: string} $data
     * @param  array{type: string, id: int, expires: int, nonce: string}  $payload
     */
    private function scanGatePass(Request $request, array $data, array $payload): JsonResponse
    {
        $pass = GatePass::query()->with('registration')->lockForUpdate()->find($payload['id']);
        $registration = $pass?->registration;

        if (! $pass || ! $registration || $pass->status !== 'active' || $registration->status !== 'approved' || $pass->valid_from->isFuture() || $pass->expires_at->isPast()) {
            $this->recordDenied($request, $data, 'invalid_or_expired_pass', null, $registration?->id, $pass?->id);

            return response()->json(['allowed' => false, 'message' => 'Pass expired or inactive.'], 422);
        }

        if ($data['zone'] === 'laboratory') {
            $this->recordDenied($request, $data, 'visitor_pass_restricted_from_laboratories', null, $registration->id, $pass->id);

            return response()->json(['allowed' => false, 'message' => 'Visitor passes do not grant laboratory access.'], 422);
        }

        if ($data['zone'] === 'office') {
            if (! $pass->entered_at || $pass->exited_at) {
                $this->recordDenied($request, $data, 'visitor_not_checked_in', null, $registration->id, $pass->id);

                return response()->json(['allowed' => false, 'message' => 'Visitor must be checked in at campus before office access.'], 422);
            }

            if (mb_strtolower($registration->destination) !== mb_strtolower($data['location'])) {
                $this->recordDenied($request, $data, 'destination_mismatch', null, $registration->id, $pass->id);

                return response()->json(['allowed' => false, 'message' => 'This pass is restricted to '.$registration->destination.'.', 'person' => $registration->name], 422);
            }

            $direction = $this->nextVisitorDirection($registration->id, $data['zone']);
            $this->recordEvent($request, $data, 'gate_pass', $direction, 'allowed', null, $registration->id, $pass->id);

            return response()->json(['allowed' => true, 'message' => 'Visitor office access verified.', 'person' => $registration->name, 'direction' => $direction]);
        }

        if ($data['zone'] === 'campus' && $pass->allowed_gate && mb_strtolower($pass->allowed_gate) !== mb_strtolower($data['location'])) {
            $this->recordDenied($request, $data, 'wrong_gate', null, $registration->id, $pass->id);

            return response()->json(['allowed' => false, 'message' => 'This pass is restricted to '.$pass->allowed_gate.'.', 'person' => $registration->name], 422);
        }

        if (! $pass->entered_at) {
            $direction = 'entry';
            $pass->update(['entered_at' => now()]);
        } elseif (! $pass->exited_at) {
            $direction = 'exit';
            $pass->update(['exited_at' => now(), 'status' => 'consumed']);
        } else {
            $this->recordDenied($request, $data, 'pass_already_consumed', null, $registration->id, $pass->id);

            return response()->json(['allowed' => false, 'message' => 'This single-use pass has already completed its entry and exit.'], 422);
        }

        $this->recordEvent($request, $data, 'gate_pass', $direction, 'allowed', null, $registration->id, $pass->id);

        return response()->json(['allowed' => true, 'message' => 'Visitor pass verified.', 'person' => $registration->name, 'direction' => $direction]);
    }

    private function canScanZone(User $user, string $zone): bool
    {
        return $user->role === 'administrator'
            || ($zone === 'campus' && $user->role === 'security')
            || ($zone === 'laboratory' && $user->role === 'laboratory')
            || ($zone === 'office' && $user->role === 'office');
    }

    private function nextDirection(User $user, string $zone): string
    {
        $lastDirection = AccessEvent::query()
            ->where('user_id', $user->id)
            ->where('zone', $zone)
            ->where('result', 'allowed')
            ->latest('scanned_at')
            ->value('direction');

        return $lastDirection === 'entry' ? 'exit' : 'entry';
    }

    private function nextVisitorDirection(int $registrationId, string $zone): string
    {
        $lastDirection = AccessEvent::query()
            ->where('visitor_registration_id', $registrationId)
            ->where('zone', $zone)
            ->where('result', 'allowed')
            ->latest('scanned_at')
            ->value('direction');

        return $lastDirection === 'entry' ? 'exit' : 'entry';
    }

    /** @param array{zone: string, location: string} $data */
    private function recordEvent(Request $request, array $data, string $type, string $direction, string $result, ?User $user = null, ?int $registrationId = null, ?int $passId = null, ?string $anomaly = null): void
    {
        AccessEvent::create([
            'user_id' => $user?->id,
            'visitor_registration_id' => $registrationId,
            'gate_pass_id' => $passId,
            'actor_id' => $request->user()->id,
            'credential_type' => $type,
            'zone' => $data['zone'],
            'direction' => $direction,
            'location' => $data['location'],
            'result' => $result,
            'anomaly' => $anomaly,
            'scanned_at' => now(),
        ]);
    }

    /** @param array{zone: string, location: string} $data */
    private function recordDenied(Request $request, array $data, string $reason, ?User $user = null, ?int $registrationId = null, ?int $passId = null): void
    {
        $this->recordEvent($request, $data, $passId ? 'gate_pass' : 'digital_id', 'denied', 'denied', $user, $registrationId, $passId, $reason);
    }
}
