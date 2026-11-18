@extends('layouts.app')
@section('title', 'Scan access')
@section('section', 'Scan access')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ strtoupper(auth()->user()->role) }} TERMINAL</p><h1>Verify access</h1></div></div>
<div class="scanner-layout">
    <section class="scanner-panel">
        <div class="scanner-panel-head"><span class="scanner-live"><span class="live-dot"></span>TERMINAL READY</span><span class="scanner-location-label"><i data-lucide="map-pin"></i>{{ ucfirst(auth()->user()->role) }} terminal</span></div>
        <div id="qr-reader" class="camera-frame"><div class="camera-placeholder"><span><i data-lucide="scan-qr-code"></i></span><strong>Camera scanner</strong><small>Start the camera to scan a QR credential.</small><button class="button button-primary" type="button" data-camera-start><i data-lucide="camera"></i>Start camera</button></div></div>
        <div class="scanner-manual"><span>OR ENTER TOKEN MANUALLY</span><form id="scan-form" class="manual-form"><input type="hidden" name="zone" value="{{ auth()->user()->role === 'laboratory' ? 'laboratory' : (auth()->user()->role === 'office' ? 'office' : 'campus') }}"><label class="sr-only" for="location">Gate or room</label><input id="location" name="location" value="{{ auth()->user()->role === 'laboratory' ? 'Laboratory' : (auth()->user()->role === 'office' ? 'Office' : 'Main Gate') }}" placeholder="Gate or room" required maxlength="120"><label class="sr-only" for="token">QR token</label><input id="token" name="token" placeholder="Paste encrypted QR payload" required><button class="button button-primary" type="submit" aria-label="Verify token"><i data-lucide="arrow-right"></i></button></form></div>
    </section>
    <aside class="scan-result-panel"><div class="result-empty" id="scan-result"><span class="result-icon"><i data-lucide="scan-line"></i></span><p class="eyebrow">AWAITING CREDENTIAL</p><h2>Ready to verify</h2><p class="muted">A result will appear here after each scan.</p></div><div class="result-log"><div><span class="live-dot"></span>SESSION</div><strong>{{ now()->format('g:i A') }}</strong></div></aside>
</div>
@endsection