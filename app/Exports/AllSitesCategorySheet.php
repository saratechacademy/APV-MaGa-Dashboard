<?php

namespace App\Exports;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SensorReading;
use App\Models\ManualReading;
use App\Support\SafeExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AllSitesCategorySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Site         $site,
        protected SiteCategory $category,
        protected Carbon       $from,
        protected ?Carbon      $to = null
    ) {
        $this->to ??= now();
    }

    public function title(): string
    {
        // Format: "Fass - Solar" (max 31 chars pour Excel)
        $title = substr($this->site->name, 0, 15) . ' - ' . substr($this->category->name, 0, 13);
        return substr($title, 0, 31);
    }

    public function headings(): array
    {
        $params       = $this->category->activeParameters;
        $sensorParams = $params->where('input_type', 'sensor');
        $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

        $headers = ['Timestamp'];
        foreach ($sensorParams as $p) {
            $headers[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        foreach ($manualParams as $p) {
            $headers[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        $headers[] = 'Type';

        return $headers;
    }

    public function collection(): Collection
    {
        $params       = $this->category->activeParameters;
        $sensorParams = $params->where('input_type', 'sensor');
        $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

        $rows = collect();

        // Sensor readings
        $sensorReadings = SensorReading::where('site_id', $this->site->id)
            ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
            ->whereBetween('read_at', [$this->from, $this->to])
            ->orderBy('read_at', 'desc')
            ->get();

        foreach ($sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s')) as $timestamp => $readings) {
            $row = [$timestamp];
            foreach ($sensorParams as $p) {
                $r     = $readings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r ? ($r->value ?? $r->value_text ?? '—') : '—';
            }
            foreach ($manualParams as $p) {
                $row[] = '—';
            }
            $row[] = 'Sensor';
            $rows->push(SafeExport::row($row));
        }

        // Manual readings
        $manualReadings = ManualReading::where('site_id', $this->site->id)
            ->whereIn('site_parameter_id', $manualParams->pluck('id'))
            ->whereBetween('reading_date', [$this->from, $this->to])
            ->orderBy('reading_date', 'desc')
            ->get();

        foreach ($manualReadings->groupBy(fn($r) => $r->reading_date->format('Y-m-d H:i:s')) as $timestamp => $readings) {
            $row = [$timestamp];
            foreach ($sensorParams as $p) {
                $row[] = '—';
            }
            foreach ($manualParams as $p) {
                $r     = $readings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r ? ($r->value ?? '—') : '—';
            }
            $row[] = 'Manual';
            $rows->push(SafeExport::row($row));
        }

        // Si pas de données, retourner une ligne vide
        if ($rows->isEmpty()) {
            $rows->push(array_fill(0, count($this->headings()), 'No data'));
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '15803d'],
                ],
            ],
        ];
    }
}