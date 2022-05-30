<?php

file_put_contents('anagrafica_impianti_attivi.csv', file_get_contents('https://www.mise.gov.it/images/exportCSV/anagrafica_impianti_attivi.csv'));
file_put_contents('prezzo_alle_8.csv', file_get_contents('https://www.mise.gov.it/images/exportCSV/prezzo_alle_8.csv'));

// Header file geojson finale
$geojson = [
    "type" => "FeatureCollection",
];

// Lettura 2 CSV
$csvDistributori = fopen('anagrafica_impianti_attivi.csv', 'r');
$csvPrezzi = fopen('prezzo_alle_8.csv', 'r');


// Cache prezzi per distributore
$prezzi = [];

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
 
// Costruzione dati geojson
while(($line = fgetcsv($csvDistributori, 0,  ';')) !== FALSE){
    $idimpianto = $line[0];
    $name = $line[2];
    $lat = $line[8] !== 'NULL' ? $line[8] : null;
    $lon = $line[9] !== 'NULL' ? $line[9] : null;
    $ultimo_aggiornamento = '';
    $description = [];

    if (!empty($lat) && !empty($lon)) {
        $description[] = '**GESTORE:**
'.$line[1].'
';

        if (isset($prezzi[$idimpianto])) {
            foreach ($prezzi[$idimpianto] as $idx => $prezzo) {
                $description[] = '**'.$prezzo['tipo'].($prezzo['isSelf'] ? ' (self)' : '').':**
                '.number_format($prezzo['prezzo'], 3, ',', '.').'
                ';

                // Tengo lo stesso per il diverso tipo di carburante
                $ultimo_aggiornamento = $prezzo['ultimo_aggiornamento'];
            }
        }

        $description[] = 'Ultimo aggiornamento: '.$ultimo_aggiornamento;

        $geojson['features'][] = [
            "type" => "Feature",
            "properties" => [
                "idImpianto" => $idimpianto,
                "description" => implode("\n", $description),
                "name" => $name
            ],
            "geometry" => [
                "type" => "Point",
                "coordinates" => [
                    $lon,
                    $lat
                ]
            ]
        ];
    }
}

// Chiusura CSV originale
fclose($csvDistributori);


// Salvataggio file geojson
file_put_contents( 'data.geojson', json_encode($geojson, JSON_PRETTY_PRINT) );
