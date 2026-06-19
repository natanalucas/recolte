<?php

namespace App\Http\Controllers;

use App\Models\FicheReception;
use App\Services\Pdf\FicheReceptionPdfService;
use App\Services\Pdf\SoufragePdfService;
use App\Services\Pdf\TriagePdfService;
use App\Services\Pdf\PaletisationPdfService;
use App\Services\Pdf\ExpeditionPdfService;
use Illuminate\Http\Request;

class ExportPdfController extends Controller
{
    public function __construct(
        private FicheReceptionPdfService $receptionPdf,
        private SoufragePdfService $soufragePdf,
        private TriagePdfService $triagePdf,
        private PaletisationPdfService $paletisationPdf,
        private ExpeditionPdfService $expeditionPdf,
    ) {}

    public function reception(FicheReception $ficheReception)
    {
        return $this->receptionPdf->download($ficheReception);
    }

    public function receptionByYear(Request $request, int $year)
    {
        $produit = $request->query('produit', 'litchi');

        return $this->receptionPdf->downloadByYear($year, $produit);
    }

    public function soufrage(string $ficheNumber)
    {
        return $this->soufragePdf->download($ficheNumber);
    }

    public function soufrageByYear(Request $request, int $year)
    {
        $produit = $request->query('produit', 'litchi');

        return $this->soufragePdf->downloadByYear($year, $produit);
    }

    public function triage(string $ficheNumber)
    {
        return $this->triagePdf->download($ficheNumber);
    }

    public function triageByYear(int $year, \Illuminate\Http\Request $request)
    {
        $produitFiltre = $request->query('produit', 'litchi');

        return $this->triagePdf->downloadByYear($year, $produitFiltre);
    } 

    public function paletisationByYear(Request $request, int $year)
    {
        $produit = $request->query('produit', 'litchi');

        return $this->paletisationPdf->downloadByYear($year, $produit);
    }
    
    public function expeditionByYear(Request $request, int $year)
    {
        $produit = $request->query('produit', 'litchi');
        return $this->expeditionPdf->downloadByYear($year, $produit);
    }
}