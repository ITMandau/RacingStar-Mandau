<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StarRegionSerpoExport implements FromCollection, WithHeadings, WithTitle, WithEvents, WithCustomStartCell
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $q = DB::table('serpos as s')
            ->join('regions as r', 'r.id_region', '=', 's.id_region')
            ->select([
                'r.nama_region as region',
                's.nama_serpo as serpo',
                DB::raw('SUM(s.total_star) as total_star')
            ])
            ->groupBy('r.nama_region', 's.nama_serpo')
            ->orderByDesc(DB::raw('SUM(s.total_star)'));

        if (!empty($this->filters['region'])) {
            $q->where('r.id_region', (int)$this->filters['region']);
        }
        if (!empty($this->filters['serpo'])) {
            $q->where('s.id_serpo', (int)$this->filters['serpo']);
        }

        return $q->get();
    }

    public function headings(): array
    {
        return ['Region', 'Serpo', 'Total Star'];
    }

    public function title(): string
    {
        return 'Star Region & Serpo';
    }

    /**
     * Letakkan header mulai di A3 agar A1 dipakai untuk note
     */
    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Tulis note di A1 dan merge sampai C1 (kolom header kita 3 kolom)
                $noteText = 'Note: Hasil Total Star ini adalah hasil dari pengurangan star dari admin.';
                $sheet->setCellValue('A1', $noteText);
                $sheet->mergeCells('A1:C1');

                // Style untuk note
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'bold' => true,
                        'color' => ['rgb' => 'C00000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                // Biar note keliatan rapih
                $sheet->getRowDimension(1)->setRowHeight(20);

                // Header berada di baris 3 (startCell = A3)
                $headerRow = 3;
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

                // style header (A3:LastCol3)
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
                      ->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('93C47D');

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER);

                // wrap & border for whole table (from header row to last row)
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
                      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // autosize columns
                // range('A', $lastCol) expects single-letter end; if $lastCol longer (e.g. 'AA') handle differently
                // safest: iterate with coordinate conversion
                $col = 'A';
                while (true) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    if ($col === $lastCol) break;
                    $col = ++$col; // increment column letter
                }

                // freeze header (baris setelah header)
                $freezeCell = 'A' . ($headerRow + 1);
                $sheet->freezePane($freezeCell);

                // auto filter on header row
                $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$headerRow}");
            }
        ];
    }
}
