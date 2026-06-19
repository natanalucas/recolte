<?php

namespace App\Services\Pdf;

use App\Models\Paletisation;

class PaletisationPdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    private function buildLignesForFiche(string $ficheNumber)
    {
        return Paletisation::with(['lots.codeTraca', 'typeCertification', 'enqueteur'])
            ->where('fiche_number', $ficheNumber)
            ->get();
    }

    public function exportByYear(int $year, string $produitFiltre = 'litchi')
    {
        $ficheNumbers = Paletisation::whereYear('created_at', $year)
            ->orderBy('fiche_number')
            ->pluck('fiche_number')
            ->unique()
            ->values();

        $fichesData = $ficheNumbers->map(function ($ficheNumber) {
            $palettes = Paletisation::with(['lots.codeTraca', 'typeCertification', 'enqueteur'])
                ->where('fiche_number', $ficheNumber)
                ->get();

            return [
                'ficheNumber' => $ficheNumber,
                'palettes' => $palettes,
            ];
        })->filter(fn($item) => $item['palettes']->isNotEmpty())->values();

        $data = [
            'year' => $year,
            'fiches' => $fichesData,
            'produitFiltre' => $produitFiltre, // transmis à la vue pour l'affichage
        ];

        $filename = 'fiches-paletisation-' . $year . '-' . $produitFiltre . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.paletisation-annee', $data, ['orientation' => 'landscape']),
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