<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #12261c; font-size: 12px; }
        h1 { color: #087445; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { border: 1px solid #d9e3dc; padding: 6px; text-align: left; }
        .muted { color: #52685c; }
    </style>
</head>
<body>
    <img src="{{ public_path('images/logo-nu.png') }}" alt="Logo Nahdlatul Ulama" style="height: 52px;">
    <h1>{{ $type === 'konfirmasi' ? 'Bukti Konfirmasi Lapak' : 'Bukti Pengajuan Lapak' }}</h1>
    <p>{{ $registration->event->name }}</p>
    <p class="muted">{{ $registration->event->activity_name }} · {{ $registration->event->venue }} · {{ $registration->event->starts_at->timezone('Asia/Jakarta')->translatedFormat('l, j F Y') }}</p>
    <p><strong>{{ $registration->code }}</strong> · {{ $registration->status->label() }}</p>
    @if ($type !== 'konfirmasi')
        <p>Dokumen ini belum menyatakan lapak terkonfirmasi.</p>
    @endif
    <p>{{ $registration->applicant->business_name }} — {{ $registration->applicant->full_name }}</p>
    <table>
        <tr><th>Lapak</th><th>Zona</th><th>Ukuran</th><th>Tenda</th><th>Nominal</th></tr>
        @foreach ($registration->items as $item)
            <tr>
                <td>{{ $item->booth_code }}</td>
                <td>{{ $item->zone_name }}</td>
                <td>{{ $item->sizeLabel() }}</td>
                <td>{{ $item->with_tent ? 'Ya' : 'Tidak' }}</td>
                <td>{{ rupiah($item->line_total) }}</td>
            </tr>
        @endforeach
    </table>
    <p><strong>Total {{ rupiah($registration->grand_total) }}</strong></p>
    @foreach ($codes as $code)
        <p>{{ $code['code'] }}</p>
        <img src="{{ $code['image'] }}" width="120" height="120" alt="QR {{ $code['code'] }}">
    @endforeach
</body>
</html>
