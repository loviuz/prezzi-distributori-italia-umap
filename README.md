# Prezzi Distributori Italia uMap
Script per la generazione di un file in formato GeoJSON da importare su uMap per la visualizzazione dei distributori con i prezzi provenienti dal MISE.

## Come funziona
Lo script scarica i 2 file CSV provenienti dal sito del **MISE** (MInistero dello Sviluppo Economico), li rielabora e genera un file in formato [GeoJSON](https://geojson.org/) da importare su [uMap](https://umap.openstreetmap.fr/it/).

I 2 file sono:
- **anagrafica_impianti_attivi.csv**: lista degli impianti
- **prezzo_alle_8.csv**: lista prezzi per idImpianto generati alle 8:00 di ogni mattina

La prima importazione è stata eseguita qui:
https://umap.openstreetmap.fr/it/map/distributori-prezzi-italia_932492

## Da completare
- [ ] aggiungere controllo degli errori pr debug
- [ ] generazione file distinti per Benzina, Diesel, GPL e altre tipologie
- [ ] colorazione marker in base al tipo di carburante
- [ ] aggiunta elementi grafici (loghi, ecc)
