<?php

include 'vendor/autoload.php';

$client = new \GuzzleHttp\Client();

$response = $client->request('GET', 'http://www.mise.gov.it/images/exportCSV/anagrafica_impianti_attivi.csv');
file_put_contents('anagrafica_impianti_attivi.csv', $response->getBody());

$response = $client->request('GET', 'http://www.mise.gov.it/images/exportCSV/prezzo_alle_8.csv');
file_put_contents('prezzo_alle_8.csv', $response->getBody());

// Lettura template file umap
$umap = json_decode(file_get_contents('template.umap'), 1);

// Lettura 2 CSV
$csvDistributori = fopen('anagrafica_impianti_attivi.csv', 'r');
$csvPrezzi = fopen('prezzo_alle_8.csv', 'r');


// Cache prezzi per distributore
$prezzi = [];
$prezzi_per_tipo = [];


/**
 * Raggruppamento varie tipologie di benzina per evitare di creare troppi
 * livelli e di facilitare anche la ricerca all'utente
 */
$raggruppamenti_iniziali = [
    'BENZINA' => 'BENZINA',
    'GPL' => 'GPL',
    'BLUE DIESEL' => 'DIESEL',
    'HI-Q DIESEL' => 'DIESEL',
    'HIQ PERFORM+' => 'DIESEL',
    'V-POWER' => 'BENZINA',
    'GASOLIO' => 'GASOLIO',
    'GASOLIO PREMIUM' => 'GASOLIO',
    'SUPREME DIESEL' => 'DIESEL',
    'METANO' => 'METANO',
    'BENZINA SPECIALE' => 'BENZINA',
    'BLUE SUPER' => 'BENZINA',
    'L-GNC' => 'L-GNC',
    'GNL' => 'GAS',
    'GASOLIO ARTICO' => 'GASOLIO',
    'GASOLIO ARTICO IGLOO' => 'GASOLIO',
    'BENZINA WR 100' => 'BENZINA',
    'GASOLIO SPECIALE' => 'GASOLIO',
    'EXCELLIUM DIESEL' => 'DIESEL',
    'BENZINA PLUS 98' => 'BENZINA',
    'DIESEL SHELL V POWER' => 'DIESEL',
    'E-DIESEL' => 'DIESEL',
    'R100' => 'BENZINA',
    'GASOLIO ORO DIESEL' => 'GASOLIO',
    'DIESELMAX' => 'DIESEL',
    'GASOLIO ALPINO' => 'GASOLIO',
    'BLU DIESEL ALPINO' => 'DIESEL',
    'GASOLIO GELO' => 'GASOLIO',
    'GASOLIO ECOPLUS' => 'GASOLIO',
    'S-DIESEL' => 'DIESEL',
    'V-POWER DIESEL' => 'DIESEL',
    'DIESEL E+10' => 'DIESEL',
    'GP DIESEL' => 'DIESEL',
    'F101' => 'BENZINA',
    'BENZINA 100 OTTANI' => 'BENZINA',
    'GASOLIO ENERGY D' => 'GASOLIO',
    'BENZINA ENERGY 98 OTTANI' => 'BENZINA',
    'BENZINA SHELL V POWER' => 'BENZINA',
    'SSP98' => 'BENZINA',
    'GASOLIO PLUS' => 'GASOLIO',
    'HVOLUTION' => 'BIO-DIESEL',
    'BIO-DIESEL' => 'BIO-DIESEL',
    'F-101' => 'BENZINA',
    'GASOLIO PRESTAZIONALE' => 'GASOLIO',
    'VERDE SPECIALE' => 'BENZINA',
    'BENZINA 102 OTTANI' => 'BENZINA',
    'HVO100' => 'HVO',
    'HVO' => 'HVO',
    'HVO ECOPLUS' => 'HVO',
    'DIESEL HVO ENERGY' => 'HVO',
    'REHVO' => 'HVO',
    'HVOVOLUTION' => 'HVO',
    'GASOLIO HVO' => 'HVO',
    'BCHVO' => 'HVO',
    'BENZINA SPECIALE 98 OTTANI' => 'BENZINA',
    'HVO ECO DIESEL' => 'HVO',
    'HVO FUTURE' => 'HVO',
    'GASOLIO BIO HVO' => 'HVO',
];

$raggruppamenti = [];

// Aggiunta (self) e (servito) ai tipi
foreach ($raggruppamenti_iniziali as $tipo => $raggruppamento) {
    $raggruppamenti[$tipo.' (self)'] = $raggruppamento;
    $raggruppamenti[$tipo.' (servito)'] = $raggruppamento;
}

$colori_per_tipo = [
    'BENZINA' => '#119429',
    'GPL' => '#d9c800',
    'DIESEL' => '#193cbf',
    'BIO-DIESEL' => '#1f63df',
    'GASOLIO' => '#0636a5',
    'METANO' => '#067057',
    'GAS' => '#193e92',
    'L-GNC' => '#193e92',
    'HVO' => '#ddaa33',
];

