<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the tables required for campus access workflows', function () {
    expect(Schema::hasColumns('users', ['role', 'id_number', 'is_active']))->toBeTrue()
        ->and(Schema::hasTable('digital_ids'))->toBeTrue()
        ->and(Schema::hasTable('visitor_registrations'))->toBeTrue()
        ->and(Schema::hasTable('gate_passes'))->toBeTrue()
        ->and(Schema::hasTable('access_events'))->toBeTrue()
        ->and(Schema::hasTable('audit_logs'))->toBeTrue();
});
