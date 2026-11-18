@extends('layouts.app')
@section('title', 'Visit status')
@section('public-content')
<main class="public-page public-status-page">
    <header class="public-header"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><i data-lucide="scan-qr-code"></i></span><span><strong>GatePass<span>QR</span></strong><small>Campus access</small></span></a><a class="text-link" href="{{ route('visitor.create') }}">New visit request <i data-lucide="arrow-up-right"></i></a></header>
    <section class="status-wrap">
        <div class="status-copy"><p class="eyebrow">VISITOR REQUEST · {{ $registration->tracking_code }}</p><h1>Visit <em>status</em></h1>
            <span class="status-pill status-{{ $registration->status }}">{{ ucfirst($registration->status) }}</span>
            <dl class="detail-list"><div><dt>Visitor</dt><dd>{{ $registration->name }}</dd></div><div><dt>Visit date</dt><dd>{{ $registration->visit_date->format('d M Y') }}</dd></div><div><dt>Destination</dt><dd>{{ $registration->destination }}</dd></div><div><dt>Purpose</dt><dd>{{ $registration->purpose }}</dd></div>@if($registration->remarks)<div><dt>Security note</dt><dd>{{ $registration->remarks }}</dd></div>@endif</dl>
            @if($registration->status === 'pending')<p class="muted status-note">Submitted {{ $registration->created_at->format('d M Y · g:i A') }}</p>@elseif($registration->status === 'rejected')<p class="muted status-note">Decision recorded {{ $registration->reviewed_at?->format('d M Y · g:i A') }}</p>@elseif($registration->status === 'approved' && $registration->gatePass)<p class="muted status-note">Approved {{ $registration->reviewed_at?->format('d M Y · g:i A') }}</p>@endif
        </div>
        @if($registration->status === 'approved' && $registration->gatePass)
            <aside class="pass-card"><div class="pass-card-top"><span class="pass-kicker">DIGITAL GATE PASS</span><span class="pass-valid"><i data-lucide="circle-check"></i>Approved</span></div><div id="gate-pass-qr" class="qr-code" data-qr-token data-token-url="{{ route('visitor.pass-token', ['reference' => $registration->tracking_code]) }}"></div><p class="qr-holder">{{ $registration->name }}</p><p class="qr-detail">Valid {{ $registration->gatePass->valid_from->format('d M Y') }} · until {{ $registration->gatePass->expires_at->format('g:i A') }}</p>@if($registration->gatePass->allowed_gate)<p class="qr-detail">Gate: {{ $registration->gatePass->allowed_gate }}</p>@endif<div class="qr-divider"></div><p class="qr-small">Pass {{ substr($registration->gatePass->pass_uuid, 0, 8) }}</p></aside>
        @endif
    </section>
    <footer class="public-footer"><span>GatePassQR · CHMSU College of Computer Studies</span><a class="text-link" href="{{ route('visitor.status', ['reference' => $registration->tracking_code]) }}">Refresh status</a></footer>
</main>
@endsection
@push('scripts')
<script>window.addEventListener('load', () => window.lucide?.createIcons());</script>
@endpush