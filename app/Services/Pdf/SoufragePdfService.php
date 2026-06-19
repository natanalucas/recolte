<?php

namespace App\Services\Pdf;

use App\Models\Soufrage;

class SoufragePdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Charge et filtre les lignes d'une année sur le produit du producteur,
     * triées par ordre de création (liste plate, sans regroupement par fiche).
     */
    private function buildLignesForYear(int $year, string $produitFiltre)
    {
        return Soufrage::with(['parcelle.producteur', 'operateur', 'enqueteur'])
            ->whereYear('created_at', $year)
            ->orderBy('created_at')
            ->get()
            ->filter(function ($soufrage) use ($produitFiltre) {
                $produit = $soufrage->parcelle?->producteur?->produit;
                return $produit && strtolower($produit) === strtolower($produitFiltre);
            })
            ->values();
    }

    public function export(int $id, string $produitFiltre = 'litchi')
    {
        $soufrage = Soufrage::with(['parcelle.producteur', 'operateur', 'enqueteur'])
            ->findOrFail($id);

        $produit = $soufrage->parcelle?->producteur?->produit;

        $lignes = ($produit && strtolower($produit) === strtolower($produitFiltre))
            ? collect([$soufrage])
            : collect();

        $data = [
            'lignes' => $lignes,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiche-soufrage-' . $id . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.soufrage', $data, ['orientation' => 'landscape']),
            'filename' => $filename,
        ];
    }

    public function download(int $id, string $produitFiltre = 'litchi')
    {
        $result = $this->export($id, $produitFiltre);

        return $result['pdf']->download($result['filename']);
    }

    public function stream(int $id, string $produitFiltre = 'litchi')
    {
        $result = $this->export($id, $produitFiltre);

        return $result['pdf']->stream($result['filename']);
    }

    /**
     * Récupère toutes les lignes de soufrage d'une année donnée,
     * filtrées sur le produit, triées par ordre de création,
     * et génère un PDF unique en liste plate (sans regroupement par fiche).
     */
    public function exportByYear(int $year, string $produitFiltre = 'litchi')
    {
        $lignes = $this->buildLignesForYear($year, $produitFiltre);

        $data = [
            'year' => $year,
            'lignes' => $lignes,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiches-soufrage-' . $year . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.soufrage-annee', $data, ['orientation' => 'landscape']),
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