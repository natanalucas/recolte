<?php

namespace App\Services\Pdf;

use App\Models\FicheReception;

class FicheReceptionPdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Charge et filtre les lignes d'une fiche unique sur le produit.
     */
    private function buildLignesForFiche(FicheReception $fiche, string $produitFiltre)
    {
        return $fiche->lignes->filter(function ($ligne) use ($produitFiltre) {
            $produit = $ligne->parcelle?->producteur?->produit;
            return $produit && strtolower($produit) === strtolower($produitFiltre);
        })->values();
    }

    public function export(FicheReception $fiche, string $produitFiltre = 'litchi')
    {
        $fiche->load(['lignes.parcelle.producteur', 'enqueteur']);

        $lignes = $this->buildLignesForFiche($fiche, $produitFiltre);

        $data = [
            'fiche' => $fiche,
            'lignes' => $lignes,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiche-reception-' . $fiche->fiche_number . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.fiche-reception', $data, ['orientation' => 'landscape']),
            'filename' => $filename,
        ];
    }

    public function download(FicheReception $fiche, string $produitFiltre = 'litchi')
    {
        $result = $this->export($fiche, $produitFiltre);

        return $result['pdf']->download($result['filename']);
    }

    public function stream(FicheReception $fiche, string $produitFiltre = 'litchi')
    {
        $result = $this->export($fiche, $produitFiltre);

        return $result['pdf']->stream($result['filename']);
    }

    /**
     * NOUVEAU : récupère toutes les fiches de réception d'une année donnée
     * et génère un PDF unique regroupant toutes les lignes filtrées (litchi par défaut).
     */
    public function exportByYear(int $year, string $produitFiltre = 'litchi')
    {
        $fiches = FicheReception::whereYear('created_at', $year)
            ->with(['lignes.parcelle.producteur', 'enqueteur'])
            ->orderBy('fiche_number')
            ->get();

        $fichesData = $fiches->map(function ($fiche) use ($produitFiltre) {
            return [
                'fiche' => $fiche,
                'lignes' => $this->buildLignesForFiche($fiche, $produitFiltre),
            ];
        })->filter(function ($item) {
            // On ignore les fiches qui n'ont aucune ligne après filtrage
            return $item['lignes']->isNotEmpty();
        })->values();

        $data = [
            'year' => $year,
            'fiches' => $fichesData,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiches-reception-' . $year . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.fiche-reception-annee', $data, ['orientation' => 'landscape']),
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