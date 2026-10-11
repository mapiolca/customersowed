<?php
/*
 * Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2015 Jean-François Ferry <jfefe@aternatik.fr>
 * Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * \file htdocs/customersowed/template/customersowedindex.php
 * \ingroup customersowed
 * \brief Home page of customersowed top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}

// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}

if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}

// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}

if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}

if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}

if (!$res) {
	die("Include of main fails");
}

global $conf, $langs, $user;

dol_include_once('/customersowed/class/customersowed.class.php');
dol_include_once('/customersowed/class/actions_customersowed.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
// require_once DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array(
	'bills',
	'companies',
	'projects',
	'customersowed@customersowed'));

// Parameters
$hookmanager->initHooks(array('customersowed'));
$only_user = $only_salesrepresentative = 0;
$param = '';
$object = new stdClass();
$massaction = GETPOST('massaction', 'aZ09');
$type = 'customersowed';
$action = GETPOST('action', 'alpha');
$socid = GETPOST('socid', 'int');
$date_end_month = GETPOST('date_end_month', 'int');
$date_end_day = GETPOST('date_end_day', 'int');
$date_end_year = GETPOST('date_end_year', 'int');
$date_end = dol_mktime(0, 0, 0, $date_end_month, $date_end_day, $date_end_year);
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$columns = GETPOST("columns", 'alpha');
$limit = max(1, min(1000, GETPOSTINT('limit') ?: getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25)));
$page = max(0, GETPOSTINT('page'));
$offset = $page * $limit;
if (GETPOSTISSET('exclude_dp')) {
	$exclude_dp = GETPOST('exclude_dp', 'int');
} else {
	$exclude_dp = getDolGlobalString('CUSTOMERSOWEDREPORT_EXCLUDE_DP');
}
if (GETPOSTISSET('only_late')) {
	$only_late = GETPOST('only_late', 'int');
} else {
	$only_late = getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_LATE');
}
if (GETPOST('only_user', 'int')) {
	$only_user = GETPOST('only_user', 'int');
} else {
	if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY')) {
		$only_user = $user->id;
	}
}
if (GETPOST('only_salesrepresentative', 'int')) {
	$only_salesrepresentative = GETPOST('only_salesrepresentative', 'int');
} else {
	if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE')) {
		$only_salesrepresentative = $user->id;
	}
}

// Template used to generate document
if (GETPOST('model', 'alpha')) {
	$model = GETPOST('model', 'alpha');
}
$defaultmodel = getDolGlobalString('CUSTOMERSOWEDREPORT_ADDON_PDF');
if (empty($model)) {
	$model = $defaultmodel;
}

// Securite acces client
if (!$user->hasRight('customersowed', 'read')) {
	accessforbidden();
}

$customersOwedDate = dol_now();
if ($date_end) {
	$customersOwedDate = $date_end;
}

$urlsource = $_SERVER['PHP_SELF'];
$urlsource .= str_replace('&amp;', '&', $param);

$permissioncreate = $user->hasRight('customersowed', 'write');
$genallowed = $permissioncreate;
$delallowed = $user->hasRight('customersowed', 'delete');
if (!isModEnabled('customersowed') || !isModEnabled('facture')) { accessforbidden(); }
$customersOwedReport = new CustomersOwed($db, $customersOwedDate, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);
$customersOwedReport->selectedEntities = array_map('intval', (array) GETPOST('search_entities', 'array'));
$pdfreportfilesdir = $customersOwedReport->getReportDirectory($user);
if ($pdfreportfilesdir === false) { accessforbidden($langs->trans($customersOwedReport->error)); }
$mutatingActions = array('builddoc', 'remove_file', 'send_mail_to_customer', 'send_mail_to_user', 'send_mail_to_salesrepresentative');
if (in_array($action, $mutatingActions, true)) {
	if (($action === 'remove_file' && !$delallowed) || ($action !== 'remove_file' && !$genallowed)) { accessforbidden(); }
	if (!is_string(GETPOST('token', 'alphanohtml')) || !hash_equals((string) currentToken(), GETPOST('token', 'alphanohtml'))) { accessforbidden(); }
}

$errorMessage = "";
if (!file_exists($pdfreportfilesdir)) {
	$errorMessage = $langs->trans("ErrorFolderNotExists");
	setEventMessages($errorMessage, null, 'errors');
	dol_syslog($errorMessage, LOG_ERR);
}


/* ************************************************************************** */
/* */
/* Actions */
/* */
/* ************************************************************************** */

$parameters = array(
	'socid' => $socid,
	'exclude_dp' => $exclude_dp,
	'only_late' => $only_late,
	'only_user' => $only_user,
	'only_salesrepresentative' => $only_salesrepresentative);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	$errorMessage = $hookmanager->error;
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


