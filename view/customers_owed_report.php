<?php
/*
 * Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2015 Jean-François Ferry <jfefe@aternatik.fr>
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
	'projects',	
    'customersowed@customersowed'));

// Parameters
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
if (GETPOSTISSET('exclude_dp')) {
    $exclude_dp = GETPOST('exclude_dp', 'int');
} else {
    $exclude_dp = $conf->global->CUSTOMERSOWEDREPORT_EXCLUDE_DP;
}
if (GETPOSTISSET('only_late')) {
    $only_late = GETPOST('only_late', 'int');
} else {
    $only_late = $conf->global->CUSTOMERSOWEDREPORT_ONLY_LATE;
}
if (GETPOST('only_user', 'int')) {
    $only_user = GETPOST('only_user', 'int');
} else {
    if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY) {
        $only_user = $user->id;
    }
}
if (GETPOST('only_salesrepresentative', 'int')) {
    $only_salesrepresentative = GETPOST('only_salesrepresentative', 'int');
} else {
    if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE) {
        $only_salesrepresentative = $user->id;
    }
}

// Template used to generate document
if (GETPOST('model', 'alpha')) {
    $model = GETPOST('model', 'alpha');
}
$defaultmodel = $conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF;
if (empty($model)) {
    $model = $defaultmodel;
}

// Securite acces client
if (!$user->rights->customersowed->read) {
    accessforbidden();
}

$customersOwedDate = dol_now();
if ($date_end) {
    $customersOwedDate = $date_end;
}

$urlsource = $_SERVER['PHP_SELF'];
$urlsource .= str_replace('&amp;', '&', $param);

$rootfordata = DOL_DATA_ROOT;
// If multicompany module is enabled, we redefine the root of data
if (!empty($conf->multicompany->enabled) && !empty($conf->entity) && $conf->entity > 1) {
    $rootfordata .= '/' . $conf->entity;
}
$conf->customersowed->dir_output = $rootfordata . '/customersowed';
$conf->customersowed->dir_temp = $rootfordata . '/customersowed/temp';
// $pdfreportfilename = (new DateTime())->format('Y-m-d').'.pdf';
$pdfreportfilesdir = $conf->customersowed->dir_output;
$permissioncreate = $user->rights->customersowed->write;
$genallowed = $user->rights->customersowed->write;
$delallowed = $user->rights->customersowed->delete;
$customersOwedReport = new CustomersOwed($db, $customersOwedDate, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);

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

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

if (!$sortfield) {
    $sortfield = 'f.date_lim_reglement';
}

if (!$sortorder && !empty($conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER)) {
    $sortorder = $conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER;
}

if (!$sortorder) {
    $sortorder = 'ASC';
}

$urlsource = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative;

$customersOwedReport->fetch_lines($socid, $sortfield, $sortorder);
$title = $customersOwedReport->title;

// Build doc
if ($action == 'builddoc' && $genallowed) {
    if (is_numeric(GETPOST('model', 'alpha'))) {        
        $error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Model"));
        $errorMessage = $error;
    } else {
        // Reload to get all modified line records and be ready for hooks
        $outputlangs = $langs;
        $newlang = '';

        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
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
            if ($pdf_module->write_file($customersOwedReport, $pdfreportfilesdir . '/' . $customersOwedReport->pdfreportfilename, $langs, $socid, $sortfield, $sortorder) > 0) {
                if (empty($donotredirect)) // This is set when include is done by bulk action
                {
                    setEventMessages($langs->trans("FileGenerated"), null);
                    $urltoredirect = $_SERVER['PHP_SELF'] . '?model=' . $model . '&socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative;
                    $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
                    $urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

                    header('Location: ' . $urltoredirect . '#builddoc');
                    exit();
                } else {
                    header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=" . $type . "&file=" . $customersOwedReport->pdfreportfilename);
                    return;
                }
            } else {
                $errorMessage = $module->error;
                setEventMessages($errorMessage, $module->errors, 'errors');
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
if ($action == 'remove_file' && $permissioncreate) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    $langs->load("other");
    $filetodelete = GETPOST('file', 'alpha');
    $file = $pdfreportfilesdir . '/' . $filetodelete;

    $ret = dol_delete_file($file, 0, 0, 0);
    if ($ret) {
        setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
    } else {
        $errorMessage = $langs->trans("ErrorFailToDeleteFile", $filetodelete);
        setEventMessages($errorMessage, null, 'errors');
    }

    // Make a redirect to avoid to keep the remove_file into the url that create side effects
    $urltoredirect = $_SERVER['REQUEST_URI'];
    $urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
    $urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

    header('Location: ' . $urltoredirect);
    exit();
}

// send_mail_to_customer
if ($action == 'send_mail_to_customer' && $genallowed) {
    if ($socid > 0) {
        $societe = new Societe($db);
        $societe->fetch($socid);
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
        $userstatic->fetch($only_user);
        if (empty($userstatic->email)) {
            setEventMessages($langs->trans("UserSelectedDoesntHaveEmailAddress", $userstatic->firstname . " " . $userstatic->lastname), null, 'warnings');
        } else {
            $email_to = $userstatic->email;
            $actionsCustomersOwed = new ActionsCustomersOwed($db);
            if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user) < 0) {
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
        $salesrepresentativestatic->fetch($only_salesrepresentative);
        if (empty($salesrepresentativestatic->email)) {
            setEventMessages($langs->trans("UserSelectedDoesntHaveEmailAddress", $salesrepresentativestatic->firstname . " " . $salesrepresentativestatic->lastname), null, 'warnings');
        } else {
            $email_to = $salesrepresentativestatic->email;
            $actionsCustomersOwed = new ActionsCustomersOwed($db);
            if ($actionsCustomersOwed->sendMailWithCustomersowedPdf($salesrepresentative, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_salesrepresentative) < 0) {
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

$arrayfields = array(
    'p.ref' => array('label' => $langs->trans("ProjectRef"), 'checked' => 1, 'enabled' => (empty($conf->projet->enabled) ? 0 : 1), 'position' => 40),
    'p.title' => array('label' => $langs->trans("ProjectLabel"), 'checked' => 0, 'enabled' => (empty($conf->projet->enabled) ? 0 : 1), 'position' => 40),
    's.zip' => array(
        'label' => $langs->trans("Zip"),
        'checked' => 0),
    's.town' => array(
        'label' => $langs->trans("Town"),
        'checked' => 0),
    'f.ref_client' => array(
        'label' => $langs->trans("RefCustomer"),
        'checked' => 0),
    'f.type' => array(
        'label' => $langs->trans("Type"),
        'checked' => 0),
    'f.fk_mode_reglement' => array(
        'label' => $langs->trans("PaymentMode"),
        'checked' => 0),
    'f.date' => array(
        'label' => $langs->trans("DateInvoice"),
        'checked' => 0),
    'f.datec' => array(
        'label' => $langs->trans("DateCreation"),
        'checked' => 0,
        'position' => 500),
    'f.tms' => array(
        'label' => $langs->trans("DateModificationShort"),
        'checked' => 0,
        'position' => 600),
    'f.total_ht' => array(
        'label' => $langs->trans("AmountHT"),
        'checked' => (empty($conf->global->CUSTOMERSOWEDREPORT_DEFAULT_FILTER_HT_COLUMN) ? 0 : 1)), //
    'f.total_vat' => array(
        'label' => $langs->trans("AmountVAT"),
        'checked' => 0),
    'f.total_localtax1' => array(
        'label' => $langs->transcountry("AmountLT1", $mysoc->country_code),
        'checked' => 1,
        'enabled' => ($mysoc->localtax1_assuj == "1")),
    'f.total_localtax2' => array(
        'label' => $langs->transcountry("AmountLT2", $mysoc->country_code),
        'checked' => 1,
        'enabled' => ($mysoc->localtax2_assuj == "1")),
    'rtp' => array(
        'label' => $langs->trans("Rest"),
        'checked' => 1,
        'position' => 700));

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

$varpage = $_SERVER["PHP_SELF"];
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

llxHeader("", $langs->trans("ModuleCustomersOwedName"));

print load_fiche_titre($langs->trans("ModuleCustomersOwedName"), '', 'title_accountancy.png');

print '<div class="fichecenter">';

/*
 * Unpaid customers invoices
 */
