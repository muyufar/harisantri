<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;

class AuditController extends AdminController
{
    public function index()
    {
        return view('admin.audit.index', [
            'logs' => AuditLog::query()->with('user')->latest()->paginate(30),
        ]);
    }
}