// Every list column participates in the native selector and its position ordering.
$contextpage = 'customersowedreport';
$arrayfields = array(
	'f.entity' => array('label' => 'CustomersOwedEnvironment', 'checked' => 1, 'enabled' => count(explode(',', getEntity('invoice'))) > 1 && isModEnabled('multicompany'), 'position' => 5, 'search' => 'entities', 'css' => 'center'),
	'f.ref' => array('label' => 'InvoiceRef', 'checked' => 1, 'position' => 10, 'search' => 'text'),
	's.nom' => array('label' => 'ThirdParty', 'checked' => 1, 'position' => 20, 'search' => 'text'),
	'p.ref' => array('label' => 'ProjectRef', 'checked' => 1, 'enabled' => isModEnabled('projet'), 'position' => 30, 'search' => 'text', 'sql' => 'pr.ref'),
	'p.title' => array('label' => 'ProjectLabel', 'checked' => 0, 'enabled' => isModEnabled('projet'), 'position' => 35, 'search' => 'text', 'sql' => 'pr.title'),
	's.zip' => array('label' => 'Zip', 'checked' => 0, 'position' => 40, 'search' => 'text'),
	's.town' => array('label' => 'Town', 'checked' => 0, 'position' => 45, 'search' => 'text'),
	'f.ref_client' => array('label' => 'RefCustomer', 'checked' => 0, 'position' => 50, 'search' => 'text'),
	'f.type' => array('label' => 'Type', 'checked' => 0, 'position' => 55, 'search' => 'select', 'css' => 'center'),
	'f.fk_mode_reglement' => array('label' => 'PaymentMode', 'checked' => 0, 'position' => 60, 'search' => 'payment', 'css' => 'center'),
	'f.date' => array('label' => 'DateInvoice', 'checked' => 0, 'position' => 65, 'search' => 'date', 'sql' => 'f.datef', 'css' => 'center nowrap'),
	'f.datec' => array('label' => 'DateCreation', 'checked' => 0, 'position' => 70, 'search' => 'date', 'css' => 'center nowrap'),
	'f.tms' => array('label' => 'DateModificationShort', 'checked' => 0, 'position' => 75, 'search' => 'date', 'css' => 'center nowrap'),
	'f.date_lim_reglement' => array('label' => 'DateDue', 'checked' => 1, 'position' => 80, 'search' => 'date', 'css' => 'center nowrap'),
	'f.total_ht' => array('label' => 'AmountHT', 'checked' => getDolGlobalInt('CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN'), 'position' => 90, 'search' => 'amount', 'css' => 'right nowrap'),
	'f.total_vat' => array('label' => 'AmountVAT', 'checked' => 0, 'position' => 100, 'search' => 'amount', 'sql' => 'f.total_tva', 'css' => 'right nowrap'),
	'f.total_localtax1' => array('label' => $langs->transcountry('AmountLT1', $mysoc->country_code), 'checked' => 1, 'enabled' => $mysoc->localtax1_assuj == '1', 'position' => 110, 'search' => 'amount', 'sql' => 'f.localtax1', 'css' => 'right nowrap'),
	'f.total_localtax2' => array('label' => $langs->transcountry('AmountLT2', $mysoc->country_code), 'checked' => 1, 'enabled' => $mysoc->localtax2_assuj == '1', 'position' => 120, 'search' => 'amount', 'sql' => 'f.localtax2', 'css' => 'right nowrap'),
	'f.total_ttc' => array('label' => 'AmountTTC', 'checked' => 1, 'position' => 130, 'search' => 'amount', 'css' => 'right nowrap'),
	'am' => array('label' => 'Received', 'checked' => 1, 'position' => 140, 'search' => 'amount', 'css' => 'right nowrap'),
	'rtp' => array('label' => 'Rest', 'checked' => 1, 'position' => 150, 'search' => 'amount', 'css' => 'right nowrap'),
	'f.fk_statut' => array('label' => 'Status', 'checked' => 1, 'position' => 160, 'search' => 'select', 'css' => 'center nowrap')
);
$arrayfields = dol_sort_array($arrayfields, 'position');
$searchFilters = array();
$searchParams = '';
$resetFilters = GETPOSTISSET('button_removefilter_x');
if (GETPOSTISSET('button_search_x') || $resetFilters) { $page = 0; $offset = 0; }
foreach ($arrayfields as $field => $definition) {
	if ($definition['search'] === 'entities') { continue; }
	$name = 'search_'.str_replace('.', '_', $field);
	$sqlfield = $definition['sql'] ?? $field;
	if ($definition['search'] === 'date') {
		$month = $resetFilters ? 0 : GETPOSTINT($name.'month');
		$day = $resetFilters ? 0 : GETPOSTINT($name.'day');
		$year = $resetFilters ? 0 : GETPOSTINT($name.'year');
		$value = $month && $day && $year && checkdate($month, $day, $year) ? dol_mktime(0, 0, 0, $month, $day, $year) : '';
		if ($value !== '') { $searchParams .= '&'.$name.'month='.$month.'&'.$name.'day='.$day.'&'.$name.'year='.$year; }
	} else {
		$value = $resetFilters ? '' : GETPOST($name, 'alphanohtml');
		if (!is_string($value) || (in_array($definition['search'], array('select', 'payment'), true) && $value === '-1')) { $value = ''; }
		if ($value !== '') { $searchParams .= '&'.$name.'='.urlencode($value); }
	}
	$searchFilters[$field] = $value;
	if ($value !== '') { $customersOwedReport->searchFilters[$sqlfield] = $value; }
}
if ($resetFilters) { $customersOwedReport->selectedEntities = array(); }

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

