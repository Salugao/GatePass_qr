@extends('layouts.app')
@section('title', 'Audit trail')
@section('section', 'Audit trail')
@section('content')
<div class="page-heading heading-with-action"><div><p class="eyebrow">SECURITY & GOVERNANCE</p><h1>Audit trail</h1><p class="muted">Administrative changes and security-sensitive actions.</p></div><form method="POST" action="{{ route('backup.store') }}">@csrf<button class="button button-secondary" type="submit"><i data-lucide="database-backup"></i>Create database backup</button></form></div>
<form class="audit-search panel" method="GET"><label class="search-field"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search actor or action"></label><button class="button button-dark" type="submit">Search</button></form>
<section class="panel table-panel"><div class="table-scroll"><table class="data-table"><thead><tr><th>WHEN</th><th>ACTOR</th><th>ACTION</th><th>RECORD</th><th>DETAILS</th><th>IP ADDRESS</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td class="nowrap">{{ $log->created_at->format('d M Y · g:i:s A') }}</td><td>{{ $log->actor?->name ?? 'System' }}</td><td><span class="audit-action">{{ str_replace(['.', '_'], [' / ', ' '], $log->action) }}</span></td><td>{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td><td>{{ $log->details ? json_encode($log->details, JSON_UNESCAPED_SLASHES) : '—' }}</td><td>{{ $log->ip_address ?? '—' }}</td></tr>@empty<tr><td colspan="6"><div class="empty-state"><i data-lucide="shield-check"></i><strong>No audit events</strong><span>Administrative activity will appear here.</span></div></td></tr>@endforelse
</tbody></table></div><div class="pagination-wrap">{{ $logs->links() }}</div></section>
@endsection