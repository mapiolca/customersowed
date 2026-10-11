<?php
/*
 * Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Charles-Fr BENKE <charles.fr@benke.fr>
 * Copyright (C) 2018 Laurent BOUQUET <dolibarr@joliciel.fr>
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
 * \file customersowed/core/modules/customersowed/doc/pdf_joliciel.modules.php
 * \ingroup customersowed
 * \brief File of class to generate customers owed report
 */
dol_include_once('/suppliersowed/class/customersowed.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';


/**
 * Fonction permettant de trier les factures par nom de client
 */
function compareSocNom($soc1, $soc2) {
	return strcmp($soc1->name, $soc2->name);
}

/**
 * Classe permettant de generer un document PDF de 'Dû clients'
 */
require_once __DIR__.'/../../../../class/customersowedpdflayout.trait.php';

class pdf_malliaros
{
	use CustomersOwedPdfLayout;
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
	public $posxColumnToAdd0;
	/** @var array<string,float> */
	public $columnPositions = array();
	public $posxCompany;
	public $posxDateDue;
	public $posxInvoiceAmountTTC;
	public $posxLate;
	public $posxLeft;
	public $posxReceived;
	public $posxRtp;
	public $posxTotal_ht;
	public $posxTotal_vat;
	public $result;
	public $tabTop;
	public $tab_height;
	public $tab_width;
	public $type;
	public $widthRtp;

	public $doc_type;

	public $documentDate;

	public $customersOwedDate;

	public $title;

	public $excludeDpInvoices;

	public $onlyLatePaymentInvoices;

	public $onlyInvoicesCreatedBy;

	public $onlyInvoicesWhoseSalesRepresentativeIs;

	public $numberColumnsToAdd;

	public $listColumnsToAdd = array();

	public $listFieldsToAdd = array();

	public $listWidthToAdd = array();

	public $orientation;

	public $warning_image_path;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db
	 *            Database handler
	 */
	public function __construct($db, $columnsToAdd = null)
	{
		global $langs, $conf;

		// Translations
		$langs->loadLangs(array(
			"companies",
			"main",
			"invoice",
			"payment",
			"bills",
			"compta"));

		$this->db = $db;
		$this->name = "malliaros";
		$this->description = $langs->trans('PDFMalliarosCustomersOwedsModel');

		$this->warning_image_path = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/img/warning.png";
		$CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME = getDolGlobalString('CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME');
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME) {
			$this->warning_image_path = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/img/" . $CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME . ".png";
		}
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME == 'none') {
			$this->warning_image_path = "";
		}

		$this->widthRtp = 0;
		$this->posxTotal_ht = 0;
		$this->posxTotal_vat = 0;

		$littleColumnWidth = 21;

		// Columns to add
		$listAvailablesColumns = array(
			"s.town",
			"s.zip",
			"state.nom",
			"country.code_iso",
			"typent.code",
			"f.total_ht",
			"f.total_vat");
		if ($columnsToAdd) {
			$this->numberColumnsToAdd = 0;
			$listColumnsToAdd = explode('$', $columnsToAdd);
			foreach ($listColumnsToAdd as $key => $value) {
				if (!empty($value)) {
					// setEventMessages($value, NULL);
					if ($value == 'rtp') {
						$this->widthRtp = 21;
					}
					if (in_array($value, $listAvailablesColumns)) {
						if ($this->numberColumnsToAdd == 5) {
							setEventMessages("Le document PDF permet d'afficher jusqu'à 5 colonnes supplémentaires", null);
							break;
						}
						$this->numberColumnsToAdd++;
						$this->listColumnsToAdd[] = $value;
						$this->listFieldsToAdd[] = str_replace(".", "_", str_replace("f.", "", str_replace("s.", "", $value)));
					}
				}
			}
			$this->listColumnsToAdd = array_reverse($this->listColumnsToAdd);
			$this->listFieldsToAdd = array_reverse($this->listFieldsToAdd);
		}

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->orientation = 'P';
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$orientationWidthDifference = 0;
		if ($this->numberColumnsToAdd > 2) {
			$this->orientation = 'L';
			$this->page_largeur = $formatarray['height'];
			$this->page_hauteur = $formatarray['width'];
			// $orientationWidthDifference = 87; //29,7 cm - 21 cm
		}
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

		// if ($this->numberColumnsToAdd > 1 ) {
		//     $this->posxDateDue = $this->marge_gauche + $this->tab_width - (2 * $littleColumnWidth) - $this->widthRtp;
		// } else {
		//     $this->posxDateDue = $this->marge_gauche + $this->tab_width - (1 * $littleColumnWidth) - $this->widthRtp;
		// }
		$this->posxDateDue = $this->marge_gauche + $this->tab_width - (2 * $littleColumnWidth) - $this->widthRtp;

		// Columns to add
		$this->posxColumnToAdd0 = $this->posxDateDue;
		$this->columnPositions['posxColumnToAdd0'] = $this->posxColumnToAdd0;
		for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
			$posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
			$posxColumnPrevious = "posxColumnToAdd" . ($j);
			$columnWidth = 35;
			if (substr($this->listFieldsToAdd[$j], 0, 5) == 'total') {
				$columnWidth = $littleColumnWidth;
			}
			if ($this->listFieldsToAdd[$j] == 'zip') {
				$columnWidth = 12;
			}
			if ($this->listFieldsToAdd[$j] == 'town') {
				$columnWidth = 61;
			}
			$this->columnPositions[$posxColumnsToAdd] = $this->columnPositions[$posxColumnPrevious]-$columnWidth;
			$this->listWidthToAdd[$j] = $columnWidth;
		}

		// // Move DateDue column at the left of totals
		for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
			$posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
			/*
			if (($this->columnPositions[$posxColumnsToAdd] < $this->posxDateDue) && (substr($this->listFieldsToAdd[$j], 0, 5) == 'total')) {
				$_posxDateDue = $this->posxDateDue;
				$this->posxDateDue = $this->columnPositions[$posxColumnsToAdd];
				$this->columnPositions[$posxColumnsToAdd] = $_posxDateDue;
			}
			*/
			if ($this->listFieldsToAdd[$j] == 'total_ht') {
				$this->posxTotal_ht = $this->columnPositions[$posxColumnsToAdd];
			}
			if ($this->listFieldsToAdd[$j] == 'total_vat') {
				$this->posxTotal_vat = $this->columnPositions[$posxColumnsToAdd];
			}
		}

		// $this->posxInvoiceAmountTTC = 164 + $orientationWidthDifference - $this->widthRtp;
		$this->posxInvoiceAmountTTC = $this->marge_gauche + $this->tab_width - (2 * $littleColumnWidth) - $this->widthRtp;
		// $this->posxReceived = 185 + $orientationWidthDifference - $this->widthRtp;
		$this->posxReceived = $this->marge_gauche + $this->tab_width - $littleColumnWidth - $this->widthRtp;
		// $this->posxRtp = 185 + $orientationWidthDifference;
		$this->posxRtp = $this->marge_gauche + $this->tab_width - $this->widthRtp;

		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->line_per_page = 35;
			$this->posxCompany -= 0;
			$this->posxInvoiceAmountTTC -= 10;
			$this->posxReceived -= 20;
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
	 *            societe Id
	 * @param string $file
	 *            path and name of PDF file
	 * @param string $outputLangs
	 *            Lang output object
	 * @return int <0 if KO, >0 if OK
	 */
	// /$customersOwedReport = new CustomersOwed($this->db);
	// /$customersOwedReport->lines
	public function write_file($object, $file, $outputLangs, $socid = null, $sortfield = null, $sortorder = null)
	{
		dol_syslog(get_class($this) . "::write_file", LOG_DEBUG);

		include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

		global $conf, $hookmanager, $langs, $user;
		if (!isModEnabled('customersowed') || !$user->hasRight('customersowed', 'write')) { $this->error = 'NotEnoughPermissions'; return -1; }
		$expectedDirectory = $object->getReportDirectory($user);
		if ($expectedDirectory === false || !is_string($file) || realpath(dirname($file)) !== realpath($expectedDirectory)
			|| basename($file) !== $object->pdfreportfilename || is_link($file)) { $this->error = 'ErrorBadParameters'; return -1; }


		// $ext = pathinfo($file, PATHINFO_EXTENSION);
		// $filename = basename($file, ".$ext") . '_TOTAL.' . $ext;
		// $object->pdfreportfilename = $filename;
		// $file = dirname($file) . PATH_SEPARATOR . $filename;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment"));
		if (!is_object($outputLangs)) {
			$outputLangs = $langs;
		}

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '') {
			$outputLangs->charset_output = 'ISO-8859-1';
		}

		// Add pdfgeneration hook
		if (!is_object($hookmanager)) {
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

		if ($socid > 0 && count(array_filter($object->lines, static function ($line) use ($socid) { return (int) $line->fk_soc === (int) $socid; })) > 0) {
			$societe = new Societe($this->db);
			if ($societe->fetch($socid) <= 0) { $this->error = 'ErrorRecordNotFound'; return -1; }
			if (empty($object->title)) {
				// $title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompanyAtDateOf", $societe->name, dol_print_date(dol_now(), "day"));
				$object->title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompany", $societe->name);
			}
		} else {
			$object->title = $outputLangs->transnoentities("CustomersOwedAtDateOf", dol_print_date($this->customersOwedDate, "day"));
		}
		$this->title = $object->title;

		$pdf->SetTitle(getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . " - " . $object->title);
		$pdf->SetSubject($outputLangs->transnoentities("ModuleCustomersOwed"));
		$pdf->SetCreator("Dolibarr " . DOL_VERSION . " (http://www.joliciel.fr)");
		$pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION') !== '') {
			$pdf->SetCompression(false);
		}

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
		$pdf->SetAutoPageBreak(true, 0);
		// $pdf->SetAutoPageBreak(true, $this->marge_basse);

		// New page
		$pdf->AddPage();
		if (!$this->prepareReportLayout($pdf, $object, $outputLangs)) { return -1; }
		$pagenb++;
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

		if (method_exists($pdf, 'AliasNbPages')) {
			$pdf->AliasNbPages();
		}

		$this->_pagefoot($pdf, $object, $outputLangs, 0);
		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		if (!is_object($hookmanager)) {
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

		if (getDolGlobalString('MAIN_UMASK') !== '') {
			@chmod($file, octdec(getDolGlobalString('MAIN_UMASK')));
		}

		$this->result = array(
			'fullpath' => $file);

		return 1;
	}

	/**
	 * Show top header and bottom footer of page.
	 *
	 * @param PDF $pdf
	 *            Object PDF
	 * @param int $page
	 *            Object to show
	 * @param Translate $outputLangs
	 *            Object lang for output
	 * @return void
	 */
	public function _pageHeaderAndFooter(&$pdf, $page, $outputLangs, $socid = null, $sortfield = null, $sortorder = null)
	{
		global $conf, $hookmanager, $langs, $user;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment"));
		if (!is_object($outputLangs)) {
			$outputLangs = $langs;
		}

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '') {
			$outputLangs->charset_output = 'ISO-8859-1';
		}

		$defaultFontSize = pdf_getPDFFontSize($outputLangs);
		$pdf->SetFont('', '', $defaultFontSize - 4);

		$pdf->SetXY($this->posxLeft, $this->marge_haute);
		$pdf->Cell(80, $this->line_height, getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), 0, 0, 'L');

		// PDF document date
		$pdf->SetXY($this->page_largeur - $this->marge_droite - 80, $this->marge_haute);
		$pdf->Cell(80, $this->line_height, dol_print_date(time(), "day", false, $outputLangs, true), 0, 0, 'R');

		// Title
		$pdf->SetFont('', 'B', $defaultFontSize + 2);
		$pdf->SetXY($this->marge_gauche, $this->marge_haute + ($this->line_height));
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->line_height, $this->title, 0, 'C', false, 0, '', '', true, 1, false, true, $this->line_height, 'T', true);

		// Company
		$pdf->SetFont('', '', $defaultFontSize);
		$pdf->SetXY($this->posxLeft, $this->tabTop);
		$posxColumnsToAdd = "posxColumnToAdd" . ($this->numberColumnsToAdd);
		$pdf->Cell($this->columnPositions[$posxColumnsToAdd] - $this->posxLeft, $this->line_height, ucfirst($outputLangs->transnoentities("ThirdPartyName")), 'LTRB', 0, 'L', false, '', 1);

		// Columns to add
		for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
			$posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
			$posxColumnPrevious = "posxColumnToAdd" . ($j);
			$pdf->SetXY($this->columnPositions[$posxColumnsToAdd], $this->tabTop);
			if ( ($this->listColumnsToAdd[$j] == "s.zip") && ($this->listWidthToAdd[$j] < 15) ) {
				$pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $outputLangs->transnoentities("CustomersOwed_s.zip_SHORT"), 'LTRB', 0, 'C', false, '', 1);
			} else {
				$pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $outputLangs->transnoentities("CustomersOwed_" . $this->listColumnsToAdd[$j]), 'LTRB', 0, 'C', false, '', 1);
			}
		}

		// Invoice amount
		$pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop);
		$pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, $this->line_height, $outputLangs->transnoentities("AmountTTC"), 'LTRB', 0, 'C', false, '', 1);

		// Received
		$pdf->SetXY($this->posxReceived, $this->tabTop);
		$pdf->Cell($this->posxRtp - $this->posxReceived, $this->line_height, $outputLangs->transnoentities("Received"), 'LTRB', 0, 'C', false, '', 1);

		// Rtp
		if ($this->widthRtp > 0) {
			$pdf->SetXY($this->posxRtp, $this->tabTop);
			$pdf->Cell($this->widthRtp, $this->line_height, $outputLangs->transnoentities("Rest"), 'LTRB', 0, 'C', false, '', 1);
		}

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
	 *            PDF object
	 * @param string $page
	 *            Page
	 * @param array $lines
	 *            Array of lines
	 * @param Translate $outputLangs
	 *            Object langs
	 * @return void
	 */
	public function Body(&$pdf, $page, $lines, $outputLangs, $socid = null, $sortfield = null, $sortorder = null)
	{
		global $conf, $langs;

		// Translations
		$langs->loadLangs(array(
			"main",
			"invoice",
			"payment",
			"banks"));
		if (!is_object($outputLangs)) {
			$outputLangs = $langs;
		}

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '') {
			$outputLangs->charset_output = 'ISO-8859-1';
		}

		$defaultFontSize = pdf_getPDFFontSize($outputLangs);

		// order $lines by soc.nom
		usort($lines, "compareSocNom");

		$facturestatic = new Facture($this->db);
		$societestatic = new Societe($this->db);

		$total_rtp = $total_amount = $total_ttc = $total_vat = $total_ht = 0;

		$pdf->SetFont('', '', $defaultFontSize - 1);
		$pdf->SetFillColor(240, 241, 243); // or (220,220,220)
		$yp = 0;
		$numLines = count($lines);
		if ($numLines === 0) { return; }
		$numDelays = 0;
		$fill = false;
		$obj = $lines[0];
		$objCurrent = $lines[0];
		$last_fk_soc = $lines[0]->fk_soc;
		for ($i = 0; $i <= $numLines; $i++) {

			if ( ($lines[$i]->fk_soc != $last_fk_soc) ) {

				// Afficher les totaux pour le client précédent
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
					$page++;
					$this->_pageHeaderAndFooter($pdf, $page, $outputLangs, $socid, $sortfield, $sortorder);
					$pdf->SetFont('', '', $defaultFontSize - 1);
					$yp = 0;
					$fill = false;
				}

				// Company
				$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
				if ($this->numberColumnsToAdd > 0) {
					$posxColumnsToAdd = "posxColumnToAdd" . ($this->numberColumnsToAdd);
					$pdf->Cell($this->columnPositions[$posxColumnsToAdd], $this->line_height, $obj->name, 'L', 0, 'L', $fill, '', 1);
				} else {
					$pdf->Cell($this->posxDateDue - $this->posxCompany, $this->line_height, $obj->name, 'L', 0, 'L', $fill, '', 1);
				}

				// Columns to add
				for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
					$posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
					$posxColumnPrevious = "posxColumnToAdd" . ($j);
					$fieldToAdd = $this->listFieldsToAdd[$j];
					$pdf->SetXY($this->columnPositions[$posxColumnsToAdd], $this->tabTop + $this->line_height + $yp);
					$align = 'C';
					$obj_fieldToAdd = $obj->{$fieldToAdd} ?? '';
					switch ($fieldToAdd) {
						case "town":
							$align = 'L';
							break;
						case "total_ht":
							$obj_fieldToAdd = price($obj->total_ht);
							$align = 'R';
							break;
						case "total_vat":
							$obj_fieldToAdd = price($obj->total_vat);
							$align = 'R';
							break;
					}
					$pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $obj_fieldToAdd, 'LR', 0, $align, $fill, '', 1);
				}

				// Invoice amount
				$pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop + $this->line_height + $yp);
				$pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, $this->line_height, price($obj->total_ttc), 'LR', 0, 'R', $fill, '', 1);

				// Received
				$pdf->SetXY($this->posxReceived, $this->tabTop + $this->line_height + $yp);
				$pdf->Cell($this->posxRtp - $this->posxReceived, $this->line_height, price($obj->am), 'LR', 0, 'R', $fill, '', 1);

				// Rtp
				if ($this->widthRtp > 0) {
					$pdf->SetXY($this->posxRtp, $this->tabTop + $this->line_height + $yp);
					$pdf->Cell($this->widthRtp, $this->line_height, (!empty($remaintopay) ? price($remaintopay, 0, $langs) : ''), 'LR', 0, 'R', $fill, '', 1);
				}
				// Totals
				$total_ht += $obj->total_ht;
				$total_vat += $obj->total_vat;
				$total_ttc += $obj->total_ttc;
				$total_amount += $obj->am;
				$total_rtp += $remaintopay;

				$yp = $yp + $this->line_height;
				$fill = !$fill;

				$obj = $lines[$i];

			}
			else {
				// Calculer les sommes pour le client en cours
				if ($i > 0 ) {
					$objCurrent = $lines[$i];
					$obj->total_ht = $obj->total_ht + $objCurrent->total_ht;
					$obj->total_vat = $obj->total_vat + $objCurrent->total_vat;
					$obj->total_ttc = $obj->total_ttc + $objCurrent->total_ttc;
					$obj->am = $obj->am + $objCurrent->am;
				}
			}

			$last_fk_soc = $lines[$i]->fk_soc;

		}

		// End of tab
		$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->line_height, '', 'T', 0, 'L', false, '', 1);

		// Totals
		$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp + 0.5);
		$pdf->MultiCell($this->posxDateDue - $this->posxLeft + 21, $this->line_height, $outputLangs->transnoentities("RemainderToTakeAtDateOf", dol_print_date($this->customersOwedDate, "day"), price($total_ttc - $total_amount)), 0, 'L', false, 1, '', '', true, 0, true, true, 0, 'M', false);

		if ($this->posxTotal_ht > 0) {
			$pdf->SetXY($this->posxTotal_ht, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell(21, $this->line_height, price($total_ht), 0, 0, 'R', false, '', 1);
		}

		if ($this->posxTotal_vat > 0) {
			$pdf->SetXY($this->posxTotal_vat, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell(21, $this->line_height, price($total_vat), 0, 0, 'R', false, '', 1);
		}

		$pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, $this->line_height, price($total_ttc), 0, 0, 'R', false, '', 1);

		$pdf->SetXY($this->posxReceived, $this->tabTop + $this->line_height + $yp);
		$pdf->Cell($this->posxRtp - $this->posxReceived, $this->line_height, price($total_amount), 0, 0, 'R', false, '', 1);

		if ($this->widthRtp > 0) {
			$pdf->SetXY($this->posxRtp, $this->tabTop + $this->line_height + $yp);
			$pdf->SetFont('', 'B', $defaultFontSize - 1);
			$pdf->Cell($this->widthRtp, $this->line_height, price($total_rtp), 0, 0, 'R', false, '', 1);
			$pdf->SetFont('', '', $defaultFontSize - 1);
		}

		$yp = $yp + $this->line_height;

	}
}