// Salto le 2 inutili prime righe
fgetcsv($csvPrezzi);
fgetcsv($csvPrezzi);

while (($line = fgetcsv($csvPrezzi, 0, ';')) !== false) {
    $idimpianto = $line[0];
    $tipo = $line[1];
    $prezzo = (float)$line[2];
    $isSelf = (int)$line[3];
    $ultimo_aggiornamento = $line[4];

    $prezzi[ $idimpianto ][] = [
        'tipo' => strtoupper($tipo),
        'prezzo' => $prezzo,
        'isSelf' => $isSelf,
        'ultimo_aggiornamento' => $ultimo_aggiornamento,
    ];
}

fclose($csvPrezzi);


// Salto le 2 inutili prime righe
fgetcsv($csvDistributori);
fgetcsv($csvDistributori);

// Costruzione dati umap
while (($line = fgetcsv($csvDistributori, 0, ';')) !== false) {
    $idimpianto = $line[0];
    $name = $line[2];
    $lat = ($line[8] !== 'NULL' && ! empty($line[8])) ? $line[8] : null;
    $lon = ($line[9] !== 'NULL' && ! empty($line[9])) ? $line[9] : null;
    $description = [];

    if (!empty($lat) && !empty($lon)) {
        $description[] = '**GESTORE:**
'.$line[1].'
';

        if (isset($prezzi[$idimpianto])) {
            foreach ($prezzi[$idimpianto] as $idx => $prezzo) {
                $tipo = $prezzo['tipo'].($prezzo['isSelf'] ? ' (self)' : ' (servito)');
                $tipo_raggruppato = $raggruppamenti[ $tipo ];
                $giorni_ritardo_aggiornamento = (Carbon\Carbon::rawCreateFromFormat('d/m/Y H:i:s', $prezzo['ultimo_aggiornamento']))->diffInDays(new Carbon\Carbon(), false);

                // Emoji per indicare la data di ultimo aggiornamento
                $icon = '🔴';
                if ($giorni_ritardo_aggiornamento <= 3) {
                    $icon = '🟢';
                } elseif ($giorni_ritardo_aggiornamento > 3 && $giorni_ritardo_aggiornamento <= 7) {
                    $icon = '🟠';
                }

                $prezzi_per_tipo[$tipo_raggruppato][$idimpianto][$tipo] = [
                    'idimpianto' => $idimpianto,
                    'ultimo_aggiornamento' => $prezzo['ultimo_aggiornamento'],
                    'nome' => $name,
                    'button' => '[[geo:'.$lat.','.$lon.'|🏁 Guidami qui »]]',
                    'icon' => $icon,
                    'prezzo' => (float)$prezzo['prezzo'],
                    'lat' => $lat,
                    'lon' => $lon
                ];
            }
        }
    }
}

// Chiusura CSV originale
fclose($csvDistributori);

$layers = [];

// Generazione file per layer
foreach ($prezzi_per_tipo as $tipo_raggruppato => $idimpianti) {
    $markers = [];

    foreach ($idimpianti as $idimpianto => $tipi) {
        $prezzi = [];
        $descriptions = [];

        foreach ($tipi as $tipo => $impianto) {
            $prezzi[] = $impianto['prezzo'];
            $descriptions[] = '**'.$tipo.':** '.$impianto['prezzo'].' €';
        }

        $markers[] = [
            "type" => "Feature",
            "geometry" => [
                "coordinates" => [
                    $impianto['lon'],
                    $impianto['lat']
                ],
                "type" => "Point",
            ],
            "properties" => [
                'name' => $impianto['icon'].' '.min($prezzi).' - '.max($prezzi).' €',
                'description' => "# ".$impianto['nome']."\n*🗓️ ".$impianto['ultimo_aggiornamento']."*\n".implode("\n", $descriptions)."\n\n".$impianto['button'],
            ],
            'id' => $idimpianto
        ];
    }


    $layers[] = [
        'type' => 'FeatureCollection',
        'features' => $markers,
        '_umap_options' => [
            'name' => $tipo_raggruppato,
            'type' => 'Cluster',
            'color' => $colori_per_tipo[$tipo_raggruppato],
            'cluster' => [
                'radius' => 80
            ],
            'editMode' => 'advanced',
            'browsable' => true,
            'inCaption' => false,
            'remoteData' => [],
            'displayOnLoad' => false,
        ]
    ];
}

$umap['layers'] = $layers;

$json_response = json_encode($umap, JSON_PRETTY_PRINT);

// Salvataggio file umap
if (isset($_GET['response'])) {
    echo $json_response;
} else {
    file_put_contents('data.umap', $json_response);
}
