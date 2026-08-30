# Module 'Due Customers'

## Characteristics
This module is developed and distributed by Laurent Bouquet for <a href="https://www.joliciel.fr" target="_new">Joliciel</a>.

It generates a PDF table of 'Rest Due' by customers.

### Using the module
![Screenshot customersowed](img/screenshot_customersowed_steps-fr.png?raw=true "PDF Generator 'Due Customers'"){imgmd}

### Example of a generated PDF document
![Screenshot customersowed](img/screenshot_customersowed_pdf-fr.png?raw=true "CustomersOwed"){imgmd}




## For help

You can ask your questions on the <a href="https://www.dolibarr.fr/forum" target="_new">Dolibarr Forum</a>.

For help with this module, write to: <a href="mailto:dolibarr@joliciel.fr">dolibarr@joliciel.fr</a>.



## Installation

### From ZIP file and GUI

- If you obtained the module in a zip file (as when downloading from the marketplace [Dolistore](https://www.dolistore.com)):
- please click ```Home - Configuration - Modules - Deploy/Install Plugin```
- and send the file ```module_customersowed-x.x.x.zip```
- by clicking on the ```Browse``` button then` ```Send```.


Note: If this screen tells you that there is no custom directory, check that your configuration is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that the following lines are not commented on:
```Php
// $ dolibarr_main_url_root_alt ...
// $ dolibarr_main_document_root_alt ...
```

- Uncomment them if necessary (delete the ```//` `` character) and assign the correct file path according to your Dolibarr installation.

For example :

- in Linux:
```Php
$ dolibarr_main_url_root_alt = '/ custom';
$ dolibarr_main_document_root_alt = '/ var/www/Dolibarr/htdocs/custom';
```

- on Windows:
```Php
$ dolibarr_main_url_root_alt = '/ custom';
$ dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
```

### <a name="final_steps"> </a> Final Step

In your browser:

  - Log in to Dolibarr as a super administrator.
  - Go to ```Home - Configuration - Modules```
  - You should now be able to find and activate the module 'Due Customers' in the section 'Financial Modules'


## Licenses

### For the source code

The source code is freely usable and modifiable in the respect of the license! 
[GPLv3 logo](img/gplv3.png)



### For documentation

All the texts of the documentation and the README files are freely usable and modifiable in the respect of the license! 
[GFDL logo](img/gfdl.png)



## For further

Other modules are available at <a href="https://www.dolistore.com" target="_new">Dolistore.com</a>.

To obtain a new module, made especially for you, write to: <a href="mailto:dolibarr@joliciel.fr">dolibarr@joliciel.fr</a>.
