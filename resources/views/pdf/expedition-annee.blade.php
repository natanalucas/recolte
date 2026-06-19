<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiches d'expédition {{ $year }}</title>
    <style>
        @page { size: A4 portrait; margin: 20px 25px; }
        * { box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; }

        .page { page-break-after: always; width: 100%; }
        .page:last-child { page-break-after: avoid; }

        /* En-tête */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #2d6a4f; padding-bottom: 6px; }
        .header-title { font-size: 14px; font-weight: 900; text-transform: uppercase; color: #2d6a4f; letter-spacing: 1px; }
        .header-meta { font-size: 9px; color: #555; text-align: right; }

        /* Blocs généraux */
        .section { margin-bottom: 8px; }
        .section-title { background: #2d6a4f; color: white; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 8px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; font-size: 9px; vertical-align: middle; }
        th { background: #f0f0f0; font-weight: 900; text-transform: uppercase; font-size: 8px; }

        /* Grille 2 colonnes */
        .grid-2 { display: table; width: 100%; border-collapse: separate; border-spacing: 6px 0; }
        .col { display: table-cell; width: 50%; vertical-align: top; }

        /* Champ texte simulé */
        .field-row { display: flex; border: 1px solid #bbb; margin-bottom: -1px; }
        .field-label { background: #f0f0f0; font-weight: 900; text-transform: uppercase; font-size: 8px; padding: 4px 6px; white-space: nowrap; border-right: 1px solid #bbb; min-width: 120px; display: flex; align-items: center; }
        .field-value { padding: 4px 6px; font-size: 9px; flex: 1; }

        /* Proprete */
        .proprete-ok { color: #2d6a4f; font-weight: 900; text-transform: uppercase; }
        .proprete-nok { color: #c0392b; font-weight: 900; text-transform: uppercase; }

        /* Palettes */
        .palette-table th { background: #2d6a4f; color: white; }

        /* Observations */
        .obs-box { border: 1px solid #bbb; min-height: 40px; padding: 5px 6px; font-size: 9px; }

        /* Signature */
        .signature-row { display: table; width: 100%; margin-top: 12px; }
        .signature-cell { display: table-cell; width: 50%; padding: 0 10px; text-align: center; }
        .signature-line { border-top: 1px solid #555; margin-top: 30px; padding-top: 4px; font-size: 8px; color: #555; text-transform: uppercase; }

        .badge { display: inline-block; font-size: 8px; font-weight: 900; padding: 1px 5px; border-radius: 3px; text-transform: uppercase; }
        .badge-green { background: #d8f3dc; color: #2d6a4f; }
        .badge-orange { background: #fff3cd; color: #e67e22; }
    </style>
</head>
<body>

@foreach($expeditions as $expedition)
<div class="page">

    {{-- EN-TÊTE --}}
    <div class="header">
        <div>
            <div class="header-title">Fiche d'Expédition</div>
            <div style="font-size:9px; color:#555; margin-top:2px;">
                Produit : <strong>{{ ucfirst($produitFiltre) }}</strong>
                &nbsp;—&nbsp; Année : <strong>{{ $year }}</strong>
            </div>
        </div>
        <div class="header-meta">
            N° Fiche : <strong>{{ $expedition->fiche_number }}</strong><br>
            @if($expedition->enqueteur)
                Enquêteur : <strong>{{ $expedition->enqueteur->prenom ?? '' }} {{ $expedition->enqueteur->nom ?? '' }}</strong>
            @endif
        </div>
    </div>

    {{-- CONTENEUR & CAMION --}}
    <div class="section">
        <div class="section-title">Informations Transport</div>
        <table>
            <thead>
                <tr>
                    <th>Numéro Conteneur</th>
                    <th>N° Immatriculation Camion</th>
                    <th>Propreté Conteneur</th>
                    <th>Propreté Camion</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $expedition->conteneur ?? '—' }}</strong></td>
                    <td><strong>{{ $expedition->immatriculation ?? '—' }}</strong></td>
                    <td>
                        @if($expedition->proprete_conteneur === 'propre')
                            <span class="proprete-ok">✔ Propre</span>
                        @else
                            <span class="proprete-nok">✘ Sale</span>
                        @endif
                    </td>
                    <td>
                        @if($expedition->proprete_camion === 'propre')
                            <span class="proprete-ok">✔ Propre</span>
                        @else
                            <span class="proprete-nok">✘ Sale</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- DATES --}}
    <div class="section">
        <div class="section-title">Dates & Horaires</div>
        <table>
            <thead>
                <tr>
                    <th>Début Empotage</th>
                    <th>Fin Empotage</th>
                    <th>Départ Station</th>
                    <th>Arrivée Port</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $expedition->debut_empotage ? \Carbon\Carbon::parse($expedition->debut_empotage)->format('d/m/Y H:i') : '—' }}</td>
                    <td>{{ $expedition->fin_empotage   ? \Carbon\Carbon::parse($expedition->fin_empotage)->format('d/m/Y H:i')   : '—' }}</td>
                    <td>{{ $expedition->depart_station ? \Carbon\Carbon::parse($expedition->depart_station)->format('d/m/Y H:i') : '—' }}</td>
                    <td>{{ $expedition->arrivee_port   ? \Carbon\Carbon::parse($expedition->arrivee_port)->format('d/m/Y H:i')   : '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- BATEAU & BL --}}
    <div class="section">
        <div class="section-title">Expédition Maritime</div>
        <table>
            <thead>
                <tr>
                    <th>Nom du Bateau</th>
                    <th>Bon de Livraison (B.L)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $expedition->bateau ?? '—' }}</strong></td>
                    <td><strong>{{ $expedition->bon_livraison ?? '—' }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PALETTES --}}
    <div class="section">
        <div class="section-title">Détail des Palettes ({{ $expedition->palettes->count() }} palette(s))</div>
        @if($expedition->palettes->isEmpty())
            <div style="padding:6px; color:#999; border:1px solid #bbb;">Aucune palette associée.</div>
        @else
            <table class="palette-table">
                <thead>
                    <tr>
                        <th>N° Palette</th>
                        <th>Type Carton</th>
                        <th>Certification</th>
                        <th>Lot</th>
                        <th>Code Traça</th>
                        <th style="text-align:right;">Nb Cartons</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expedition->palettes as $ep)
                        @php
                            $pal = $ep->paletisation;
                            $lots = $pal?->lots ?? collect();
                            $lotCount = $lots->count();
                        @endphp

                        @if(!$pal)
                            <tr>
                                <td colspan="6" style="color:#999;">Palette introuvable</td>
                            </tr>
                        @elseif($lotCount === 0)
                            <tr>
                                <td>{{ $pal->num_palette }}</td>
                                <td>{{ $pal->type_carton }}</td>
                                <td>{{ $pal->typeCertification->nom ?? '—' }}</td>
                                <td colspan="3" style="color:#999;">Aucun lot</td>
                            </tr>
                        @else
                            @foreach($lots as $i => $lot)
                                <tr>
                                    @if($i === 0)
                                        <td rowspan="{{ $lotCount }}">{{ $pal->num_palette }}</td>
                                        <td rowspan="{{ $lotCount }}">{{ $pal->type_carton }}</td>
                                        <td rowspan="{{ $lotCount }}">{{ $pal->typeCertification->nom ?? '—' }}</td>
                                    @endif
                                    <td>Lot {{ $lot->lot_number }}</td>
                                    <td>{{ $lot->codeTraca->code ?? '—' }}</td>
                                    <td style="text-align:right;">{{ $lot->nb_cartons }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- OBSERVATIONS --}}
    <div class="section">
        <div class="section-title">Observations</div>
        <div class="obs-box">{{ $expedition->observations ?? '' }}</div>
    </div>

    {{-- SIGNATURES --}}
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">Signature Enquêteur</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">Signature Responsable</div>
        </div>
    </div>

</div>
@endforeach

</body>
</html>