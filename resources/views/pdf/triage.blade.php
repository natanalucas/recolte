<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de triage {{ $ficheNumber }}</title>
    <style>
        @page { size: A4 landscape; margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Fiche de triage N° {{ $ficheNumber }}</h1>

    <table>
        <thead>
            <tr>
                <th>Code Traça</th>
                <th>Type carton</th>
                <th>Certification</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Tapis</th>
                <th>Nombre</th>
                <th>Qualité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne->codeTraca->code ?? '-' }}</td>
                    <td>{{ $ligne->type_carton }}</td>
                    <td>{{ $ligne->certification->nom ?? '-' }}</td>
                    <td>{{ $ligne->debut }}</td>
                    <td>{{ $ligne->fin }}</td>
                    <td>{{ $ligne->tapis }}</td>
                    <td class="text-right">{{ $ligne->nombre }}</td>
                    <td>{{ $ligne->qualite }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>