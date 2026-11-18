<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use JsonException;

class CredentialTokenService
{
    /** @return array{type: string, id: int, version?: int, expires: int, nonce: string}|null */
    public function decode(string $token): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['type'], $payload['id'], $payload['expires'], $payload['nonce'])) {
            return null;
        }

        if (! is_int($payload['expires']) || $payload['expires'] < now()->timestamp) {
            return null;
        }

        return $payload;
    }

    public function forDigitalId(int $id, int $version): string
    {
        return $this->encode([
            'type' => 'digital_id',
            'id' => $id,
            'version' => $version,
            'expires' => now()->addSeconds(30)->timestamp,
            'nonce' => Str::random(24),
        ]);
    }

    public function forGatePass(int $id): string
    {
        return $this->encode([
            'type' => 'gate_pass',
            'id' => $id,
            'expires' => now()->addSeconds(30)->timestamp,
            'nonce' => Str::random(24),
        ]);
    }

    /** @param array<string, int|string> $payload */
    private function encode(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
