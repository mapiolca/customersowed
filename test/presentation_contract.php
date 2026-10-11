<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */
// Isolated presentation checks. No database writes, SMTP or ERP bootstrap.
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message) { throw new RuntimeException($message); });
require __DIR__.'/../class/customersowedmail.class.php';
require __DIR__.'/../class/customersowedpdflayout.trait.php';
define('DOL_MAIN_URL_ROOT', 'https://erp.example.invalid');
function dol_escape_htmltag($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function price2num($value, $mode) { return (float) $value; }
function price($value, $unused, $langs) { return (string) $value; }
function dol_print_date($value, $format, $unused = 0, $langs = null) { return (string) $value; }
function getDolGlobalInt($key) { global $options; return $options[$key] ?? 0; }
function pdf_getPDFFontSize($langs) { return 10; }
function pdfBuildThirdpartyName($recipient, $langs) { return $recipient->name; }
function pdf_build_address($langs, $sender, $recipient, $contact, $usecontact, $side) { return $side === 'source' ? $sender->address : $recipient->address; }
function pdf_pagehead($pdf, $langs, $height) {}
class PresentationLangs {
	public function transnoentities($key) { return $key; }
	public function convToOutputCharset($text) { return $text; }
	public function loadLangs($keys) {}
}
class PresentationPdf {
	public $texts = array(); public $y = 0;
	public function SetFont(...$args) {}
	public function getStringHeight($width, $text) { return 4 * max(1, count(explode("\n", $text))); }
	public function SetTextColor(...$args) {}
	public function SetFillColor(...$args) {}
	public function Rect(...$args) {}
	public function SetXY($x, $y) { $this->y = $y; }
	public function GetY() { return $this->y; }
	public function MultiCell($width, $height, $text, ...$args) { $this->texts[] = $text; $this->y += $height; }
}
class PresentationLayout {
	use CustomersOwedPdfLayout;
	public $marge_haute = 10; public $marge_gauche = 10; public $marge_droite = 10;
	public $page_largeur = 210; public $page_hauteur = 297;
	public $title = 'Report'; public $customersOwedDate = '2026-10-01'; public $tabTop;
}
$langs = new PresentationLangs();
$db = new class { public function jdate($value) { return $value; } };
$line = (object) array('facnumber' => '<script>invoice</script>', 'name' => 'A & B', 'id' => 1, 'fk_soc' => 7,
	'total_ttc' => 100, 'am' => 95, 'date_lim_reglement' => '2026-10-01');
$credit = clone $line; $credit->facnumber = 'Credit'; $credit->total_ttc = -12; $credit->am = 0;
$report = (object) array('lines' => array($line, $credit));
$table = CustomersOwedMail::buildInvoiceTable($report, $langs, $db, false);
$linked = CustomersOwedMail::buildInvoiceTable($report, $langs, $db, true);
$report->lines = array();
$empty = CustomersOwedMail::buildInvoiceTable($report, $langs, $db, false);
$custom = '<p>Personalized __DATE_YMD__</p>';
$template = CustomersOwedMail::selectTemplate("Legacy<br/>body", "Legacy\nbody");
if (CustomersOwedMail::selectTemplate($custom, 'Legacy') !== $custom) { throw new RuntimeException('custom template overwritten'); }
$options = array(); $conf = (object) array('entity' => 1);
$mysoc = (object) array('name' => 'Issuer', 'address' => 'Issuer address', 'logo' => '');
$layout = new PresentationLayout(); $pdf = new PresentationPdf();
$layout->prepareReportHeader($pdf, $langs); $layout->printReportHeader($pdf, $langs);
$internal = $pdf->texts;
$layout->reportRecipient = (object) array('name' => 'Recipient', 'address' => str_repeat("Address line\n", 14), 'code_client' => 'CU007');
$pdf = new PresentationPdf(); $layout->prepareReportHeader($pdf, $langs); $layout->printReportHeader($pdf, $langs);
if ($layout->tabTop <= $layout->reportHeader['top'] + 60) { throw new RuntimeException('long address overlaps table'); }
$recipient = $pdf->texts;
$options['MAIN_PDF_HIDE_SENDER_NAME'] = 1;
$layout->prepareReportHeader($pdf, $langs);
if (strpos($layout->reportHeader['sender'], "Issuer\n") !== false) { throw new RuntimeException('hidden sender name exposed'); }
echo json_encode(compact('table', 'linked', 'empty', 'template', 'internal', 'recipient'), JSON_THROW_ON_ERROR);
