<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $r)
    {
        $event  = $r->get('event');
        $userId = $r->get('user_id');
        $dateFrom = $r->get('date_from');
        $dateTo   = $r->get('date_to');

        $query = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('products as p', function ($join) {
                $join->on('p.id', '=', DB::raw("CASE WHEN a.ref_type = 'PRODUCT' THEN a.ref_id ELSE NULL END"));
            })
            ->select(
                'a.id',
                'a.event',
                'a.user_id',
                'u.full_name as user_name',
                'a.ref_type',
                'a.ref_id',
                'a.payload',
                'a.created_at',
                'p.name as product_name',
                'p.sku as product_sku'
            );

        if ($event) {
            $query->where('a.event', $event);
        }
        if ($userId) {
            $query->where('a.user_id', $userId);
        }
        if ($dateFrom) {
            $query->where('a.created_at', '>=', "{$dateFrom} 00:00:00");
        }
        if ($dateTo) {
            $query->where('a.created_at', '<=', "{$dateTo} 23:59:59");
        }

        $logs = $query->orderByDesc('a.id')->paginate(20)->withQueryString();

        // Decode payload for each log
        foreach ($logs as $log) {
            $log->payload_data = json_decode($log->payload, true) ?? [];
        }

        // Event list for filter
        $events = DB::table('audit_logs')->distinct()->pluck('event')->sort()->values();

        // User list for filter
        $users = DB::table('users')->select('id', 'full_name')->orderBy('full_name')->get();

        return view('reports.audit-log', compact('logs', 'events', 'users', 'event', 'userId', 'dateFrom', 'dateTo'));
    }
}
