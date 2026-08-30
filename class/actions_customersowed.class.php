<?php

/*
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
 * \file customersowed/class/actions_customersowed.class.php
 * \ingroup customersowed
 * \brief Example hook overload.
 *
 * Put detailed description here.
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once dol_buildpath('/customersowed/class/customersowed.class.php');

/**
 * Class ActionsCustomersOwed
 */
class ActionsCustomersOwed
{

	/**
	 *
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 *
	 * @var string Error
	 */
	public $error = '';

	/**
	 *
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 *
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 *
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	public $outputlangs;

	public $filename;

	public $fileNameToSend;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 *        	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf, $mysoc;

		$this->db = $db;

		// Define output language
		$outputlangs = $langs;
		/*
		 * $newlang = '';
		 * $lang_id = GETPOST('lang_id');
		 * if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($lang_id))
		 * $newlang = $lang_id;
		 * if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		 * $newlang = $mysoc->default_lang;
		 * if (! empty($newlang)) {
		 * $outputlangs->setDefaultLang($newlang);
		 * }
		 * $this->outputlangs = $outputlangs;
		 */
		$this->outputlangs = new Translate("", $conf);
		$newlang = $mysoc->default_lang;
		if (! empty($newlang)) {
			$this->outputlangs->setDefaultLang($newlang);
		}
		$this->outputlangs->loadLangs(array(
			"main",
			"errors",
			"companies",
			"install",
			"other",
			"admin",
			"bills",
			"members",
			"cron",
			"customersowed@customersowed"));

