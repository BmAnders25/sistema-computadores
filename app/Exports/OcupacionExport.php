<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OcupacionExport implements WithMultipleSheets
{
    public function __construct(
        private array $kpis,
        private array $detalle,
        private Carbon $desde,
        private Carbon $hasta,
        private ?int $aulaId,
        private ?string $estado,
        private ?string $aulaNombre = null,
        private ?string $generadoPor = null
    ) {}

    public function sheets(): array
    {
        $metadatos = [
            'Periodo' => $this->desde->toDateString() . ' a ' . $this->hasta->toDateString(),
            'Aula' => $this->aulaId ? ($this->aulaNombre ?? (string) $this->aulaId) : 'Todas las aulas',
            'Estado' => $this->estado ?: 'Todos',
            'Generado el' => Carbon::now()->format('Y-m-d H:i'),
            'Generado por' => $this->generadoPor ?? 'Usuario no disponible',
        ];

        return [
            new OcupacionResumenSheet($this->kpis, $metadatos),
            new OcupacionDetalleSheet($this->detalle, $metadatos),
        ];
    }
}

class OcupacionResumenSheet implements
    FromArray,
    WithTitle,
    WithEvents,
    WithColumnWidths,
    ShouldAutoSize,
    WithStrictNullComparison
{
    private const TITULO = 'S.R.C. — Reporte de Ocupación (Universidad de Pamplona)';

    public function __construct(
        private array $kpis,
        private array $metadatos
    ) {}

    public function array(): array
    {
        $filas = [[self::TITULO, '']];

        foreach ($this->metadatos as $etiqueta => $valor) {
            $filas[] = [$etiqueta, $valor];
        }

        $filas[] = [];
        $filas[] = ['Indicador', 'Valor'];
        $filas[] = ['Ocupación (%)', (float) $this->kpis['ocupacion'] / 100];
        $filas[] = ['Slots reservados', (int) $this->kpis['slots_reservados']];
        $filas[] = ['Slots disponibles', (int) $this->kpis['slots_disponibles']];
        $filas[] = ['Reservas del mes', (int) $this->kpis['reservas_mes']['total']];
        $filas[] = ['Variación mensual (%)', (float) $this->kpis['reservas_mes']['variacion_porcentaje'] / 100];
        $filas[] = ['Equipos disponibles', (int) $this->kpis['equipos_hoy']['disponibles']];
        $filas[] = ['Equipos en uso', (int) $this->kpis['equipos_hoy']['en_uso']];
        $filas[] = ['Equipos en mantenimiento', (int) $this->kpis['equipos_hoy']['mantenimiento']];
        $filas[] = ['Equipos inactivos', (int) $this->kpis['equipos_hoy']['inactivos']];
        $filas[] = ['Tasa de cancelación (%)', (float) $this->kpis['cancelaciones']['tasa_cancelacion'] / 100];
        $filas[] = ['Tasa de no_show (%)', (float) $this->kpis['cancelaciones']['tasa_no_show'] / 100];

        return $filas;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function columnWidths(): array
    {
        return ['A' => 36, 'B' => 40];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:B1');
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(8)->setRowHeight(22);
                $sheet->getStyle('A1:B1')->applyFromArray($this->titleStyle());
                $sheet->getStyle('A2:B6')->applyFromArray($this->metadataStyle());
                $sheet->getStyle('A8:B8')->applyFromArray($this->tableHeadingStyle());
                $sheet->getStyle('A1:B19')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A9:B19')->applyFromArray($this->dataStyle());
                $this->applyZebraRows($sheet, 9, 19, 'B');

                foreach (['B9', 'B13', 'B18', 'B19'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0.00%');
                }

                $sheet->getStyle('B10:B12')->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('B14:B17')->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }

    private function titleStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7B1E24']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function metadataStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function tableHeadingStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A6262E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ];
    }

    private function dataStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function applyZebraRows(Worksheet $sheet, int $firstRow, int $lastRow, string $lastColumn): void
    {
        for ($row = $firstRow; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->getFill()
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }
        }
    }
}

class OcupacionDetalleSheet implements
    FromArray,
    WithTitle,
    WithEvents,
    WithColumnWidths,
    ShouldAutoSize,
    WithStrictNullComparison
{
    private const TITULO = 'S.R.C. — Reporte de Ocupación (Universidad de Pamplona)';
    private const ULTIMA_COLUMNA = 'F';

    public function __construct(
        private array $detalle,
        private array $metadatos
    ) {}

    public function array(): array
    {
        $filas = [[self::TITULO, '', '', '', '', '']];

        foreach ($this->metadatos as $etiqueta => $valor) {
            $filas[] = [$etiqueta, $valor, '', '', '', ''];
        }

        $filas[] = [];
        $filas[] = [
            'Fecha',
            'Bloque horario',
            'Aula',
            'Slots reservados',
            'Slots disponibles',
            'Ocupación (%)',
        ];

        foreach ($this->detalle as $fila) {
            $ocupacion = (int) $fila['slots_disponibles'] > 0
                ? (float) round(((int) $fila['slots_reservados'] / (int) $fila['slots_disponibles']) * 100, 2) / 100
                : 0.0;

            $filas[] = [
                $fila['fecha'],
                $fila['bloque'],
                $fila['aula'],
                (int) $fila['slots_reservados'],
                (int) $fila['slots_disponibles'],
                $ocupacion,
            ];
        }

        return $filas;
    }

    public function title(): string
    {
        return 'Detalle';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 30,
            'C' => 30,
            'D' => 20,
            'E' => 20,
            'F' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $ultimaFila = max(8, $sheet->getHighestRow());
                $sheet->mergeCells('A1:' . self::ULTIMA_COLUMNA . '1');
                for ($row = 2; $row <= 6; $row++) {
                    $sheet->mergeCells("B{$row}:F{$row}");
                }

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(8)->setRowHeight(22);
                $sheet->getStyle('A1:F1')->applyFromArray($this->titleStyle());
                $sheet->getStyle('A2:F6')->applyFromArray($this->metadataStyle());
                $sheet->getStyle('A8:F8')->applyFromArray($this->tableHeadingStyle());
                $sheet->getStyle("A1:F{$ultimaFila}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                if ($ultimaFila >= 9) {
                    $sheet->getStyle("A9:F{$ultimaFila}")->applyFromArray($this->dataStyle());
                    $this->applyZebraRows($sheet, 9, $ultimaFila);
                    $sheet->getStyle("D9:E{$ultimaFila}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle("F9:F{$ultimaFila}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00%');
                }
            },
        ];
    }

    private function titleStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7B1E24']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function metadataStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function tableHeadingStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A6262E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ];
    }

    private function dataStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function applyZebraRows(Worksheet $sheet, int $firstRow, int $lastRow): void
    {
        for ($row = $firstRow; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID);
                $sheet->getStyle("A{$row}:F{$row}")
                    ->getFill()
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }
        }
    }
}
