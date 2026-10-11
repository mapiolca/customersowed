<?php
/*
 * Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Charles-Fr BENKE <charles.fr@benke.fr>
 * Copyright (C) 2018 Laurent BOUQUET <dolibarr@joliciel.fr>
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
 * or see http://www.gnu.org/
 */

/**
 * \file htdocs/custom/customersowed/core/modules/customersowed/doc/pdf_joliciel.modules.php
 * \ingroup customersowed
 * \brief File of class to generate customers owed report
 */
dol_include_once('/customersowed/class/customersowed.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

/**
 * Classe permettant de generer un document PDF de 'Dû clients'
 */
require_once __DIR__.'/../../../../class/customersowedpdflayout.trait.php';

class pdf_nextep
{
	use CustomersOwedPdfLayout;
	public $dateColumnWidth;
	public $db;
	public $description;
	public $format;
	public $line_height;
	public $line_per_page;
	public $marge_basse;
	public $marge_droite;
	public $marge_gauche;
	public $marge_haute;
	public $name;
	public $page_hauteur;
	public $page_largeur;
	public $posx180days;
	public $posx30days;
	public $posx60days;
	public $posx90days;
	public $posxCompany;
	public $posxDateDue;
	public $posxLate;
	public $posxLeft;
	public $posxMore180days;
	public $posxTotal_ht;
	public $posxTotal_vat;
	public $result;
	public $tabTop;
	public $tab_height;
	public $tab_width;
	public $totalColumnWidth;
	public $type;
	public $widthDateDue;
	public $widthRtp;

	public $doc_type;

	public $documentDate;

	public $customersOwedDate;

	public $title;

	public $excludeDpInvoices;

	public $onlyLatePaymentInvoices;

	public $onlyInvoicesCreatedBy;

	public $onlyInvoicesWhoseSalesRepresentativeIs;

	var $numberColumnsToAdd;

	var $listColumnsToAdd = array();

	var $listFieldsToAdd = array();

	var $listWidthToAdd = array();

	var $orientation;

	var $warning_image_path;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db
	 *        	Database handler
	 */
	function __construct($db, $columnsToAdd = NULL)
	{
		global $langs, $conf;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment",
			"bills",
			"compta"));

		$this->db = $db;
		$this->name = "nextep";
		$this->description = $langs->trans('PDFNextepCustomersOwedsModel');

		$this->warning_image_path = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/img/warning.png";
		$CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME = getDolGlobalString('CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME');
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME) {
			$this->warning_image_path = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/img/" . $CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME . ".png";
		}
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME == 'none') {
			$this->warning_image_path = "";
		}

		$this->widthDateDue = 21;
		$this->widthRtp = 0;
		$this->posxTotal_ht = 0;
		$this->posxTotal_vat = 0;

		$this->dateColumnWidth = 21;
		$this->totalColumnWidth = 30;

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->orientation = 'L';
		$this->page_largeur = $formatarray['height'];
		$this->page_hauteur = $formatarray['width'];
		$this->format = array(
			$this->page_largeur,
			$this->page_hauteur);

		$this->marge_gauche = getDolGlobalString('MAIN_PDF_MARGIN_LEFT') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_LEFT') : 10;
		$this->marge_droite = getDolGlobalString('MAIN_PDF_MARGIN_RIGHT') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_RIGHT') : 10;
		$this->marge_haute = getDolGlobalString('MAIN_PDF_MARGIN_TOP') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_TOP') : 10;
		$this->marge_basse = getDolGlobalString('MAIN_PDF_MARGIN_BOTTOM') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_BOTTOM') : 10;

		$this->tabTop = $this->marge_haute + 12;

		$this->line_height = 5;
		$this->line_per_page = 40;
		$this->tab_height = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tabTop; // must be > $this->line_height * $this->line_per_page and < $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tabTop - 5;
		$this->tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite; // 190

		$this->posxLeft = $this->marge_gauche; // InvoiceNumber
		$this->posxLate = $this->posxLeft + 18;
		$this->posxCompany = $this->posxLate + 4;
		if (empty($this->warning_image_path)) {
			$this->posxCompany = $this->posxLate;
		}

		$this->posxDateDue = $this->marge_gauche + $this->tab_width - (5 * $this->totalColumnWidth) - $this->dateColumnWidth;

		$this->posx30days = $this->posxDateDue + $this->dateColumnWidth;
		$this->posx60days = $this->posx30days + $this->totalColumnWidth;
		$this->posx90days = $this->posx60days + $this->totalColumnWidth;
		$this->posx180days = $this->posx90days + $this->totalColumnWidth;
		$this->posxMore180days = $this->posx180days + $this->totalColumnWidth;

		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->line_per_page = 35;
			$this->posxCompany -= 0;
			$this->posx30days -= 10;
			$this->posx60days -= 20;
		}
		$this->line_per_page = ($this->tab_height / $this->line_height) - 1;

		// which type of document will be generated: clients (client) or providers (fourn) invoices
		$this->doc_type = "client";

		// Date for customers owed calculation
		$this->customersOwedDate = dol_now();

		// Date of this document
		$this->documentDate = dol_now();
	}

	/**
	 * Fonction generant la rapport sur le disque
	 *
	 * @param int $socid
	 *        	societe Id
	 * @param string $file
	 *        	path and name of PDF file
	 * @param string $outputLangs
	 *        	Lang output object
	 * @return int <0 if KO, >0 if OK
	 */
	// /$customersOwedReport = new CustomersOwed($this->db);
	// /$customersOwedReport->lines
	function write_file($object, $file, $outputLangs, $socid = NULL, $sortfield = NULL, $sortorder = NULL)
	{
		dol_syslog(get_class($this) . "::write_file", LOG_DEBUG);

		include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

		global $conf, $hookmanager, $langs, $user;
		if (!isModEnabled('customersowed') || !$user->hasRight('customersowed', 'write')) { $this->error = 'NotEnoughPermissions'; return -1; }
		$expectedDirectory = $object->getReportDirectory($user);
		if ($expectedDirectory === false || !is_string($file) || realpath(dirname($file)) !== realpath($expectedDirectory)
			|| basename($file) !== $object->pdfreportfilename || is_link($file)) { $this->error = 'ErrorBadParameters'; return -1; }


		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment"));
		if (! is_object($outputLangs))
			$outputLangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '')
			$outputLangs->charset_output = 'ISO-8859-1';

		// Add pdfgeneration hook
		if (! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array(
			'pdfgeneration'));
		$parameters = array(
			'file' => $file,
			'outputlangs' => $outputLangs);
		global $action;
		$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

		$pdf = pdf_getInstance($this->format);
		$pdf->setPageOrientation($this->orientation);
		$defaultFontSize = pdf_getPDFFontSize($outputLangs); // Must be after pdf_getInstance

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputLangs));

		$pdf->Open();
		$pagenb = 0;
		$pdf->SetDrawColor(128, 128, 128);

		if (empty($socid)) {
			if ($user->socid) {
				$socid = $user->socid;
			}
		}

		if (empty($object->customersOwedDate)) {
			$object->customersOwedDate = dol_now();
		}
		$this->customersOwedDate = $object->customersOwedDate;
		$this->excludeDpInvoices = $object->excludeDpInvoices;
		$this->onlyLatePaymentInvoices = $object->onlyLatePaymentInvoices;
		$this->onlyInvoicesCreatedBy = $object->onlyInvoicesCreatedBy;
		$this->onlyInvoicesWhoseSalesRepresentativeIs = $object->onlyInvoicesWhoseSalesRepresentativeIs;

		$this->reportRecipient = null;
		if ($socid > 0 && $object->lines && count(array_filter($object->lines, static function ($line) use ($socid) { return (int) $line->fk_soc !== (int) $socid; })) === 0) {
			$societe = new Societe($this->db);
			if ($societe->fetch($socid) <= 0) { $this->error = 'ErrorRecordNotFound'; return -1; }
			$this->reportRecipient = $societe;
			if (empty($object->title)) {
				// $title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompanyAtDateOf", $societe->name, dol_print_date(dol_now(), "day"));
				$object->title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompany", $societe->name);
			}
		} else {
			$object->title = $outputLangs->transnoentities("CustomersOwedTimetableAtDateOf", dol_print_date($this->customersOwedDate, "day"));
		}
		$this->title = $object->title;

		$pdf->SetTitle(getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . " - " . $object->title);
		$pdf->SetSubject($outputLangs->transnoentities("ModuleCustomersOwed"));
		$pdf->SetCreator("Dolibarr " . DOL_VERSION . " (http://www.joliciel.fr)");
		$pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION') !== '')
			$pdf->SetCompression(false);

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
		$pdf->SetAutoPageBreak(true, 0);
		// $pdf->SetAutoPageBreak(true, $this->marge_basse);

		// New page
		$pdf->AddPage();
		$this->prepareReportHeader($pdf, $outputLangs);
		if (!$this->prepareReportLayout($pdf, $object, $outputLangs)) { return -1; }
		$pagenb ++;
		$this->_pageHeaderAndFooter($pdf, $pagenb, $outputLangs, $socid, $sortfield, $sortorder);
		$pdf->SetFont('', '', 9);
		$pdf->MultiCell(0, 3, ''); // Set interline to 3
		$pdf->SetTextColor(0, 0, 0);

		// /$customersOwedReport = new CustomersOwed($this->db);

		// /if ($customersOwedReport->fetch_lines($socid, $sortfield, $sortorder)) {
		if ($object) {
			$this->Body($pdf, 1, $object->lines, $outputLangs, $socid, $sortfield, $sortorder);
		} else {
			dol_print_error($object->error);
		}

		if (method_exists($pdf, 'AliasNbPages'))
			$pdf->AliasNbPages();

		$this->_pagefoot($pdf, $object, $outputLangs, 0);
		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		if (! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array(
			'pdfgeneration'));
		$parameters = array(
			'file' => $file,
			'object' => $object,
			'outputlangs' => $outputLangs);
		global $action;
		$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		if (getDolGlobalString('MAIN_UMASK') !== '')
			@chmod($file, octdec(getDolGlobalString('MAIN_UMASK')));

		$this->result = array(
			'fullpath' => $file);

		return 1;
	}

	/**
	 * Show top header and bottom footer of page.
	 *
	 * @param PDF $pdf
	 *        	Object PDF
	 * @param int $page
	 *        	Object to show
	 * @param Translate $outputLangs
	 *        	Object lang for output
	 * @return void
	 */
	function _pageHeaderAndFooter(&$pdf, $page, $outputLangs, $socid = NULL, $sortfield = NULL, $sortorder = NULL)
	{
		global $conf, $hookmanager, $langs, $user;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment"));
		if (! is_object($outputLangs))
			$outputLangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '')
			$outputLangs->charset_output = 'ISO-8859-1';

		$defaultFontSize = pdf_getPDFFontSize($outputLangs);
		$this->printReportHeader($pdf, $outputLangs);

		// Invoice number
		$pdf->SetXY($this->posxLeft, $this->tabTop);
		$pdf->Cell($this->posxDateDue - $this->posxLeft, $this->line_height, $outputLangs->transnoentities("Ref"), 'LTRB', 0, 'L', false, '', 1);

		// Company
		$pdf->SetXY($this->posxCompany, $this->tabTop);
		$pdf->Cell($this->posxDateDue - $this->posxCompany, $this->line_height, $outputLangs->transnoentities("Customer"), 'LTRB', 0, 'L', false, '', 1);

		// DateDu
		$pdf->SetXY($this->posxDateDue, $this->tabTop);
		$pdf->Cell($this->dateColumnWidth, $this->line_height, $outputLangs->transnoentities("DateDue"), 'LTRB', 0, 'C', false, '', 1);

		// 30days
		$pdf->SetXY($this->posx30days, $this->tabTop);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, $outputLangs->transnoentities("CustomersOwed30days"), 'LTRB', 0, 'C', false, '', 1);

		// 60days
		$pdf->SetXY($this->posx60days, $this->tabTop);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, $outputLangs->transnoentities("CustomersOwed60days"), 'LTRB', 0, 'C', false, '', 1);

		// 90days
		$pdf->SetXY($this->posx90days, $this->tabTop);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, $outputLangs->transnoentities("CustomersOwed90days"), 'LTRB', 0, 'C', false, '', 1);

		// 180days
		$pdf->SetXY($this->posx180days, $this->tabTop);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, $outputLangs->transnoentities("CustomersOwed180days"), 'LTRB', 0, 'C', false, '', 1);

		// More180days
		$pdf->SetXY($this->posxMore180days, $this->tabTop);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, $outputLangs->transnoentities("CustomersOwedMore180days"), 'LTRB', 0, 'C', false, '', 1);

		// Only late payment invoices
		$onlyLatePaymentInvoicesMessage = "";
		if ($this->onlyLatePaymentInvoices) {
			$onlyLatePaymentInvoicesMessage = $outputLangs->transnoentities('OnlyLatePaymentInvoices') . " - ";
		}

		// Exclude downpayment invoices
		$excludeDpInvoicesMessage = "";
		if ($this->excludeDpInvoices) {
			$excludeDpInvoicesMessage = $outputLangs->transnoentities('ExcludeDownpaymentInvoices') . " - ";
		}

		// Sort message
		$sortMessage = "";
		if ($sortorder == "desc") {
			$sortOrderMessage = $outputLangs->transnoentities("DescendingOrder");
		} else {
			$sortOrderMessage = $outputLangs->transnoentities("AscendingOrder");
		}
		if ($sortfield) {
			$sortMessage = $outputLangs->transnoentities("InvoicesSortedBy", $sortOrderMessage, $outputLangs->transnoentities("CustomersOwed_" . $sortfield));
		}

		// Only invoices created by
		$onlyInvoicesCreatedByMessage = "";
		if ($this->onlyInvoicesCreatedBy > 0) {
			$userstaticCreatedBy = new User($this->db);
			$userstaticCreatedBy->fetch($this->onlyInvoicesCreatedBy);
			$onlyInvoicesCreatedByMessage = $outputLangs->transnoentities('CustomersOwedCreatedBy', trim($userstaticCreatedBy->firstname . " " . $userstaticCreatedBy->lastname));
		}

		// Only invoices whose sales representative is
		$onlyInvoicesWhoseSalesRepresentativeIs = "";
		if ($this->onlyInvoicesWhoseSalesRepresentativeIs > 0) {
			$userstaticSalesRepresentativeIs = new User($this->db);
			$userstaticSalesRepresentativeIs->fetch($this->onlyInvoicesWhoseSalesRepresentativeIs);
			$onlyInvoicesWhoseSalesRepresentativeIsMessage = $outputLangs->transnoentities('OnlyInvoicesWhoseSalesRepresentativeIs') . " " . trim($userstaticSalesRepresentativeIs->firstname . " " . $userstaticSalesRepresentativeIs->lastname);
		}
	}

	/**
	 * Output body
	 *
	 * @param PDF $pdf
	 *        	PDF object
	 * @param string $page
	 *        	Page
	 * @param array $lines
	 *        	Array of lines
	 * @param Translate $outputLangs
	 *        	Object langs
	 * @return void
	 */
	function Body(&$pdf, $page, $lines, $outputLangs, $socid = NULL, $sortfield = NULL, $sortorder = NULL)
	{
		global $conf, $langs;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment",
			"banks"));
		if (! is_object($outputLangs))
			$outputLangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '')
			$outputLangs->charset_output = 'ISO-8859-1';

		$defaultFontSize = pdf_getPDFFontSize($outputLangs);

		$facturestatic = new Facture($this->db);
		$societestatic = new Societe($this->db);

		$total_rtp = $total_amount = $total_ttc = $total_vat = $total_ht = 0;

		$pdf->SetFont('', '', $defaultFontSize - 1);
		$pdf->SetFillColor(240, 241, 243); // or (220,220,220)
		$yp = 0;
		$numLines = count($lines);
		$numDelays = 0;

		$total_ttc_30days = 0;
		$total_ttc_60days = 0;
		$total_ttc_90days = 0;
		$total_ttc_180days = 0;
		$total_ttc_More180days = 0;

		$fill = false;
		for ($i = 0; $i < $numLines; $i ++) {
			$obj = $lines[$i];

			$societestatic->name = $obj->name;

			$facturestatic->ref = $obj->facnumber;
			$facturestatic->id = $obj->id;
			$facturestatic->type = $obj->type;
			$facturestatic->entity = $obj->entity;
			$facturestatic->statut = $obj->fk_statut;
			$facturestatic->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
			$facturestatic->total_ttc = $obj->total_ttc;

			/*
			 * $paiement = $facturestatic->getSommePaiement();
			 * $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
			 * $totaldeposits = $facturestatic->getSumDepositsUsed();
			 * $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
			 */
			$totalpay = $obj->am;

			$remaintopay = $facturestatic->total_ttc - $totalpay;


			if ($numLines > $i + 2) {
				$yChangePage = $this->tab_height;
			} else {
				$yChangePage = $this->tab_height - ($this->line_height * 2);
			}

			if ($yp > $yChangePage) {
				// End of tab
				$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
				$pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->line_height, '', 'T', 0, 'L', false, '', 1);
				// New page
				$this->_pagefoot($pdf, $this->reportObject, $outputLangs, 1);
				$pdf->AddPage();
				$page ++;
				$this->_pageHeaderAndFooter($pdf, $page, $outputLangs, $socid, $sortfield, $sortorder);
				$pdf->SetFont('', '', $defaultFontSize - 1);
				$yp = 0;
				$fill = false;
			}

			// Invoice number and company
			$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxLate - $this->posxLeft, $this->line_height, $obj->facnumber, 'L', 0, 'L', $fill, '', 1);

			$pdf->SetXY($this->posxLate, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxCompany - $this->posxLate, $this->line_height, '', 0, 0, 'L', $fill, '', 1);
			/*if ($obj->hasDelay() && ! empty($this->warning_image_path)) {
				$numDelays ++;
				$pdf->Image($this->warning_image_path, $this->posxLate, $this->tabTop + $this->line_height + $yp + 0.4, 4, 4);
			}*/
			$pdf->SetXY($this->posxCompany, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxDateDue - $this->posxCompany, $this->line_height, $obj->name, 'LR', 0, 'L', $fill, '', 1);

			// DateDue
			$pdf->SetXY($this->posxDateDue, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->widthDateDue, $this->line_height, dol_print_date($this->db->jdate($obj->date_lim_reglement), 'day'), 'LR', 0, 'C', $fill);

			// 30days + 60days + 90days + 180days + More180days
			$pdf->SetXY($this->posx30days, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, "", 'LR', 0, 'R', $fill, '', 1);
			$pdf->SetXY($this->posx60days, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, "", 'LR', 0, 'R', $fill, '', 1);
			$pdf->SetXY($this->posx90days, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, "", 'LR', 0, 'R', $fill, '', 1);
			$pdf->SetXY($this->posx180days, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, "", 'LR', 0, 'R', $fill, '', 1);
			$pdf->SetXY($this->posxMore180days, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, "", 'LR', 0, 'R', $fill, '', 1);

			// Placer dans la bonne colonne le montant
			$total_remaintopay = $remaintopay;
			$posx = $this->posx30days;
			$date1 = new DateTime(dol_print_date(dol_now(), "dayrfc"));
			$date2 = new DateTime($obj->date_lim_reglement);
			$diff = $date2->diff($date1)->format("%a");
			if ($diff < 30) {
				$posx = $this->posx30days;
				$total_ttc_30days += $total_remaintopay;
			}
			if ( ($diff > 30) && ($diff <= 60) ) {
				$posx = $this->posx30days + $this->totalColumnWidth;
				$total_ttc_60days += $total_remaintopay;
			}
			if ( ($diff > 60) && ($diff <= 90) ) {
			$posx = $this->posx30days + (2 * $this->totalColumnWidth);
				$total_ttc_90days += $total_remaintopay;
			}
			if ( ($diff > 90) && ($diff <= 180) ) {
			$posx = $this->posx30days + (3 * $this->totalColumnWidth);
				$total_ttc_180days += $total_remaintopay;
			}
			if ($diff > 180) {
			$posx = $this->posx30days + (4 * $this->totalColumnWidth);
				$total_ttc_More180days += $total_remaintopay;
			}

			$pdf->SetXY($posx, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_remaintopay), 'LR', 0, 'R', $fill);

			// Totals
			$total_ht += $obj->total_ht;
			$total_vat += $obj->total_vat;
			$total_ttc += $obj->total_ttc;
			$total_amount += $obj->am;
			$total_rtp += $remaintopay;

			$yp = $yp + $this->line_height;
			$fill = ! $fill;
		}

		// End of tab
		$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->line_height, '', 'T', 0, 'L', false, '', 1);

		// Totals
		$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp + 0.5);
		$pdf->MultiCell($this->posxDateDue - $this->posxLeft + 21, $this->line_height, $outputLangs->transnoentities("RemainderToTakeAtDateOf", dol_print_date($this->customersOwedDate, "day"), price($total_ttc - $total_amount)), 0, 'L', false, 1, '', '', true, 0, true, true, 0, 'M', false);

		// 30days
		$pdf->SetXY($this->posx30days, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_ttc_30days), 0, 0, 'R', false, '', 1);

		// 60days
		$pdf->SetXY($this->posx60days, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_ttc_60days), 0, 0, 'R', false, '', 1);

		// 90days
		$pdf->SetXY($this->posx90days, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_ttc_90days), 0, 0, 'R', false, '', 1);

		// 180days
		$pdf->SetXY($this->posx180days, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_ttc_180days), 0, 0, 'R', false, '', 1);

		// More180days
		$pdf->SetXY($this->posxMore180days, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->totalColumnWidth, $this->line_height, price($total_ttc_More180days), 0, 0, 'R', false, '', 1);

		$yp = $yp + $this->line_height;

		/* // WarningDelay
		if ($numDelays > 0 && ! empty($this->warning_image_path)) {
			$numDays = abs($conf->facture->client->warning_delay) / 60 / 60 / 24;
			$pdf->Image($this->warning_image_path, $this->posxLeft, $this->page_hauteur - $this->heightforfooter - $this->line_height + 0.4, 4, 4);
			$pdf->SetFont('', '', $defaultFontSize - 2);
			$pdf->SetXY($this->posxLeft + 3, $this->page_hauteur - $this->heightforfooter - $this->line_height);
			$pdf->Cell(80, $this->line_height, "= " . $outputLangs->transnoentities('WarningDelay', $numDays), 0, 0, 'L', 0, false, '', 0);
		}*/
	}
}
