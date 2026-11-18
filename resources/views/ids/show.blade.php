@extends('layouts.app')
@section('title', 'Digital ID')
@section('section', 'Digital ID')
@section('content')
<div class="page-heading"><div><p class="eyebrow">CAMPUS CREDENTIAL</p><h1>Digital ID</h1></div></div>
@if(!$digitalId)
    <section class="panel id-missing"><span class="quick-icon quick-yellow"><i data-lucide="badge-alert"></i></span><div><h2>No active Digital ID</h2><p class="muted">Your campus administrator has not issued an active credential to this account.</p></div></section>
@else
    <div class="id-page-grid"><section class="digital-id-card"><div class="id-card-top"><span class="id-brand"><span class="brand-mark"><i data-lucide="scan-qr-code"></i></span><span>CHMSU<small>Digital campus ID</small></span></span><span class="id-status"><span class="live-dot"></span>ACTIVE</span></div><div class="id-card-body"><div class="id-photo">@if(auth()->user()->photo_path)<img src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="{{ auth()->user()->name }}">@else<span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span>@endif</div><div class="id-person"><p class="eyebrow">{{ strtoupper(auth()->user()->role) }}</p><h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->department }}</p><strong>{{ auth()->user()->id_number }}</strong></div></div><div class="id-card-foot"><span>CARLOS HILADO MEMORIAL STATE UNIVERSITY</span><span>{{ $digitalId->credential_uuid }}</span></div></section>
        <aside class="panel live-credential"><div class="panel-heading"><div><p class="eyebrow">LIVE CREDENTIAL</p><h2>Present to scanner</h2></div><span class="live-dot"></span></div><div id="digital-id-qr" class="qr-code" data-qr-token data-token-url="{{ route('my-id.token') }}"></div><p class="qr-holder">{{ auth()->user()->name }}</p><p class="qr-detail">ID {{ auth()->user()->id_number }} · Refreshes automatically</p><div class="token-timer"><span data-token-timer></span></div></aside>
    </div>
@endif
@endsection