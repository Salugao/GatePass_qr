<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GatePass;
use App\Models\VisitorRegistration;
use App\Services\CredentialTokenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VisitorController extends Controller
{
    public function create(): View
    {
        return view('visitors.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_number' => ['required', 'string', 'max:40'],
            'id_type' => ['required', 'in:government,school,passport,other'],
            'id_number' => ['required', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'max:160'],
            'visit_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
        ]);

        $registration = VisitorRegistration::create($data + ['tracking_code' => (string) Str::uuid()]);

        return redirect()->route('visitor.status', ['reference' => $registration->tracking_code]);
    }

    public function status(Request $request): View
    {
        $data = $request->validate(['reference' => ['required', 'uuid']]);
        $registration = VisitorRegistration::query()
            ->with('gatePass')
            ->where('tracking_code', $data['reference'])
            ->firstOrFail();

        return view('visitors.status', compact('registration'));
    }

    public function passToken(Request $request, CredentialTokenService $tokens): JsonResponse
    {
        $data = $request->validate(['reference' => ['required', 'uuid']]);
        $pass = GatePass::query()
            ->whereHas('registration', fn (Builder $query) => $query->where('tracking_code', $data['reference'])->where('status', 'approved'))
            ->where('status', 'active')
            ->firstOrFail();

        abort_if($pass->valid_from->isFuture() || $pass->expires_at->isPast(), 410, 'This gate pass is not currently valid.');

        return response()->json(['token' => $tokens->forGatePass($pass->id), 'expires_in' => 30]);
    }

    public function index(Request $request): View
    {
        $query = VisitorRegistration::query()->with('reviewer:id,name')->latest();

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected', 'expired'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->user()->role === 'security') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return view('visitors.index', ['registrations' => $query->paginate(20)->withQueryString()]);
    }

    public function decide(Request $request, VisitorRegistration $visitorRegistration): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'remarks' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
            'allowed_gate' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($request, $visitorRegistration, $data): void {
            $registration = VisitorRegistration::query()->lockForUpdate()->findOrFail($visitorRegistration->id);
            abort_unless($registration->status === 'pending' && $registration->created_at->gte(now()->subHours(2)), 409, 'This request is no longer pending.');

            $registration->update([
                'status' => $data['decision'],
                'remarks' => $data['remarks'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($data['decision'] === 'approved') {
                GatePass::create([
                    'visitor_registration_id' => $registration->id,
                    'pass_uuid' => (string) Str::uuid(),
                    'status' => 'active',
                    'allowed_gate' => $data['allowed_gate'] ?? null,
                    'valid_from' => $registration->visit_date->copy()->startOfDay(),
                    'expires_at' => $registration->visit_date->copy()->endOfDay(),
                ]);
            }

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'visitor.'.$data['decision'],
                'subject_type' => VisitorRegistration::class,
                'subject_id' => $registration->id,
                'details' => ['remarks' => $data['remarks'] ?? null],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Visitor request '.$data['decision'].'.');
    }
}
