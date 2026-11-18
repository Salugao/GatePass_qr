@extends('layouts.app')
@section('title', 'Register visit')
@section('public-content')
<main class="public-page">
    <header class="public-header"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><i data-lucide="scan-qr-code"></i></span><span><strong>GatePass<span>QR</span></strong><small>Campus access</small></span></a><a class="text-link" href="{{ route('login') }}">Staff sign in <i data-lucide="arrow-up-right"></i></a></header>
    <section class="public-content-grid">
        <div class="public-intro"><p class="eyebrow">CARLOS HILADO MEMORIAL STATE UNIVERSITY</p><h1>Campus<br><em>visit request.</em></h1><p>College of Computer Studies · Alijis Campus</p></div>
        <section class="form-panel"><div class="panel-heading"><div><p class="eyebrow">VISIT DETAILS</p><h2>Request campus access</h2></div><span class="required-note">* Required</span></div>
            @if($errors->any())<div class="notice notice-error"><i data-lucide="triangle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
            <form method="POST" action="{{ route('visitor.store') }}" class="form-grid">
                @csrf
                <label class="field field-wide">Full name <span>*</span><input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="160"></label>
                <label class="field">Contact number <span>*</span><input name="contact_number" value="{{ old('contact_number') }}" autocomplete="tel" required maxlength="40"></label>
                <label class="field">Valid ID type <span>*</span><select name="id_type" required><option value="">Select ID type</option>@foreach(['government' => 'Government ID', 'school' => 'School ID', 'passport' => 'Passport', 'other' => 'Other valid ID'] as $value => $label)<option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="field field-wide">ID number <span>*</span><input name="id_number" value="{{ old('id_number') }}" required maxlength="100"></label>
                <label class="field field-wide">Purpose of visit <span>*</span><textarea name="purpose" rows="3" required maxlength="500">{{ old('purpose') }}</textarea></label>
                <label class="field field-mobile-wide">Destination / office <span>*</span><input name="destination" value="{{ old('destination') }}" placeholder="Building or department" required maxlength="160"></label>
                <label class="field">Visit date <span>*</span><input type="date" name="visit_date" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" value="{{ old('visit_date', today()->toDateString()) }}" required></label>
                <div class="field-wide form-submit-row"><p>Requests are held for review and expire if not acted on within two hours.</p><button class="button button-primary" type="submit">Submit for review <i data-lucide="arrow-right"></i></button></div>
            </form>
        </section>
    </section>
    <footer class="public-footer"><span>GatePassQR · CHMSU College of Computer Studies</span><span>Visitor data is used for campus access clearance.</span></footer>
</main>
@endsection