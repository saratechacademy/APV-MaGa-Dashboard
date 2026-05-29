<?php

namespace App\Exports;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SensorReading;
use App\Models\ManualReading;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CategorySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected Site         $site,
        protected SiteCategory $category,
        protected Carbon       $from
    ) {}

    public function title(): string
    {
        return substr($this->category->name, 0, 31);
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

        // Sensor readings groupées par timestamp
        $sensorReadings = SensorReading::where('site_id', $this->site->id)
            ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
            ->where('read_at', '>=', $this->from)
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
            $rows->push($row);
        }

        // Manual readings groupées par reading_date
        $manualReadings = ManualReading::where('site_id', $this->site->id)
            ->whereIn('site_parameter_id', $manualParams->pluck('id'))
            ->where('reading_date', '>=', $this->from)
            ->orderBy('reading_date', 'desc')
            ->get();

        foreach ($manualReadings->groupBy(fn($r) => Carbon::parse($r->reading_date)->format('Y-m-d H:i:s')) as $timestamp => $readings) {
            $row = [$timestamp];
            foreach ($sensorParams as $p) {
                $row[] = '—';
            }
            foreach ($manualParams as $p) {
                $r     = $readings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r ? ($r->value ?? '—') : '—';
            }
            $row[] = 'Manual';
            $rows->push($row);
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
                    'startColor' => ['rgb' => '1d6ed8'],
                ],
            ],
        ];
    }
}