print '<div class="attacharea">';

if (!empty($conf->facture->enabled) && $user->rights->facture->lire) {
    print '<form method="POST" name="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="exclude_dp" value="0">';
    print '<input type="hidden" name="only_late" value="0">';
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

    print '<div class="titre">' . $title . '</div>';
    print '<table class="noborder valid tableforfield" width="100%">';

    // Date
    print '<tr>';
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
    print '<tr class="">';
    print '<td class="">' . $langs->trans('ExcludeDownpaymentInvoices') . ' </td>';
    if ($exclude_dp > 0) {
        print '<td><input type="checkbox" name="exclude_dp" value="1" checked></td>';
    } else {
        print '<td><input type="checkbox" name="exclude_dp" value="1"></td>';
    }
    print '</tr>';

    // Only late payment invoices
    print '<tr>';
    print '<td class="">' . $langs->trans('OnlyLatePaymentInvoices') . ' </td>';
    if ($only_late > 0) {
        print '<td><input type="checkbox" name="only_late" value="1" checked></td>';
    } else {
        print '<td><input type="checkbox" name="only_late" value="1"></td>';
    }
    print '</tr>';

    // Thirdparty
    print '<tr>';
    print '<td>' . $langs->trans('OnlyInvoicesToThisCustomer') . '</td>';
    print '<td>';
    if (intval(DOL_VERSION) < 18) {
        print $form->select_company($socid, 'socid', '(s.client = 1 OR s.client = 3) AND status=1', 'AllCustomers', 0, 0, null, 0, 'minwidth300');
    }
    else {
        print $form->select_company($socid, 'socid', '(s.client:=:1)', 'SelectThirdParty', 'customer', 0, null, 0, 'minwidth300');
    }    
    // Option to reload page to retrieve customer informations. Note, this clear other input
    if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE)) {
        print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
			        var date_end_month = $(\'#date_end_month\').val();
			        var date_end_day = $(\'#date_end_day\').val();
			        var date_end_year = $(\'#date_end_year\').val();
					// reload page
					window.location.href = "' . $_SERVER["PHP_SELF"] . '?action=view&socid="+socid+"&date_end_month="+date_end_month+"&date_end_day="+date_end_day+"&date_end_year="+date_end_year+"&exclude_dp="+exclude_dp+"&only_late="+only_late;
				});
			});
			</script>';
    }
    if ($genallowed) {
        print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_user_button" name="action" value="send_mail_to_customer" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
    }
    print '</td>';
    print '</tr>';

    if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY) {
        // Only invoices created by
        print '<tr class="">';
        print '<td class="">' . $langs->trans('OnlyInvoicesCreatedBy') . ' </td>';
        print '<td>';
        print $form->select_dolusers($only_user, 'only_user', 1);
        if ($genallowed) {
            print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_user_button" name="action" value="send_mail_to_user" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
        }
        print '</td>';
        print '</tr>';
    }

    if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE) {
        // only invoices whose sales representative is
        print '<tr class="">';
        print '<td class="">' . $langs->trans('OnlyInvoicesWhoseSalesRepresentativeIs') . ' </td>';
        print '<td>';
        print $form->select_dolusers($only_salesrepresentative, 'only_salesrepresentative', 1);
        if ($genallowed) {
            print '<button class="butAction button_removefilter" id="customersowed_send_mail_to_salesrepresentative_button" name="action" value="send_mail_to_salesrepresentative" type="submit">' . $langs->trans("CustomersOwedSendAMail") . '</button>';
        }
        print '</td>';
        print '</tr>';
    }

    print '</tr>';
    print '</table>';
    // Refresh button
    print '<table class="noborder" width="100%">';
    print '<td class="center"><input type="submit" name="customersowed_refresh_button" class="button" value="' . $langs->trans("CustomersowedInvoicesListActualize") . '"></td>';
    print '</table>';

    $num = $customersOwedReport->lines_count;

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th>' . print_liste_field_titre($langs->trans("InvoiceRef"), $_SERVER["PHP_SELF"], 'f.facnumber', '', $param, 'align="left"', $sortfield, $sortorder) . ' <a href="' . DOL_URL_ROOT . '/compta/facture/list.php?search_status=1"><span class="badge">' . $num . '</span></a></th>';

    if (!empty($arrayfields['p.ref']['checked'])) {
        print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['p.title']['checked'])) {
        print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], 'p.title', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['s.zip']['checked'])) {
        print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['s.town']['checked'])) {
        print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.ref_client']['checked'])) {
        print_liste_field_titre($arrayfields['f.ref_client']['label'], $_SERVER["PHP_SELF"], 'f.ref_client', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.type']['checked'])) {
        print_liste_field_titre($arrayfields['f.type']['label'], $_SERVER["PHP_SELF"], 'f.type', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
        print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_mode_reglement", "", $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.date']['checked'])) {
        print_liste_field_titre($arrayfields['f.date']['label'], $_SERVER['PHP_SELF'], 'f.datef', '', $param, 'align="center"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.datec']['checked'])) {
        print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER["PHP_SELF"], "f.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.tms']['checked'])) {
        print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER["PHP_SELF"], "f.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
    }

    print_liste_field_titre($langs->trans("DateDue"), $_SERVER["PHP_SELF"], 'f.date_lim_reglement', '', $param, 'align="center"', $sortfield, $sortorder);
    // if (! empty($arrayfields['p.ref']['checked'])) print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER['PHP_SELF'],"p.ref",'',$param,'align="center"',$sortfield,$sortorder);
    // if (! empty($arrayfields['state.nom']['checked'])) print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'align="center"',$sortfield,$sortorder);
    // if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
    // if (! empty($arrayfields['typent.code']['checked'])) print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
    if (!empty($arrayfields['f.total_ht']['checked'])) {
        print_liste_field_titre($arrayfields['f.total_ht']['label'], $_SERVER['PHP_SELF'], 'f.total', '', $param, 'align="right"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.total_vat']['checked'])) {
        print_liste_field_titre($arrayfields['f.total_vat']['label'], $_SERVER['PHP_SELF'], 'f.tva', '', $param, 'align="right"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.total_localtax1']['checked'])) {
        print_liste_field_titre($arrayfields['f.total_localtax1']['label'], $_SERVER['PHP_SELF'], 'f.localtax1', '', $param, 'align="right"', $sortfield, $sortorder);
    }

    if (!empty($arrayfields['f.total_localtax2']['checked'])) {
        print_liste_field_titre($arrayfields['f.total_localtax2']['label'], $_SERVER['PHP_SELF'], 'f.localtax2', '', $param, 'align="right"', $sortfield, $sortorder);
    }

    print_liste_field_titre($langs->trans("AmountTTC"), $_SERVER["PHP_SELF"], 'f.total_ttc', '', $param, 'align="right"', $sortfield, $sortorder);
    print '<th align="right">' . $langs->trans("Received") . '</th>';
    if (!empty($arrayfields['rtp']['checked'])) {
        print_liste_field_titre($arrayfields['rtp']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'align="right"', $sortfield, $sortorder);
    }

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');

    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

    // Hook fields
    $parameters = array(
        'arrayfields' => $arrayfields,
        'param' => $param,
        'sortfield' => $sortfield,
        'sortorder' => $sortorder);
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</tr>';

    if ($num > 0) {
        if ($num) {
            $societestatic = new Societe($db);
            $facturestatic = new Facture($db);
            $total_ttc = $total_amount = $total_ht = $total_vat = 0;
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
                $facturestatic->id = $obj->id;
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

                if ($societestatic->id == 2056) {
                    
                    $remaintopay = $facturestatic->total_ttc - $totalpay;
                    if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {
                        $remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source=' . $facturestatic->id);
                        $remaintopay = -$remaincreditnote;
                        $totalpay = $facturestatic->total_ttc - $remaintopay;
                    }


                }else {

                    $remaintopay = $facturestatic->total_ttc - $totalpay;
                    if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {
                        $remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source=' . $facturestatic->id);
                        $remaintopay = -$remaincreditnote;
                        $totalpay = $facturestatic->total_ttc - $remaintopay;
                    }

                }

                

                print '<tr class="oddeven">';
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
                $pdfinvoicefilesdir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
                // $urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->id.'&sortfield='.$sortfield.'&sortorder='.$sortorder.'&date_end_month='.$date_end_month.'&date_end_day='.$date_end_day.'&date_end_year='.$date_end_year;
                print $formfile->getDocumentsLink($facturestatic->element, $pdfinvoicefilename, $pdfinvoicefilesdir);
                print '</td></tr></table>';
                print '</td>';

                print '<td align="left">';
                print $societestatic->getNomUrl(1, 'customer', 44);
                print '</td>';

                // project_ref
                if (!empty($arrayfields['p.ref']['checked'])) {
                    print '<td align="center" class="nocellnopadd">';
                    print $obj->project_ref;
                    print '</td>';
                }

                // project_label
                if (!empty($arrayfields['p.title']['checked'])) {
                    print '<td align="center" class="nocellnopadd">';
                    print $obj->project_label;
                    print '</td>';
                }

                // Zip
                if (!empty($arrayfields['s.zip']['checked'])) {
                    print '<td align="center" class="nocellnopadd">';
                    print $obj->zip;
                    print '</td>';
                }

                // Town
                if (!empty($arrayfields['s.town']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->town;
                    print '</td>';
                }

                // Customer ref
                if (!empty($arrayfields['f.ref_client']['checked'])) {
                    print '<td align="center" class="nowrap">';
                    print $obj->ref_client;
                    print '</td>';
                }

                // Type
                if (!empty($arrayfields['f.type']['checked'])) {
                    print '<td align="center" class="nowrap">';
                    print $facturestatic->getLibType();
                    print "</td>";
                }

                // Payment mode
                if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
                    print '<td align="center">';
                    $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
                    print '</td>';
                }

                // Date invoice
                if (!empty($arrayfields['f.date']['checked'])) {
                    print '<td align="center" class="nowrap">';
                    print dol_print_date($db->jdate($obj->date), 'day');
                    print '</td>';
                }

                // Date creation
                if (!empty($arrayfields['f.datec']['checked'])) {
                    print '<td align="center" class="nowrap">';
                    print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
                    print '</td>';
                }

                // Date modification
                if (!empty($arrayfields['f.tms']['checked'])) {
                    print '<td align="center" class="nowrap">';
                    print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
                    print '</td>';
                }

                // DateDue
                print '<td align="center">' . dol_print_date($db->jdate($obj->date_lim_reglement), 'day') . '</td>';

                // Project
                if (!empty($arrayfields['p.ref']['checked'])) {
                    // print '<td class="nowrap">';
                    // if ($obj->project_id > 0)
                    // {
                    // $projectstatic->id=$obj->project_id;
                    // $projectstatic->ref=$obj->project_ref;
                    // $projectstatic->title=$obj->project_label;
                    // print $projectstatic->getNomUrl(1);
                    // }
                    // print '</td>';
                }

                // State
                if (!empty($arrayfields['state.nom']['checked'])) {
                    // print "<td>".$obj->state_name."</td>\n";
                }

                // Country
                if (!empty($arrayfields['country.code_iso']['checked'])) {
                    // print '<td align="center">';
                    // $tmparray=getCountry($obj->fk_pays,'all');
                    // print $tmparray['label'];
                    // print '</td>';
                }

                // Type ent
                if (!empty($arrayfields['typent.code']['checked'])) {
                    // print '<td align="center">';
                    // if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
                    // print $typenArray[$obj->typent_code];
                    // print '</td>';
                }

                // Amount HT
                if (!empty($arrayfields['f.total_ht']['checked'])) {
                    print '<td align="right">' . price($obj->total_ht) . "</td>\n";
                }

                // Amount VAT
                if (!empty($arrayfields['f.total_vat']['checked'])) {
                    print '<td align="right">' . price($obj->total_vat) . "</td>\n";
                }

                // Amount LocalTax1
                if (!empty($arrayfields['f.total_localtax1']['checked'])) {
                    print '<td align="right">' . price($obj->total_localtax1) . "</td>\n";
                }

                // Amount LocalTax2
                if (!empty($arrayfields['f.total_localtax2']['checked'])) {
                    print '<td align="right">' . price($obj->total_localtax2) . "</td>\n";
                }

                // Extra fields
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
                // Fields from hook
                $parameters = array(
                    'arrayfields' => $arrayfields,
                    'obj' => $obj);
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;

                // Amount TTC
                print '<td align="right">' . price($obj->total_ttc) . '</td>';

                // Received
                if (!empty($arrayfields['rtp']['checked'])) {

                    if ($societestatic->id == 2056) {

                        print '<td align="right">' . price($obj->am) . '</td>';

                    }else {

                    print '<td align="right">' . price($obj->am) . '</td>';

                    }
                }

                // Rtp
                if (!empty($arrayfields['rtp']['checked'])) {
                    print '<td align="right">' . (!empty($remaintopay) ? price($remaintopay, 0, $langs) : '&nbsp;') . '</td>'; // TODO Use a denormalized field
                }

                // Status
                print '<td align="center">' . $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 3, $obj->am) . '</td>';
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
            print '<td colspan="2">' . $langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y"), price($total_ttc - $total_amount)) . '</font> </td>';
            // Zip
            if (!empty($arrayfields['s.zip']['checked'])) {
                print '<td></td>';
            }
            // Town
            if (!empty($arrayfields['s.town']['checked'])) {
                print '<td></td>';
            }
            // Ref client
            if (!empty($arrayfields['f.ref_client']['checked'])) {
                print '<td></td>';
            }
            // Type
            if (!empty($arrayfields['f.type']['checked'])) {
                print '<td></td>';
            }
            // Payment mode
            if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
                print '<td></td>';
            }
            // Date invoice
            if (!empty($arrayfields['f.date']['checked'])) {
                print '<td></td>';
            }
            // Date creation
            if (!empty($arrayfields['f.datec']['checked'])) {
                print '<td></td>';
            }
            // Date modification
            if (!empty($arrayfields['f.tms']['checked'])) {
                print '<td></td>';
            }
            // Datedue
            print '<td>&nbsp;</td>';
            // Project
            if (!empty($arrayfields['p.ref']['checked'])) {
                // print '<td class="nowrap">';
                // print '</td>';
            }
            // State
            if (!empty($arrayfields['state.nom']['checked'])) {
                // print "<td>".$obj->state_name."</td>\n";
            }
            // Country
            if (!empty($arrayfields['country.code_iso']['checked'])) {
                // print '<td align="center">';
                // print '</td>';
            }
            // Type ent
            if (!empty($arrayfields['typent.code']['checked'])) {
                // print '<td align="center">';
                // print '</td>';
            }
            // Amount HT
            if (!empty($arrayfields['f.total_ht']['checked'])) {
                print '<td align="right">' . price($total_ht) . '</td>';
            }
            // Amount VAT
            if (!empty($arrayfields['f.total_vat']['checked'])) {
                print '<td align="right">' . price($total_vat) . '</td>';
            }
            // Amount LocalTax1
            if (!empty($arrayfields['f.total_localtax1']['checked'])) {
                print '<td></td>';
            }
            // Amount LocalTax2
            if (!empty($arrayfields['f.total_localtax2']['checked'])) {
                print '<td></td>';
            }
            print '<td align="right">' . price($total_ttc) . '</td>';
            print '<td align="right">' . price($total_amount) . '</td>';
            if (!empty($arrayfields['rtp']['checked'])) {
                print '<td align="right"><b>' . price($total_rtp) . '</b></td>';
            }
            print '</tr>';
        } else {
            $colspan = 6;
            print '<tr class="oddeven"><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoInvoice") . '</td></tr>';
        }
        print '</table>';
        print '</div>';
        if (empty($errorMessage)) {
            // setEventMessages($langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y"), price($total_ttc-$total_amount)), null, 'mesgs');
            if ($socid && $socid > 0) {
                $societe = new Societe($db);
                $societe->fetch($socid);
                $societe_name = dol_sanitizeFileName($societe->name);
                setEventMessages($langs->trans("RemainderToTakeForCompanyAtDateOf", $societe_name, dol_print_date($customersOwedDate, "%d/%m/%Y"), price($total_ttc - $total_amount)), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y"), price($total_ttc - $total_amount)), null, 'mesgs');
            }
        }
        else {
            setEventMessages($errorMessage, null, 'error');
        }
    } else {
        print '<tr><td colspan="4" class="opacitymedium">' . $langs->trans("CustomersOwedNoInvoice") . '</td></tr>';
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

$urlsource = $_SERVER['PHP_SELF'] . '?socid=' . $socid . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&columns=' . $columnsToAdd . '&date_end_month=' . $date_end_month . '&date_end_day=' . $date_end_day . '&date_end_year=' . $date_end_year . '&exclude_dp=' . $exclude_dp . '&only_late=' . $only_late . '&only_user=' . $only_user . '&only_salesrepresentative=' . $only_salesrepresentative;

if ($user->rights->customersowed->read) {
    $allowgenifempty = 1; 
    $forcenomultilang = 0; 
    $iconPDF = 0; 
    $notused = 0;
    $noform = 0;
    $param = '';
    print $formfile->showdocuments('customersowed', '', $pdfreportfilesdir, $urlsource, $genallowed, $delallowed, $model, $allowgenifempty, $forcenomultilang, $iconPDF, $notused, $noform, $param, $langs->trans("PdfDocuments"), $langs->trans("CustomersowedPdfDocumentsGenerate"), $soc->default_lang, '', null, $hidegeneratedfilelistifempty);
    $somethingshown = $formfile->numoffiles;
}
print '</div>';

print '</div>';

llxFooter();

$db->close();