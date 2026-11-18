<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessLogController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'zone' => ['nullable', 'in:campus,laboratory,office'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'result' => ['nullable', 'in:allowed,denied'],
        ]);

        $query = AccessEvent::query()->with(['user:id,name,id_number', 'visitorRegistration:id,name', 'actor:id,name'])->latest('scanned_at');
        $roleZone = match ($request->user()->role) {
            'security' => 'campus',
            'laboratory' => 'laboratory',
            'office' => 'office',
            default => null,
        };

        if ($roleZone) {
            $query->where('zone', $roleZone);
        } elseif (! empty($data['zone'])) {
            $query->where('zone', $data['zone']);
        }

        if (! empty($data['from'])) {
            $query->whereDate('scanned_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $query->whereDate('scanned_at', '<=', $data['to']);
        }
        if (! empty($data['result'])) {
            $query->where('result', $data['result']);
        }
        if (! empty($data['q'])) {
            $term = $data['q'];
            $query->where(function ($events) use ($term): void {
                $events->where('location', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$term.'%')->orWhere('id_number', 'like', '%'.$term.'%'))
                    ->orWhereHas('visitorRegistration', fn ($visitors) => $visitors->where('name', 'like', '%'.$term.'%'));
            });
        }

        return view('logs.index', ['events' => $query->limit(1000)->paginate(50)->withQueryString()]);
    }
}
