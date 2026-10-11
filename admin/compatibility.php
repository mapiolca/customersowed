<?php
/*
 * Copyright (C) 2004-2017 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Laurent BOUQUET <dolibarr@joliciel.fr>
 * Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file customersowed/admin/about.php
 * \ingroup customersowed
 * \brief About page of module CustomersOwed.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]))
	$res = @include ($_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i --;
	$j --;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php"))
	$res = @include (substr($tmp, 0, ($i + 1)) . "/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php"))
	$res = @include (dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once __DIR__.'/../lib/customersowed.lib.php';

// Translations
$langs->loadLangs(array(
	"errors",
	"admin",
	"customersowed@customersowed"));

// Access control
if (! $user->admin)
	accessforbidden();

require_once __DIR__.'/../class/customersowedcompatibility.class.php';
dol_include_once('/customersowed/core/modules/modCustomersOwed.class.php');
$descriptor = new modCustomersOwed($db);
llxHeader('', $langs->trans('Compatibility'));
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword=customersowed">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($langs->trans('Compatibility'), $linkback, 'customersowed@customersowed');
print dol_get_fiche_head(customersowedAdminPrepareHead(), 'compatibility', $descriptor->name, -1, $descriptor->picto);
print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
foreach (array('Dolibarr' => DOL_VERSION, 'PHP' => PHP_VERSION, 'Version' => $descriptor->version,
	'CustomersOwedMinimumDolibarr' => implode('.', $descriptor->need_dolibarr_version),
	'CustomersOwedMinimumPhp' => implode('.', $descriptor->phpmin)) as $label => $value) {
	print '<tr class="oddeven"><td>'.$langs->trans($label).'</td><td>'.dol_escape_htmltag($value).'</td></tr>';
}
foreach (CustomersOwedCompatibility::getFeatures() as $label => $feature) {
	print '<tr class="oddeven"><td>'.$langs->trans($label).'</td><td>'.$langs->trans($feature['available'] ? 'Enabled' : 'Disabled')
		.' — '.$langs->trans($feature['reason']).'</td></tr>';
}
print '</table></div>';
print '<p>'.$langs->trans('CustomersOwedCompatibilityNotTested').'</p>';
print dol_get_fiche_end();
llxFooter();
$db->close();