if (!$sortfield) {
	$sortfield = 'f.date_lim_reglement';
}

if (!$sortorder && getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER') !== '') {
	$sortorder = getDolGlobalString('INVOICE_DEFAULT_UNPAYED_SORT_ORDER');
}

if (!$sortorder) {
	$sortorder = 'ASC';
}

$urlsource = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative.$searchParams;

if ($customersOwedReport->fetch_lines($socid, $sortfield, $sortorder, $user,
	in_array($action, $mutatingActions, true) ? 0 : $limit, $offset) < 0) { accessforbidden($langs->trans('ErrorBadParameters')); }
$title = $customersOwedReport->title;
$entityParams = '';
foreach ($customersOwedReport->selectedEntities as $selectedEntity) { $entityParams .= '&search_entities[]='.(int) $selectedEntity; }
$urlsource .= $entityParams.$searchParams;
$param .= $searchParams;
$param .= '&socid='.(int) $socid.'&date_end_month='.(int) $date_end_month.'&date_end_day='.(int) $date_end_day.'&date_end_year='.(int) $date_end_year.'&exclude_dp='.(int) $exclude_dp.'&only_late='.(int) $only_late.'&only_user='.(int) $only_user.'&only_salesrepresentative='.(int) $only_salesrepresentative.'&limit='.(int) $limit.$entityParams;

// Build doc
if ($action == 'builddoc' && $genallowed) {
	if (!preg_match('/^[a-z0-9_]+$/D', (string) $model)) {
		$error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Model"));
		$errorMessage = $error;
	} else {
		// Reload to get all modified line records and be ready for hooks
		$outputlangs = $langs;
		$newlang = '';

		if (getDolGlobalString('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}

		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output = $outputlangs->charset_output;

		// Check pdfreportfilesdir exists

		// Search template files
		$modulefile = '';
		$classname = '';
		$modulefile_found = false;
		$modelsdir = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($modelsdir as $reldir) {
			$modulefile = dol_buildpath($reldir . "core/modules/$type/doc/pdf_" . $model . ".modules.php", 0);
			if (file_exists($modulefile)) {
				$modulefile_found = true;
				$classname = "pdf_" . $model;
				break;
			}
		}

		if ($modulefile_found) {
			require_once $modulefile;

			$pdf_module = new $classname($db, $columns);
			if ($pdf_module->write_file($customersOwedReport, $pdfreportfilesdir . '/' . $customersOwedReport->pdfreportfilename, $langs, $socid, $sortfield, $sortorder) > 0 && $customersOwedReport->recordDocumentManifest($pdfreportfilesdir . '/' . $customersOwedReport->pdfreportfilename) > 0) {
				if (empty($donotredirect)) // This is set when include is done by bulk action
				{
					setEventMessages($langs->trans("FileGenerated"), null);
					$urltoredirect = $_SERVER['PHP_SELF'] . '?model=' . $model . '&socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative.$searchParams;
					$urltoredirect .= $entityParams;
					$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
					$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

					header('Location: ' . $urltoredirect . '#builddoc');
					exit();
				} else {
					header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=" . $type . "&file=private/".(int) $user->id."/" . $customersOwedReport->pdfreportfilename);
					return;
				}
			} else {
				$errorMessage = $pdf_module->error;
				setEventMessages($errorMessage, $pdf_module->errors, 'errors');
				dol_syslog($pdf_module->error, LOG_ERR);
			}
		} else {
			$errorMessage = $langs->trans("ErrorModuleNotFound");
			setEventMessages($errorMessage, null, 'errors');
			dol_syslog($errorMessage, LOG_ERR);
		}
	}
}

// Delete file in doc form
if ($action == 'remove_file' && $delallowed) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$langs->load("other");
	$filetodelete = GETPOST('file', 'alpha');
	if (!is_string($filetodelete) || ($filetodelete !== basename($filetodelete)
		&& $filetodelete !== 'private/'.(int) $user->id.'/'.basename($filetodelete))) { accessforbidden(); }
	$filetodelete = basename($filetodelete);
	if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}-[a-f0-9]{20}\.pdf$/D', $filetodelete)) { accessforbidden(); }
	$file = $pdfreportfilesdir . '/' . $filetodelete;
	if (is_link($file)) { accessforbidden(); }

	$ret = dol_delete_file($file, 0, 0, 0);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
	} else {
		$errorMessage = $langs->trans("ErrorFailToDeleteFile", $filetodelete);
		setEventMessages($errorMessage, null, 'errors');
	}

	// Make a redirect to avoid to keep the remove_file into the url that create side effects
	$urltoredirect = $_SERVER['REQUEST_URI'];
	$urltoredirect .= $entityParams;
					$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
	$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

	header('Location: ' . $urltoredirect);
	exit();
}

