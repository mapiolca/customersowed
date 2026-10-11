<!-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> -->


FR
## 2.0.0 — en préparation
 - Maintenir le rapport, le widget, les quatre modèles PDF et les emails sur un socle déclaré Dolibarr 20+/PHP 8.0+ ; classement dans « Joliciel x Les Métiers du Bâtiment » et onglets Compatibilité/À propos issus du descripteur.
 - Respecter les droits natifs, les restrictions commerciales et les partages de factures ; ajouter le filtre Environnement, la pagination SQL et corriger les agrégats de règlements/avoirs.
 - Protéger les rapports par utilisateur et entité, vérifier leur périmètre au téléchargement et réserver le pied de page natif dans les PDF ; régénération nécessaire des anciens rapports.
 - Préserver les réglages, modèles et cron ; migrer les droits utilisateurs/groupes de 423100/423101/423102 vers 42310001/42310002/42310003. Encadrer les envois périodiques et les reprises après résultat SMTP incertain.
 - Retirer les squelettes de démonstration et le log distribué. Tests locaux réussis ; essais réels Dolibarr/Multicompany/MySQL et rendu PDF encore nécessaires avant publication, voir V2.0.0-AUDIT.md.

## 1.4.3
 - Corriger bug affichage liste des clients pour Dolibarr > 17
## 1.4.1
 - Améliorer les sauts de page dans les documents PDF
## 1.4.0 
 - Tenir compte de la constante FACTURE_DEPOSITS_ARE_JUST_PAYMENTS
## 1.3.16
 - Améliorer la mise en forme du mail
## 1.3.15
 - Ne pas masquer le bouton 'GENERER' si la liste est vide
## 1.3.12
 - Corriger l'erreur 500 lors de l'envoi d'un mail avec uniquement les factures créées par un utilisateur
## 1.3.11
 - Corriger les liens vers les factures et les client (dans la liste des factures client impayées)
## 1.3.10
 - Corriger la non prise en compte de l'option 'Exclure les factures d'acompte' par défaut (défini dans la configuration du module)
## 1.3.9
 - Améliorer l'affichage du total restant dû dans le widget de la page d'accueil de Dolibarr
## 1.3.8
 - Corriger l'envoi du ficher PDF en pièce jointe
## 1.3.7
 - Corriger l'envoi du ficher PDF en pièce jointe
## 1.3.6
 - Corriger le nom du ficher PDF, en pièce jointe, lors de l'envoi du mail (retirer le chemin)
## 1.3.5
 - Préciser le nom du ficher PDF, en pièce jointe, lors de l'envoi du mail
## 1.3.4
 - Corriger le calcul du total restant dû, lorsqu'un paiement couvre plusieurs factures
## 1.3.3
 - Corriger la superposition de colonnes dans le modèle 'malliaros'
## 1.3.2
 - Réduction de la taille du fichier .zip d'installation
## 1.3.1
 - Adaptation du module pour la version 14 de Dolibarr
## 1.3
 - Ajout du modèle de PDF "malliaros"
## 1.2
 - Ajout de la prise en compte du projet, lorsque ce module est activé
## 1.1.5
 - Correction d'un problème de date de paiement (repéré par Philippe GUYOT)
## 1.1.4
 - Correction d'un problème avec le lien hypertexte menant à la mauvaise URL dans la zone de la page d'accueil de Dolibarr (repéré par Pierre ARDOIN)
## 1.1.3
 - Nouveau modèle de tableau PDF "Echéancier restant dû par les clients à 30 jours, 60 jours, 90 jours, 180 jours, et à plus de 180 jours"  (nextep)
## 1.1.2
 - Envoyer le 'Dû clients' par mail en 1 clic
## 1.1.1
 - Programmer l'envoi par mail du 'Dû clients' avec un 'Travail planifié'
## 1.0.13
 - Correction du calcul du montant reçu (quand un paiement concerne plusieurs factures)
## 1.0.12
 - Compatibilité avec les versions de Dolibarr de 6.0 à 10.0 (incluses)
## 1.0.11
 - Filtrage des factures plus ergonomique
## 1.0.10
 - Exclure les factures d'acompte
## 1.0.9
 - Connaitre le "Dû clients" sur des dates antérieures notamment avant la clôture comptable (par exemple le 31/12/2018)
 - Connaitre le "Dû clients" pour un client précis

EN
## 1.3.3
 - Fix columns overlapping in 'malliaros' model
## 1.3.2
 - Downsize installation .zip file
## 1.3.1
 - Fix module to Dolibarr version 14
## 1.3
 - Adding "malliaros" PDF template
## 1.2
 - Adding project, when this module is activated
## 1.1.5
 - Correction of a payment date problem (identified by Philippe GUYOT)
## 1.1.4
 - Fix an issue with the hyperlink leading to the wrong URL in the box on Dolibarr's home page (identified by Pierre ARDOIN)
## 1.1.3
 - New template for PDF table "Remaining customer due date at 30 days, 60 days, 90 days, 180 days, and more than 180 days" (nextep)
## 1.1.2
 - Send the 'Due customers' by email in 1 click
## 1.1.1
 - Schedule the mailing of the 'Due Customers' with a 'Planned Work'
## 1.0.13
 - Correction of the calculation of the amount received (when a payment concerns several invoices)
