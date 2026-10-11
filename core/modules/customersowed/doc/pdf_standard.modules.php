<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015		Charles-Fr BENKE  	 <charles.fr@benke.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file	   htdocs/custom/customersowed/core/modules/customersowed/doc/pdf_standard.modules.php
 *	\ingroup	customersowed
 *	\brief	  File of class to generate customers owed report
 */
dol_include_once('/customersowed/class/customersowed.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


/**
 *	Classe permettant de generer un document PDF de 'Dû clients'
 */
require_once __DIR__.'/../../../../class/customersowedpdflayout.trait.php';

class pdf_standard
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
	public $posxCompany;
	public $posxDateDue;
	public $posxInvoiceAmountTTC;
	public $posxLate;
	public $posxLeft;
	public $posxReceived;
	public $result;
	public $tabTop;
	public $tab_height;
	public $type;
	public $doc_type;

	public $documentDate;

	public $customersOwedDate;

	public  $title;

	public $excludeDpInvoices;

	var $numberColumnsToAdd;

	var $listColumnsToAdd = array();

	var $listFieldsToAdd = array();

	var $listWidthToAdd = array();

	var $orientation;

	var $warning_image_path;


	/**
	 *  Constructor
	 *
	 *  @param	  DoliDb		$db	  Database handler
	 */
	function __construct($db)
	{
		global $langs,$conf;

		// Translations
		$langs->loadLangs(array("main", "invoice", "payment", "bills", "compta", "main"));

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans('PDFStandardCustomersOwedsModel');

		$this->warning_image_path = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/img/warning.png";
		$CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME = getDolGlobalString('CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME');
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME) {
			$this->warning_image_path = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/img/".$CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME.".png";
		}
		if ($CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME == 'none') {
			$this->warning_image_path = "";
		}

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalString('MAIN_PDF_MARGIN_LEFT') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_LEFT') : 10;
		$this->marge_droite = getDolGlobalString('MAIN_PDF_MARGIN_RIGHT') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_RIGHT') : 10;
		$this->marge_haute = getDolGlobalString('MAIN_PDF_MARGIN_TOP') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_TOP') : 10;
		$this->marge_basse = getDolGlobalString('MAIN_PDF_MARGIN_BOTTOM') !== '' ? getDolGlobalString('MAIN_PDF_MARGIN_BOTTOM') : 10;

		$this->tabTop = 20;

		$this->line_height = 5;
		$this->line_per_page = 40;
		$this->tab_height = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tabTop - 5;	// must be > $this->line_height * $this->line_per_page and < $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tabTop - 5;

		$this->posxLeft = $this->marge_gauche; //InvoiceNumber
		$this->posxLate = 24;
		$this->posxCompany = 28;
		if (empty($this->warning_image_path)) {
			$this->posxCompany = $this->posxLate;
		}
		$this->posxDateDue = 143;
		$this->posxInvoiceAmountTTC = 164;
		$this->posxReceived = 185;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->line_per_page = 35;
			$this->posxCompany -= 0;
			$this->posxInvoiceAmountTTC -= 10;
			$this->posxReceived -= 20;
		}
		// which type of document will be generated: clients (client) or providers (fourn) invoices
		$this->doc_type = "client";

		// Date for customers owed calculation
		$this->customersOwedDate = dol_now();

		// Date of this document
		$this->documentDate = dol_now();
	}

	// Page footer
	public function Footer() {
		// Position at 1.5 cm from bottom
		$this->SetY(-10);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
	}

	/**
	 *	Fonction generant la rapport sur le disque
	 *
	 *	@param	int		$socid			societe Id
	 *	@param	string	$file			path and name of PDF file
	 *	@param	string	$outputLangs	Lang output object
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function write_file($object, $file, $outputLangs, $socid=NULL, $sortfield=NULL, $sortorder=NULL)
	{
		dol_syslog(get_class($this)."::write_file", LOG_DEBUG);

		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		global $conf, $hookmanager, $langs, $user;
		if (!isModEnabled('customersowed') || !$user->hasRight('customersowed', 'write')) { $this->error = 'NotEnoughPermissions'; return -1; }
		$expectedDirectory = $object->getReportDirectory($user);
		if ($expectedDirectory === false || !is_string($file) || realpath(dirname($file)) !== realpath($expectedDirectory)
			|| basename($file) !== $object->pdfreportfilename || is_link($file)) { $this->error = 'ErrorBadParameters'; return -1; }



		// Translations
		$langs->loadLangs(array("main", "invoice", "payment"));

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

		$this->reportRecipient = null;
		if ($socid > 0 && $object->lines && count(array_filter($object->lines, static function ($line) use ($socid) { return (int) $line->fk_soc !== (int) $socid; })) === 0) {
			$societe = new Societe($this->db);
			if ($societe->fetch($socid) <= 0) { $this->error = 'ErrorRecordNotFound'; return -1; }
			$this->reportRecipient = $societe;
			if (empty($object->title)) {
				//$title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompanyAtDateOf", $societe->name, dol_print_date(dol_now(), "day"));
				$object->title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompany", $societe->name);
			}
		}
		else {
			$object->title = $outputLangs->transnoentities("CustomersOwedAtDateOf", dol_print_date($this->customersOwedDate, "day"));
		}
		$this->title = $object->title;

		if (! is_object($outputLangs)) $outputLangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF') !== '') $outputLangs->charset_output='ISO-8859-1';

		// Add pdfgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters=array('file'=>$file,'outputlangs'=>$outputLangs);
		global $action;
		$reshook=$hookmanager->executeHooks('beforePDFCreation', $parameters, $object ,$action);	// Note that $action and $object may have been modified by some hooks

		$pdf=pdf_getInstance($this->format);
		$defaultFontSize = pdf_getPDFFontSize($outputLangs);	// Must be after pdf_getInstance

		if (class_exists('TCPDF'))
		{
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputLangs));

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);

		$pdf->SetTitle(getDolGlobalString('MAIN_INFO_SOCIETE_NOM')." - ".$outputLangs->transnoentities("CustomersOwedAtDateOf", dol_print_date(dol_now(), "day")));
		$pdf->SetSubject($outputLangs->transnoentities("ModuleCustomersOwed"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION." (http://www.joliciel.fr)");
		$pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
		//$pdf->SetKeyWords();
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION') !== '') $pdf->SetCompression(false);

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
		$pdf->SetAutoPageBreak(1,0);

		// New page
		$pdf->AddPage();
		$this->prepareReportHeader($pdf, $outputLangs);
		if (!$this->prepareReportLayout($pdf, $object, $outputLangs)) { return -1; }
		$pagenb++;
		$this->_pagehead($pdf, $pagenb, $outputLangs);
		$pdf->SetFont('', '', 9);
		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetTextColor(0,0,0);

		///$customersOwedReport = new CustomersOwed($this->db);

		///if ($customersOwedReport->fetch_lines($socid, $sortfield, $sortorder)) {
		if ($object) {
			$this->Body($pdf, 1, $object->lines, $outputLangs, $socid, $sortfield, $sortorder);

		}
		else {
			dol_print_error($object->error);
		}

		if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

		$this->_pagefoot($pdf, $object, $outputLangs, 0);
		$pdf->Close();

		$pdf->Output($file,'F');

		// Add pdfgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file'=>$file,'object'=>$object,'outputlangs'=>$outputLangs);
		global $action;
		$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);	// Note that $action and $object may have been modified by some hooks

		if (getDolGlobalString('MAIN_UMASK') !== '')
			@chmod($file, octdec(getDolGlobalString('MAIN_UMASK')));

		$this->result = array('fullpath'=>$file);

		return 1;
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf	 		Object PDF
	 *  @param  int			$page		 	Object to show
	 *  @param  Translate	$outputLangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $page, $outputLangs)
	{
		global $conf, $hookmanager, $langs, $user;

		$defaultFontSize = pdf_getPDFFontSize($outputLangs);
		$this->printReportHeader($pdf, $outputLangs);

		// Columns
		$pdf->SetFont('', '', $defaultFontSize);
		$pdf->SetXY($this->posxLeft, $this->tabTop);
		$pdf->Cell($this->posxDateDue - $this->posxLeft, 2, $outputLangs->transnoentities("BillsCustomersUnpaid"), 0, 0, 'L', false, '', 1);

		$pdf->line($this->posxDateDue, $this->tabTop, $this->posxDateDue, $this->tabTop + $this->tab_height + $this->line_height - 1);
		$pdf->SetXY($this->posxDateDue, $this->tabTop);
		$pdf->Cell($this->posxInvoiceAmountTTC - $this->posxDateDue, 2, $outputLangs->transnoentities("DateDue"), 0, 0, 'C', false, '', 1);

		$pdf->line($this->posxInvoiceAmountTTC, $this->tabTop, $this->posxInvoiceAmountTTC, $this->tabTop + $this->tab_height + $this->line_height - 1);
		$pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop);
		$pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, 2, $outputLangs->transnoentities("AmountTTC"), 0, 0, 'C', false, '', 1);

		$pdf->line($this->posxReceived, $this->tabTop, $this->posxReceived, $this->tabTop + $this->tab_height + $this->line_height - 1);
		$pdf->SetXY($this->posxReceived, $this->tabTop);
		$pdf->Cell($this->page_largeur - $this->marge_droite - $this->posxReceived, 2, $outputLangs->transnoentities("Received"), 0, 0, 'C', false, '', 1);

		$pdf->line($this->marge_gauche, $this->tabTop + $this->line_height, $this->page_largeur - $this->marge_droite, $this->tabTop + $this->line_height);

		$pdf->Rect($this->marge_gauche, $this->tabTop, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->tab_height + $this->line_height - 1);
	}


	/**
	 *	Output body
	 *
	 *	@param	PDF			$pdf			PDF object
	 *	@param	string		$page			Page
	 *	@param	array		$lines			Array of lines
	 *	@param	Translate	$outputLangs	Object langs
	 *	@return	void
	 */
	function Body(&$pdf, $page, $lines, $outputLangs)
	{
		global $langs, $conf;
		$defaultFontSize = pdf_getPDFFontSize($outputLangs);

		$pdf->SetFont('', '', $defaultFontSize - 1);
		$total_ttc = $total_amount = $total_ht = 0;
		$pdf->SetFillColor(220,220,220);
		$yp = 0;
		$numLines = count($lines);
		$numDelays = 0;
		$fill = false;
		for ($i = 0 ; $i < $numLines ; $i++)
		{
			$obj = $lines[$i];

			if ($yp > $this->tab_height - ($this->line_height))
			{
				$this->_pagefoot($pdf, $this->reportObject, $outputLangs, 1);
				$pdf->AddPage();
				$page++;
				$this->_pagehead($pdf, $page, $outputLangs);
				$pdf->SetFont('', '', $defaultFontSize - 1);
				$yp = 0;
				$fill = false;
			}

			// Invoice number
			$pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxLate - $this->posxLeft, $this->line_height, $obj->facnumber, 0, 0, 'L', $fill, '', 1);

			if ($obj->hasDelay() && !empty($this->warning_image_path)) {
				$numDelays++;
				$pdf->Image($this->warning_image_path, $this->posxLate, $this->tabTop + $this->line_height + $yp + 1, 3, 3);
			}

			$pdf->SetXY($this->posxCompany, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxDateDue - $this->posxCompany, $this->line_height, $obj->name, 0, 0, 'L', $fill);

			// DateDue
			$pdf->SetXY($this->posxDateDue, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxInvoiceAmountTTC - $this->posxDateDue, $this->line_height, dol_print_date($this->db->jdate($obj->date_lim_reglement),'day'), 0, 0, 'C', $fill);

			// Invoice amount
			$pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->posxReceived- $this->posxInvoiceAmountTTC, $this->line_height, price($obj->total_ttc), 0, 0, 'R', $fill, '', 1);

			// Received
			$pdf->SetXY($this->posxReceived, $this->tabTop + $this->line_height + $yp);
			$pdf->Cell($this->page_largeur - $this->marge_droite - $this->posxReceived, $this->line_height, price($obj->am), 0, 0, 'R', $fill, '', 1);

			$total_ttc +=  $obj->total_ttc;
			$total_ht += $obj->total_ht;
			$total_amount +=  $obj->am;

			$yp = $yp + $this->line_height;
		}

		$pdf->SetXY($this->posxLeft, $this->page_hauteur - $this->heightforfooter - ($this->line_height*2) - 1);
		$pdf->MultiCell($this->posxDateDue - $this->posxLeft, $this->line_height, $langs->transnoentities("RemainderToTakeAtDateOf", dol_print_date($this->customersOwedDate, "day"), price($total_ttc - $total_amount)), 0, 'L', false, 1, '', '', true, 0, true);

		$pdf->SetXY($this->posxInvoiceAmountTTC, $this->page_hauteur - $this->heightforfooter - ($this->line_height*2) - 1);
		$pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, $this->line_height, price($total_ttc), 0, 0, 'R', false, '', 1);

		$pdf->SetXY($this->posxReceived, $this->page_hauteur - $this->heightforfooter - ($this->line_height*2) - 1);
		$pdf->Cell($this->page_largeur - $this->marge_droite - $this->posxReceived, $this->line_height, price($total_amount), 0, 0, 'R', false, '', 1);

		if ($numDelays > 0 && !empty($this->warning_image_path)) {
			$numDays = abs($conf->facture->client->warning_delay)/60/60/24;
			$pdf->Image($this->warning_image_path, $this->posxLeft, $this->page_hauteur - $this->heightforfooter - $this->line_height + 0.5, 3, 3);
			$pdf->SetXY($this->posxLeft + 3, $this->page_hauteur - $this->heightforfooter - $this->line_height);
			$pdf->MultiCell($this->posxDateDue - $this->posxLate, $this->line_height, "= ".$langs->transnoentities('WarningDelay', $numDays), 0, 'L', 0);
		}

	}
}