// send_mail_to_customer
if ($action == 'send_mail_to_customer' && $genallowed) {
	if ($socid > 0) {
		$societe = new Societe($db);
		if ($societe->fetch($socid) <= 0) { accessforbidden(); }
		if (count($customersOwedReport->lines) < 1) {
			setEventMessages($langs->trans("CustomerSelectedDoesntHaveOwedInvoice", $societe->name), null, 'warnings');
		} else {
			if (empty($societe->email)) {
				setEventMessages($langs->trans("CustomerSelectedDoesntHaveEmailAddress", $societe->name), null, 'warnings');
			} else {
				$email_to = $societe->email;
				$actionsCustomersOwed = new ActionsCustomersOwed($db);
				if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative) < 0) {
					$errorMessage = $langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans($actionsCustomersOwed->error);
					setEventMessages($errorMessage, $actionsCustomersOwed->errors, 'errors');
					dol_syslog("CustomersOwed module SendByMail -> KO : " . $actionsCustomersOwed->error, LOG_ERR);
				} else {
					setEventMessages($langs->trans("CustomersOwedSendByMailOK"), null);
				}
			}
		}
	} else {
		setEventMessages($langs->trans("NoCustomerSelected"), null, 'warnings');
	}
}

// send_mail_to_user
if ($action == 'send_mail_to_user' && $genallowed) {
	if ($only_user > 0) {
		$userstatic = new User($db);
		if ($userstatic->fetch($only_user) <= 0) { accessforbidden(); }
		$userstatic->getrights();
		if (empty($userstatic->email)) {
			setEventMessages($langs->trans("UserSelectedDoesntHaveEmailAddress", $userstatic->firstname . " " . $userstatic->lastname), null, 'warnings');
		} else {
			$email_to = $userstatic->email;
			$actionsCustomersOwed = new ActionsCustomersOwed($db);
			if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative, $userstatic) < 0) {
				$errorMessage = $langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans($actionsCustomersOwed->error);
				setEventMessages($errorMessage, $actionsCustomersOwed->errors, 'errors');
				dol_syslog("CustomersOwed module SendByMail -> KO : " . $actionsCustomersOwed->error, LOG_ERR);
			} else {
				setEventMessages($langs->trans("CustomersOwedSendByMailOK"), null);
			}
		}
	} else {
		setEventMessages($langs->trans("NoUserSelected"), null, 'warnings');
	}
}

// send_mail_to_salesrepresentative
if ($action == 'send_mail_to_salesrepresentative' && $genallowed) {
	if ($only_salesrepresentative > 0) {
		$salesrepresentativestatic = new User($db);
		if ($salesrepresentativestatic->fetch($only_salesrepresentative) <= 0) { accessforbidden(); }
		$salesrepresentativestatic->getrights();
		if (empty($salesrepresentativestatic->email)) {
			setEventMessages($langs->trans("UserSelectedDoesntHaveEmailAddress", $salesrepresentativestatic->firstname . " " . $salesrepresentativestatic->lastname), null, 'warnings');
		} else {
			$email_to = $salesrepresentativestatic->email;
			$actionsCustomersOwed = new ActionsCustomersOwed($db);
			if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative, $salesrepresentativestatic) < 0) {
				$errorMessage = $langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans($actionsCustomersOwed->error);
				setEventMessages($errorMessage, $actionsCustomersOwed->errors, 'errors');
				dol_syslog("CustomersOwed module SendByMail -> KO : " . $actionsCustomersOwed->error, LOG_ERR);
			} else {
				setEventMessages($langs->trans("CustomersOwedSendByMailOK"), null);
			}
		}
	} else {
		setEventMessages($langs->trans("NoUserSelected"), null, 'warnings');
	}
}

