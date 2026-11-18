@extends('layouts.app')
@section('title', 'Visitor requests')
@section('section', 'Visitor requests')
@section('content')
<div class="page-heading heading-with-action"><div><p class="eyebrow">GATE & VISITOR MANAGEMENT</p><h1>Visitor requests</h1><p class="muted">Review visit details and issue date-limited campus passes.</p></div><a class="button button-secondary" href="{{ route('visitor.create') }}" target="_blank"><i data-lucide="external-link"></i>Open guest form</a></div>
<div class="filter-bar"><div class="filter-tabs"><a class="{{ request('status') ? '' : 'selected' }}" href="{{ route('visitors.index') }}">All requests</a>@foreach(['pending', 'approved', 'rejected'] as $status)<a class="{{ request('status') === $status ? 'selected' : '' }}" href="{{ route('visitors.index', ['status' => $status]) }}">{{ ucfirst($status) }}</a>@endforeach</div><span class="result-count">{{ $registrations->total() }} records</span></div>
<section class="request-list">
    @forelse($registrations as $registration)
        <article class="request-row">
            <div class="request-main"><span class="avatar">{{ mb_substr($registration->name, 0, 1) }}</span><div><div class="request-name-row"><h2>{{ $registration->name }}</h2><span class="status-pill status-{{ $registration->status }}">{{ ucfirst($registration->status) }}</span></div><p>{{ $registration->purpose }}</p><div class="request-meta"><span><i data-lucide="map-pin"></i>{{ $registration->destination }}</span><span><i data-lucide="calendar-days"></i>{{ $registration->visit_date->format('d M Y') }}</span><span><i data-lucide="clock-3"></i>Submitted {{ $registration->created_at->diffForHumans() }}</span></div></div></div>
            <details class="request-details"><summary>Review details <i data-lucide="chevron-down"></i></summary><div class="request-detail-grid"><div><small>CONTACT</small><strong>{{ $registration->contact_number }}</strong></div><div><small>{{ strtoupper($registration->id_type) }} ID</small><strong>{{ $registration->id_number }}</strong></div><div><small>REFERENCE</small><strong>{{ $registration->tracking_code }}</strong></div><div><small>REVIEWED BY</small><strong>{{ $registration->reviewer?->name ?? 'Not reviewed' }}</strong></div></div>
                @if($registration->status === 'pending' && $registration->created_at->gte(now()->subHours(2)))
                    <form method="POST" action="{{ route('visitors.decision', $registration) }}" class="decision-form">@csrf
                        <label class="field">Gate restriction<input name="allowed_gate" maxlength="120" placeholder="Any gate"></label>
                        <label class="field">Remarks<textarea name="remarks" rows="2" maxlength="1000" placeholder="Required for rejection"></textarea></label>
                        <div class="decision-actions"><button class="button button-danger" name="decision" value="rejected" type="submit"><i data-lucide="x"></i>Reject</button><button class="button button-primary" name="decision" value="approved" type="submit"><i data-lucide="check"></i>Approve & issue pass</button></div>
                    </form>
                @elseif($registration->status === 'pending')
                    <p class="muted expiry-note">This request is past the two-hour review window.</p>
                @elseif($registration->remarks)
                    <p class="decision-remark"><strong>Decision note:</strong> {{ $registration->remarks }}</p>
                @endif
            </details>
        </article>
    @empty
        <div class="panel empty-state"><i data-lucide="contact-round"></i><strong>No visitor requests</strong><span>Requests submitted by guests will appear here.</span></div>
    @endforelse
</section>
<div class="pagination-wrap">{{ $registrations->links() }}</div>
@endsection