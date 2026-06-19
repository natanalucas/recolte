<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiches de palettisation {{ $year }}</title>
    <style>
        @page { size: A4 landscape; margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 24px; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .lot-row td { background-color: #fafafa; font-size: 9px; }
        .lot-label { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <h1>Récapitulatif des fiches de palettisation — {{ $year }}</h1>
    <div style="text-align:center; color:#555; margin-bottom:16px;">
        Produit : {{ ucfirst($produitFiltre) }}
    </div>

    @foreach($fiches as $item)
        @php $ficheNumber = $item['ficheNumber']; $palettes = $item['palettes']; @endphp

        <!-- <h2>Fiche N° {{ $ficheNumber }}</h2> -->

        <table>
            <thead>
                <tr>
                    <th>N° Palette</th>
                    <th>Type carton</th>
                    <th>Certification</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Lot</th>
                    <th>Code Traça</th>
                    <th class="text-right">Nb cartons</th>
                </tr>
            </thead>
            <tbody>
                @foreach($palettes as $palette)
                    @php $lots = $palette->lots; $lotCount = $lots->count(); @endphp

                    @if($lotCount === 0)
                        <tr>
                            <td>{{ $palette->num_palette }}</td>
                            <td>{{ $palette->type_carton }}</td>
                            <td>{{ $palette->typeCertification->nom ?? '-' }}</td>
                            <td>{{ $palette->debut?->format('d/m/Y H:i') }}</td>
                            <td>{{ $palette->fin?->format('d/m/Y H:i') }}</td>
                            <td colspan="3" style="color:#999;">Aucun lot</td>
                        </tr>
                    @else
                        @foreach($lots as $index => $lot)
                            <tr class="{{ $index > 0 ? 'lot-row' : '' }}">
                                @if($index === 0)
                                    <td rowspan="{{ $lotCount }}">{{ $palette->num_palette }}</td>
                                    <td rowspan="{{ $lotCount }}">{{ $palette->type_carton }}</td>
                                    <td rowspan="{{ $lotCount }}">{{ $palette->typeCertification->nom ?? '-' }}</td>
                                    <td rowspan="{{ $lotCount }}">{{ $palette->debut?->format('d/m/Y H:i') }}</td>
                                    <td rowspan="{{ $lotCount }}">{{ $palette->fin?->format('d/m/Y H:i') }}</td>
                                @endif
                                <td><span class="lot-label">Lot {{ $lot->lot_number }}</span></td>
                                <td>{{ $lot->codeTraca->code ?? '-' }}</td>
                                <td class="text-right">{{ $lot->nb_cartons }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>