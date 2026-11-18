<?php

namespace App\Http\Controllers;

use App\Models\AccessEvent;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'zone' => ['nullable', 'in:campus,laboratory,office'],
            'result' => ['nullable', 'in:allowed,denied'],
        ]);

        $query = AccessEvent::query()->with(['user:id,name,id_number', 'visitorRegistration:id,name'])->latest('scanned_at');
        $roleZone = match ($request->user()->role) {
            'security' => 'campus',
            'laboratory' => 'laboratory',
            'office' => 'office',
            default => null,
        };

        if ($roleZone) {
            $query->where('zone', $roleZone);
        } elseif (! empty($filters['zone'])) {
            $query->where('zone', $filters['zone']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('scanned_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('scanned_at', '<=', $filters['to']);
        }
        if (! empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'report.access_log_exported',
            'details' => $filters,
            'ip_address' => $request->ip(),
        ]);

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Timestamp', 'Credential', 'Name', 'ID number', 'Zone', 'Direction', 'Location', 'Result', 'Anomaly']);

            foreach ($query->limit(1000)->get() as $event) {
                fputcsv($output, [
                    $event->scanned_at->toIso8601String(),
                    $event->credential_type,
                    $event->user?->name ?? $event->visitorRegistration?->name ?? 'Unknown',
                    $event->user?->id_number ?? '',
                    $event->zone,
                    $event->direction,
                    $event->location,
                    $event->result,
                    $event->anomaly,
                ]);
            }

            fclose($output);
        }, 'gatepass-access-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
