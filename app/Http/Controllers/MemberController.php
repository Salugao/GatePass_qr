<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['digitalIds' => fn ($ids) => $ids->latest()->limit(1), 'zoneAuthorizations'])
            ->whereIn('role', ['administrator', 'security', 'laboratory', 'office', 'student', 'faculty', 'staff'])
            ->latest();

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(fn ($users) => $users->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')
                ->orWhere('id_number', 'like', '%'.$term.'%'));
        }

        return view('members.index', ['members' => $query->paginate(20)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'temporary_password' => ['required', 'string', 'min:12', 'max:128'],
            'id_number' => ['nullable', 'required_if:role,student,faculty,staff', 'string', 'max:80', 'unique:users,id_number'],
            'role' => ['required', 'in:administrator,security,laboratory,office,student,faculty,staff'],
            'department' => ['nullable', 'required_if:role,student,faculty,staff', 'string', 'max:160'],
            'photo' => ['nullable', 'required_if:role,student,faculty,staff', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'zones' => ['nullable', 'array'],
            'zones.*' => ['in:laboratory,office'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'id_number' => $data['id_number'] ?? null,
                'role' => $data['role'],
                'department' => $data['department'] ?? null,
                'photo_path' => $request->hasFile('photo') ? $request->file('photo')->storePublicly('member-photos', 'public') : null,
                'password' => $data['temporary_password'],
                'must_change_password' => true,
                'is_active' => true,
            ]);

            foreach (in_array($data['role'], ['student', 'faculty', 'staff'], true) ? ($data['zones'] ?? []) : [] as $zone) {
                $user->zoneAuthorizations()->create(['zone' => $zone, 'granted_by' => $request->user()->id]);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'member.created',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Member account created. Set a temporary password with the user before their first sign-in.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'id_number' => ['nullable', 'required_if:role,student,faculty,staff', 'string', 'max:80', 'unique:users,id_number,'.$user->id],
            'role' => ['required', 'in:administrator,security,laboratory,office,student,faculty,staff'],
            'department' => ['nullable', 'required_if:role,student,faculty,staff', 'string', 'max:160'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'zones' => ['nullable', 'array'],
            'zones.*' => ['in:laboratory,office'],
        ]);

        DB::transaction(function () use ($request, $user, $data): void {
            $deactivatesLastAdmin = $user->role === 'administrator'
                && ($data['role'] !== 'administrator' || ! $request->boolean('is_active'))
                && User::query()->where('role', 'administrator')->where('is_active', true)->count() <= 1;

            abort_if($deactivatesLastAdmin, 422, 'The last active administrator cannot be deactivated or reassigned.');
            abort_if($request->user()->is($user) && ($data['role'] !== 'administrator' || ! $request->boolean('is_active')), 422, 'You cannot deactivate or reassign your own administrator account.');

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'id_number' => $data['id_number'] ?? null,
                'role' => $data['role'],
                'department' => $data['department'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'photo_path' => $request->hasFile('photo') ? $request->file('photo')->storePublicly('member-photos', 'public') : ($data['role'] === $user->role ? $user->photo_path : null),
            ]);

            $user->zoneAuthorizations()->delete();
            foreach (in_array($user->role, ['student', 'faculty', 'staff'], true) ? ($data['zones'] ?? []) : [] as $zone) {
                $user->zoneAuthorizations()->create(['zone' => $zone, 'granted_by' => $request->user()->id]);
            }

            if (! $user->is_active || ! in_array($user->role, ['student', 'faculty', 'staff'], true)) {
                $user->digitalIds()->where('status', 'active')->update(['status' => 'revoked']);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'member.updated',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'details' => ['is_active' => $user->is_active, 'role' => $user->role],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Member record updated.');
    }
}
