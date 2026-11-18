<?php

use App\Models\AccessEvent;
use App\Models\AuditLog;
use App\Models\DigitalId;
use App\Models\GatePass;
use App\Models\User;
use App\Models\VisitorRegistration;
use App\Services\CredentialTokenService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('renders the operator login and visitor registration pages', function () {
    $this->get(route('login'))->assertOk()->assertSee('Welcome back');
    $this->get(route('visitor.create'))->assertOk()->assertSee('visit request.');
});

it('allows an active operator to sign in and blocks a disabled account', function () {
    $operator = User::factory()->create(['role' => 'security', 'email' => 'guard@campus.test', 'password' => 'StrongPassword!123']);

    $this->post(route('login.store'), ['login' => $operator->email, 'password' => 'StrongPassword!123'])
        ->assertRedirect(route('dashboard'));

    auth()->logout();
    $operator->update(['is_active' => false]);

    $this->post(route('login.store'), ['login' => $operator->email, 'password' => 'StrongPassword!123'])
        ->assertSessionHasErrors(['login' => 'This account is disabled.']);
});

it('registers a visitor and issues an approved date-bound gate pass', function () {
    $response = $this->post(route('visitor.store'), [
        'name' => 'Avery Visitor',
        'contact_number' => '09171234567',
        'id_type' => 'government',
        'id_number' => 'ID-9988',
        'purpose' => 'Records request',
        'destination' => 'Registrar',
        'visit_date' => today()->toDateString(),
    ]);

    $registration = VisitorRegistration::firstOrFail();
    $response->assertRedirect(route('visitor.status', ['reference' => $registration->tracking_code]));
    expect($registration->status)->toBe('pending');

    $security = User::factory()->create(['role' => 'security']);
    $this->actingAs($security)->post(route('visitors.decision', $registration), [
        'decision' => 'approved',
        'allowed_gate' => 'Main Gate',
    ])->assertSessionHas('status');

    $pass = GatePass::firstOrFail();
    expect($pass->status)->toBe('active')
        ->and($pass->allowed_gate)->toBe('Main Gate')
        ->and($pass->expires_at->toDateString())->toBe(today()->toDateString());
});

it('toggles member entry and exit and rejects a replayed qr token', function () {
    $member = User::factory()->create(['role' => 'student', 'id_number' => 'ST-2048']);
    $security = User::factory()->create(['role' => 'security']);
    $digitalId = DigitalId::create([
        'user_id' => $member->id,
        'credential_uuid' => (string) Str::uuid(),
        'status' => 'active',
        'token_version' => 1,
    ]);
    $tokens = app(CredentialTokenService::class);
    $firstToken = $tokens->forDigitalId($digitalId->id, 1);

    $this->actingAs($security)->postJson(route('scanner.verify'), [
        'token' => $firstToken,
        'zone' => 'campus',
        'location' => 'Main Gate',
    ])->assertOk()->assertJsonPath('direction', 'entry');

    $this->postJson(route('scanner.verify'), [
        'token' => $firstToken,
        'zone' => 'campus',
        'location' => 'Main Gate',
    ])->assertUnprocessable()->assertJsonPath('allowed', false);

    $this->postJson(route('scanner.verify'), [
        'token' => $tokens->forDigitalId($digitalId->id, 1),
        'zone' => 'campus',
        'location' => 'Main Gate',
    ])->assertOk()->assertJsonPath('direction', 'exit');

    expect(AccessEvent::where('result', 'allowed')->count())->toBe(2)
        ->and(AccessEvent::where('anomaly', 'replayed_token')->exists())->toBeTrue();
});

it('completes visitor pass entry and exit then consumes the pass', function () {
    $security = User::factory()->create(['role' => 'security']);
    $registration = VisitorRegistration::create([
        'tracking_code' => (string) Str::uuid(),
        'name' => 'Campus Guest',
        'contact_number' => '09170000000',
        'id_type' => 'passport',
        'id_number' => 'P-1234',
        'purpose' => 'Meeting',
        'destination' => 'Library',
        'visit_date' => today(),
        'status' => 'approved',
        'reviewed_by' => $security->id,
        'reviewed_at' => now(),
    ]);
    $pass = GatePass::create([
        'visitor_registration_id' => $registration->id,
        'pass_uuid' => (string) Str::uuid(),
        'status' => 'active',
        'valid_from' => today()->startOfDay(),
        'expires_at' => today()->endOfDay(),
    ]);
    $tokens = app(CredentialTokenService::class);

    $this->actingAs($security)->postJson(route('scanner.verify'), [
        'token' => $tokens->forGatePass($pass->id),
        'zone' => 'campus',
        'location' => 'Main Gate',
    ])->assertOk()->assertJsonPath('direction', 'entry');

    $this->postJson(route('scanner.verify'), [
        'token' => $tokens->forGatePass($pass->id),
        'zone' => 'campus',
        'location' => 'Main Gate',
    ])->assertOk()->assertJsonPath('direction', 'exit');

    expect($pass->fresh()->status)->toBe('consumed');
});

it('keeps scan access limited to the assigned operator role', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)->get(route('scanner'))->assertForbidden();
});

it('seeds local operator accounts and requires a first-login password update', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::where('email', 'admin@gatepassqr.local')->firstOrFail();

    expect($admin->role)->toBe('administrator')
        ->and($admin->must_change_password)->toBeTrue();

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('password.edit'));
    $this->get(route('password.edit'))->assertOk();
});

