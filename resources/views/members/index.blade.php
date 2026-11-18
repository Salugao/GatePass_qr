@extends('layouts.app')
@section('title', 'People & IDs')
@section('section', 'People & IDs')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCOUNT & DIGITAL ID</p><h1>People & IDs</h1><p class="muted">Campus members and operator accounts.</p></div></div>
<div class="members-layout">
    <section class="panel member-create-panel"><div class="panel-heading"><div><p class="eyebrow">NEW ACCOUNT</p><h2>Create account</h2></div><i data-lucide="user-round-plus" class="panel-icon"></i></div>
        <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data" class="stack-form">@csrf
            <label for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="160">
            <label for="email">Campus email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255">
            <label for="temporary_password">Temporary password</label><input id="temporary_password" name="temporary_password" type="password" minlength="12" autocomplete="new-password" required>
            <div class="form-two"><label>Role<select name="role" data-account-role required>@foreach(['student', 'faculty', 'staff', 'security', 'laboratory', 'office', 'administrator'] as $role)<option value="{{ $role }}" @selected(old('role', 'student') === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label><label>Department<input name="department" value="{{ old('department') }}" maxlength="160"></label></div>
            <div class="member-only-fields" data-member-fields>
                <label for="id_number">Institutional ID number</label><input id="id_number" name="id_number" value="{{ old('id_number') }}" maxlength="80">
                <label for="photo">Profile photo</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                <fieldset class="zone-fieldset"><legend>Facility authorization</legend><label class="check-row"><input type="checkbox" name="zones[]" value="laboratory"><span>Laboratory access</span></label><label class="check-row"><input type="checkbox" name="zones[]" value="office"><span>Office access</span></label></fieldset>
            </div>
            <button class="button button-primary button-wide" type="submit">Create account <i data-lucide="arrow-right"></i></button>
        </form>
    </section>
    <section class="member-list-panel">
        <div class="panel-heading list-heading"><div><p class="eyebrow">CAMPUS DIRECTORY</p><h2>Accounts <span class="count-badge">{{ $members->total() }}</span></h2></div><form class="search-form" method="GET"><i data-lucide="search"></i><input aria-label="Search accounts" name="q" value="{{ request('q') }}" placeholder="Name, email or ID"><button class="icon-button" type="submit" title="Search"><i data-lucide="arrow-right"></i></button></form></div>
        <div class="member-list">
            @forelse($members as $member)
                @php($activeId = $member->digitalIds->first())
                <article class="member-row">
                    <div class="member-summary"><span class="avatar">{{ mb_substr($member->name, 0, 1) }}</span><div class="member-name"><strong>{{ $member->name }}</strong><small>{{ $member->id_number }} · {{ ucfirst($member->role) }}</small></div><span class="status-pill {{ $member->is_active ? 'status-approved' : 'status-denied' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span><span class="id-state {{ $activeId?->status === 'active' ? 'id-active' : '' }}">{{ $activeId?->status === 'active' ? 'ID active' : 'No active ID' }}</span></div>
                    <div class="member-actions">
                        @if(in_array($member->role, ['student', 'faculty', 'staff'], true))
                            @if($activeId?->status === 'active')<form method="POST" action="{{ route('digital-ids.revoke', $activeId) }}">@csrf @method('DELETE')<button class="button button-small button-secondary" type="submit"><i data-lucide="ban"></i>Revoke ID</button></form>@else<form method="POST" action="{{ route('users.digital-id.issue', $member) }}">@csrf<button class="button button-small button-primary" type="submit"><i data-lucide="badge-check"></i>Issue ID</button></form>@endif
                        @endif
                        <details class="edit-member"><summary class="button button-small button-quiet"><i data-lucide="pencil"></i>Edit</summary><form method="POST" action="{{ route('members.update', $member) }}" enctype="multipart/form-data" class="edit-member-form">@csrf @method('PUT')
                            <label class="field">Full name<input name="name" value="{{ $member->name }}" required maxlength="160"></label><label class="field">Email<input name="email" type="email" value="{{ $member->email }}" required></label><div class="form-two"><label>Role<select name="role">@foreach(['student', 'faculty', 'staff', 'security', 'laboratory', 'office', 'administrator'] as $role)<option value="{{ $role }}" @selected($member->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label><label>Department<input name="department" value="{{ $member->department }}"></label></div><label class="field">ID number<input name="id_number" value="{{ $member->id_number }}"></label><label class="field">Replace photo<input name="photo" type="file" accept="image/jpeg,image/png,image/webp"></label>
                            <fieldset class="zone-fieldset"><legend>Facility authorization</legend><label class="check-row"><input type="checkbox" name="zones[]" value="laboratory" @checked($member->zoneAuthorizations->contains('zone', 'laboratory'))><span>Laboratory</span></label><label class="check-row"><input type="checkbox" name="zones[]" value="office" @checked($member->zoneAuthorizations->contains('zone', 'office'))><span>Office</span></label></fieldset>
                            <input type="hidden" name="is_active" value="0"><label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($member->is_active)><span>Account active</span></label><button class="button button-primary" type="submit">Save changes</button>
                        </form></details>
                    </div>
                </article>
            @empty<div class="empty-state"><i data-lucide="users-round"></i><strong>No member records found</strong><span>Try a different search or create the first member.</span></div>@endforelse
        </div>
        <div class="pagination-wrap">{{ $members->links() }}</div>
    </section>
</div>
@endsection