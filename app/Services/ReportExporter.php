<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\SponsorshipRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    public function __construct(private BoothMapService $maps) {}

    /** @return array<int, array<int, string|int|null>> */
    public function rows(Event $event, string $report): array
    {
        return match ($report) {
            'finance' => $this->financeRows($event),
            'booths' => $this->boothRows($event),
            'sponsorships' => $this->sponsorshipRows($event),
            default => $this->registrantRows($event),
        };
    }

    public function download(Event $event, string $report, string $format)
    {
        $rows = $this->rows($event, $report);
        $title = $this->title($report);
        $filename = $report.'-hsn-2026.'.$format;

        return match ($format) {
            'xlsx' => $this->xlsx($rows, $filename),
            'pdf' => Pdf::loadView('pdf.report', ['title' => $title, 'rows' => $rows, 'event' => $event])
                ->setPaper('a4', 'landscape')
                ->download($filename),
            default => $this->csv($rows, str_replace('.csv', '', $filename).'.csv'),
        };
    }

    private function title(string $report): string
    {
        return match ($report) {
            'finance' => 'Laporan Keuangan',
            'booths' => 'Laporan Penggunaan Lapak',
            'sponsorships' => 'Laporan Sponsorship',
            default => 'Laporan Pendaftar UMKM',
        };
    }

    private function registrantRows(Event $event): array
    {
        $rows = [['Kode', 'Usaha', 'Penanggung jawab', 'NIK', 'WhatsApp', 'Lapak', 'Tenda', 'Total', 'Status', 'Dibuat']];

        Registration::query()
            ->with(['applicant', 'items'])
            ->where('event_id', $event->id)
            ->orderByDesc('id')
            ->each(function (Registration $registration) use (&$rows) {
                $rows[] = [
                    $registration->code,
                    $registration->applicant->business_name,
                    $registration->applicant->full_name,
                    $registration->applicant->nik,
                    $registration->whatsapp,
                    $registration->items->pluck('booth_code')->join(', '),
                    $registration->items->where('with_tent', true)->count(),
                    $registration->grand_total,
                    $registration->status->label(),
                    $registration->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i'),
                ];
            });

        return $rows;
    }

    private function financeRows(Event $event): array
    {
        $rows = [['Kode', 'Usaha', 'Nominal', 'Status bayar', 'Status daftar', 'Diverifikasi']];

        Registration::query()
            ->with(['applicant', 'payment'])
            ->where('event_id', $event->id)
            ->orderByDesc('id')
            ->each(function (Registration $registration) use (&$rows) {
                $rows[] = [
                    $registration->code,
                    $registration->applicant->business_name,
                    $registration->payment?->amount ?? $registration->grand_total,
                    $registration->payment?->status->label() ?? '-',
                    $registration->status->label(),
                    $registration->payment?->verified_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
                ];
            });

        return $rows;
    }

    private function boothRows(Event $event): array
    {
        $rows = [['Kode', 'Zona', 'Tipe', 'Ukuran', 'Status']];

        foreach ($this->maps->present($event) as $booth) {
            $rows[] = [
                $booth['code'],
                $booth['zone'],
                $booth['type'] === 'umkm' ? 'UMKM' : 'Sponsorship',
                $booth['size_label'],
                $booth['status_label'],
            ];
        }

        return $rows;
    }

    private function sponsorshipRows(Event $event): array
    {
        $rows = [['Kode', 'Instansi', 'PIC', 'WhatsApp', 'Kategori', 'Status', 'Harga disepakati', 'Lokasi']];

        SponsorshipRequest::query()
            ->with(['category', 'booth'])
            ->where('event_id', $event->id)
            ->orderByDesc('id')
            ->each(function (SponsorshipRequest $request) use (&$rows) {
                $rows[] = [
                    $request->code,
                    $request->company_name,
                    $request->pic_name,
                    $request->whatsapp,
                    $request->category?->name,
                    $request->status->label(),
                    $request->agreed_price,
                    $request->booth?->code ?? '-',
                ];
            });

        return $rows;
    }

    private function csv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function xlsx(array $rows, string $filename)
    {
        $path = storage_path('app/private/exports');
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file = $path.'/'.uniqid('laporan-', true).'.xlsx';
        $writer = new Writer;
        $writer->openToFile($file);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return response()->download($file, $filename)->deleteFileAfterSend(true);
    }
}
