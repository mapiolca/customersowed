# Targeted regressions

Run `python -B -m unittest discover -s test -v` with PHP 8.2 or later on PATH.

These tests simulate core services and evaluate the module classes without their
bootstrap imports. They do not exercise a full Dolibarr installation, database,
image endpoint, PDF rendering, mail delivery or Multicompany sharing.

On PHP 8.4.22, all three tests fail on baseline
`8d3f577113e69aa83836d165d2e8e6196fafa4f0`: missing Translate class in the
hook constructor, dynamic PDF database property and dynamic line option property.
All three pass with this patch, including the four PDF constructors and five
optional columns in Joliciel and Malliaros. PHP syntax checks pass for all 22 files.

The native viewimage.php bootstrap was read in Dolibarr 20.0.0: it defines
NOREQUIRETRAN and NOREQUIRESOC. HookManager constructors in 20.0.0 and 24.0.0
instantiate hooks with the database alone. Line fields were compared to the
CommonObject and CommonObjectLine declarations in 20.0.0; inherited fields are
left intact. This source review does not establish runtime compatibility across
Dolibarr 20 through 25.

Release 1.4.3 was verified as published and stable, with its remote tag targeting
the baseline commit above. The about page reads the descriptor version directly.
No release or installable ZIP is created by this patch.

No local Dolibarr instance or PHPStan installation/configuration is available.
Browser validation, actual image loading, report rendering, scheduler execution,
and the Dolibarr/PHP/Multicompany runtime matrix remain to be checked on a test
instance. No deployment or email was performed.
