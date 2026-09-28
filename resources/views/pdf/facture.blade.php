<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture d'achat de litchi</title>
    <style>
        @page { margin: 40px 40px 60px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a2e1a; font-size: 12px; }
        h1 { text-align: center; text-transform: uppercase; letter-spacing: 2px;
             font-size: 22px; margin: 0 0 6px 0; color: #1e5631; }
        .subtitle { text-align: center; font-size: 11px; opacity: .6;
                    letter-spacing: 3px; text-transform: uppercase; margin-bottom: 30px; }

        .meta { display: flex; justify-content: space-between; margin-bottom: 24px; font-size: 11px; }
        .meta div { line-height: 1.6; }
        .meta strong { display: block; text-transform: uppercase; font-size: 10px;
                        letter-spacing: 1px; color: #1e5631; margin-bottom: 4px; }

        /* ── Bloc vendeur / acheteur ── */
        .vendeur { margin-bottom: 24px; page-break-inside: avoid; }
        .vendeur h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 2px;
                       color: #1e5631; border-bottom: 2px solid #1e5631;
                       padding-bottom: 4px; margin-bottom: 10px; }
        .vendeur table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .vendeur td { padding: 5px 0; vertical-align: top; }
        .vendeur td:first-child { width: 180px; font-weight: bold; }

        /* ── Tableau des articles ── */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items thead th { background: #1e5631; color: #fff; padding: 8px 10px;
                                text-transform: uppercase; font-size: 10px; letter-spacing: 1px; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #e2e2e2; }
        table.items tbody tr:nth-child(even) td { background: #f8fbf8; }
        .right { text-align: right; }
        .center { text-align: center; }

        table.total { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.total td { padding: 10px 12px; font-size: 12px; }
        table.total tr.grand-total td { background: #fdf1e1; color: #d97706;
                                         font-size: 15px; font-weight: bold;
                                         border-top: 2px solid #d97706; }

        .footer { position: fixed; bottom: -30px; left: 0; right: 0;
                   text-align: center; font-size: 10px; opacity: .5; }
    </style>
</head>
<body style="margin-bottom: 60px;">

    <h1>Facture d'achat de litchi</h1>

    <!-- ══════════════════════════════════════════════════════════
         1. Informations du vendeur
    ══════════════════════════════════════════════════════════ -->
    <div class="vendeur">
        <h2>Informations du vendeur</h2>
        <table>
            <tr>
                <td>Nom / Société :</td>
                <td>
                    @if($producteur)
                        {{ trim(($producteur->prenom ?? '').' '.($producteur->nom ?? '')) }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <td>Adresse :</td>
                <td>{{ $producteur->adresse ?? '—' }}</td>
            </tr>
            <tr>
                <td>Téléphone :</td>
                <td>{{ $producteur->phone ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         2. Informations de l'acheteur (société)
    ══════════════════════════════════════════════════════════ -->
    <div class="vendeur">
        <h2>Informations de l'acheteur</h2>
        <table>
            <tr>
                <td>Nom / Société :</td>
                <td>{{ $societe->nom ?? '—' }}</td>
            </tr>
            <tr>
                <td>Adresse :</td>
                <td>{{ $societe->adresse ?? '—' }}</td>
            </tr>
            <tr>
                <td>Téléphone :</td>
                <td>{{ $societe->phone ?? '—' }}</td>
            </tr>
            @if(!empty($societe->nif))
            <tr>
                <td>NIF :</td>
                <td>{{ $societe->nif }}</td>
            </tr>
            @endif
            @if(!empty($societe->stat))
            <tr>
                <td>STAT :</td>
                <td>{{ $societe->stat }}</td>
            </tr>
            @endif
        </table>
    </div>

        <!-- ══════════════════════════════════════════════════════════
         3. Détails de la facture
    ══════════════════════════════════════════════════════════ -->
    <div class="vendeur">
        <h2></h2>
        <table>
            <tr>
                <td>Numéro de facture :</td>
                <td>{{ $numeroFacture }}</td>
            </tr>
            <tr>
                <td>Date :</td>
                <td>{{ $dateFacture->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Lieu :</td>
                <td>{{ $lieu }}</td>
            </tr>
        </table>
    </div>

        <!-- ══════════════════════════════════════════════════════════
         4. Détails des produits
    ══════════════════════════════════════════════════════════ -->
    @php
        $montantLigne = $prixUnitaireCaissette + $prixUnitaireProduit;
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th style="text-align:left;">Désignation</th>
                <th style="width: 70px;"  class="center">Quantité</th>
                <th style="width: 90px;"  class="center">Unité (kg)</th>
                <th style="width: 120px;" class="center">Prix unitaire caissette</th>
                <th style="width: 120px;" class="center">Prix unitaire produit</th>
                <th style="width: 120px;" class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Litchi — lot {{ $triage->codeTraca?->code ?? '—' }}</strong>
                </td>
                <td class="center">{{ $nbCaissettes }}</td>
                <td class="center">{{ number_format($kg, 2, ',', ' ') }} kg</td>
                <td class="center">{{ number_format($prixUnitaireCaissette, 0, ',', ' ') }} Ar</td>
                <td class="center">{{ number_format($prixUnitaireProduit, 0, ',', ' ') }} Ar</td>
                <td class="right">{{ number_format($montantLigne, 0, ',', ' ') }} Ar</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Facture générée le {{ now()->format('d/m/Y à H:i') }}
    </div>

</body>
</html>