<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de réception {{ $fiche->fiche_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15px;
        }
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .meta { text-align: center; margin-bottom: 16px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Fiche de réception N° {{ $fiche->fiche_number }}</h1>
    <div class="meta">
        Produit : {{ ucfirst($produitFiltre) }}
        @if($fiche->poids_par_caissette)
            — Poids par caissette : {{ $fiche->poids_par_caissette }} kg
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Parcelle</th>
                <th>Producteur</th>
                <th>Qtté Caissette</th>
                <th>Quantité livrée (kg)</th>
                <th>N° Voiture</th>
                <th>Commune et district</th>
                <th>Collecte</th>
                <th>Départ champ</th>
                <th>Retour station</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                @php
                    $quantiteLivree = $ligne->caissette * $fiche->poids_par_caissette;
                @endphp
                <tr>
                    <td>{{ $ligne->parcelle->num ?? '-' }}</td>
                    <td>
                        {{ $ligne->parcelle->producteur->nom ?? '' }}
                        {{ $ligne->parcelle->producteur->prenom ?? '' }}
                    </td>
                    <td class="text-right">{{ $ligne->caissette }}</td>
                    <td class="text-right">{{ number_format($quantiteLivree, 2) }}</td>
                    <td>{{ $ligne->voiture }}</td>
                    <td>{{ $ligne->commune }}</td>
                    <td>{{ $ligne->collecte }}</td>
                    <td>{{ $ligne->depart_champ }}</td>
                    <td>{{ $ligne->retour_station }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>