$entityLabels = array();
$availableEntities = array_map('intval', explode(',', getEntity('invoice')));
if (isModEnabled('multicompany')) {
	$resEntities = $db->query('SELECT rowid, label FROM '.MAIN_DB_PREFIX.'entity WHERE rowid IN ('.implode(',', $availableEntities).')');
	if (!$resEntities) { accessforbidden($langs->trans('CustomersOwedDirectoryUnavailable')); }
	while (is_object($entityRow = $db->fetch_object($resEntities))) { $entityLabels[(int) $entityRow->rowid] = $entityRow->label; }
	$db->free($resEntities);
}
$showEntities = count($entityLabels) > 1;

if (in_array($action, array('send_mail_to_customer', 'send_mail_to_user', 'send_mail_to_salesrepresentative'), true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Location: '.$urlsource);
	exit();
}
// 'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
// 'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
// 'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>0),

/* ************************************************************************** */
/* */
/* View */
/* */
/* ************************************************************************** */

$form = new Form($db);
$formfile = new FormFile($db);

$arrayfields['f.entity']['enabled'] = $showEntities;
$varpage = $contextpage;
// Preserve the optional choices from the former selector when opening the full native list for the first time.
$legacyPreference = 'MAIN_SELECTEDFIELDS_'.$_SERVER['PHP_SELF'];
$newPreference = 'MAIN_SELECTEDFIELDS_'.$contextpage;
if (empty($user->conf->{$newPreference}) && !empty($user->conf->{$legacyPreference})) {
	$legacyFields = explode(',', $user->conf->{$legacyPreference});
	foreach ($arrayfields as $field => &$definition) {
		if (!in_array($field, array('f.entity', 'f.ref', 's.nom', 'f.date_lim_reglement', 'f.total_ttc', 'am', 'f.fk_statut'), true)) {
			$definition['checked'] = in_array($field, $legacyFields, true) ? 1 : 0;
		}
	}
	unset($definition);
}
$selectorOnLeft = getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN');
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $selectorOnLeft);
$visibleFields = array_filter($arrayfields, static function ($field) { return !empty($field['checked']); });

llxHeader("", $langs->trans("ModuleCustomersOwedName"));

// The native list bar carries the single report title and invoice pictogram.

print '<div class="fichecenter">';

/*
 * Unpaid customers invoices
 */
print '<div class="attacharea">';

