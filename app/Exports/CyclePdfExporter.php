<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Cycle;
use App\Services\CycleExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class CyclePdfExporter
{
    public function __construct(
        private readonly CycleExportService $exportService
    ) {}

    /**
     * Экспортировать цикл в PDF (подробный)
     * 
     * @param Cycle $cycle Цикл для экспорта
     * 
     * @return Response HTTP ответ с PDF файлом
     */
    public function exportDetailed(Cycle $cycle): Response
    {
        $data = $this->exportService->getDetailedData($cycle);
        
        $pdf = Pdf::loadView('exports.cycle-detailed', [
            'cycle' => $data,
        ]);

        $filename = 'cycle_' . $cycle->id . '_detailed_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Экспортировать цикл в PDF (структурный)
     * 
     * @param Cycle $cycle Цикл для экспорта
     * 
     * @return Response HTTP ответ с PDF файлом
     */
    public function exportStructure(Cycle $cycle): Response
    {
        $data = $this->exportService->getStructureData($cycle);
        
        $pdf = Pdf::loadView('exports.cycle-structure', [
            'cycle' => $data,
        ]);

        $filename = 'cycle_' . $cycle->id . '_structure_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

