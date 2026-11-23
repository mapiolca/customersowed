<!-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> -->

# Module 'Dû clients'

## Caractéristiques
Ce module est développé et diffusé par Laurent Bouquet pour <a href="https://www.joliciel.fr" target="_new">Joliciel</a>.

Il permet de générer un tableau PDF du 'Reste dû' par les clients.

### Utilisation du module
![Screenshot customersowed](img/screenshot_customersowed_steps-fr.png?raw=true "Générateur de PDF 'Dû clients'"){imgmd}

### Exemple d'un document PDF généré
![Screenshot customersowed](img/screenshot_customersowed_pdf-fr.png?raw=true "CustomersOwed"){imgmd}



## Pour obtenir de l'aide

Vous pouvez poser vos questions sur le <a href="https://www.dolibarr.fr/forum" target="_new">Forum Dolibarr</a>.

Pour obtenir de l'aide sur ce module, écrivez à : <a href="mailto:dolibarr@joliciel.fr">dolibarr@joliciel.fr</a>.



## Installation

### À partir du fichier ZIP et de l'interface graphique

- Si vous avez obtenu le module dans un fichier zip (comme lors du téléchargement depuis la place de marché [Dolistore](https://www.dolistore.com)) :  
	 - veuillez cliquer sur ```Accueil - Configuration - Modules - Déployer/Installer un module externe```
	 - et envoyer le fichier `module_customersowed-1.x.x.zip`
	 - en cliquant sur le bouton ```Parcourir``` puis ```Envoyer```.


Remarque: Si cet écran vous indique qu'il n'y a pas de répertoire personnalisé, vérifiez que votre configuration est correcte :

- Dans votre répertoire d'installation Dolibarr, éditez le fichier ```htdocs/conf/conf.php``` et vérifiez que les lignes suivantes ne sont pas commentées :
	```php
	//$dolibarr_main_url_root_alt ...
	//$dolibarr_main_document_root_alt ...
	```

- Décommentez-les si nécessaire (supprimez le caractère ```//```) et attribuez le chemin de fichiers correct en fonction de votre installation de Dolibarr.

	Par exemple :

	- sous Linux :
		```php
		$dolibarr_main_url_root_alt = '/custom';
		$dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
		```

	- sous Windows:
		```php
		$dolibarr_main_url_root_alt = '/custom';
		$dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
		```
		
### <a name="final_steps"></a>Étape finale

Dans votre navigateur :

  - Connectez-vous à Dolibarr en tant que super administrateur.
  - Allez dans ```Accueil - Configuration - Modules```
  - Vous devriez maintenant pouvoir trouver et activer le module 'Dû clients' dans la partie 'Joliciel x Les Métiers du Bâtiment'


## Licences

### Pour le code source

Le code source est librement utilisable et modifiable dans le respect de la licence 
![GPLv3 logo](img/gplv3.png)



### Pour la documentation

Tous les textes de la documentation et des fichiers README sont librement utilisables et modifiables dans le respect de la licence 
![GFDL logo](img/gfdl.png)


## Pour aller plus loin

D'autres modules sont disponibles sur <a href="https://www.dolistore.com" target="_new">Dolistore.com</a>.

Pour obtenir un nouveau module, réalisé spécialement pour vous, écrivez à : <a href="mailto:dolibarr@joliciel.fr">dolibarr@joliciel.fr</a>.

## Contribution

### Pour proposer vos améliorations

Faire une nouvelle version :
0) git pull
1) incrémenter le n° de version dans /var/www/htdocs/custom/customersowed/core/modules/modcustomersowed.class.php
2) changer le n° de version dans /var/www/htdocs/custom/customersowed/.github/workflows/master.yml
3) ajouter le n° de version et une description dans /var/www/htdocs/custom/customersowed/ChangeLog.md
4) git add . ; git commit -m "laDescription" ; git push
5) git tag 1.4.3 ; git push --tags 


## Préparation v2.0.0

Famille : **Joliciel x Les Métiers du Bâtiment**.
Audit et travaux avant publication : [V2.0.0-AUDIT.md](V2.0.0-AUDIT.md). La conformité complète et la compatibilité v20+/PHP 8.0+ restent à valider.
