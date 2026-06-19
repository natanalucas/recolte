<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de soufrage {{ $ficheNumber }}</title>
    <style>
        @page { size: A4 landscape; margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .meta { text-align: center; margin-bottom: 16px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Fiche de soufrage N° {{ $ficheNumber }}</h1>
    <div class="meta">Produit : {{ ucfirst($produitFiltre) }}</div>

    <table>
        <thead>
            <tr>
                <th>Parcelle</th>
                <th>Producteur</th>
                <th>Cycle</th>
                <th>Box</th>
                <th>Concentration</th>
                <th>Caissette</th>
                <th>Soufre</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Opérateur</th>
                <th>Contrôle RAQT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne->parcelle->num ?? '-' }}</td>
                    <td>
                        {{ $ligne->parcelle->producteur->nom ?? '' }}
                        {{ $ligne->parcelle->producteur->prenom ?? '' }}
                    </td>
                    <td>{{ $ligne->cycle }}</td>
                    <td>{{ $ligne->box }}</td>
                    <td>{{ $ligne->concent }}</td>
                    <td class="text-right">{{ $ligne->caissette }}</td>
                    <td>{{ $ligne->soufre }}</td>
                    <td>{{ $ligne->debut }}</td>
                    <td>{{ $ligne->fin }}</td>
                    <td>{{ $ligne->operateur->name ?? '-' }}</td>
                    <td>{{ $ligne->controle_raqt }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>