if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
	print '<form method="POST" name="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	foreach ($searchFilters as $field => $value) {
		if ($value === '' || isset($visibleFields[$field])) { continue; }
		$name = 'search_'.str_replace('.', '_', $field);
		if ($arrayfields[$field]['search'] === 'date') {
			foreach (array('month' => '%m', 'day' => '%d', 'year' => '%Y') as $suffix => $format) {
				print '<input type="hidden" name="'.$name.$suffix.'" value="'.dol_print_date($value, $format).'">';
			}
		} else { print '<input type="hidden" name="'.$name.'" value="'.dol_escape_htmltag((string) $value).'">'; }
	}

	if (!isset($visibleFields['f.entity'])) {
		foreach ($customersOwedReport->selectedEntities as $entity) { print '<input type="hidden" name="search_entities[]" value="'.(int) $entity.'">'; }
	}
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="exclude_dp" value="'.(int) $exclude_dp.'">';
	print '<input type="hidden" name="only_late" value="'.(int) $only_late.'">';
	$paginationParams = '&socid='.(int) $socid.'&exclude_dp='.(int) $exclude_dp.'&only_late='.(int) $only_late
		.'&only_user='.(int) $only_user.'&only_salesrepresentative='.(int) $only_salesrepresentative
		.'&date_end_year='.(int) $date_end_year.'&date_end_month='.(int) $date_end_month.'&date_end_day='.(int) $date_end_day.$entityParams;
	// Native navigation expects one extra row when another page exists.
	$navigationRows = min($limit + 1, max(0, $customersOwedReport->total_rows - $offset));
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $paginationParams.$searchParams, $sortfield, $sortorder, '',
		$navigationRows, $customersOwedReport->total_rows, 'bill', 0, '', '', $limit);
	print '<div class="fichecenter">';

	/*
	 * Filtre
	 */
	/*
	 * print '<div class="attacharea fichecenter">';
	 * $title = $langs->trans('Filter');
	 * print '<div class="centpercent notopnoleftnoright" style="margin-bottom: 6px;">';
	 * print '<tr><td class="nobordernopadding valignmiddle"><div class="titre inline-block">'.$title.'</div></td></tr>';
	 * print '</div>';
	 */

	print '<div class="div-table-responsive-no-min">';
	print '<table class="tagtable liste centpercent" id="customersowed-parameters">';

	// Date
	print '<tr class="oddeven">';
	print '<td class="">' . $langs->trans('Date') . '</td>';
	print '<td>';
	if ((float) DOL_VERSION >= 9) {
		print $form->selectDate($date_end ? $date_end : '', 'date_end_');
	} else {
		print $form->select_date($date_end ? $date_end : '', 'date_end_');
	}
	print '</td>';
	print '</tr>';

	// Exclude down payment invoices
	print '<tr class="oddeven">';
	print '<td class="">' . $langs->trans('ExcludeDownpaymentInvoices') . ' </td>';
	$switchUrl = preg_replace('/([?&])exclude_dp=[^&]*/', '$1exclude_dp='.($exclude_dp > 0 ? '0' : '1'), $urlsource);
	print '<td><a href="'.dol_escape_htmltag($switchUrl).'">'.img_picto($langs->trans($exclude_dp > 0 ? 'Enabled' : 'Disabled'), $exclude_dp > 0 ? 'on' : 'off').'</a></td>';
	print '</tr>';

	// Only late payment invoices
	print '<tr class="oddeven">';
	print '<td class="">' . $langs->trans('OnlyLatePaymentInvoices') . ' </td>';
	$switchUrl = preg_replace('/([?&])only_late=[^&]*/', '$1only_late='.($only_late > 0 ? '0' : '1'), $urlsource);
	print '<td><a href="'.dol_escape_htmltag($switchUrl).'">'.img_picto($langs->trans($only_late > 0 ? 'Enabled' : 'Disabled'), $only_late > 0 ? 'on' : 'off').'</a></td>';
	print '</tr>';

	// Thirdparty
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans('OnlyInvoicesToThisCustomer') . '</td>';
	print '<td>';
	if (intval(DOL_VERSION) < 18) {
		print $form->select_company($socid, 'socid', '(s.client = 1 OR s.client = 3) AND status=1', 'AllCustomers', 0, 0, null, 0, 'minwidth300');
	}
	else {
		print $form->select_company($socid, 'socid', '(s.client:=:1)', 'SelectThirdParty', 'customer', 0, null, 0, 'minwidth300');
	}
	if ($genallowed) {
		print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_user_button" name="action" value="send_mail_to_customer" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
	}
	print '</td>';
	print '</tr>';

	if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY')) {
		// Only invoices created by
		print '<tr class="oddeven">';
		print '<td class="">' . $langs->trans('OnlyInvoicesCreatedBy') . ' </td>';
		print '<td>';
		print $form->select_dolusers($only_user, 'only_user', 1);
		if ($genallowed) {
			print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_user_button" name="action" value="send_mail_to_user" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
		}
		print '</td>';
		print '</tr>';
	}

	if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE')) {
		// only invoices whose sales representative is
		print '<tr class="oddeven">';
		print '<td class="">' . $langs->trans('OnlyInvoicesWhoseSalesRepresentativeIs') . ' </td>';
		print '<td>';
		print $form->select_dolusers($only_salesrepresentative, 'only_salesrepresentative', 1);
		if ($genallowed) {
			print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_salesrepresentative_button" name="action" value="send_mail_to_salesrepresentative" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
		}
		print '</td>';
		print '</tr>';
	}

	print '</table></div>';
	print '<div class="center"><input type="submit" name="customersowed_refresh_button" class="button" value="' . $langs->trans('CustomersowedInvoicesListActualize') . '"></div>';

	$num = $customersOwedReport->lines_count;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="tagtable nobottomiftotal liste centpercent" id="customersowed-list">';
	print '<tr class="liste_titre_filter">';
	if ($selectorOnLeft) { print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons('left').'</td>'; }
	foreach ($visibleFields as $field => $definition) {
		$name = 'search_'.str_replace('.', '_', $field);
		$value = $searchFilters[$field] ?? '';
		print '<td class="liste_titre '.($definition['css'] ?? '').'">';
		switch ($definition['search']) {
			case 'entities': print $form->multiselectarray('search_entities', $entityLabels, $customersOwedReport->selectedEntities, 0, 0, 'minwidth100'); break;
			case 'date': print $form->selectDate($value, $name, 0, 0, 1, '', 1, 0); break;
			case 'payment': print $form->select_types_paiements($value, $name, '', 0, 1, 1, 0, 1, 'maxwidth100', 1); break;
			case 'select':
				$options = $field === 'f.type' ? array(Facture::TYPE_STANDARD => $langs->trans('InvoiceStandard'), Facture::TYPE_REPLACEMENT => $langs->trans('InvoiceReplacement'), Facture::TYPE_CREDIT_NOTE => $langs->trans('InvoiceAvoir'), Facture::TYPE_DEPOSIT => $langs->trans('InvoiceDeposit'), Facture::TYPE_SITUATION => $langs->trans('InvoiceSituation'))
					: array(Facture::STATUS_VALIDATED => $langs->trans('BillStatusNotPaid'), Facture::STATUS_CLOSED => $langs->trans('BillStatusPaid'));
				print $form->selectarray($name, $options, $value, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
				print ajax_combobox($name); break;
			default: print '<input class="flat maxwidth75" type="text" name="'.$name.'" aria-label="'.dol_escape_htmltag($langs->trans($definition['label'])).'" value="'.dol_escape_htmltag((string) $value).'">';
		}
		print '</td>';
	}
	$parameters = array('arrayfields' => $arrayfields);
	$hookmanager->executeHooks('printFieldListOption', $parameters);
	print $hookmanager->resPrint;
	if (!$selectorOnLeft) { print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons().'</td>'; }
	print '</tr><tr class="liste_titre">';
	if ($selectorOnLeft) { print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'class="center maxwidthsearch"'); }
	foreach ($visibleFields as $field => $definition) {
		print_liste_field_titre($langs->trans($definition['label']), $_SERVER['PHP_SELF'], $definition['sql'] ?? $field, '', $param, '', $sortfield, $sortorder, $definition['css'] ?? '');
	}
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$hookmanager->executeHooks('printFieldListTitle', $parameters);
	print $hookmanager->resPrint;
	if (!$selectorOnLeft) { print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'class="center maxwidthsearch"'); }
	print '</tr>';

	if ($num > 0) {
		if ($num) {
			$societestatic = new Societe($db);
			$facturestatic = new Facture($db);
			$total_ttc = $total_amount = $total_ht = $total_vat = $total_rtp = 0;
			$i = 0;
			while ($i < $num) // && $i < $conf->liste_limit
			{
				$obj = $customersOwedReport->lines[$i];

				$societestatic->id = $obj->fk_soc;
				$societestatic->name = $obj->name;
				$societestatic->code_client = $obj->code_client;
				$societestatic->phone = $obj->phone;
				$societestatic->address = $obj->address;
				$societestatic->zip = $obj->zip;
				$societestatic->town = $obj->town;
				$societestatic->email = $obj->email;

				$facturestatic->ref = $obj->facnumber;
				$facturestatic->id = $obj->id; $facturestatic->entity = $obj->entity; $facturestatic->type = $obj->type;
				$facturestatic->fk_soc = $obj->fk_soc;
				$facturestatic->statut = $obj->fk_statut;
				$facturestatic->date_lim_reglement = $db->jdate($obj->date_lim_reglement);
				$facturestatic->total_ttc = $obj->total_ttc;

				/*
				 * $paiement = $facturestatic->getSommePaiement();
				 * $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
				 * $totaldeposits = $facturestatic->getSumDepositsUsed();
				 * $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
				 */
				$totalpay = $obj->am;

				$remaintopay = price2num($obj->total_ttc - $obj->am, 'MT');

				print '<tr class="oddeven">';
				if ($selectorOnLeft) { print '<td></td>'; }
				foreach ($visibleFields as $field => $definition) {
					if ($field === 'f.ref') {
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $facturestatic->getNomUrl(1, '');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($facturestatic->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$pdfinvoicefilename = dol_sanitizeFileName($obj->facnumber);
				$pdfinvoicefilesdir = '';
				if (isset($conf->facture->multidir_output[(int) $obj->entity])
					&& is_string($conf->facture->multidir_output[(int) $obj->entity])
					&& trim($conf->facture->multidir_output[(int) $obj->entity]) !== '') {
					$pdfinvoicefilesdir = getMultidirOutput($facturestatic, 'facture', 1);
				}
				// $urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->id.'&sortfield='.$sortfield.'&sortorder='.$sortorder.'&date_end_month='.$date_end_month.'&date_end_day='.$date_end_day.'&date_end_year='.$date_end_year;
				if (is_string($pdfinvoicefilesdir) && $pdfinvoicefilesdir !== '' && strpos($pdfinvoicefilesdir, 'error-') !== 0) { print $formfile->getDocumentsLink($facturestatic->element, $pdfinvoicefilename, $pdfinvoicefilesdir); }
				print '</td></tr></table>';
				print '</td>';

						continue;
					}
					print '<td class="'.($definition['css'] ?? '').'">';
					switch ($field) {
						case 'f.entity': print '<div class="refidno multicompany-entity-card-container"><span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.dol_escape_htmltag($entityLabels[(int) $obj->entity] ?? '').'</span></div>'; break;
						case 's.nom': print $societestatic->getNomUrl(1, 'customer', 44); break;
						case 'p.ref': print dol_escape_htmltag($obj->project_ref ?? ''); break;
						case 'p.title': print dol_escape_htmltag($obj->project_label ?? ''); break;
						case 's.zip': print dol_escape_htmltag($obj->zip ?? ''); break;
						case 's.town': print dol_escape_htmltag($obj->town ?? ''); break;
						case 'f.ref_client': print dol_escape_htmltag($obj->ref_client ?? ''); break;
						case 'f.type': print $facturestatic->getLibType(); break;
						case 'f.fk_mode_reglement': $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1); break;
						case 'f.date': print dol_print_date($db->jdate($obj->date), 'day'); break;
						case 'f.datec': print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser'); break;
						case 'f.tms': print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser'); break;
						case 'f.date_lim_reglement': print dol_print_date($db->jdate($obj->date_lim_reglement), 'day'); break;
						case 'am': print price($obj->am); break;
						case 'rtp': print price($remaintopay); break;
						case 'f.fk_statut': print $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 6, $obj->am); break;
						default: $property = substr($field, 2); print price($obj->{$property});
					}
					print '</td>';
				}
				// Hook columns follow the same order as their headings.
				$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);
				print $hookmanager->resPrint;
				if (!$selectorOnLeft) { print '<td></td>'; }
				print '</tr>';

				// Totals calc
				$total_ttc += $obj->total_ttc;
				$total_ht += $obj->total_ht;
				$total_vat += $obj->total_vat;
				$total_amount += $obj->am;
				$total_rtp += $remaintopay;

				$i++;
			}

			print '<tr class="liste_total">';
			if ($selectorOnLeft) { print '<td></td>'; }
			$pageTotals = array('f.total_ttc' => $total_ttc, 'f.total_ht' => $total_ht, 'f.total_vat' => $total_vat, 'am' => $total_amount, 'rtp' => $total_rtp);
			$firstColumn = true;
			foreach ($visibleFields as $field => $definition) {
				print '<td class="'.($definition['css'] ?? '').'">';
				if (isset($pageTotals[$field])) { print price($pageTotals[$field]); }
				elseif ($firstColumn) { print $langs->trans('Total').' ('.$langs->trans('Page').')'; }
				print '</td>';
				$firstColumn = false;
			}
			if (!$selectorOnLeft) { print '<td></td>'; }
			print '</tr>';

		} else {
			$colspan = 1 + count($visibleFields);
			print '<tr class="oddeven"><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</td></tr>';
		}
		print '</table>';
		print '</div>';
		if (empty($errorMessage)) {
			// setEventMessages($langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y"), price($total_ttc-$total_amount)), null, 'mesgs');
			if ($socid && $socid > 0) {
				$societe = new Societe($db);
				$societe->fetch($socid);
				$societe_name = dol_sanitizeFileName($societe->name);
				setEventMessages($langs->trans("RemainderToTakeForCompanyAtDateOf", $societe_name, dol_print_date($customersOwedDate, "day"), price($customersOwedReport->getTotalCustomersOwed())), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "day"), price($customersOwedReport->getTotalCustomersOwed())), null, 'mesgs');
			}
		}
		else {
			setEventMessages($errorMessage, null, 'error');
		}
	} else {
		print '<tr><td colspan="'.(1 + count($visibleFields)).'" class="opacitymedium">' . $langs->trans('NoRecordFound') . '</td></tr>';
		print '</table>';
		print '</div>';
		//setEventMessages("NoBalanceDue", null, 'mesgs');
	}
	print '</div>';

	print "</form>";
}

