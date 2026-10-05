<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture d'achat de litchi</title>
    <style>
        /* Marges de page */
        @page { margin: 25px 30px 40px 30px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1a2e1a;
            font-size: 10.5px;
            line-height: 1.4;
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 18px;
            margin: 0 0 22px 0;      /* ⬆️ 4 → 22 : espace sous le titre */
            color: #1e5631;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            opacity: .6;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 10px;
        }
        .meta div { line-height: 1.5; }
        .meta strong {
            display: block;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
            color: #1e5631;
            margin-bottom: 3px;
        }

        /* ── Bloc vendeur / acheteur ── */
        .vendeur {
            margin-bottom: 22px;     /* ⬆️ 14 → 22 : espace entre chaque bloc */
            page-break-inside: avoid;
        }
        .vendeur:last-of-type {
            margin-bottom: 26px;     /* un peu plus avant le tableau des articles */
        }
        .vendeur h2 {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1e5631;
            border-bottom: 1.5px solid #1e5631;
            padding-bottom: 4px;
            margin: 0 0 10px 0;      /* ⬆️ 6 → 10 : espace titre/bloc */
        }
        .vendeur table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .vendeur td {
            padding: 4px 0;          /* ⬆️ 3 → 4 : lignes moins tassées */
            vertical-align: top;
        }
        .vendeur td:first-child { width: 160px; font-weight: bold; }

        /* ── Tableau des articles ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;     /* ⬆️ 14 → 18 */
        }
        table.items thead th {
            background: #1e5631;
            color: #fff;
            padding: 7px 8px;        /* ⬆️ 6 → 7 */
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
        }
        table.items tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e2e2;
        }
        table.items tbody tr:nth-child(even) td { background: #f8fbf8; }
        .right { text-align: right; }
        .center { text-align: center; }

        table.total {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 22px;     /* ⬆️ nouveau : espace avant le bloc paiement */
        }
        table.total td { padding: 8px 10px; font-size: 11px; }
        table.total tr.grand-total td {
            background: #fdf1e1;
            color: #d97706;
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #d97706;
        }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            opacity: .5;
        }

        /* ── Bloc paiement & observations ── */
        .bloc-paiement {
            margin-top: 22px;        /* ⬆️ 12 → 22 */
            margin-bottom: 22px;     /* ⬆️ nouveau : espace avant signatures */
            page-break-inside: avoid;
        }
        .bloc-paiement h2 {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1e5631;
            border-bottom: 1.5px solid #1e5631;
            padding-bottom: 4px;
            margin: 0 0 10px 0;
        }
        .bloc-paiement table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .bloc-paiement td {
            padding: 4px 0;
            vertical-align: top;
        }
        .bloc-paiement td:first-child { width: 160px; font-weight: bold; }
        .mode-paiement {
            color: #d97706;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .observations-box {
            border: 1px solid #e2e2e2;
            border-radius: 4px;
            padding: 6px 8px;
            min-height: 30px;
            background: #fafafa;
            font-size: 10px;
            line-height: 1.4;
            white-space: pre-wrap;
        }

        /* ── Signatures ── */
        .signatures {
            margin-top: 30px;        /* ⬆️ 20 → 30 */
            page-break-inside: avoid;
        }
        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
            text-align: center;
        }
        .signatures .sig-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e5631;
            border-bottom: 1px solid #1e5631;
            padding-bottom: 4px;
        }
        .signatures .sig-space { height: 55px; }
    </style>
</head>
<body style="margin-bottom: 40px;">

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
        <h2>Détails de la facture</h2>
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
                <th style="width: 60px;"  class="center">Quantité</th>
                <th style="width: 75px;"  class="center">Unité (kg)</th>
                <th style="width: 110px;" class="center">Prix unitaire caissette</th>
                <th style="width: 110px;" class="center">Prix unitaire produit</th>
                <th style="width: 110px;" class="right">Montant</th>
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

    <table class="total">
        <tr class="grand-total">
            <td class="right" style="width: 70%;">TOTAL GÉNÉRAL</td>
            <td class="right">{{ number_format($montantLigne, 0, ',', ' ') }} Ar</td>
        </tr>
    </table>

    <!-- ══════════════════════════════════════════════════════════
         5. Mode de paiement & Observations
    ══════════════════════════════════════════════════════════ -->
    <div class="bloc-paiement">
        <h2>Mode de paiement & Observations</h2>
        <table>
            <tr>
                <td>Mode de paiement :</td>
                <td><span class="mode-paiement">{{ $modePaiement }}</span></td>
            </tr>
            <tr>
                <td>Observations :</td>
                <td>
                    @if(!empty($observations))
                        <div class="observations-box">{{ $observations }}</div>
                    @else
                        <span style="opacity:.4;">—</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         6. Signatures
    ══════════════════════════════════════════════════════════ -->
    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sig-label">Signature du vendeur</div>
                    <div class="sig-space"></div>
                </td>
                <td>
                    <div class="sig-label">Signature de l'acheteur</div>
                    <div class="sig-space"></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Facture générée le {{ now()->format('d/m/Y à H:i') }}
    </div>

</body>
</html>