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
 * \file htdocs/custom/suppliersowed/core/modules/suppliersowed/doc/pdf_joliciel.modules.php
 * \ingroup suppliersowed
 * \brief File of class to generate customers owed report
 */
dol_include_once('/suppliersowed/class/suppliersowed.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

/**
 * Classe permettant de generer un document PDF de 'Dû clients'
 */
class pdf_joliciel
{

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
            "main",
            "invoice",
            "payment",
            "bills",
            "compta"));

        $this->db = $db;
        $this->name = "joliciel";
        $this->description = $langs->trans('PDFJolicielCustomersOwedsModel');

        $this->warning_image_path = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/img/warning.png";
        $CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME = $conf->global->CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME;
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

        $dateColumnWidth = 21;

        // Columns to add
        $listAvailablesColumns = array(
            "p.ref",
            "p.title",
            "f.ref_client",
            "f.type",
            "f.date",
            "s.town",
            "s.zip",
            "state.nom",
            "country.code_iso",
            "typent.code",
            "f.fk_mode_reglement",
            "f.total_ht",
            "f.total_vat",
            "f.datec",
            "f.tms");
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

        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

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

        $this->posxDateDue = $this->marge_gauche + $this->tab_width - (3 * $dateColumnWidth) - $this->widthRtp;

        // Columns to add
        $this->posxColumnToAdd0 = $this->posxDateDue;
        for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
            $posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
            $posxColumnPrevious = "posxColumnToAdd" . ($j);
            $columnWidth = 35;
            if ((substr($this->listFieldsToAdd[$j], 0, 4) == 'date') || ($this->listFieldsToAdd[$j] == 'tms')) {
                $columnWidth = $dateColumnWidth;
            }
            if (substr($this->listFieldsToAdd[$j], 0, 5) == 'total') {
                $columnWidth = $dateColumnWidth;
            }
            if ($this->listFieldsToAdd[$j] == 'zip') {
                $columnWidth = 12;
            }
            if ($this->listFieldsToAdd[$j] == 'town') {
                $columnWidth = 40;
            }
            $this->{$posxColumnsToAdd} = $this->{$posxColumnPrevious}-$columnWidth;
            $this->listWidthToAdd[$j] = $columnWidth;
        }

        // Move DateDue column at the left of totals
        for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
            $posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
            if (($this->{$posxColumnsToAdd} < $this->posxDateDue) && (substr($this->listFieldsToAdd[$j], 0, 5) == 'total')) {
                $_posxDateDue = $this->posxDateDue;
                $this->posxDateDue = $this->{$posxColumnsToAdd};
                $this->{$posxColumnsToAdd} = $_posxDateDue;
            }
            if ($this->listFieldsToAdd[$j] == 'total_ht') {
                $this->posxTotal_ht = $this->{$posxColumnsToAdd};
            }
            if ($this->listFieldsToAdd[$j] == 'total_vat') {
                $this->posxTotal_vat = $this->{$posxColumnsToAdd};
            }
        }

        // $this->posxInvoiceAmountTTC = 164 + $orientationWidthDifference - $this->widthRtp;
        $this->posxInvoiceAmountTTC = $this->marge_gauche + $this->tab_width - (2 * $dateColumnWidth) - $this->widthRtp;
        // $this->posxReceived = 185 + $orientationWidthDifference - $this->widthRtp;
        $this->posxReceived = $this->marge_gauche + $this->tab_width - $dateColumnWidth - $this->widthRtp;
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

        // Translations
        $langs->loadLangs(array(
            "main",
            "invoice",
            "payment"));
        if (!is_object($outputLangs)) {
            $outputLangs = $langs;
        }

        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) {
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
            if ($user->societe_id) {
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

        if ($socid > 0) {
            $societe = new Societe($this->db);
            $societe->fetch($socid);
            if (empty($object->title)) {
                // $title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompanyAtDateOf", $societe->name, dol_print_date(dol_now(), "%d/%m/%Y"));
                $object->title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompany", $societe->name);
            }
        } else {
            $object->title = $outputLangs->transnoentities("CustomersOwedAtDateOf", dol_print_date($this->customersOwedDate, "%d/%m/%Y"));
        }
        $this->title = $object->title;

        $pdf->SetTitle($conf->global->MAIN_INFO_SOCIETE_NOM . " - " . $title);
        $pdf->SetSubject($outputLangs->transnoentities("ModuleCustomersOwed"));
        $pdf->SetCreator("Dolibarr " . DOL_VERSION . " (http://www.joliciel.fr)");
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
            $pdf->SetCompression(false);
        }

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
        $pdf->SetAutoPageBreak(true, 0);
        // $pdf->SetAutoPageBreak(true, $this->marge_basse);

        // New page
        $pdf->AddPage();
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

        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
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
        if (!empty($conf->global->MAIN_USE_FPDF)) {
            $outputLangs->charset_output = 'ISO-8859-1';
        }

        $defaultFontSize = pdf_getPDFFontSize($outputLangs);
        $pdf->SetFont('', '', $defaultFontSize - 4);

        $pdf->SetXY($this->posxLeft, $this->marge_haute);
        $pdf->Cell(80, $this->line_height, $conf->global->MAIN_INFO_SOCIETE_NOM, 0, 0, 'L');

        // PDF document date
        $pdf->SetXY($this->page_largeur - $this->marge_droite - 80, $this->marge_haute);
        $pdf->Cell(80, $this->line_height, dol_print_date(time(), "day", false, $outputLangs, true), 0, 0, 'R');

        // Title
        $pdf->SetFont('', 'B', $defaultFontSize + 2);
        $pdf->SetXY($this->marge_gauche, $this->marge_haute + ($this->line_height));
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->line_height, $this->title, 0, 'C', false, 0, '', '', true, 1, false, true, $this->line_height, 'T', true);

        // Invoice number and company
        $pdf->SetFont('', '', $defaultFontSize);
        $pdf->SetXY($this->posxLeft, $this->tabTop);
        $posxColumnsToAdd = "posxColumnToAdd" . ($this->numberColumnsToAdd);
        $pdf->Cell($this->{$posxColumnsToAdd}-$this->posxLeft, $this->line_height, ucfirst($outputLangs->transnoentities("BillsCustomersUnpaid")), 'LTRB', 0, 'L', false, '', 1);

        // Columns to add
        for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
            $posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
            $posxColumnPrevious = "posxColumnToAdd" . ($j);
			$pdf->SetXY($this->{$posxColumnsToAdd}, $this->tabTop);
			if ( ($this->listColumnsToAdd[$j] == "s.zip") && ($this->listWidthToAdd[$j] < 15) ) {
				$pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $outputLangs->transnoentities("CustomersOwed_s.zip_SHORT"), 'LTRB', 0, 'C', false, '', 1);
			} else {
				$pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $outputLangs->transnoentities("CustomersOwed_" . $this->listColumnsToAdd[$j]), 'LTRB', 0, 'C', false, '', 1);
			}			
        }

        // DateDu
        $pdf->SetXY($this->posxDateDue, $this->tabTop);
        $pdf->Cell($this->widthDateDue, $this->line_height, $outputLangs->transnoentities("DateDue"), 'LTRB', 0, 'C', false, '', 1);

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

        $pdf->SetFont('', 'I', $defaultFontSize - 3);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->SetXY($this->posxLeft, $this->page_hauteur - $this->marge_basse - 3);
        $pdf->Cell($this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->line_height, $onlyLatePaymentInvoicesMessage . $excludeDpInvoicesMessage . $sortMessage, 0, 0, 'C', 0, false, '', 0);
        $pdf->SetTextColor(0, 0, 0);

        // Only invoices created by or Only invoices whose sales representative is
        $pdf->SetFont('', '', $defaultFontSize - 3);
        $pdf->SetXY($this->posxLeft, $this->page_hauteur - $this->marge_basse);
        $pdf->Cell($this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->line_height, trim($onlyInvoicesCreatedByMessage . " " . $onlyInvoicesWhoseSalesRepresentativeIsMessage), 0, 0, 'C');

        // Page number
        $pdf->SetFont('', '', $defaultFontSize - 2);
        $pdf->SetXY($this->page_largeur - $this->marge_droite - 20, $this->page_hauteur - $this->marge_basse);
        $pdf->Cell(25, $this->line_height, $page . "/" . $pdf->getAliasNbPages(), 0, 0, 'R');
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
        if (!empty($conf->global->MAIN_USE_FPDF)) {
            $outputLangs->charset_output = 'ISO-8859-1';
        }

        $defaultFontSize = pdf_getPDFFontSize($outputLangs);

        $facturestatic = new Facture($this->db);
        $societestatic = new Societe($this->db);

        $total_rtp = $total_amount = $total_ttc = $total_vat = $total_ht = 0;

        $pdf->SetFont('', '', $defaultFontSize - 1);
        $pdf->SetFillColor(240, 241, 243); // or (220,220,220)
        $yp = 0;
        $numLines = count($lines);
        $numDelays = 0;
        $fill = false;
        for ($i = 0; $i < $numLines; $i++) {
            $obj = $lines[$i];

            $societestatic->name = $obj->name;
            $societestatic->id = $obj->fk_soc;

            $facturestatic->ref = $obj->facnumber;
            $facturestatic->id = $obj->id;
            $facturestatic->statut = $obj->fk_statut;
            $facturestatic->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
            $facturestatic->total_ttc = $obj->total_ttc;

            /*
             * $paiement = $facturestatic->getSommePaiement();
             * $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
             * $totaldeposits = $facturestatic->getSumDepositsUsed();
             * $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
             */
            //$totalpay = $obj->am;

                $totalpay = $obj->am;

            $remaintopay = $facturestatic->total_ttc - $totalpay;
            if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {
                $remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source=' . $facturestatic->id);
                $remaintopay = -$remaincreditnote;
                $totalpay = $facturestatic->total_ttc - $remaintopay;
            }

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
                $pdf->AddPage();
                $page++;
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
            if ($obj->hasDelay() && !empty($this->warning_image_path)) {
                $numDelays++;
                $pdf->Image($this->warning_image_path, $this->posxLate, $this->tabTop + $this->line_height + $yp + 0.4, 4, 4);
            }
            $pdf->SetXY($this->posxCompany, $this->tabTop + $this->line_height + $yp);
            if ($this->numberColumnsToAdd > 0) {
                $posxColumnsToAdd = "posxColumnToAdd" . ($this->numberColumnsToAdd);
                $pdf->Cell($this->{$posxColumnsToAdd}-$this->posxCompany, $this->line_height, $obj->name, 0, 0, 'L', $fill, '', 1);
            } else {
                $pdf->Cell($this->posxDateDue - $this->posxCompany, $this->line_height, $obj->name, 0, 0, 'L', $fill, '', 1);
            }

            // Columns to add
            for ($j = 0; $j < $this->numberColumnsToAdd; $j++) {
                $posxColumnsToAdd = "posxColumnToAdd" . ($j + 1);
                $posxColumnPrevious = "posxColumnToAdd" . ($j);
                $fieldToAdd = $this->listFieldsToAdd[$j];
                $pdf->SetXY($this->{$posxColumnsToAdd}, $this->tabTop + $this->line_height + $yp);
                $align = 'C';
                $obj_fieldToAdd = $obj->{$fieldToAdd};
                switch ($fieldToAdd) {
                    case "p_ref":
                        $obj_fieldToAdd = $obj->project_ref;
                        break;
                    case "p_title":
                        $obj_fieldToAdd = $obj->project_label;
                        break;
                    case "date":
                        $obj_fieldToAdd = dol_print_date($this->db->jdate($obj->date), 'day');
                        break;
                    case "datec":
                        $obj_fieldToAdd = dol_print_date($this->db->jdate($obj->datec), 'day');
                        break;
                    case "tms":
                        $obj_fieldToAdd = dol_print_date($this->db->jdate($obj->tms), 'day');
                        break;
                    case "type":
                        $obj_fieldToAdd = $facturestatic->getLibType();
                        break;
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
                    case "fk_mode_reglement":
                        if ($obj->fk_mode_reglement) {
                            $obj_fieldToAdd = $outputLangs->transnoentities("PaymentTypeShort" . $obj->mode_reglement_code);
                        } else {
                            $obj_fieldToAdd = "";
                        }
                        break;
                }
                $pdf->Cell($this->listWidthToAdd[$j], $this->line_height, $obj_fieldToAdd, 'LR', 0, $align, $fill, '', 1);
            }

            // DateDue
            $pdf->SetXY($this->posxDateDue, $this->tabTop + $this->line_height + $yp);
            $pdf->Cell($this->widthDateDue, $this->line_height, dol_print_date($this->db->jdate($obj->date_lim_reglement), 'day'), 'LR', 0, 'C', $fill);

            // Invoice amount
            $pdf->SetXY($this->posxInvoiceAmountTTC, $this->tabTop + $this->line_height + $yp);
            $pdf->Cell($this->posxReceived - $this->posxInvoiceAmountTTC, $this->line_height, price($obj->total_ttc), 'LR', 0, 'R', $fill, '', 1);

            // Received
            $pdf->SetXY($this->posxReceived, $this->tabTop + $this->line_height + $yp);
            $pdf->Cell($this->posxRtp - $this->posxReceived, $this->line_height, price($totalpay), 'LR', 0, 'R', $fill, '', 1);

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
        }

        // End of tab
        $pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp);
        $pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->line_height, '', 'T', 0, 'L', false, '', 1);

        // Totals
        $pdf->SetXY($this->posxLeft, $this->tabTop + $this->line_height + $yp + 0.5);
        $pdf->MultiCell($this->posxDateDue - $this->posxLeft + 21, $this->line_height, $outputLangs->transnoentities("RemainderToTakeAtDateOf", dol_print_date($this->customersOwedDate, "%d/%m/%Y"), price($total_ttc - $total_amount)), 0, 'L', false, 1, '', '', true, 0, true, true, 0, 'M', false);

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

        // WarningDelay
        if ($numDelays > 0 && !empty($this->warning_image_path)) {
            $numDays = abs($conf->facture->client->warning_delay) / 60 / 60 / 24;
            $pdf->Image($this->warning_image_path, $this->posxLeft, $this->page_hauteur - $this->marge_basse - $this->line_height - 3 + 0.4, 4, 4);
            $pdf->SetFont('', '', $defaultFontSize - 2);
            $pdf->SetXY($this->posxLeft + 3, $this->page_hauteur - $this->marge_basse - $this->line_height - 3);
            $pdf->Cell(80, $this->line_height, "= " . $outputLangs->transnoentities('WarningDelay', $numDays), 0, 0, 'L', 0, false, '', 0);
        }
    }
}