/*
 * PDF Documents
 */
print '<a name="builddoc"></a>'; // ancre
print '<div class="">';

print '<div class="attacharea fichecenter">';

$hidegeneratedfilelistifempty = 1;
if ($massaction == 'builddoc' || $action == 'remove_file' || empty($action)) {
	$hidegeneratedfilelistifempty = 0;
}

// Columns to add
$columnsToAdd = "";
foreach ($arrayfields as $field => $value) {
	if ($value['checked']) {
		$columnsToAdd .= $field . "$";
	}
}

$urlsource = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&columns=' . $columnsToAdd . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative.$searchParams;

$urlsource .= $entityParams;
if ($user->hasRight('customersowed', 'read')) {
	$allowgenifempty = 1;
	$forcenomultilang = 0;
	$iconPDF = 0;
	$notused = 0;
	$noform = 0;
	$param = '';
	print $formfile->showdocuments('customersowed', 'private/'.((int) $user->id), $pdfreportfilesdir, $urlsource, $genallowed, $delallowed, $model, $allowgenifempty, $forcenomultilang, $iconPDF, $notused, $noform, $param, $langs->trans("PdfDocuments"), $langs->trans("CustomersowedPdfDocumentsGenerate"), $langs->defaultlang, '', null, $hidegeneratedfilelistifempty);
	$somethingshown = $formfile->numoffiles;
}
print '</div>';

print '</div>';

llxFooter();

$db->close();
