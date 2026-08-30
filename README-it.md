# Modulo 'A causa dei clienti'

## Caratteristiche
Questo modulo è sviluppato e distribuito da Laurent Bouquet per <a href="https://www.joliciel.fr" target="_new"> Joliciel </a>.

Permette di generare una tabella PDF del 'Resto dovuto' dai clienti.

### Usando il modulo
![Screenshot customersowed](img/screenshot_customersowed_steps-it.png?raw=true "Generatore PDF" Due clienti ""){imgmd}

### Esempio di un documento PDF generato
![Screenshot customersowed](img/screenshot_customersowed_pdf-it.png?raw=true "CustomersOwed"){imgmd}



## Per aiuto

Puoi porre le tue domande sul <a href="https://www.dolibarr.fr/forum" target="_new"> Forum di Dolibarr </a>.

Per assistenza con questo modulo, scrivi a: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.



## Installazione

### Da file ZIP e GUI

- Se hai ottenuto il modulo in un file zip (come quando si scarica dal marketplace [Dolistore](https://www.dolistore.com)):
- fare clic su `` Home - Configurazione - Moduli - Distribuisci/Installa Plugin```
- e invia il file `module_customersowed-1.x.x.zip`
- facendo clic sul pulsante ```Sfoglia```, quindi su ```Invia```.


Nota: se questa schermata ti dice che non esiste una directory personalizzata, controlla che la tua configurazione sia corretta:

- Nella directory di installazione di Dolibarr, modifica il file ```htdocs/conf/conf.php``` e verifica che le seguenti righe non siano commentate:
```Php
//$ dolibarr_main_url_root_alt ...
//$ dolibarr_main_document_root_alt ...
```

- Eliminali se necessario (cancella il carattere ```//` ``) e assegna il percorso file corretto in base all'installazione di Dolibarr.

Ad esempio:

- in Linux:
```Php
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
```

- su Windows:
```Php
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = 'C:/I miei siti Web/Dolibarr/htdocs/personalizzato';
```

### <a name="final_steps"> </a> Passaggio finale

Nel tuo browser:

  - Accedi a Dolibarr come super amministratore.
  - Vai a ```Home - Configurazione - Moduli```
  - Ora dovresti essere in grado di trovare e attivare il modulo "Due clienti" nella sezione "Moduli finanziari"


## Licenze

### Per il codice sorgente

Il codice sorgente è liberamente utilizzabile e modificabile nel rispetto della licenza
![GPLv3 logo](img/gplv3.png)



### Per la documentazione

Tutti i testi della documentazione e dei file README sono liberamente utilizzabili e modificabili nel rispetto della licenza
![Logo GFDL](img/gfdl.png)


## Per andare oltre

Altri moduli sono disponibili su <a href="https://www.dolistore.com" target="_new"> Dolistore.com </a>.

Per ottenere un nuovo modulo, creato appositamente per te, scrivi a: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.