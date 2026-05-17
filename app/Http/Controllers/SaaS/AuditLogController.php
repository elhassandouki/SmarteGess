<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::query()->latest('id')->paginate(100);

        return view('support.audit-logs', compact('logs'));
    }
}
