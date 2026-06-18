<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfGeneratorService
{
    /**
     * Génère un PDF à partir d'une vue Blade et de données.
     *
     * @param string $view Nom de la vue Blade (ex: 'pdf.fiche-reception')
     * @param array $data Données à injecter dans la vue
     * @param array $options Options dompdf (paper, orientation...)
     */
    public function generate(string $view, array $data = [], array $options = []): \Barryvdh\DomPDF\PDF
    {
        $paper = $options['paper'] ?? 'a4';
        $orientation = $options['orientation'] ?? 'portrait';

        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation);
    }

    /**
     * Génère et retourne le PDF en téléchargement direct.
     */
    public function download(string $view, array $data = [], string $filename = 'document.pdf', array $options = []): Response
    {
        return $this->generate($view, $data, $options)->download($filename);
    }

    /**
     * Génère et affiche le PDF dans le navigateur (stream).
     */
    public function stream(string $view, array $data = [], string $filename = 'document.pdf', array $options = []): Response
    {
        return $this->generate($view, $data, $options)->stream($filename);
    }

    /**
     * Génère et enregistre le PDF sur le disque (storage).
     */
    public function save(string $view, array $data, string $path, array $options = []): string
    {
        $pdf = $this->generate($view, $data, $options);
        $pdf->save($path);

        return $path;
    }
}