		return 1;
	}

	/**
	 * Execute action
	 *
	 * @param array $parameters
	 *        	Array of parameters
	 * @param CommonObject $object
	 *        	The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action
	 *        	'add', 'update', 'view'
	 * @return int <0 if KO,
	 *         =0 if OK but we want to process standard actions too,
	 *         >0 if OK and we want to replace standard actions.
	 */
	function getNomUrl($parameters, &$object, &$action)
	{
		// global $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters
	 *        	Hook metadatas (context, etc...)
	 * @param CommonObject $object
	 *        	The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action
	 *        	Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager
	 *        	Hook manager propagated to allow calling another hook
	 * @return int < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array(
			'somecontext1',
			'somecontext2'))) // do something only for the context 'somecontext1' or 'somecontext2'
		{
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (! $error) {
			$this->results = array(
				'myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return - 1;
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters
	 *        	Hook metadatas (context, etc...)
	 * @param CommonObject $object
	 *        	The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action
	 *        	Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager
	 *        	Hook manager propagated to allow calling another hook
	 * @return int < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array(
			'somecontext1',
			'somecontext2'))) // do something only for the context 'somecontext1' or 'somecontext2'
		{
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (! $error) {
			$this->results = array(
				'myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return - 1;
		}
	}

	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters
	 *        	Hook metadatas (context, etc...)
	 * @param CommonObject $object
	 *        	The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action
	 *        	Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager
	 *        	Hook manager propagated to allow calling another hook
	 * @return int < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array(
			'somecontext1',
			'somecontext2'))) // do something only for the context 'somecontext1' or 'somecontext2'
		{
			$this->resprints = '<option value="0"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("CustomersOwedMassAction") . '</option>';
		}

		if (! $error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return - 1;
		}
	}

	/**
	 * Execute action
	 *
	 * @param array $parameters
	 *        	Array of parameters
	 * @param Object $object
	 *        	Object output on PDF
	 * @param string $action
	 *        	'add', 'update', 'view'
	 * @return int <0 if KO,
	 *         =0 if OK but we want to process standard actions too,
	 *         >0 if OK and we want to replace standard actions.
	 */
	function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array(
			'somecontext1',
			'somecontext2'))) // do something only for the context 'somecontext1' or 'somecontext2'
		{
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param array $parameters
	 *        	Array of parameters
	 * @param Object $pdfhandler
	 *        	PDF builder handler
	 * @param string $action
	 *        	'add', 'update', 'view'
	 * @return int <0 if KO,
	 *         =0 if OK but we want to process standard actions too,
	 *         >0 if OK and we want to replace standard actions.
	 */
	function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array(
			'somecontext1',
			'somecontext2'))) // do something only for the context 'somecontext1' or 'somecontext2'
		{
		}

		return $ret;
	}

	/**
	 * Generate the pdf due customer.
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function generateCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield = NULL, $sortorder = NULL, $socid = NULL, $exclude_dp = NULL, $only_late = NULL, $only_user = NULL, $only_salesrepresentative = NULL)
	{
		global $conf, $user;

		$error = 0;

		$this->filename = $customersOwedReport->pdfreportfilename;
		$this->fileNameToSend = $pdfreportfilesdir . '/' . $this->filename;

		dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Start generating PDF fileNameToSend = '" . $this->fileNameToSend . "' model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_DEBUG);

		$sav_charset_output = $outputlangs->charset_output;

		// Search template files
		$type = 'customersowed';
		$modulefile = '';
		$classname = '';
		$modulefile_found = false;
		$modelsdir = array_merge(array(
			'/'), (array) $conf->modules_parts['models']);
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

			$pdf_module = new $classname($this->db);
			if ($pdf_module->write_file($customersOwedReport, $pdfreportfilesdir . '/' . $customersOwedReport->pdfreportfilename, $this->outputlangs, $socid, $sortfield, $sortorder) < 0) {
				$error ++;
				$this->error = $this->error = "CanNotWriteFile";
				$this->errors[] = $this->error;
				dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Error during generating PDF : Can not write file - model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_ERR);
			}
		} else {
			$error ++;
			$this->error = $this->error = "ErrorModuleNotFound";
			$this->errors[] = $this->error;
			dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Error during generating PDF : Module not found - model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_ERR);
		}

		$this->error = $this->errors[0];

		return $error ? - 1 : 0;
	}

	/**
	 * Send a mail with the pdf due customer to email_to.
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield = NULL, $sortorder = NULL, $email_to = NULL, $socid = NULL, $exclude_dp = NULL, $only_late = NULL, $only_user = NULL, $only_salesrepresentative = NULL)
	{
		global $conf, $mysoc;

		$error = 0;

		dol_syslog(get_class($this) . "::sendMailWithCustomersowedPdf " . "Start sending mail to '" . $email_to . "'", LOG_DEBUG);

		if (empty($socid)) {
			$socid = - 1;
		}

		// Builddoc
		if ($this->generateCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative) < 0) {
			$error ++;
			$this->errors[] = $this->error;
			dol_syslog(get_class($this) . "::sendMailWithCustomersowedPdf " . "Error during generating PDF : " . $this->error . " - model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_ERR);
		}

		if ($error < 1) {

			// Send mail from address
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

			// Set email recipient
			if (empty($email_to)) {
				$email_to = $user->email;
				if (empty($email_to)) {
					$email_to = $email_from;
				}
			}
			if (empty($email_to)) {
				$error ++;
				$this->errors[] = $this->outputlangs->transnoentities("CustomersOwedSendByMailKONoRecipientAddress");
			}

			$application = 'Dolibarr';
			if (! empty($conf->global->MAIN_APPLICATION_TITLE))
				$application = $conf->global->MAIN_APPLICATION_TITLE;

			$email_subject = "";
			$email_body = "";			
			$email_subject_template = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE ? $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE : $this->outputlangs->trans('CustomersowedMessageMailSubject');

			// Mail body template
			$langs_trans_CustomersowedMessageMailBody = str_replace('<br/>', '
			', $this->outputlangs->trans('CustomersowedMessageMailBody'));
			$langs_trans_CustomersowedMessageMailBody = str_replace('&lt;br/>', '
			', $langs_trans_CustomersowedMessageMailBody);
			$email_body_template = $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE ? $conf->global->CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE : $langs_trans_CustomersowedMessageMailBody; 

			$CUSTOMERSOWEDREPORT_EXCLUDE_DP = "";
			if (! isset($exclude_dp)) {
				$CUSTOMERSOWEDREPORT_EXCLUDE_DP = $conf->global->CUSTOMERSOWEDREPORT_EXCLUDE_DP ? $this->outputlangs->trans("ExcludeDownpaymentInvoicesTooltip") : "";
			} else {
				if ($exclude_dp > 0) {
					$CUSTOMERSOWEDREPORT_EXCLUDE_DP = $this->outputlangs->trans("ExcludeDownpaymentInvoicesTooltip");
				}
			}

			$CUSTOMERSOWEDREPORT_ONLY_LATE = "";
			if (! isset($only_late)) {
				$CUSTOMERSOWEDREPORT_ONLY_LATE = $conf->global->CUSTOMERSOWEDREPORT_ONLY_LATE ? $this->outputlangs->trans("OnlyLatePaymentInvoicesTooltip") : "";
			} else {
				if ($only_late > 0) {
					$CUSTOMERSOWEDREPORT_ONLY_LATE = $this->outputlangs->trans("OnlyLatePaymentInvoicesTooltip");
				}
			}

			$CUSTOMERSOWEDREPORT_ONLY_CREATED_BY = ""; // $this->outputlangs->trans("Everybody");
			if (! isset($only_user)) {
				if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY) {
					$CUSTOMERSOWEDREPORT_ONLY_CREATED_BY = $this->outputlangs->trans("OnlyInvoicesCreatedBy") . " : " . $user->firstname . " " . $user->lastname;
				}
			} else {
				if ($only_user > 0) {
					$userstatic = new User($this->db);
					$userstatic->fetch($only_user);
					$CUSTOMERSOWEDREPORT_ONLY_CREATED_BY = $this->outputlangs->trans("OnlyInvoicesCreatedBy") . " : " . $userstatic->firstname . " " . $userstatic->lastname;
				}
			}

			$CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE = "";
			if (! isset($only_salesrepresentative)) {
				if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY) {
					$CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE = $this->outputlangs->trans("OnlyInvoicesWhoseSalesRepresentativeIs") . " : " . $user->firstname . " " . $user->lastname;
				}
			} else {
				if ($only_salesrepresentative > 0) {
					$userstatic = new User($this->db);
					$userstatic->fetch($only_salesrepresentative);
					$CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE = $this->outputlangs->trans("OnlyInvoicesWhoseSalesRepresentativeIs") . " : " . $userstatic->firstname . " " . $userstatic->lastname;
				}
			}

			$substitutionarray = array();

			$substitutionarray = array_merge($substitutionarray, array(
				'__USER_ID__' => (string) $user->id,
				'__USER_LOGIN__' => (string) $user->login,
				'__USER_LASTNAME__' => (string) $user->lastname,
				'__USER_FIRSTNAME__' => (string) $user->firstname,
				'__USER_SIGNATURE__' => (string) $user->firstname . " " . $user->lastname,
				'__USER_FULLNAME__' => (string) $user->getFullName($this->outputlangs),
				'__USER_SUPERVISOR_ID__' => (string) ($user->fk_user ? $user->fk_user : '0'),
				'__USER_REMOTE_IP__' => (string) getUserRemoteIP()));

			$substitutionarray = array_merge($substitutionarray, array(
				'__MAIN_APPLICATION_TITLE__' => $application,
				'__MAIN_INFO_SOCIETE_NOM__' => $mysoc->name,
				'__MAIN_INFO_SOCIETE_ADDRESS__' => $mysoc->address,
				'__MAIN_INFO_SOCIETE_ZIP__' => $mysoc->zip,
				'__MAIN_INFO_SOCIETE_TOWN__' => $mysoc->town,
				'__MAIN_INFO_SOCIETE_TEL__' => $mysoc->phone,
				'__MAIN_INFO_SOCIETE_OBJECT__' => $mysoc->object,
				'__FROM_EMAIL__' => $email_from));

			$substitutionarray = array_merge($substitutionarray, array(
				'__DATE_YMD__' => dol_print_date(dol_now(), 'day', 0, $this->outputlangs),
				'__DATE_YMDHMS__' => dol_print_date(dol_now(), 'dayhoursec', 0, $this->outputlangs),
				'__CUSTOMERSOWEDREPORT_EXCLUDE_DP__' => $CUSTOMERSOWEDREPORT_EXCLUDE_DP,
				'__CUSTOMERSOWEDREPORT_ONLY_LATE__' => $CUSTOMERSOWEDREPORT_ONLY_LATE,
				'__CUSTOMERSOWEDREPORT_ONLY_CREATED_BY__' => $CUSTOMERSOWEDREPORT_ONLY_CREATED_BY,
				'__CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE__' => $CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE,
				'__FILE_NAME__' => $this->filename,
				'__RESULT__' => $this->lastresult > 0 ? $this->outputlangs->transnoentities("CustomersowedResultOK") : $this->outputlangs->transnoentities("CustomersowedResultError")));

			$DOL_MAIN_URL_ROOT = "";
			$CUSTOMERSOWEDREPORT_URL = "";
			if (($socid < 1) && ($conf->global->CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL > 0)) {
				$DOL_MAIN_URL_ROOT = DOL_MAIN_URL_ROOT;
				$CUSTOMERSOWEDREPORT_URL = DOL_MAIN_URL_ROOT . '/custom/customersowed/view/customers_owed_report.php';
			}
			$substitutionarray = array_merge($substitutionarray, array(
				'__DOL_MAIN_URL_ROOT__' => $DOL_MAIN_URL_ROOT,
				'__CUSTOMERSOWEDREPORT_URL__' => $CUSTOMERSOWEDREPORT_URL));

			$email_subject = make_substitutions($email_subject_template, $substitutionarray, $this->outputlangs);

			// /////////////////////////////////////////////////
			$listTable = "";

			$form = new Form($this->db);
			$formfile = new FormFile($this->db);

			$num = $customersOwedReport->lines_count;

			$listTable .= '<div>';
			$listTable .= '<table style="border-collapse: collapse;" width="700px">';
			$listTable .= '<tr>';
			$listTable .= '<th align="left">' . $this->outputlangs->trans("InvoiceRef") . '</th>';
			$listTable .= '<th align="left">' . $this->outputlangs->trans("RefCustomer") . '</th>';
			$listTable .= '<th align="center">' . $this->outputlangs->trans("DateDue") . '</th>';
			$listTable .= '<th align="center">' . $this->outputlangs->trans("AmountTTC") . '</th>';
			$listTable .= '<th align="center">' . $this->outputlangs->trans("Received") . '</th>';
			$listTable .= '</tr>';

			if ($num > 0) {
				if ($num) {
					$societestatic = new Societe($this->db);
					$facturestatic = new Facture($this->db);
					$total_ttc = $total_amount = $total_ht = $total_vat = 0;
					$i = 0;
					while ($i < $num) // && $i < $conf->liste_limit
					{
						$obj = $customersOwedReport->lines[$i];

						$societestatic->name = $obj->name;
						$societestatic->id = $obj->fk_soc;

						$facturestatic->ref = $obj->facnumber;
						$facturestatic->id = $obj->id;
						$facturestatic->fk_soc = $obj->fk_soc;
						$facturestatic->statut = $obj->fk_statut;
						$facturestatic->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
						$facturestatic->total_ttc = $obj->total_ttc;

						$totalpay = $obj->am;

						$remaintopay = $facturestatic->total_ttc - $totalpay;
						if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {
							$remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source=' . $facturestatic->id);
							$remaintopay = - $remaincreditnote;
							$totalpay = $facturestatic->total_ttc - $remaintopay;
						}

						if ($i % 2 == 1) {
							$listTable .= '<tr style="background: #EEE;">';
						} else {
							$listTable .= '<tr style="background: #FFF;">';
						}
						$listTable .= '<td style="border-style: solid; border-width: thin;" width="110">';
						if (($socid < 1) && ($conf->global->CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL > 0)) {
							$listTable .= '<a href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?facid=' . $facturestatic->id . '">' . $facturestatic->ref . '</a>';
						} else {
							$listTable .= $facturestatic->ref;
						}
						$listTable .= '</td>';
						$listTable .= '<td style="border-style: solid; border-width: thin;" align="left">';
						if (($socid < 1) && ($conf->global->CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL > 0)) {
							$listTable .= '<a href="' . DOL_MAIN_URL_ROOT . '/comm/card.php?socid=' . $societestatic->id . '">' . $societestatic->name . '</a>';
						} else {
							$listTable .= $societestatic->name;
						}
						$listTable .= '</td>';

						// DateDue
						$listTable .= '<td style="border-style: solid; border-width: thin;" align="center">' . dol_print_date($this->db->jdate($obj->date_lim_reglement), 'day') . '</td>';

						// Amount TTC
						$listTable .= '<td style="border-style: solid; border-width: thin;" align="right">' . price($obj->total_ttc) . '</td>';

						// Received
						$listTable .= '<td style="border-style: solid; border-width: thin;" align="right">' . price($obj->am) . '</td>';

						// Totals calc
						$total_ttc += $obj->total_ttc;
						$total_ht += $obj->total_ht;
						$total_vat += $obj->total_vat;
						$total_amount += $obj->am;
						$total_rtp += $remaintopay;

						$i ++;
					}

					$listTable .= '<tr>';
					$listTable .= '<td colspan="2">' . $this->outputlangs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedReport->customersOwedDate, "%d/%m/%Y"), price($total_ttc - $total_amount)) . '</font> </td>';
					$listTable .= '<td></td>';
					$listTable .= '<td align="right">' . price($total_ttc) . '</td>';
					$listTable .= '<td align="right">' . price($total_amount) . '</td>';
					$listTable .= '</tr>';
				} else {
					$colspan = 6;
					$listTable .= '<tr><td colspan="' . $colspan . '">' . $this->outputlangs->trans("NoInvoice") . '</td></tr>';
				}
				$listTable .= '</table>';
			}
			// /////////////////////////////////////////////////

			$substitutionarray = array_merge($substitutionarray, array(
				'__CUSTOMERSOWEDREPORT_INVOICES_LIST_TABLE__' => $listTable));
			$email_body = make_substitutions($email_body_template, $substitutionarray, $this->outputlangs);
			$email_body = str_replace("\n", "<br/>\n", $email_body);

			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

			$filename_list[] = $this->fileNameToSend;
			$mimetype_list[] = mime_content_type($this->fileNameToSend);
			$mimefilename_list[] = $this->filename;

			$mailfile = new CMailFile($email_subject, $email_to, $email_from, $email_body, $filename_list, $mimetype_list, $mimefilename_list, "", "", 0, 1);
			$result = $mailfile->sendfile();

			if ($result) {
				$this->result .= $this->outputlangs->transnoentities("MailSuccessfulySent", $mailfile->getValidAddress($email_from, 2), $mailfile->getValidAddress($email_to, 2));
				dol_syslog(get_class($this) . "::sendResultByMail " . $this->result, LOG_DEBUG);
			} else {
				$error ++;
				$this->error .= $this->outputlangs->transnoentities("CustomersOwedSendByMailKO") . " : " . $this->outputlangs->transnoentities("ResultKo") . '<br>' . $mailfile->error . ' ' . $result;
				dol_syslog(get_class($this) . "::sendResultByMail " . $this->error, LOG_DEBUG);
				return - 1;
			}
		}

		$this->error = $this->errors[0];

		return $error ? - 1 : 0;
	}

	/**
	 * Send all the mails with the pdf due customer.
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function sendMails()
	{
		global $conf;

		$error = 0;
		$this->output = "";
		$mails_sended_count = 0;

		dol_syslog(get_class($this) . "::sendMails " . "Start sending mail to '" . $email_to . "'", LOG_DEBUG);

		// 1) get first admin user
		$sql = 'SELECT login, entity from ' . MAIN_DB_PREFIX . 'user WHERE admin = 1 and statut = 1 ORDER BY entity LIMIT 1';
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$userlogin = $obj->login;
				dol_syslog(get_class($this) . "::sendMails First admin user login found : '" . $userlogin . "', entity " . $obj->entity, LOG_DEBUG);
			}
		} else {
			$error ++;
			$this->errors[] = $this->outputlangs->transnoentities("CustomersOwedUnableToGetFirstAdminUser");
		}

		if ($error < 1) {
			// Check user login
			$user = new User($this->db);
			$result = $user->fetch('', $userlogin);
			if ($result < 0) {
				$error ++;
				$this->errors[] = "User Error: " . $user->error;
				dol_syslog(get_class($this) . "::sendMails User Error:" . $user->error, LOG_ERR);
			} else {
				if (empty($user->id)) {
					$error ++;
					$this->errors[] = "User login: " . $userlogin . " does not exists";
					dol_syslog(get_class($this) . "::sendMails User login:" . $userlogin . " does not exists", LOG_ERR);
				}
			}
			$user->getrights();
			dol_syslog(get_class($this) . "::sendMails The user login used to send mail is : '" . $userlogin . "'", LOG_ERR);
		}

		if ($error < 1) {
			// 2) Create $customersOwed instances
			$customersOwedDate = dol_now();
			$pdfreportfilesdir = $conf->customersowed->dir_output;
			$model = $conf->global->CUSTOMERSOWEDREPORT_ADDON_PDF;
			$exclude_dp = $conf->global->CUSTOMERSOWEDREPORT_EXCLUDE_DP;
			$only_late = $conf->global->CUSTOMERSOWEDREPORT_ONLY_LATE;

			// 3) Load customers owed invoices
			$socid = 0;
			$sortfield = 'f.date_lim_reglement';
			$sortorder = 'ASC';
			$this->output .= "Load customers owed invoices : ";
			$customersOwedReport = new CustomersOwed($this->db, $customersOwedDate, $exclude_dp, $only_late);
			$customersOwedReport->fetch_lines($socid, $sortfield, $sortorder);
			$this->output .= $customersOwedReport->lines_count . " \n";

			// 4) Send mail to first admin user, with PDF 'Customers owed'
			$this->output .= "Send mail to first admin user, with PDF 'Customers owed', (" . $user->email . ") : ";
			if ($customersOwedReport->lines_count > 0) {
				$only_user = 0;
				$only_salesrepresentative = 0;
				$this->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);
				print ".";
				sleep(3);
				$this->output .= "OK \n";
				$mails_sended_count ++;
			}
			print ".";
		}

		if ($error < 1) {
			if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_CREATED_BY > 0) {
				// 5) Send mail to all users, with PDF 'Customers owed'
				$staticuser = new User($this->db);
				$result = $staticuser->fetchAll();
				$users_count = sizeof($staticuser->users);
				foreach ($staticuser->users as $createdbystatic) {
					$only_user = $createdbystatic->id;
					$only_salesrepresentative = 0;
					$this->output .= "Load customers owed invoices (created by '" . $createdbystatic->login . "') : ";
					$customersOwedReport = new CustomersOwed($this->db, $customersOwedDate, $exclude_dp, $only_late, $createdbystatic->id, $only_salesrepresentative);
					$customersOwedReport->fetch_lines($socid, $sortfield, $sortorder);
					$this->output .= $customersOwedReport->lines_count . " \n";
					if ($customersOwedReport->lines_count > 0) {
						$email_to = $createdbystatic->email;
						$this->output .= "Send mail to user '" . $createdbystatic->login . "', with PDF 'Customers owed', (" . $createdbystatic->email . ") : ";
						if (! empty($email_to)) {
							$this->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);
							print ".";
							sleep(3);
							$this->output .= "OK \n";
							$mails_sended_count ++;
						} else {
							$this->output .= "KO (this user does not have an email) \n";
						}
					}
					print ".";
				}
			}
		}

		if ($error < 1) {
			if ($conf->global->CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE > 0) {
				// 6) Send mail to all sales representative user, with PDF 'Customers owed'
				$staticuser = new User($this->db);
				$result = $staticuser->fetchAll();
				$users_count = sizeof($staticuser->users);
				foreach ($staticuser->users as $salesrepresentativestatic) {
					$only_user = 0;
					$only_salesrepresentative = $salesrepresentativestatic->id;
					$this->output .= "Load customers owed invoices (whose sales representative is '" . $salesrepresentativestatic->login . "') : ";
					$customersOwedReport = new CustomersOwed($this->db, $customersOwedDate, $exclude_dp, $only_late, $only_user, $salesrepresentativestatic->id);
					$customersOwedReport->fetch_lines($socid, $sortfield, $sortorder);
					$this->output .= $customersOwedReport->lines_count . " \n";
					if ($customersOwedReport->lines_count > 0) {
						$email_to = $salesrepresentativestatic->email;
						$this->output .= "Send mail to user '" . $salesrepresentativestatic->login . "', with PDF 'Customers owed', (" . $salesrepresentativestatic->email . ") : ";
						if (! empty($email_to)) {
							$this->sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield, $sortorder, $email_to, $socid, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);
							print ".";
							sleep(3);
							$this->output .= "OK \n";
							$mails_sended_count ++;
						} else {
							$this->output .= "KO (this user does not have an email) \n";
						}
					}
					print ".";
				}
			}
		}

		if ($mails_sended_count > 1) {
			$this->output .= $this->outputlangs->transnoentities("CustomersOwedMailsSend", $mails_sended_count) . "\n";
		} else {
			$this->output .= $this->outputlangs->transnoentities("CustomersOwedMailSend", $mails_sended_count) . "\n";
		}

		print "\n" . $this->output;

		$this->error = "";
		if ($error > 0) {
			$this->error = $this->errors[0];
			print "\n" . $this->error;
		}

		return $error ? - 1 : 0;
	}
}
