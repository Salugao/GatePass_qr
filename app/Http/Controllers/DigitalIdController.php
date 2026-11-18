<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DigitalId;
use App\Models\User;
use App\Services\CredentialTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DigitalIdController extends Controller
{
    public function show(Request $request): View
    {
        $digitalId = $request->user()->digitalIds()->where('status', 'active')->latest()->first();

        return view('ids.show', compact('digitalId'));
    }

    public function token(Request $request, CredentialTokenService $tokens): JsonResponse
    {
        $digitalId = $request->user()->digitalIds()->where('status', 'active')->latest()->firstOrFail();

        return response()->json(['token' => $tokens->forDigitalId($digitalId->id, $digitalId->token_version), 'expires_in' => 30]);
    }

    public function issue(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['student', 'faculty', 'staff'], true), 422, 'Only campus members can receive a digital ID.');
        abort_unless($user->is_active && $user->photo_path, 422, 'An active institutional record and profile photo are required.');

        $digitalId = $user->digitalIds()->where('status', 'active')->first();

        if (! $digitalId) {
            $digitalId = $user->digitalIds()->create([
                'credential_uuid' => (string) Str::uuid(),
                'status' => 'active',
                'issued_by' => $request->user()->id,
            ]);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'digital_id.issued',
            'subject_type' => DigitalId::class,
            'subject_id' => $digitalId->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Digital ID is active.');
    }

    public function revoke(Request $request, DigitalId $digitalId): RedirectResponse
    {
        $digitalId->update(['status' => 'revoked', 'token_version' => $digitalId->token_version + 1]);

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'digital_id.revoked',
            'subject_type' => DigitalId::class,
            'subject_id' => $digitalId->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Digital ID revoked.');
    }
}
