<?php

namespace App\Services\Pdf;

use App\Models\FicheReception;

class FicheReceptionPdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Prépare les données et génère le PDF pour une fiche de réception donnée.
     */
    public function export(FicheReception $fiche, string $produitFiltre = 'litchi')
    {
        $fiche->load([
            'lignes.parcelle.producteur',
            'enqueteur',
        ]);

        $lignes = $fiche->lignes->filter(function ($ligne) use ($produitFiltre) {
            $produit = $ligne->parcelle?->producteur?->produit;
            return $produit && strtolower($produit) === strtolower($produitFiltre);
        })->values();

        $data = [
            'fiche' => $fiche,
            'lignes' => $lignes,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiche-reception-' . $fiche->fiche_number . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.fiche-reception', $data),
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
}