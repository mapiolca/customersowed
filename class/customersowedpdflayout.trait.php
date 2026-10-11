<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */
/** Reserve and render the native footer during the normal page lifecycle. */
trait CustomersOwedPdfLayout
{
	public $error = '';
	/** @var list<string> */
	public $errors = array();
	public $heightforfooter = 0;
	public $reportObject;
	/** @var Societe|null Explicit single-third-party recipient; null for internal reports. */
	public $reportRecipient = null;
	/** @var string */
	public $reportLogo = '';
	/** @var float */
	public $reportLogoHeight = 0.0;
	/** @var array{sender:string, recipient:string, width:float, height:float, top:float} */
	public $reportHeader = array('sender' => '', 'recipient' => '', 'width' => 0.0, 'height' => 0.0, 'top' => 0.0);

	/**
	 * Reserve native address blocks before computing the table/footer capacity.
	 * @param TCPDF $pdf PDF instance
	 * @param Translate $outputlangs Output language
	 * @return void
	 */
	public function prepareReportHeader($pdf, $outputlangs)
	{
		global $mysoc, $conf;
		$this->reportLogo = '';
		$this->reportLogoHeight = 0.0;
		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && !empty($mysoc->logo)
			&& isset($conf->mycompany->multidir_output[$conf->entity])) {
			$directory = $conf->mycompany->multidir_output[$conf->entity];
			$logo = getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') || empty($mysoc->logo_small)
				? $directory.'/logos/'.$mysoc->logo : $directory.'/logos/thumbs/'.$mysoc->logo_small;
			if (is_readable($logo)) {
				$this->reportLogo = $logo;
				$this->reportLogoHeight = (float) pdf_getHeightForLogo($logo);
			}
		}
		$outputlangs->loadLangs(array('main', 'companies', 'bills', 'customersowed@customersowed'));
		$recipient = $this->reportRecipient;
		$width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 10) / 2;
		$sender = (getDolGlobalInt('MAIN_PDF_HIDE_SENDER_NAME') ? '' : $outputlangs->convToOutputCharset($mysoc->name)."\n")
			.pdf_build_address($outputlangs, $mysoc, $recipient ?: '', '', 0, 'source');
		$target = $recipient ? pdfBuildThirdpartyName($recipient, $outputlangs)."\n".pdf_build_address($outputlangs, $mysoc, $recipient, '', 0, 'target') : '';
		$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);
		$height = max(40.0, $pdf->getStringHeight($width - 4, $sender) + 8, $pdf->getStringHeight($width - 4, $target) + 8);
		$pdf->SetFont('', 'B', pdf_getPDFFontSize($outputlangs) + 2);
		$titleWidth = $this->reportLogo !== '' ? 110 : $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$titleHeight = $pdf->getStringHeight($titleWidth, $this->title);
		$top = max(getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40.0 : 42.0,
			$this->marge_haute + $titleHeight + ($recipient && $recipient->code_client ? 18 : 14),
			$this->marge_haute + $this->reportLogoHeight + 8);
		$this->reportHeader = array('sender' => $sender, 'recipient' => $target, 'width' => $width, 'height' => $height, 'top' => (float) $top);
		$this->tabTop = $top + $height + 8;
	}

	/** @param TCPDF $pdf PDF instance
	 * @param Translate $outputlangs Output language
	 * @return void
	 */
	public function printReportHeader($pdf, $outputlangs)
	{
		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
		if ($this->reportLogo !== '') {
			$pdf->Image($this->reportLogo, $this->marge_gauche, $this->marge_haute, 0, $this->reportLogoHeight);
		}
		$titleWidth = $this->reportLogo !== '' ? 110 : $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$titleX = $this->page_largeur - $this->marge_droite - $titleWidth;
		$fontSize = pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $fontSize + 2);
		$pdf->SetXY($titleX, $this->marge_haute);
		$pdf->MultiCell($titleWidth, 5, $this->title, 0, 'R');
		$pdf->SetFont('', '', $fontSize - 1);
		$pdf->SetXY($titleX, $pdf->GetY() + 1);
		$pdf->MultiCell($titleWidth, 4,
			$outputlangs->transnoentities('Date').' : '.dol_print_date($this->customersOwedDate, 'day', 0, $outputlangs), 0, 'R');
		if ($this->reportRecipient && $this->reportRecipient->code_client) {
			$pdf->SetXY($titleX, $pdf->GetY());
			$pdf->MultiCell($titleWidth, 4,
				$outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($this->reportRecipient->code_client), 0, 'R');
		}
		$width = $this->reportHeader['width'];
		$height = $this->reportHeader['height'];
		$top = $this->reportHeader['top'];
		foreach (array('sender' => 'BillFrom', 'recipient' => 'BillTo') as $side => $label) {
			if ($side === 'recipient' && !$this->reportRecipient) { continue; }
			$left = $side === 'sender';
			if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) { $left = !$left; }
			$x = $left ? $this->marge_gauche : $this->page_largeur - $this->marge_droite - $width;
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $fontSize - 2);
			$pdf->SetXY($x, $top - 5);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities($label), 0, 'L');
			if ($side === 'sender' && !getDolGlobalInt('MAIN_PDF_NO_SENDER_FRAME')) {
				$pdf->SetFillColor(230, 230, 230);
				$pdf->Rect($x, $top, $width, $height, 'F');
			} elseif ($side === 'recipient' && !getDolGlobalInt('MAIN_PDF_NO_RECIPENT_FRAME')) {
				$pdf->Rect($x, $top, $width, $height);
			}
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', '', $fontSize - 1);
			$pdf->SetXY($x + 2, $top + 3);
			$pdf->MultiCell($width - 4, 4, $this->reportHeader[$side], 0, 'L');
		}
		$pdf->SetTextColor(0, 0, 0);
	}

	/** @param TCPDF $pdf PDF instance
	 * @param CustomersOwed $object Report
	 * @param Translate $outputlangs Output language
	 * @return bool Whether the footer fits the available page
	 */
	public function prepareReportLayout(&$pdf, $object, $outputlangs)
	{
		$this->reportObject = $object;
		// Measure the actual native free text, company details and footer hooks on a clone.
		$probe = clone $pdf;
		$probe->SetAutoPageBreak(false, 0);
		$measured = $this->_pagefoot($probe, $object, $outputlangs, 0);
		$this->heightforfooter = max($this->marge_basse, (float) $measured);
		$this->tab_height = $this->page_hauteur - $this->heightforfooter - $this->tabTop - 4 * $this->line_height;
		if ($this->tab_height < 3 * $this->line_height) {
			$this->error = 'CustomersOwedFooterTooLarge';
			return false;
		}
		$pdf->setPageOrientation('', true, $this->heightforfooter);
		return true;
	}

	/** @param TCPDF $pdf PDF instance
	 * @param CustomersOwed $object Report
	 * @param Translate $outputlangs Output language
	 * @param int $hidefreetext Hide final free text on intermediate pages
	 * @return float Native footer height
	 */
	public function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $mysoc;
		$pdf->SetAutoPageBreak(false, 0);
		$height = pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $mysoc,
			$this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object,
			getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS'), $hidefreetext, $this->page_largeur);
		$pdf->setPageOrientation('', true, $this->heightforfooter ?: $this->marge_basse);
		return (float) $height;
	}
}
