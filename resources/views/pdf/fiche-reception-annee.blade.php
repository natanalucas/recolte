<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiches de réception {{ $year }}</title>
    <style>
        @page { size: A4 landscape; margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 24px; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Récapitulatif des fiches de réception — {{ $year }}</h1>
    <div style="text-align:center; color:#555;">Produit : {{ ucfirst($produitFiltre) }}</div>

    @foreach($fiches as $item)
        @php $fiche = $item['fiche']; $lignes = $item['lignes']; @endphp

        <h2>Fiche N° {{ $fiche->fiche_number }}</h2>

        <table>
            <thead>
                <tr>
                    <th>Parcelle</th>
                    <th>Producteur</th>
                    <th>Qtté Caissette</th>
                    <th>Quantité (kg)</th>
                    <th>N° Voiture</th>
                    <th>Commune et district</th>
                    <th>Collecte</th>
                    <th>Départ champ</th>
                    <th>Retour station</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    @php $quantiteLivree = $ligne->caissette * $fiche->poids_par_caissette; @endphp
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
    @endforeach
</body>
</html>