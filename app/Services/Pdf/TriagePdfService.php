<?php

namespace App\Services\Pdf;

use App\Models\Triage;
use App\Models\Parcelle;

class TriagePdfService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    public function export(string $ficheNumber)
    {
        $lignes = Triage::with(['certification', 'codeTraca', 'enqueteur'])
            ->where('fiche_number', $ficheNumber)
            ->get();

        $data = [
            'ficheNumber' => $ficheNumber,
            'lignes' => $lignes,
        ];

        $filename = 'fiche-triage-' . $ficheNumber . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.triage', $data, ['orientation' => 'landscape']),
            'filename' => $filename,
        ];
    }

    public function download(string $ficheNumber)
    {
        $result = $this->export($ficheNumber);

        return $result['pdf']->download($result['filename']);
    }

    public function stream(string $ficheNumber)
    {
        $result = $this->export($ficheNumber);

        return $result['pdf']->stream($result['filename']);
    }

    /**
     * Charge les lignes de triage d'une année donnée, filtrées sur le produit
     * du producteur. Le produit est déduit via les 2 premiers caractères du
     * code de traçabilité (= numéro de parcelle), triées par ordre de création.
     */
    private function buildLignesForYear(int $year, string $produitFiltre)
    {
        $lignes = Triage::with(['certification', 'codeTraca', 'enqueteur'])
            ->whereYear('created_at', $year)
            ->orderBy('created_at')
            ->get();

        // Les 2 premiers caractères du code de traçabilité = numéro de parcelle
        $numerosParcelles = $lignes
            ->pluck('codeTraca.code')
            ->filter()
            ->map(fn ($code) => substr($code, 0, 2))
            ->unique()
            ->values();

        $parcelles = Parcelle::with('producteur')
            ->whereIn('num', $numerosParcelles)
            ->get()
            ->keyBy('num');

            return $lignes->filter(function ($ligne) use ($parcelles, $produitFiltre) {
                $code = $ligne->codeTraca->code ?? null;
                if (!$code) {
                    \Log::info('Rejeté: pas de code', ['ligne' => $ligne->id]);
                    return false;
                }

                $parcelle = $parcelles->get(substr($code, 0, 2));
                if (!$parcelle) {
                    \Log::info('Rejeté: parcelle introuvable', ['num' => substr($code, 0, 2)]);
                    return false;
                }

                $produit = $parcelle->producteur?->produit;
                if (!$produit || strtolower($produit) !== strtolower($produitFiltre)) {
                    \Log::info('Rejeté: produit ne correspond pas', ['produit' => $produit, 'filtre' => $produitFiltre]);
                    return false;
                }

                $ligne->parcelle = $parcelle;
                return true;
            })->values();
    }

    public function exportByYear(int $year, string $produitFiltre = 'litchi')
    {
        $lignes = $this->buildLignesForYear($year, $produitFiltre);

        $data = [
            'year' => $year,
            'lignes' => $lignes,
            'produitFiltre' => $produitFiltre,
        ];

        $filename = 'fiches-triage-' . $year . '.pdf';

        return [
            'pdf' => $this->pdfGenerator->generate('pdf.triage-annee', $data, ['orientation' => 'landscape']),
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