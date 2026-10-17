<?php

namespace App\Http\Controllers\Admin;

use App\Services\ReportExporter;
use Illuminate\Http\Request;

class ReportController extends AdminController
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function download(Request $request, ReportExporter $exporter)
    {
        $data = $request->validate([
            'report' => ['required', 'in:registrants,finance,booths,sponsorships'],
            'format' => ['required', 'in:csv,xlsx,pdf'],
        ]);

        return $exporter->download($this->event(), $data['report'], $data['format']);
    }
}
