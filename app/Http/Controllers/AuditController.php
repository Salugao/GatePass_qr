<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('actor:id,name')->latest('created_at');

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(fn ($logs) => $logs->where('action', 'like', '%'.$term.'%')
                ->orWhereHas('actor', fn ($users) => $users->where('name', 'like', '%'.$term.'%')));
        }

        return view('audit.index', ['logs' => $query->paginate(50)->withQueryString()]);
    }
}