it('allows a user to complete the forced password update', function () {
    $operator = User::factory()->create([
        'role' => 'security',
        'password' => 'TemporaryPassword!123',
        'must_change_password' => true,
    ]);

    $this->actingAs($operator)->put(route('password.update'), [
        'current_password' => 'TemporaryPassword!123',
        'password' => 'NewStrongPassword!123',
        'password_confirmation' => 'NewStrongPassword!123',
    ])->assertRedirect(route('dashboard'));

    expect($operator->fresh()->must_change_password)->toBeFalse();
});

it('renders the authenticated operations pages for administrators', function () {
    $admin = User::factory()->create(['role' => 'administrator']);
    $this->actingAs($admin);

    foreach (['dashboard', 'members.index', 'visitors.index', 'scanner', 'logs.index', 'audit.index', 'my-id'] as $routeName) {
        $this->get(route($routeName))->assertOk();
    }
});

it('creates an operator account with a temporary password and forced reset', function () {
    $admin = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($admin)->post(route('members.store'), [
        'name' => 'Lab Operator',
        'email' => 'lab.operator@campus.test',
        'temporary_password' => 'GatePass!2026',
        'role' => 'laboratory',
    ])->assertSessionHas('status');

    $operator = User::where('email', 'lab.operator@campus.test')->firstOrFail();
    expect($operator->role)->toBe('laboratory')
        ->and($operator->must_change_password)->toBeTrue()
        ->and(Hash::check('GatePass!2026', $operator->password))->toBeTrue();
});

it('expires visitor requests after the two-hour review window', function () {
    $security = User::factory()->create(['role' => 'security']);
    $registration = VisitorRegistration::create([
        'tracking_code' => (string) Str::uuid(),
        'name' => 'Late Visitor',
        'contact_number' => '09170000000',
        'id_type' => 'government',
        'id_number' => 'ID-EXP',
        'purpose' => 'Meeting',
        'destination' => 'Registrar',
        'visit_date' => today(),
        'status' => 'pending',
    ]);
    $registration->forceFill(['created_at' => now()->subHours(3)])->save();

    $this->actingAs($security)->post(route('visitors.decision', $registration), [
        'decision' => 'approved',
    ])->assertStatus(409);

    expect(GatePass::where('visitor_registration_id', $registration->id)->exists())->toBeFalse();
});

it('limits laboratory personnel to laboratory access logs', function () {
    $operator = User::factory()->create(['role' => 'laboratory']);
    $member = User::factory()->create(['role' => 'student', 'id_number' => 'ST-300']);

    foreach ([['zone' => 'laboratory', 'location' => 'Lab 1'], ['zone' => 'campus', 'location' => 'Main Gate']] as $event) {
        AccessEvent::create([
            'user_id' => $member->id,
            'actor_id' => $operator->id,
            'credential_type' => 'digital_id',
            'zone' => $event['zone'],
            'direction' => 'entry',
            'location' => $event['location'],
            'result' => 'allowed',
            'scanned_at' => now(),
        ]);
    }

    $this->actingAs($operator)->get(route('logs.index'))
        ->assertOk()
        ->assertSee('Lab 1')
        ->assertDontSee('Main Gate');
});

it('allows visitor office access only at its approved destination while checked in', function () {
    $officePersonnel = User::factory()->create(['role' => 'office']);
    $security = User::factory()->create(['role' => 'security']);
    $registration = VisitorRegistration::create([
        'tracking_code' => (string) Str::uuid(),
        'name' => 'Office Guest',
        'contact_number' => '09170000000',
        'id_type' => 'government',
        'id_number' => 'ID-OFFICE',
        'purpose' => 'Records request',
        'destination' => 'Registrar',
        'visit_date' => today(),
        'status' => 'approved',
        'reviewed_by' => $security->id,
        'reviewed_at' => now(),
    ]);
    $pass = GatePass::create([
        'visitor_registration_id' => $registration->id,
        'pass_uuid' => (string) Str::uuid(),
        'status' => 'active',
        'valid_from' => today()->startOfDay(),
        'expires_at' => today()->endOfDay(),
        'entered_at' => now(),
    ]);
    $tokens = app(CredentialTokenService::class);

    $this->actingAs($officePersonnel)->postJson(route('scanner.verify'), [
        'token' => $tokens->forGatePass($pass->id),
        'zone' => 'office',
        'location' => 'Registrar',
    ])->assertOk()->assertJsonPath('direction', 'entry');

    $this->postJson(route('scanner.verify'), [
        'token' => $tokens->forGatePass($pass->id),
        'zone' => 'office',
        'location' => 'Finance',
    ])->assertUnprocessable()->assertJsonPath('message', 'This pass is restricted to Registrar.');

    expect($pass->fresh()->status)->toBe('active');
});

it('creates an audited backup with a checksum and no serialized passwords', function () {
    $admin = User::factory()->create(['role' => 'administrator', 'password' => 'SensitiveTestPassword!']);
    Storage::fake('local');

    $this->actingAs($admin)->post(route('backup.store'))->assertOk();

    $backupPath = Storage::disk('local')->files('backups')[0];
    $backupJson = Storage::disk('local')->get($backupPath);
    $backup = json_decode($backupJson, true, flags: JSON_THROW_ON_ERROR);
    $serializedSnapshot = json_encode($backup['snapshot'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

    expect($backup['checksum_sha256'])->toBe(hash('sha256', $serializedSnapshot))
        ->and($backupJson)->not->toContain('SensitiveTestPassword!')
        ->and(AuditLog::where('action', 'backup.created')->exists())->toBeTrue();
});
