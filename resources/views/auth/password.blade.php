@extends('layouts.app')
@section('title', 'Update password')
@section('section', 'Account security')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCOUNT SECURITY</p><h1>Update your password</h1><p class="muted">A new password is required before continuing.</p></div></div>
<section class="panel narrow-panel">
    <form method="POST" action="{{ route('password.update') }}" class="stack-form">
        @csrf @method('PUT')
        <label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        <label for="password">New password</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
        <label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <button class="button button-primary" type="submit">Save password <i data-lucide="arrow-right"></i></button>
    </form>
</section>
@endsection