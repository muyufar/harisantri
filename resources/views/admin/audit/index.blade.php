@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Audit</h1>
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-sm">
            <thead class="text-left text-slate-500"><tr><th class="p-3">Waktu</th><th>Aksi</th><th>Petugas</th><th>Data</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr class="border-t border-slate-100">
                        <td class="p-3">{{ $log->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->user->name ?? 'Sistem' }}</td>
                        <td class="max-w-xs truncate">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
