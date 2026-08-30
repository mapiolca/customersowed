<?php
/*
 * Copyright (C) 2004-2017 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Laurent BOUQUET <dolibarr@joliciel.fr>
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
 * \file customersowed/admin/setup.php
 * \ingroup customersowed
 * \brief CustomersOwed setup page.
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

global $conf, $langs, $user;

// Libraries
dol_include_once('/customersowed/class/customersowed.class.php');
dol_include_once('/customersowed/class/actions_customersowed.class.php');
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/customersowed.lib.php';
require_once '../class/html.formcustomersowed.class.php';

// Translations
$langs->loadLangs(array(
	"admin",
	"customersowed@customersowed"));

// Access control
if (! $user->admin)
	accessforbidden();

// Parameters
$customersOwedDate = dol_now();
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$email_from = GETPOST('email_from', 'alpha');
$exclude_dp = GETPOST('exclude_dp', 'int');
$only_late = GETPOST('only_late', 'int');
$only_user = GETPOST('only_user', 'int');
if (empty($socid)) {
	$exclude_dp = $conf->global->CUSTOMERSOWEDREPORT_EXCLUDE_DP;
	$only_late = $conf->global->CUSTOMERSOWEDREPORT_ONLY_LATE;
	if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY) {
		$only_user = $user->id;
	}
}
// Mail body template
$langs_trans_CustomersowedMessageMailBody = str_replace('<br/>', '
', $langs->trans('CustomersowedMessageMailBody'));
$langs_trans_CustomersowedMessageMailBody = str_replace('&lt;br/>', '
', $langs_trans_CustomersowedMessageMailBody);
$email_body_template = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE ? $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE : $langs_trans_CustomersowedMessageMailBody; 

$type = 'customersowed';

$arrayofparameters = array(
	'CUSTOMERSOWEDREPORT_EXCLUDE_DP' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'),
	'CUSTOMERSOWEDREPORT_ONLY_LATE' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'),
	'INVOICE_DEFAULT_UNPAYED_SORT_ORDER' => array(
		'css' => 'minwidth10',
		'enabled' => 1),
	'CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'),
	'CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME' => array(
		'css' => 'minwidth10',
		'enabled' => 1),
	'CUSTOMERSOWEDREPORT_ONLY_CREATED_BY' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'),
	'CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'),
	'CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL' => array(
		'css' => 'minwidth10',
		'enabled' => 1,
		'type' => 'checkbox'));
// 'CUSTOMERSOWEDREPORT_PDF_HEADER_TABLE_BACKGROUND_COLOR'=>array('css'=>'minwidth100','enabled'=>1),
// 'CUSTOMERSOWEDREPORT_PDF_HEADER_TABLE_TITLE_TEXT_COLOR'=>array('css'=>'minwidth100','enabled'=>1),
// 'CUSTOMERSOWEDREPORT_USE_OLD_NUMBERING_RULE'=>array('css'=>'minwidth100','enabled'=>1),
// 'CUSTOMERSOWEDREPORT_PREFIX'=>array('css'=>'minwidth100','enabled'=>1),
// 'CUSTOMERSOWEDREPORT_SAND_MASK'=>array('css'=>'minwidth100','enabled'=>1),

$dirmodels = array_merge(array(
	'/'), (array) $conf->modules_parts['models']);
$pdfreportfilesdir = $conf->customersowed->dir_output;

foreach ($arrayofparameters as $key => $val) {
	if ($val['type'] == 'checkbox') {
		// echo $key . " = '" . GETPOST($key) . "'<br/>";
		if (empty(GETPOST($key))) {
			$_POST["$key"] = 0;
		} else {
			$_POST["$key"] = 1;
		}
	}
}

$customersOwedReport = new CustomersOwed($db, $customersOwedDate, $exclude_dp, $only_late, $only_user);
$actionsCustomersOwed = new ActionsCustomersOwed($db);

$sortfield = 'f.date_lim_reglement';
$sortorder = 'ASC';

$customersOwedReport->fetch_lines($socid, $sortfield, $sortorder);
$title = $customersOwedReport->title;

$model = $conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF;

if (! isset($conf->customersowed) || ! isset($conf->customersowed->enabled)) {
	$conf->customersowed = new stdClass();
	$conf->customersowed->enabled = 0;
	print "Module '" . $langs->trans("ModuleCustomersOwedName") . "' not enabled.";
} else {

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

	if ((float) DOL_VERSION >= 6) {
		include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
	}

	if ((float) DOL_VERSION < 8) {
		if ($action == 'update' && GETPOST('INVOICE_DEFAULT_UNPAYED_SORT_ORDER') >= 0) {
			$res = dolibarr_set_const($db, "INVOICE_DEFAULT_UNPAYED_SORT_ORDER", GETPOST('INVOICE_DEFAULT_UNPAYED_SORT_ORDER'), 'chaine', 0, '', $conf->entity);
			if (! $res > 0)
				$error ++;
		}
		if ($action == 'update' && GETPOST('CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN') >= 0) {
			$res = dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN", GETPOST('CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN'), 'chaine', 0, '', $conf->entity);
			if (! $res > 0)
				$error ++;
		}
		if ($action == 'update' && GETPOST('CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME') >= 0) {
			$res = dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME", GETPOST('CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME'), 'chaine', 0, '', $conf->entity);
			if (! $res > 0)
				$error ++;
		}
	}

	if ($action == 'update') {
		if (isset($email_from)) {
			dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM", $email_from, 'chaine', 0, '', $conf->entity);
		}
		if (null !== GETPOST('email_subject_template')) {
			dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE", GETPOST('email_subject_template'), 'chaine', 0, '', $conf->entity);
		}
		if (null !== GETPOST('email_body_template')) {
			dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE", GETPOST('email_body_template'), 'chaine', 0, '', $conf->entity);
		}
	}
	
	
	

	if ($action == 'specimen') {
		$customersOwedReport->initAsSpecimen();

		$model = GETPOST('module', 'alpha');

		// Search template files
		$file = '';
		$classname = '';
		$filefound = 0;
		$dirmodels = array_merge(array(
			'/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$file = dol_buildpath($reldir . "core/modules/$type/doc/pdf_" . $model . ".modules.php", 0);
			if (file_exists($file)) {
				$filefound = 1;
				$classname = "pdf_" . $model;
				break;
			}
		}

		if ($filefound) {
			// setEventMessages($file, null, 'mesgs');
			require_once $file;

			$module = new $classname($db);
			if ($module->write_file($customersOwedReport, $pdfreportfilesdir . '/' . 'SPECIMEN.pdf', $langs) > 0) {
				header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=" . $type . "&file=SPECIMEN.pdf");
				return;
			} else {
				setEventMessages($module->error, $module->errors, 'errors');
				dol_syslog($module->error, LOG_ERR);
			}
		} else {
			setEventMessages($langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans("ErrorModuleNotFound"), null, 'errors');
			dol_syslog($langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans("ErrorModuleNotFound"), LOG_ERR);
		}
	} // Activate a model
	else if ($action == 'set') {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	} else if ($action == 'del') {
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			if ($conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF == "$value")
				dolibarr_del_const($db, 'CUSTOMERSOWEDREPORT_ADDON_PDF', $conf->entity);
		}
	} // Set default model
	else if ($action == 'setdoc') {
		if (dolibarr_set_const($db, "CUSTOMERSOWEDREPORT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
			// La constante qui a ete lue en avant du nouveau set
			// on passe donc par une variable pour avoir un affichage coherent
			$conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF = $value;
		}

		// On active le modele
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}

	if ($action == "builddoc") {

		if ($actionsCustomersOwed->generateCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder) < 0) {
			setEventMessages($langs->trans("CustomersOwedTestBuilddoc") . " : " . $langs->trans($actionsCustomersOwed->error), $actionsCustomersOwed->errors, 'errors');
			dol_syslog("CustomersOwed module build PDF -> KO : " . $actionsCustomersOwed->error, LOG_ERR);
		} else {
			setEventMessages($langs->trans("FileGenerated"), null);
		}
		$urltoredirect = $_SERVER['PHP_SELF'] . '?model=' . $model . '&socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user;
		$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
		$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop
		header('Location: ' . $urltoredirect . '#builddoc');
		exit();
	}

	if ($action == "sendmail") {

		if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user) < 0) {
			setEventMessages($langs->trans("CustomersOwedSendByMailKO") . " : " . $langs->trans($actionsCustomersOwed->error), $actionsCustomersOwed->errors, 'errors');
			dol_syslog("CustomersOwed module SendByMail -> KO : " . $actionsCustomersOwed->error, LOG_ERR);
		} else {
			setEventMessages($langs->trans("CustomersOwedSendByMailOK"), null);
		}

		$urltoredirect = $_SERVER['PHP_SELF'] . '?model=' . $model . '&socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user;
		$urltoredirect = preg_replace('/#sendmail$/', '', $urltoredirect);
		$urltoredirect = preg_replace('/action=sendmail&?/', '', $urltoredirect); // To avoid infinite loop

		header('Location: ' . $urltoredirect . '#sendmail');
		exit();
	}

	/* ************************************************************************** */
	/* */
	/* View */
	/* */
	/* ************************************************************************** */

	$page_name = "CustomersOwedSetup";
	$formCustomersowed = new FormCustomersowed($db);
	llxHeader('', $langs->trans($page_name));

	// Subheader
	$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

	print load_fiche_titre($langs->trans($page_name), $linkback, 'object_customersowed@customersowed');

	// Configuration header
	$head = customersowedAdminPrepareHead();
	dol_fiche_head($head, 'settings', '', - 1, "customersowed@customersowed");

	/*
	 * Document templates generators
	 */
	print load_fiche_titre($langs->trans("CustomersOwedPDFModules"), '', '');

	// Load array def with activated templates
	$type = 'customersowed';
	$def = array();
	$sql = "SELECT nom";
	$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
	$sql .= " WHERE type = '" . $type . "'";
	$sql .= " AND entity = " . $conf->entity;
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num_rows = $db->num_rows($resql);
		while ($i < $num_rows) {
			$array = $db->fetch_array($resql);
			array_push($def, $array[0]);
			$i ++;
		}
	} else {
		dol_print_error($db);
	}

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
	print '<td align="center" width="60">' . $langs->trans("Default") . '</td>';
	print '<td align="center" width="32">' . $langs->trans("ShortInfo") . '</td>';
	print '<td align="center" width="32">' . $langs->trans("Preview") . '</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		foreach (array(
			'',
			'/doc') as $valdir) {
			$dir = dol_buildpath($reldir . "core/modules/$type" . $valdir);
			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						$filelist[] = $file;
					}
					closedir($handle);
					arsort($filelist);

					foreach ($filelist as $file) {

						if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
							if (file_exists($dir . '/' . $file)) {

								$name = substr($file, 4, dol_strlen($file) - 16);
								$classname = substr($file, 0, dol_strlen($file) - 12);

								require_once $dir . '/' . $file;
								$module = new $classname($db);

								$modulequalified = 1;
								if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
									$modulequalified = 0;
								if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
									$modulequalified = 0;

								if ($modulequalified) {
									print '<tr class="oddeven"><td width="100">';
									print(empty($module->name) ? $name : $module->name);
									print "</td><td>\n";
									if (method_exists($module, 'info'))
										print $module->info($langs);
									else
										print $module->description;
									print '</td>';

									// Active
									if (in_array($name, $def)) {
										print '<td align="center">' . "\n";
										print '<a href="' . $_SERVER["PHP_SELF"] . '?action=del&value=' . $name . '">';
										print img_picto($langs->trans("Enabled"), 'switch_on');
										print '</a>';
										print '</td>';
									} else {
										print "<td align=\"center\">\n";
										print '<a href="' . $_SERVER["PHP_SELF"] . '?action=set&value=' . $name . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '">' . img_picto($langs->trans("YouMustEnableOneModule"), 'switch_off') . '</a>';
										print "</td>";
									}

									// Defaut
									print "<td align=\"center\">";
									if ($conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF == "$name") {
										print img_picto($langs->trans("Default"), 'on');
									} else {
										print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setdoc&value=' . $name . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("SetAsDefault"), 'off') . '</a>';
									}
									print '</td>';

									// Info
									$htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
									$htmltooltip .= '<br/>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
									if ($module->type == 'pdf') {
										$htmltooltip .= '<br/>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
									}
									$htmltooltip .= '<br/><br/><u>' . $langs->trans("FeaturesSupported") . ':</u>';
									$htmltooltip .= '<br/>' . $langs->trans("Logo") . ': ' . yn($module->option_logo, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("PaymentMode") . ': ' . yn($module->option_modereg, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("PaymentConditions") . ': ' . yn($module->option_condreg, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("Discounts") . ': ' . yn($module->option_escompte, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("CreditNote") . ': ' . yn($module->option_credit_note, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("MultiLanguage") . ': ' . yn($module->option_multilang, 1, 1);
									$htmltooltip .= '<br/>' . $langs->trans("WatermarkOnDraftInvoices") . ': ' . yn($module->option_draft_watermark, 1, 1);

									print '<td align="center">';
									print $form->textwithpicto('', $htmltooltip, 1, 0);
									print '</td>';

									// Preview
									print '<td align="center">';
									if ($module->type == 'pdf') {
										print '<a href="' . $_SERVER["PHP_SELF"] . '?action=specimen&module=' . $name . '">' . img_object($langs->trans("Preview"), 'bill') . '</a>';
									} else {
										print img_object($langs->trans("PreviewNotAvailable"), 'generic');
									}
									print '</td>';

									print "</tr>\n";
								}
							}
						}
					}
				}
			}
		}
	}
	print '</table>';

	/*
	 * Folder
	 */
	print load_fiche_titre($langs->trans("PathToDocuments"), '', '');

	print '<table class="noborder" width="100%">' . "\n";
	print '<tr class="liste_titre">' . "\n";
	print '<td>' . $langs->trans("Name") . '</td>' . "\n";
	print '<td>' . $langs->trans("Value") . '</td>' . "\n";
	print "</tr>\n";
	print '<tr ' . $bc[false] . '>' . "\n";
	print '<td width="140">' . $langs->trans("PathDirectory") . '</td>' . "\n";
	print '<td>' . $conf->customersowed->dir_output . '</td>' . "\n";
	print '</tr>' . "\n";
	print "</table>\n";

	print load_fiche_titre($langs->trans("CustomersOwedPdfParameters"), '', '');

	if ($action == 'edit') {

		// /////////////////////////////////////////////////////////
		// Pdf parameters

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="">' . $langs->trans("Parameter") . '</td>';
		print '<td>' . $langs->trans("Value") . '</td>';
		// print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr>';

		foreach ($arrayofparameters as $key => $val) {

			if ($val['section'] == 'yes') {
				$class = "thumbstat";
			} else {
				$class = "";
			}
			print '<tr class="oddeven ' . $class . '"><td>';
			print $form->textwithpicto($langs->trans($key), $langs->trans($key . 'Tooltip'));
			print '</td>';
			if ($val['type'] == 'password') {
				print '<td><input type="password" name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
			} else {
				if ($val['type'] == 'checkbox') {

					// if ($conf->global->$key > 0) {
					// print '<td><input type="checkbox" name="' . $key . '" checked></td></tr>';
					// } else {
					// print '<td><input type="checkbox" name="' . $key . '" ></td></tr>';
					// }
					print '<td>' . $formCustomersowed->select_OnOff($key, $conf->global->$key) . '</td></tr>';
				} else {
					print '<td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . $conf->global->$key . '"></td></tr>';
				}
			}
		}

		print '</table>';

		// /////////////////////////////////////////////////////////
		// Sending by email
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';
		print '<div class="titre inline-block">' . $langs->trans("CustomersOwedSendingMailWithPdf") . '<div>';

		// Mail from address
		$email_from = "";
		if (! empty($conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM)) {
			$email_from = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM;
		} else {
			if ((empty($email_from)) && ! empty($conf->global->PDF_EMAIL_FROM)) {
				$email_from = $conf->global->PDF_EMAIL_FROM;
			} else {
				if ((empty($email_from)) && ! empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
					$email_from = $conf->global->MAIN_INFO_SOCIETE_MAIL;
				} else {
					if ((empty($email_from)) && ! empty($conf->global->MAIN_MAIL_EMAIL_FROM)) {
						$email_from = $conf->global->MAIN_MAIL_EMAIL_FROM;
					} else {
						if ((empty($email_from)) && ! empty($conf->global->MAILING_EMAIL_FROM)) {
							$email_from = $conf->global->MAILING_EMAIL_FROM;
						} else {
							if ((empty($email_from)) && ! empty($user->email)) {
								$email_from = $user->email;
							}
						}
					}
				}
			}
		}
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedSendMailFrom"), $langs->trans('CustomersowedSendMailFromHelp')) . '</td>';
		print '<td class="left">';
		print '<input type="text" name="email_from" value="' . $email_from . '" size="20" ></td>';
		print '</tr>';

		// Mail subject template
		$email_subject_template = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE ? $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE : $langs->trans('CustomersowedMessageMailSubject');
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedMessageMailSubjectLabel"), $langs->trans('CustomersowedMessageMailSubjectHelp')) . '</td>';
		print '<td class="left">';
		print '<input type="text" name="email_subject_template" value="' . $email_subject_template . '" size="90" ></td>';
		print '</tr>';

		// Mail body template
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedMessageMailBodyLabel"), $langs->trans('CustomersowedMessageMailBodyHelp')) . '</td>';
		print '</td><td>';
		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		$htmlname = 'email_body_template';
		$content = $email_body_template;
		$width = 600;
		$height = 400;
		$toolbarname = 'Basic';
		$toolbarlocation = 'In';
		$toolbarstartexpanded = false;
		$uselocalbrowser = true;
		$okforextendededitor = false;
		$rows = 0;	
		$cols = 0;
		$readonly = 0;
		$doleditor = new DolEditor($htmlname, $content, $width, $height, $toolbarname, $toolbarlocation, $toolbarstartexpanded, $uselocalbrowser, $okforextendededitor, $rows, $cols, $readonly);
		$doleditor->Create();
		print '</td>';
		print '</tr>';

		print '</table>';

		// // /////////////////////////////////////////////////////////
		// Buttons
		print '<br/><div class="center">';
		print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
		print '</div>';

		print '</form>';
	} else {

		// /////////////////////////////////////////////////////////
		// Pdf parameters

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

		foreach ($arrayofparameters as $key => $val) {
			if ($val['section'] == 'yes') {
				$class = "thumbstat";
			} else {
				$class = "";
			}
			print '<tr class="oddeven ' . $class . '"><td>';
			print $form->textwithpicto($langs->trans($key), $langs->trans($key . 'Tooltip'));
			print '</td>';
			if ($val['type'] == 'password') {
				print '<td>**************</td></tr>';
			} else {
				if ($val['type'] == 'checkbox') {
					if ($conf->global->$key > 0) {
						print '<td>' . $langs->trans("Yes") . '</td></tr>';
					} else {
						print '<td>' . $langs->trans("No") . '</td></tr>';
					}
				} else {
					print '<td>' . $conf->global->$key . '</td></tr>';
				}
			}
		}

		print '</table>';

		// /////////////////////////////////////////////////////////
		// Sending by email

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';
		print '<div class="titre inline-block">' . $langs->trans("CustomersOwedSendingMailWithPdf") . '<div>';

		$email_from = "";
		if (! empty($conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM)) {
			$email_from = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM;
		} else {
			if ((empty($email_from)) && ! empty($conf->global->PDF_EMAIL_FROM)) {
				$email_from = $conf->global->PDF_EMAIL_FROM;
			} else {
				if ((empty($email_from)) && ! empty($conf->global->MAIN_INFO_SOCIETE_MAIL)) {
					$email_from = $conf->global->MAIN_INFO_SOCIETE_MAIL;
				} else {
					if ((empty($email_from)) && ! empty($conf->global->MAIN_MAIL_EMAIL_FROM)) {
						$email_from = $conf->global->MAIN_MAIL_EMAIL_FROM;
					} else {
						if ((empty($email_from)) && ! empty($conf->global->MAILING_EMAIL_FROM)) {
							$email_from = $conf->global->MAILING_EMAIL_FROM;
						} else {
							if ((empty($email_from)) && ! empty($user->email)) {
								$email_from = $user->email;
							}
						}
					}
				}
			}
		}
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedSendMailFrom"), $langs->trans('CustomersowedSendMailFromHelp')) . '</td>';
		print '<td class="left">';
		print $email_from . '</td>';
		print '</tr>';

		// Mail subject template
		$email_subject_template = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE ? $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE : $langs->trans('CustomersowedMessageMailSubject');
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedMessageMailSubjectLabel"), $langs->trans('CustomersowedMessageMailSubjectHelp')) . '</td>';
		print '<td class="left">';
		print $email_subject_template . '</td>';
		print '</tr>';

		// Mail body template
		print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("CustomersowedMessageMailBodyLabel"), $langs->trans('CustomersowedMessageMailBodyHelp')) . '</td>';
		print '</td><td>';
		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		$htmlname = 'email_body_template';
		$content = $email_body_template;
		$width = 600;
		$height = 400;
		$toolbarname = 'Basic';
		$toolbarlocation = 'In';
		$toolbarstartexpanded = false;
		$uselocalbrowser = true;
		$okforextendededitor = false;
		$rows = 0;	
		$cols = 0;
		$readonly = 1;
		$doleditor = new DolEditor($htmlname, $content, $width, $height, $toolbarname, $toolbarlocation, $toolbarstartexpanded, $uselocalbrowser, $okforextendededitor, $rows, $cols, $readonly);
		$noprint = 0;
		$morejs = '';
		$disallowAnyContent = true;
		$titlecontent = '';
		$option = '';
		$moreparam = 'disabled="disabled"';
		$morecss = '';
		$doleditor->Create($noprint, $morejs, $disallowAnyContent, $titlecontent, $option, $moreparam, $morecss);
		print '</td>';
		print '</tr>';

		print '</table>';

		// // /////////////////////////////////////////////////////////
		// Buttons
		print '<div class="tabsAction">';

		// Builddoc test
		if ($conf->global->SYSLOG_LEVEL == LOG_DEBUG) {
			print '<a name="builddoc"></a>'; // ancre
			print '<a class="butActionNew" href="' . $_SERVER["PHP_SELF"] . '?action=builddoc">' . $langs->trans("CustomersOwedTestBuilddoc") . '</a>';
		}

		// Send mail test
		print '<a name="sendmail"></a>'; // ancre
		print '<a class="butActionNew" href="' . $_SERVER["PHP_SELF"] . '?action=sendmail">' . $langs->trans("CustomersOwedTestSendByMail") . '</a>';

		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';

		print '</div>';
	}
}

/*
 * Page end
 */
dol_fiche_end();

llxFooter();
$db->close();
