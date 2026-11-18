@extends('layouts.app')
@section('title', 'Overview')
@section('section', 'Overview')
@section('content')
@push('head')<meta http-equiv="refresh" content="10">@endpush
<div class="page-heading heading-with-action">
    <div><p class="eyebrow">{{ now()->format('l · d F Y') }}</p><h1>Campus overview</h1><p class="muted">Access activity across campus, updated for today.</p></div>
    @if(in_array(auth()->user()->role, ['administrator', 'security', 'laboratory', 'office'], true))<a class="button button-primary" href="{{ route('scanner') }}"><i data-lucide="scan-line"></i>Open scanner</a>@endif
</div>
<section class="metric-grid" aria-label="Campus metrics">
    <article class="metric metric-ink"><div class="metric-top"><span>ON CAMPUS</span><i data-lucide="users-round"></i></div><strong>{{ number_format($presentCount) }}</strong><small>Current headcount</small><span class="metric-accent">LIVE</span></article>
    <article class="metric"><div class="metric-top"><span>VISITORS TODAY</span><i data-lucide="contact-round"></i></div><strong>{{ number_format($visitorCount) }}</strong><small>Registered visit requests</small></article>
    <article class="metric metric-warm"><div class="metric-top"><span>PENDING REVIEW</span><i data-lucide="hourglass"></i></div><strong>{{ number_format($pendingCount) }}</strong><small>Awaiting security decision</small>@if($pendingCount)<a class="metric-link" href="{{ route('visitors.index', ['status' => 'pending']) }}">Review queue <i data-lucide="arrow-up-right"></i></a>@endif</article>
    <article class="metric"><div class="metric-top"><span>ACTIVE DIGITAL IDS</span><i data-lucide="badge-check"></i></div><strong>{{ number_format($activeIdCount) }}</strong><small>Of {{ number_format($memberCount) }} active members</small></article>
</section>
<div class="dashboard-grid">
    <section class="panel activity-panel">
        <div class="panel-heading"><div><p class="eyebrow">GATE ACTIVITY</p><h2>Recent scans</h2></div><a class="text-link" href="{{ route('logs.index') }}">Full access log <i data-lucide="arrow-up-right"></i></a></div>
        <div class="table-scroll"><table class="data-table"><thead><tr><th>PERSON</th><th>AREA</th><th>TIME</th><th>RESULT</th></tr></thead><tbody>
        @forelse($recentEvents as $event)
            <tr><td><span class="person-cell"><span class="avatar avatar-small">{{ mb_substr($event->user?->name ?? $event->visitorRegistration?->name ?? 'U', 0, 1) }}</span><span><strong>{{ $event->user?->name ?? $event->visitorRegistration?->name ?? 'Unknown credential' }}</strong><small>{{ $event->user?->id_number ?? ucfirst(str_replace('_', ' ', $event->credential_type)) }}</small></span></span></td><td><span class="zone-label">{{ ucfirst($event->zone) }} · {{ $event->location }}</span></td><td>{{ $event->scanned_at->format('g:i A') }}</td><td><span class="status-pill {{ $event->result === 'allowed' ? 'status-approved' : 'status-denied' }}">{{ ucfirst($event->result) }}</span></td></tr>
        @empty
            <tr><td colspan="4"><div class="empty-state"><i data-lucide="scan-line"></i><strong>No scans yet today</strong><span>Verified access activity will appear here.</span></div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>
    <aside class="panel quick-panel"><p class="eyebrow">QUICK ACCESS</p><h2>Operations</h2>
        <a class="quick-link" href="{{ route('scanner') }}"><span class="quick-icon quick-green"><i data-lucide="scan-line"></i></span><span><strong>Verify credential</strong><small>Scan a QR pass or ID</small></span><i data-lucide="arrow-up-right"></i></a>
        @if(in_array(auth()->user()->role, ['administrator', 'security'], true))<a class="quick-link" href="{{ route('visitors.index') }}"><span class="quick-icon quick-yellow"><i data-lucide="clipboard-check"></i></span><span><strong>Visitor requests</strong><small>{{ $pendingCount }} pending review</small></span><i data-lucide="arrow-up-right"></i></a>@endif
        @if(auth()->user()->role === 'administrator')<a class="quick-link" href="{{ route('members.index') }}"><span class="quick-icon quick-blue"><i data-lucide="id-card"></i></span><span><strong>Manage people</strong><small>Accounts and digital IDs</small></span><i data-lucide="arrow-up-right"></i></a>@endif
        <div class="quick-footer"><span class="live-dot"></span>Updated {{ now()->format('g:i A') }}</div>
    </aside>
</div>
@endsection