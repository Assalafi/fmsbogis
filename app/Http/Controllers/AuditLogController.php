<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('event')) {
            $query->where('event', 'like', '%'.$request->event.'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        $users = \App\Models\User::orderBy('name')->get();

        return view('audit-logs.index', compact('logs', 'users'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('audit-logs.show', compact('auditLog'));
    }
}
