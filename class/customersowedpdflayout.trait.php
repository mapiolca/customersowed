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
