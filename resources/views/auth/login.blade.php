@extends('layouts.app')

@section('title', 'Sign in')
@section('public-content')
<main class="auth-page">
    <section class="auth-visual">
        <div class="auth-visual-top"><span class="brand-mark"><i data-lucide="scan-qr-code"></i></span><span>CARLOS HILADO MEMORIAL STATE UNIVERSITY<br><small>College of Computer Studies</small></span></div>
        <div class="auth-visual-copy"><p class="eyebrow">CAMPUS ACCESS, IN SYNC</p><h1>One scan.<br><em>Clear passage.</em></h1><p>Digital identity and campus entry, brought into one secure view.</p></div>
        <div class="auth-visual-foot"><span>GatePassQR</span><span>Alijis · Negros Occidental</span></div>
        <div class="auth-pattern" aria-hidden="true"></div>
    </section>
    <section class="auth-panel">
        <div class="auth-form-wrap">
            <a class="mobile-brand" href="{{ route('login') }}"><span class="brand-mark"><i data-lucide="scan-qr-code"></i></span><strong>GatePassQR</strong></a>
            <p class="eyebrow">OPERATOR ACCESS</p>
            <h2>Welcome back</h2>
            <p class="muted">Sign in with your campus account.</p>
            @if($errors->any())<div class="notice notice-error"><i data-lucide="triangle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
            <form method="POST" action="{{ route('login.store') }}" class="stack-form">
                @csrf
                <label for="login">Email or ID number</label>
                <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
                <div class="label-row"><label for="password">Password</label></div>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <label class="check-row"><input type="checkbox" name="remember" value="1"><span>Keep me signed in</span></label>
                <button class="button button-primary button-wide" type="submit">Sign in <i data-lucide="arrow-right"></i></button>
            </form>
            <div class="auth-separator"><span>GUEST ACCESS</span></div>
            <a class="button button-secondary button-wide" href="{{ route('visitor.create') }}"><i data-lucide="user-round-plus"></i>Register a campus visit</a>
            <p class="auth-footnote">Access is limited to authorized campus accounts.</p>
        </div>
    </section>
</main>
@endsection