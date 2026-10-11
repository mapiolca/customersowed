<!-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> -->

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
  - You should now be able to find and activate the module 'Due Customers' in the section 'Joliciel x Les Métiers du Bâtiment'


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

## Preparing v2.0.0

Family: **Joliciel x Les Métiers du Bâtiment**.
Declared baseline: **Dolibarr 20+ / PHP 8.0+**. The report, widget, four PDF models and manual/scheduled emails are retained. The unused demonstration API, numbering, SQL and mailing scaffolding are removed; no business table is created or dropped.

The report applies native invoice/third-party sharing and sales restrictions before SQL pagination. Multicompany lists expose an Environment filter and native badges. Payments and applied credits are aggregated separately, including consumption of source credit notes. The payment cutoff includes the selected day. Historical credit application cannot be reconstructed exactly: Dolibarr's discount record has a creation date, but no reliable timestamp for its later application. This report is not an immutable historical ledger.

### Updating an existing installation

- Install the new module files before disabling/re-enabling it, so the new preservation logic handles the existing settings and scheduled job. Back up the database and document directory first.
- Reactivation migrates user/group rights `423100 → 42310001`, `423101 → 42310002`, `423102 → 42310003`. Existing grants, entity settings, custom/default models and cron frequency/status are preserved. The historical module ID `423100` is retained; no replacement ID has been allocated.
- Generate reports again. New PDFs live in the current entity's native document directory under `private/<execution-user-id>/`. Their invoice manifest is checked against current permissions at download time. Legacy PDFs in the shared root are not moved or deleted and are no longer exposed by the module.
- Configure the native scheduled job's execution user with module read/write, invoice and third-party read rights. Each recipient receives the intersection of their own scope and that user's scope. The job is declared disabled by default; an existing enabled job keeps its status.
- The job locks per entity and remembers daily deliveries in `private/.cron/`. After an uncertain SMTP result it refuses automatic replay. Check delivery and logs before an administrator removes the affected `mail-<hash>.json` marker to authorize a deliberate retry. A marker is not proof of recipient delivery.

### Verification

Run `python -m unittest discover -s test -v` with PHP on PATH (or set `PHP_BINARY`). Seven relational tests execute SQL emitted by the production methods against an isolated SQLite fixture. The PHP harness also checks access refusals, repeated reads, directory errors, manifests, footer reservation and simulated cron replay without sending mail.

PHP lint and nine local tests passed under PHP 8.2.11. PHPStan is not installed/configured. A partial live acceptance test was performed on develop with Dolibarr 25.0.0-alpha/PHP 8.3.35: activation cycle, settings, widget, report filters/pagination/column alignment, PDF generation/download and one targeted SMTP send. Receipt, minimum supported versions, restricted accounts, full Multicompany sharing, scheduled execution and PDF footer variants remain unverified. See [V2.0.0-AUDIT.md](V2.0.0-AUDIT.md) for evidence, restored settings and limits. This version remains in preparation; no release is published.
