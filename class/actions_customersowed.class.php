<?php

/*
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
 * \file customersowed/class/actions_customersowed.class.php
 * \ingroup customersowed
 * \brief Example hook overload.
 *
 * Put detailed description here.
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once __DIR__.'/customersowed.class.php';
require_once __DIR__.'/customersowedmail.class.php';

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
	public $output = '';
	public $result = '';
	public $lastresult = 0;

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
		 * if (getDolGlobalString('MAIN_MULTILANGS') && empty($newlang) && ! empty($lang_id))
		 * $newlang = $lang_id;
		 * if (getDolGlobalString('MAIN_MULTILANGS') && empty($newlang))
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
	 * Generate the pdf due customer.
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function generateCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield = NULL, $sortorder = NULL, $socid = NULL, $exclude_dp = NULL, $only_late = NULL, $only_user = NULL, $only_salesrepresentative = NULL)
	{
		global $conf;

		$error = 0;
		$this->error = '';
		$this->errors = array();

		if (!isModEnabled('customersowed') || !$user->hasRight('customersowed', 'write')) {
			$this->error = 'NotEnoughPermissions';
			return -1;
		}
		$expectedDirectory = $customersOwedReport->getReportDirectory($user);
		if ($expectedDirectory === false || realpath($pdfreportfilesdir) !== realpath($expectedDirectory)
			|| !preg_match('/^[a-z0-9_]+$/D', (string) $model)) {
			$this->error = 'ErrorBadParameters';
			return -1;
		}
		$this->filename = $customersOwedReport->pdfreportfilename;
		$this->fileNameToSend = $pdfreportfilesdir . '/' . $this->filename;

		dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Start generating PDF fileNameToSend = '" . $this->fileNameToSend . "' model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_DEBUG);

		$sav_charset_output = $this->outputlangs->charset_output;

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
			if ($pdf_module->write_file($customersOwedReport, $pdfreportfilesdir . '/' . $customersOwedReport->pdfreportfilename, $this->outputlangs, $socid, $sortfield, $sortorder) <= 0) {
				$error ++;
				$this->error = "CanNotWriteFile";
				$this->errors[] = $this->error;
				dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Error during generating PDF : Can not write file - model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_ERR);
			}
		} else {
			$error ++;
			$this->error = "ErrorModuleNotFound";
			$this->errors[] = $this->error;
			dol_syslog(get_class($this) . "::generateCustomersowedPdf " . "Error during generating PDF : Module not found - model = '" . $model . "' socid = '" . $socid . "' sortfield = '" . $sortfield . "' sortorder = '" . $sortorder . "' ", LOG_ERR);
		}

		if (!$error && $customersOwedReport->recordDocumentManifest($this->fileNameToSend) < 0) {
			$error++;
			$this->errors[] = $customersOwedReport->error;
		}
		$this->error = $this->errors[0] ?? '';
		$this->lastresult = $error ? -1 : 1;

		return $error ? - 1 : 0;
	}

	/**
	 * Send a mail with the pdf due customer to email_to.
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function sendMailWithCustomersowedPdf($user, $customersOwedReport, $pdfreportfilesdir, $model, $sortfield = NULL, $sortorder = NULL, $email_to = NULL, $socid = NULL, $exclude_dp = NULL, $only_late = NULL, $only_user = NULL, $only_salesrepresentative = NULL, $recipient = null)
	{
		global $conf, $mysoc;
		$this->errors = array();

		$error = 0;
		if (is_object($recipient)) {
			global $langs;
			if ((int) $recipient->statut !== 1 || !in_array((int) $recipient->entity, array(0, (int) $conf->entity), true)
				|| $recipient->email !== $email_to || !$recipient->hasRight('customersowed', 'read')) {
				$this->error = 'NotEnoughPermissions';
				return -1;
			}
			$scoped = new CustomersOwed($this->db, $customersOwedReport->customersOwedDate, $exclude_dp, $only_late, $only_user, $only_salesrepresentative);
			$scoped->selectedEntities = $customersOwedReport->selectedEntities;
			if ($scoped->fetch_lines($socid, $sortfield, $sortorder, $recipient) < 0) { $this->error = $scoped->error; return -1; }
			$executorIds = array_map(static function ($line) { return (int) $line->id; }, $customersOwedReport->lines);
			$scoped->lines = array_values(array_filter($scoped->lines, static function ($line) use ($executorIds) { return in_array((int) $line->id, $executorIds, true); }));
			$scoped->lines_count = count($scoped->lines);
			$scoped->totalCustomersOwed = 0;
			foreach ($scoped->lines as $line) { $scoped->totalCustomersOwed = price2num($scoped->totalCustomersOwed + $line->total_ttc - $line->am, 'MT'); }
			if (!$scoped->lines_count) { $this->error = 'NoRecordFound'; return -1; }
			$customersOwedReport = $scoped;
		}

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
			if (getDolGlobalString('CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM') !== '') {
				$email_from = getDolGlobalString('CUSTOMERSOWEDREPORT_PDF_EMAIL_FROM');
			} else {
				if ((empty($email_from)) && getDolGlobalString('PDF_EMAIL_FROM') !== '') {
					$email_from = getDolGlobalString('PDF_EMAIL_FROM');
				} else {
					if ((empty($email_from)) && getDolGlobalString('MAIN_INFO_SOCIETE_MAIL') !== '') {
						$email_from = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
					} else {
						if ((empty($email_from)) && getDolGlobalString('MAIN_MAIL_EMAIL_FROM') !== '') {
							$email_from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
						} else {
							if ((empty($email_from)) && getDolGlobalString('MAILING_EMAIL_FROM') !== '') {
								$email_from = getDolGlobalString('MAILING_EMAIL_FROM');
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
			if (getDolGlobalString('MAIN_APPLICATION_TITLE') !== '')
				$application = getDolGlobalString('MAIN_APPLICATION_TITLE');

			$email_subject = "";
			$email_body = "";
			$email_subject_template = getDolGlobalString('CUSTOMERSOWEDREPORT_PDF_EMAIL_SUBJECT_TEMPLATE', $this->outputlangs->trans('CustomersowedMessageMailSubject'));

			// Keep personalized templates; upgrade only the unchanged translated default.
			$legacyBody = $this->outputlangs->trans('CustomersowedMessageMailBody');
			$email_body_template = CustomersOwedMail::selectTemplate(
				getDolGlobalString('CUSTOMERSOWEDREPORT_PDF_EMAIL_BODY_TEMPLATE', $legacyBody), $legacyBody);

			$CUSTOMERSOWEDREPORT_EXCLUDE_DP = "";
			if (! isset($exclude_dp)) {
				$CUSTOMERSOWEDREPORT_EXCLUDE_DP = getDolGlobalString('CUSTOMERSOWEDREPORT_EXCLUDE_DP') ? $this->outputlangs->trans("ExcludeDownpaymentInvoicesTooltip") : "";
			} else {
				if ($exclude_dp > 0) {
					$CUSTOMERSOWEDREPORT_EXCLUDE_DP = $this->outputlangs->trans("ExcludeDownpaymentInvoicesTooltip");
				}
			}

			$CUSTOMERSOWEDREPORT_ONLY_LATE = "";
			if (! isset($only_late)) {
				$CUSTOMERSOWEDREPORT_ONLY_LATE = getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_LATE') ? $this->outputlangs->trans("OnlyLatePaymentInvoicesTooltip") : "";
			} else {
				if ($only_late > 0) {
					$CUSTOMERSOWEDREPORT_ONLY_LATE = $this->outputlangs->trans("OnlyLatePaymentInvoicesTooltip");
				}
			}

			$CUSTOMERSOWEDREPORT_ONLY_CREATED_BY = ""; // $this->outputlangs->trans("Everybody");
			if (! isset($only_user)) {
				if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY')) {
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
				if (getDolGlobalString('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY')) {
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
				'__DATE_YMD__' => dol_print_date($customersOwedReport->customersOwedDate, 'day', 0, $this->outputlangs),
				'__DATE_YMDHMS__' => dol_print_date(dol_now(), 'dayhoursec', 0, $this->outputlangs),
				'__CUSTOMERSOWEDREPORT_EXCLUDE_DP__' => $CUSTOMERSOWEDREPORT_EXCLUDE_DP,
				'__CUSTOMERSOWEDREPORT_ONLY_LATE__' => $CUSTOMERSOWEDREPORT_ONLY_LATE,
				'__CUSTOMERSOWEDREPORT_ONLY_CREATED_BY__' => $CUSTOMERSOWEDREPORT_ONLY_CREATED_BY,
				'__CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE__' => $CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE,
				'__FILE_NAME__' => $this->filename,
				'__RESULT__' => $this->lastresult > 0 ? $this->outputlangs->transnoentities("CustomersowedResultOK") : $this->outputlangs->transnoentities("CustomersowedResultError")));

			$DOL_MAIN_URL_ROOT = "";
			$CUSTOMERSOWEDREPORT_URL = "";
			$DOL_MAIN_URL_ROOT = DOL_MAIN_URL_ROOT;
			if (($socid < 1) && (getDolGlobalString('CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL') > 0)) {
				$DOL_MAIN_URL_ROOT = DOL_MAIN_URL_ROOT;
				$CUSTOMERSOWEDREPORT_URL = dol_buildpath('/customersowed/view/customers_owed_report.php', 2);
			}
			$substitutionarray = array_merge($substitutionarray, array(
				'__DOL_MAIN_URL_ROOT__' => $DOL_MAIN_URL_ROOT,
				'__CUSTOMERSOWEDREPORT_URL__' => $CUSTOMERSOWEDREPORT_URL));

			$email_subject = make_substitutions($email_subject_template, $substitutionarray, $this->outputlangs);

			$withLinks = $socid < 1 && getDolGlobalInt('CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL') > 0;
			$listTable = CustomersOwedMail::buildInvoiceTable($customersOwedReport, $this->outputlangs, $this->db, $withLinks);
			// Escape business values before inserting them into an HTML template.
			$bodySubstitutions = array_map(static function ($value) { return dol_escape_htmltag((string) $value); }, $substitutionarray);
			$bodySubstitutions['__MAIN_INFO_SOCIETE_ADDRESS__'] = dol_nl2br($bodySubstitutions['__MAIN_INFO_SOCIETE_ADDRESS__']);
			$filters = array_filter(array($CUSTOMERSOWEDREPORT_EXCLUDE_DP, $CUSTOMERSOWEDREPORT_ONLY_LATE,
				$CUSTOMERSOWEDREPORT_ONLY_CREATED_BY, $CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE));
			$filterBlock = '';
			if ($filters) {
				$filterBlock = '<p style="margin:0 0 20px;color:#526174">'.implode('<br>', array_map('dol_escape_htmltag', $filters)).'</p>';
			}
			$actionBlock = '';
			if ($withLinks) {
				$actionBlock = '<a href="'.dol_escape_htmltag($CUSTOMERSOWEDREPORT_URL).'" style="background-color:#253449;border-radius:6px;color:#ffffff;display:inline-block;font-weight:bold;padding:14px 20px;text-decoration:none">'
					.dol_escape_htmltag($this->outputlangs->transnoentities('CustomersOwedMailOpenReport')).'</a>';
			}
			$bodySubstitutions['__CUSTOMERSOWEDREPORT_INVOICES_LIST_TABLE__'] = $listTable;
			$bodySubstitutions['__CUSTOMERSOWEDREPORT_TOTAL_OWED__'] = price($customersOwedReport->totalCustomersOwed, 0, $this->outputlangs);
			$bodySubstitutions['__CUSTOMERSOWEDREPORT_INVOICE_COUNT__'] = (string) count($customersOwedReport->lines);
			$bodySubstitutions['__CUSTOMERSOWEDREPORT_FILTERS_BLOCK__'] = $filterBlock;
			$bodySubstitutions['__CUSTOMERSOWEDREPORT_ACTION_BLOCK__'] = $actionBlock;
			// Convert plain-text newlines before adding the generated HTML table.
			$email_body_template = dol_textishtml($email_body_template)
				? dol_htmlwithnojs($email_body_template)
				: dol_nl2br(dol_escape_htmltag($email_body_template));
			$email_body = make_substitutions($email_body_template, $bodySubstitutions, $this->outputlangs);

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

		$this->error = $this->errors[0] ?? '';

		return $error ? - 1 : 0;
	}

	/**
	 * Run the native scheduled job under its configured user and entity.
	 * Each recipient receives the intersection of their own scope and the executor's.
	 * A daily pending marker prevents replay after an uncertain SMTP outcome.
	 * @return int 0 on success, -1 on error
	 */
	public function sendMails()
	{
		global $conf, $user;
		$this->output = '';
		$this->error = '';
		if (!CustomersOwedCompatibility::getFeatures()['CustomersOwedCronCapability']['available'] || !isModEnabled('customersowed')
			|| !$user->hasRight('customersowed', 'write')) {
			$this->error = 'NotEnoughPermissions';
			return -1;
		}
		$report = new CustomersOwed($this->db, dol_now(),
			getDolGlobalInt('CUSTOMERSOWEDREPORT_EXCLUDE_DP'), getDolGlobalInt('CUSTOMERSOWEDREPORT_ONLY_LATE'));
		if ($report->fetch_lines() < 0 || ($directory = $report->getReportDirectory($user)) === false) {
			$this->error = $report->error;
			return -1;
		}
		$metadata = dirname($directory).'/.cron';
		if (is_link($metadata) || dol_mkdir($metadata) < 0 || is_link($metadata.'/cron.lock')) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return -1;
		}
		$lock = fopen($metadata.'/cron.lock', 'c');
		if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
			if (is_resource($lock)) { fclose($lock); }
			$this->error = 'CustomersOwedCronBusy';
			return -1;
		}
		try {
			$recipientIds = array((int) $user->id);
			if (getDolGlobalInt('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY') || getDolGlobalInt('CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE')) {
				$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'user WHERE statut = 1 AND entity IN (0, '.((int) $conf->entity).')';
				$resql = $this->db->query($sql);
				if (!$resql) { $this->error = $this->db->lasterror(); return -1; }
				while (is_object($row = $this->db->fetch_object($resql))) { $recipientIds[] = (int) $row->rowid; }
				$this->db->free($resql);
			}
			$allowedIds = array_map(static function ($line) { return (int) $line->id; }, $report->lines);
			$sent = 0;
			foreach (array_unique($recipientIds) as $id) {
				$recipient = new User($this->db);
				if ($recipient->fetch($id) <= 0) { $this->error = 'ErrorRecordNotFound'; return -1; }
				$recipient->getrights();
				if ((int) $recipient->statut !== 1 || !in_array((int) $recipient->entity, array(0, (int) $conf->entity), true)
					|| !$recipient->hasRight('customersowed', 'read') || empty($recipient->email)) { continue; }
				$variants = $id === (int) $user->id ? array(array(0, 0)) : array();
				if (getDolGlobalInt('CUSTOMERSOWEDREPORT_ONLY_CREATED_BY')) { $variants[] = array($id, 0); }
				if (getDolGlobalInt('CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE')) { $variants[] = array(0, $id); }
				foreach ($variants as $variant) {
					$scoped = new CustomersOwed($this->db, $report->customersOwedDate, $report->excludeDpInvoices,
						$report->onlyLatePaymentInvoices, $variant[0], $variant[1]);
					if ($scoped->fetch_lines(null, null, null, $recipient) < 0) { $this->error = $scoped->error; return -1; }
					$scoped->lines = array_values(array_filter($scoped->lines,
						static function ($line) use ($allowedIds) { return in_array((int) $line->id, $allowedIds, true); }));
					$scoped->lines_count = count($scoped->lines);
					$scoped->totalCustomersOwed = 0;
					foreach ($scoped->lines as $line) { $scoped->totalCustomersOwed = price2num($scoped->totalCustomersOwed + $line->total_ttc - $line->am, 'MT'); }
					if (!$scoped->lines_count) { continue; }
					$key = hash('sha256', dol_print_date(dol_now(), '%Y-%m-%d').'|'.$id.'|'.implode(',', $variant));
					$marker = $metadata.'/mail-'.$key.'.json';
					if (file_exists($marker) || is_link($marker)) {
						$state = json_decode((string) file_get_contents($marker), true);
						if (!is_array($state) || ($state['state'] ?? '') !== 'sent') {
							$this->error = 'CustomersOwedMailUncertain'; return -1;
						}
						continue;
					}
					if (file_put_contents($marker, '{"state":"pending"}', LOCK_EX) === false) {
						$this->error = 'CustomersOwedDirectoryUnavailable'; return -1;
					}
					if ($this->sendMailWithCustomersowedPdf($user, $scoped, $directory,
						getDolGlobalString('CUSTOMERSOWEDREPORT_ADDON_PDF'), null, null, $recipient->email,
						null, $scoped->excludeDpInvoices, $scoped->onlyLatePaymentInvoices, $variant[0], $variant[1]) < 0) { return -1; }
					if (file_put_contents($marker, '{"state":"sent"}', LOCK_EX) === false) {
						$this->error = 'CustomersOwedMailUncertain'; return -1;
					}
					$sent++;
				}
			}
			$this->output = $this->outputlangs->trans('CustomersOwedMailsSend', $sent);
			return 0;
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	/** Native fileslib security hook; legacy unscoped reports are deliberately refused.
	 * @param array<string,mixed> $parameters Native document parameters
	 * @param CommonObject $object Native hook object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Native hook manager
	 * @return int 0: continue native processing
	 */
	public function checkSecureAccess($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;
		if (($parameters['modulepart'] ?? '') !== 'customersowed') { return 0; }
		$actor = $parameters['fuser'] ?? null;
		if (!is_object($actor) || !isModEnabled('customersowed')
			|| !$actor->hasRight('customersowed', 'read') || (int) ($parameters['entity'] ?? 0) !== (int) $conf->entity) {
			accessforbidden();
		}
		$report = new CustomersOwed($this->db);
		$directory = $report->getReportDirectory($actor);
		$file = $parameters['original_file'] ?? '';
		if ($directory === false || !is_string($file) || is_link($file)
			|| realpath(dirname($file)) !== realpath($directory)
			|| !preg_match('/^(SPECIMEN(?:_(?:standard|nextep|malliaros|joliciel))?|[0-9]{4}-[0-9]{2}-[0-9]{2}-[a-f0-9]{20})\.pdf$/D', basename($file))) { accessforbidden(); }
		if (preg_match('/^SPECIMEN(?:_(?:standard|nextep|malliaros|joliciel))?\.pdf$/D', basename($file))) { return 0; }
		$manifestPath = $directory.'/.metadata/'.basename($file).'.json';
		if (is_link($directory.'/.metadata') || is_link($manifestPath) || !is_file($manifestPath)) { accessforbidden(); }
		$manifest = json_decode((string) file_get_contents($manifestPath), true);
		if (!is_array($manifest) || !isset($manifest['date'], $manifest['invoices'])
			|| !is_int($manifest['date']) || !is_array($manifest['invoices'])) { accessforbidden(); }
		$report->customersOwedDate = $manifest['date'];
		if ($report->fetch_lines(null, null, null, $actor) < 0) { accessforbidden(); }
		$accessible = array_map(static function ($line) { return (int) $line->id; }, $report->lines);
		if (array_diff($manifest['invoices'], $accessible)) { accessforbidden(); }
		return 0;
	}

}
