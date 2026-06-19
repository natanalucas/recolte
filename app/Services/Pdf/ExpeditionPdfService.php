<?php

namespace App\Services\Pdf;

use App\Models\Expedition;

class ExpeditionPdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    public function exportByYear(int $year, string $produitFiltre = 'litchi')
    {
        $expeditions = Expedition::whereYear('created_at', $year)
            ->with([
                'palettes.paletisation.typeCertification',
                'palettes.paletisation.lots.codeTraca',
                'enqueteur',
            ])
            ->orderBy('fiche_number')
            ->get();

        $data = [
            'year' => $year,
            'expeditions' => $expeditions,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiches-expedition-' . $year . '-' . $produitFiltre . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.expedition-annee', $data, ['orientation' => 'portrait']),
            'filename' => $filename,
        ];
    }

    public function downloadByYear(int $year, string $produitFiltre = 'litchi')
    {
        $result = $this->exportByYear($year, $produitFiltre);
        return $result['pdf']->download($result['filename']);
    }

    public function streamByYear(int $year, string $produitFiltre = 'litchi')
    {
        $result = $this->exportByYear($year, $produitFiltre);
        return $result['pdf']->stream($result['filename']);
    }
}