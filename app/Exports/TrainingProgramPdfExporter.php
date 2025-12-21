<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\TrainingProgram;
use App\Services\TrainingProgramExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class TrainingProgramPdfExporter
{
    public function __construct(
        private readonly TrainingProgramExportService $exportService
    ) {}

    /**
     * Экспортировать программу в PDF (подробный)
     * 
     * @param TrainingProgram $program Программа для экспорта
     * 
     * @return Response HTTP ответ с PDF файлом
     */
    public function exportDetailed(TrainingProgram $program): Response
    {
        $data = $this->exportService->getDetailedData($program);
        
        $pdf = Pdf::loadView('exports.training-program-detailed', [
            'program' => $data,
        ]);

        $filename = 'training_program_' . $program->id . '_detailed_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Экспортировать программу в PDF (структурный)
     * 
     * @param TrainingProgram $program Программа для экспорта
     * 
     * @return Response HTTP ответ с PDF файлом
     */
    public function exportStructure(TrainingProgram $program): Response
    {
        $data = $this->exportService->getStructureData($program);
        
        $pdf = Pdf::loadView('exports.training-program-structure', [
            'program' => $data,
        ]);

        $filename = 'training_program_' . $program->id . '_structure_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

