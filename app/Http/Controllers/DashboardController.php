<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use App\Models\DigitalId;
use App\Models\User;
use App\Models\VisitorRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        if (in_array($user->role, ['student', 'faculty', 'staff'], true)) {
            return redirect()->route('my-id');
        }

        $eventsQuery = AccessEvent::query()->with(['user:id,name,id_number', 'visitorRegistration:id,name'])->latest('scanned_at');

        if ($user->role === 'laboratory') {
            $eventsQuery->where('zone', 'laboratory');
        } elseif ($user->role === 'office') {
            $eventsQuery->where('zone', 'office');
        } elseif ($user->role === 'security') {
            $eventsQuery->where('zone', 'campus');
        }

        $entriesToday = AccessEvent::query()->whereDate('scanned_at', today())->where('result', 'allowed')->where('zone', 'campus')->where('direction', 'entry')->count();
        $exitsToday = AccessEvent::query()->whereDate('scanned_at', today())->where('result', 'allowed')->where('zone', 'campus')->where('direction', 'exit')->count();
        $presentCount = max(0, $entriesToday - $exitsToday);

        return view('dashboard', [
            'presentCount' => $presentCount,
            'visitorCount' => VisitorRegistration::query()->whereDate('visit_date', today())->count(),
            'pendingCount' => VisitorRegistration::query()->where('status', 'pending')->where('created_at', '>=', now()->subHours(2))->count(),
            'memberCount' => User::query()->whereIn('role', ['student', 'faculty', 'staff'])->where('is_active', true)->count(),
            'activeIdCount' => DigitalId::query()->where('status', 'active')->count(),
            'recentEvents' => $eventsQuery->limit(8)->get(),
        ]);
    }
}