## 1.0.12
 - Compatibility with Dolibarr versions from 6.0 to 10.0 (included)
## 1.0.11
 - More ergonomic invoice filtering
## 1.0.10
 - Exclude down payment invoices
## 1.0.9
 - To know the "Due customers" on previous dates in particular before the closing accounting (for example the 31/12/2018)
 - Know the "Due customers" for a specific customer
## 1.0.0
 - Initial version

ES
## 1.3.3
 - Corregir el solapamiento de columnas en el modelo 'malliaros'
## 1.3.2
 - Reducir el tamaño del archivo .zip de instalación
## 1.3.1
 - Modificar el módulo a la versión 14 de Dolibarr
## 1.3
 - Adición de la plantilla PDF "malliaros"
## 1.2
 - Añadiendo proyecto, cuando este módulo está activado
## 1.1.5
 - Corrección de un problema de fecha de pago 
## 1.1.4
 - Fix an issue with the hyperlink leading to the wrong URL in the box on Dolibarr's home page
## 1.1.3
 - Nueva plantilla para la tabla PDF "Fecha de vencimiento del cliente restante a los 30 días, 60 días, 90 días, 180 días y más de 180 días" (siguiente paso)
## 1.1.2
 - Envíe los 'Clientes pendientes' por correo electrónico en 1 clic
## 1.1.1
 - Programe el envío por correo de los 'clientes vencidos' con un 'trabajo planificado'
## 1.0.13
 - Corrección del cálculo del importe recibido (cuando un pago se refiere a varias facturas)
## 1.0.12
 - Compatibilidad con las versiones Dolibarr de 6.0 a 10.0 (incluidas)
## 1.0.11
 - Filtrado de facturas más ergonómico
## 1.0.10
 - Excluir las facturas de anticipo
## 1.0.9
 - Para conocer los "clientes debidos" en fechas anteriores, en particular antes de la contabilidad de cierre (por ejemplo, el 31/12/2018)
 - Conocer los "clientes debidos" para un cliente específico


IT
## 1.3.3
 - Fissare le colonne che si sovrappongono nel modello 'malliaros
## 1.3.2
 - Ridimensionare il file .zip di installazione
## 1.3.1
 - Modificare il modulo a Dolibarr versione 14
## 1.3
 - Aggiunta del modello PDF "malliaros"
## 1.2
    - Aggiunta progetto, quando questo modulo è attivato
## 1.1.5
 - Correzione di un problema di data di pagamento 
## 1.1.4
 - Fix an issue with the hyperlink leading to the wrong URL in the box on Dolibarr's home page
## 1.1.3
 - Nuovo modello per la tabella PDF "Data di scadenza del cliente rimanente a 30 giorni, 60 giorni, 90 giorni, 180 giorni e più di 180 giorni" (Nextep)
## 1.1.2
 - Invia i "due clienti" via e-mail in 1 clic
## 1.1.1
 - Pianifica la spedizione dei "Due Clienti" con un "Lavoro pianificato"
## 1.0.13
 - Correzione del calcolo dell'importo ricevuto (quando un pagamento riguarda più fatture)
## 1.0.12
 - Compatibilità con le versioni Dolibarr dalla 6.0 alla 10.0 (inclusa)
## 1.0.11
 - Filtro delle fatture più ergonomico
## 1.0.10
 - Escludere fatture di anticipo
## 1.0.9
 - Conoscere i "Debiti clienti" in date precedenti in particolare prima della chiusura contabile (ad esempio il 31/12/2018)
 - Conoscere i "Due clienti" per un cliente specifico


DE
## 1.3.3
 - Überlappung von Spalten im Modell 'malliaros' behoben
## 1.3.2
 - Verkleinern der Installations-.zip-Datei
## 1.3.1
 - Anpassung des Moduls an Dolibarr Version 14
## 1.3
  - Hinzufügen der PDF-Vorlage "malliaros"
## 1.2
    - Projekt hinzufügen, wenn dieses Modul aktiviert ist
## 1.1.5
  - Korrektur eines Zahlungsdatumsproblems
## 1.1.4
 - Fix an issue with the hyperlink leading to the wrong URL in the box on Dolibarr's home page
## 1.1.3
 - Neue Vorlage für die PDF-Tabelle "Verbleibendes Fälligkeitsdatum des Kunden bei 30 Tagen, 60 Tagen, 90 Tagen, 180 Tagen und mehr als 180 Tagen" (Nextep)
## 1.1.2
 - Senden Sie die "Fälligen Kunden" per E-Mail in 1 Klick
## 1.1.1
 - Planen Sie den Versand der "fälligen Kunden" mit einer "geplanten Arbeit"
## 1.0.13
 - Korrektur der Berechnung des erhaltenen Betrags (wenn eine Zahlung mehrere Rechnungen betrifft)
## 1.0.12
 - Kompatibilität mit Dolibarr-Versionen von 6.0 bis 10.0 (enthalten)
## 1.0.11
 - Ergonomischere Rechnungsfilterung
## 1.0.10
 - Anzahlungsrechnungen ausschließen
## 1.0.9
 - Die "fälligen Kunden" an früheren Terminen insbesondere vor dem Rechnungsabschluss zu kennen (zum Beispiel am 31.12.2008)
 - Die "fälligen Kunden" für einen bestimmten Kunden kennen



