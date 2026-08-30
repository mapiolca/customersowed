# Modul 'Aufgrund der Kunden'

## Funktionen
Dieses Modul wird entwickelt und vertrieben von Laurent Bouquet <a href="https://www.joliciel.fr" target="_new"> Joliciel </a>.

Es ermöglicht die Erstellung einer PDF-Tabelle der 'Rest due' durch die Kunden.

### Verwendung des Moduls
![Screenshot customersowed](img/screenshot_customersowed_steps-de.png?raw=true "PDF Generator‚Weil Kunden"){imgmd}

### Beispiel eines generierten PDF-Dokuments
![Screenshot customersowed](img/screenshot_customersowed_pdf-de.png?raw=true "CustomersOwed"){imgmd}



## Für Hilfe

Sie können Ihre Fragen über die <a href="https://www.dolibarr.fr/forum" target="_new"> Forum Dolibarr </a> fragen.

Wenn Sie Hilfe zu diesem Modul, schreiben Sie an: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.



## Installation

### Aus ZIP-Datei und GUI

- Wenn Sie das Modul in einer ZIP-Datei erhalten haben (wie beim Herunterladen vom Marktplatz [Dolistore](https://www.dolistore.com)):
- klicken Sie bitte auf ```Home - Konfiguration - Module - Deploy/Install Plugin``` '
- und senden Sie die Datei `module_customersowed-1.x.x.zip`
- Klicken Sie auf die Schaltfläche ```Browse``` und dann auf ```Send```.


Hinweis: Wenn auf diesem Bildschirm angezeigt wird, dass kein benutzerdefiniertes Verzeichnis vorhanden ist, überprüfen Sie, ob Ihre Konfiguration korrekt ist:

- Bearbeiten Sie in Ihrem Dolibarr-Installationsverzeichnis die Datei ```htdocs/conf/conf.php``` und überprüfen Sie, ob die folgenden Zeilen nicht kommentiert sind:
```Php
//$ dolibarr_main_url_root_alt ...
//$ dolibarr_main_document_root_alt ...
```

- Kommentieren Sie sie ggf. (löschen Sie das Zeichen `` //'`) und weisen Sie den korrekten Dateipfad entsprechend Ihrer Dolibarr-Installation zu.

Zum Beispiel:

- unter Linux:
```Php
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
```

- unter Windows:
```Php
$ dolibarr_main_url_root_alt = '/custom';
$ dolibarr_main_document_root_alt = 'C:/Meine Websites/Dolibarr/htdocs/custom';
```

### <a name="final_steps"> </a> Letzter Schritt

In Ihrem Browser:

  - Melden Sie sich als Superadministrator bei Dolibarr an.
  - Gehen Sie zu ```Home - Konfiguration - Module```
  - Sie sollten jetzt das Modul 'Due Customers' im Abschnitt 'Financial Modules' finden und aktivieren können.


## Lizenzen

### Für den Quellcode

Der Quellcode ist in Bezug auf die Lizenz frei verwendbar und modifizierbar
![GPLv3-Logo](img/gplv3.png)



### Zur Dokumentation

Alle Texte der Dokumentation und der README-Dateien sind in Bezug auf die Lizenz frei verwendbar und modifizierbar
![GFDL-Logo](img/gfdl.png)


## Weiter gehen

Weitere Module sind unter <a href="https://www.dolistore.com" target="_new"> Dolistore.com </a> verfügbar.

Um ein neues Modul zu erhalten, speziell für Sie, schreiben Sie an: <a href="mailto:dolibarr@joliciel.fr"> dolibarr@joliciel.fr </a>.