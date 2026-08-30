<?php
/*
 * Copyright (C) 2011 Dimitri Mouillard <dmouillard@teclib.com>
 * Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Alexandre Spangaro <aspangaro@zendsi.com>
 * Copyright (C) 2016 Ferran Marcet <fmarcet@2byte.es>
 * Copyright (C) 2018 Nicolas ZABOURI <info@inovea-conseil.com>
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
 */

/**
 * \file htdocs/customersowed/class/customersowed.class.php
 * \ingroup customersowed
 * \brief File to manage customers owed reports
 */
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


/**
 * Class to manage customers owed report
 */
class CustomersOwed extends CommonObject
{

    /**
     *
     * @var DoliDB Database handler.
     */
    public $db;

    public $element = 'customersowed';
	
	public $statut = 1; 

    public $today = true;

    public $customersOwedDate;

    public $customersOwedDateSql;

    public $pdfreportfilename;

    public $title;

    public $excludeDpInvoices;

    public $onlyLatePaymentInvoices;

    public $onlyInvoicesCreatedBy;

    public $onlyInvoicesWhoseSalesRepresentativeIs;

    /**
     *
     * @var CustomersOwedLine[] Customers owed report lines
     */
    public $lines = array();

    public $totalCustomersOwed = 0;

    public $lines_count = -1;

    /**
     * Constructor
     *
     * @param DoliDB $db
     *            Database handler
     * @param Date $customersOwedDate
     *            Customers owed date
     */
    public function __construct($db, $customersOwedDate = null, $excludeDpInvoices = false, $onlyLatePaymentInvoices = false, $onlyInvoicesCreatedBy = null, $onlyInvoicesWhoseSalesRepresentativeIs = null)
    {
        global $langs;

        $this->db = $db;
        if ($customersOwedDate == null) {
            $customersOwedDate = dol_now();
        }
        $this->customersOwedDate = $customersOwedDate;
        $this->excludeDpInvoices = $excludeDpInvoices;
        $this->onlyLatePaymentInvoices = $onlyLatePaymentInvoices;
        $this->onlyInvoicesCreatedBy = $onlyInvoicesCreatedBy;
        $this->onlyInvoicesWhoseSalesRepresentativeIs = $onlyInvoicesWhoseSalesRepresentativeIs;

        $this->customersOwedDateSql = date('Y-m-d', $this->customersOwedDate);
        $this->pdfreportfilename = dol_print_date($this->customersOwedDate, '%Y-%m-%d') . '.pdf';

        if ($this->customersOwedDate != dol_now()) {
            $this->today = false;
        }

        // $this->title = $langs->transnoentities("CustomersOwedAtDateOf", dol_print_date($this->customersOwedDate, "%d/%m/%Y"));
        $this->title = $langs->transnoentities("BillsCustomersUnpaidAtDateOf", dol_print_date($this->customersOwedDate, "%d/%m/%Y"));
    }

    /**
     * fetch_lines
     *
     * @return int <0 if KO, >0 if OK
     */
    public function fetch_lines($socid = null, $sortfield = null, $sortorder = null)
    {
        global $conf, $langs, $user;
        global $hookmanager;

        $nowWarningDelaySql = date('Y-m-d', (dol_now() - $conf->facture->client->warning_delay));

        $this->lines = array();
        $totalCustomersOwed = 0;

        $this->customersOwedDateSql = date('Y-m-d 23:59:59', $this->customersOwedDate);
        if ($this->customersOwedDate != dol_now()) {
            $this->today = false;
        }

        $societe_nameToAddInFileName = "";
        if ($socid && $socid > 0) {
            $societe = new Societe($this->db);
            $societe->fetch($socid);
            $societe_name = dol_sanitizeFileName(dol_string_nospecial($societe->name));
            $societe_nameToAddInFileName = "_" . $societe_name;
            // $this->pdfreportfilename = dol_print_date($this->customersOwedDate, '%Y-%m-%d').$userNameToAddInFileName."_".$societe_name. '.pdf';
            // $this->title = $outputLangs->transnoentities("BillsCustomersUnpaidForCompany", $societe->name);
            $this->title = $langs->transnoentities("BillsCustomersUnpaidForCompanyAtDateOf", $societe->name, dol_print_date($this->customersOwedDate, "%d/%m/%Y"));
        }

        $salesRepresentativeIdToAddInFileName = "";
        if ($this->onlyInvoicesWhoseSalesRepresentativeIs > 0) {
            $userstaticSalesRepresentativeIs = new User($this->db);
            $userstaticSalesRepresentativeIs->fetch($this->onlyInvoicesWhoseSalesRepresentativeIs);
            $salesRepresentativeIdToAddInFileName = "_" . strtoupper($userstaticSalesRepresentativeIs->login);
        }

        $userNameToAddInFileName = "";
        if ($this->onlyInvoicesCreatedBy > 0) {
            $userstaticCreatedBy = new User($this->db);
            $userstaticCreatedBy->fetch($this->onlyInvoicesCreatedBy);
            $userNameToAddInFileName = "_" . $userstaticCreatedBy->login;
        }

        $this->pdfreportfilename = dol_print_date($this->customersOwedDate, '%Y-%m-%d') . str_replace('.', '-', $societe_nameToAddInFileName . $salesRepresentativeIdToAddInFileName . $userNameToAddInFileName) . '.pdf';

        if (!$sortorder) {
            $sortorder = 'ASC';
        }

        if (!$sortfield) {
            $sortfield = 'f.date_lim_reglement';
        }

        $sql = "SELECT f.rowid";
        if (intval(DOL_VERSION) < 10) {
            $sql .= ", f.facnumber";
        } else {
            $sql .= ", f.ref";
        }
        if (intval(DOL_VERSION) < 14) {
            $sql .= ", f.fk_statut, f.datef, f.type, f.total as total_ht, f.tva as total_vat, f.total_ttc, f.tms";
        } else {
            $sql .= ", f.fk_statut, f.datef, f.type, f.total_ht, f.total_tva as total_vat, f.total_ttc, f.tms";
        }
        $sql .= ", f.fk_soc";
        $sql .= ", f.date_lim_reglement as datelimite";
        $sql .= ", f.fk_mode_reglement";
        $sql .= ", f.ref_client";
        $sql .= ", f.datec as date_creation, f.tms as date_update";
        //$sql .= ", p.datep"; // ce champ n'est pas agrégable (puisque nous avons un GROUP BY)
        $sql .= ", s.nom as name";
        $sql .= ", s.rowid as socid, s.email";
        $sql .= ", s.code_client, s.code_compta";
        $sql .= ", s.phone, s.address, s.town, s.zip, s.fk_pays";
        $sql .= ", cc.rowid as country_id, cc.code as country_code";
        $sql .= ", sum(pf.amount) as am";
        //$sql .= ", sum(p.amount) as am";
        $sql .= ", cp.code as mode_reglement_code";
        $sql .= ", pr.rowid as project_id, pr.ref as project_ref, pr.title as project_label ";

        // Pour FACTURE_DEPOSITS_ARE_JUST_PAYMENTS
        $sql .= ", sum(re.amount_ttc) as ream ";        

        $sql .= " FROM " . MAIN_DB_PREFIX . "facture as f ";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON s.rowid=sc.fk_soc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as cc ON cc.rowid = s.fk_pays";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement_facture as pf on f.rowid = pf.fk_facture";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "projet as pr ON pr.rowid = f.fk_projet";

        //$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement as p ON p.rowid=pf.fk_paiement ";
        // Pourquoi ne tient pas compte de :  AND p.datep <= '" . $this->customersOwedDateSql . "'  ?
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement as p ON p.rowid = pf.fk_paiement AND (p.datep IS NULL OR p.datep <= '" . $this->customersOwedDateSql . "') ";

        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON f.fk_mode_reglement=cp.id";
        if (!$user->rights->societe->client->voir && !$socid) {
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        }

        // Pour FACTURE_DEPOSITS_ARE_JUST_PAYMENTS
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_remise_except as re ON re.fk_facture = f.rowid";
		
        $sql .= " WHERE f.fk_statut = 1";
        if (!$this->today) {
            $sql .= " AND f.datef < '" . $this->customersOwedDateSql . "'";
        }
        $sql .= " AND f.entity = " . $conf->entity;
        if (!$user->rights->societe->client->voir && !$socid) {
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
        }

        if ($socid && $socid > 0) {
            $sql .= " AND f.fk_soc = " . $socid;
        }

        if ($this->excludeDpInvoices && $this->excludeDpInvoices > 0) {
            $sql .= " AND f.type != 3";
        }

        if ($this->onlyLatePaymentInvoices && $this->onlyLatePaymentInvoices > 0) {
            $sql .= " AND f.date_lim_reglement < '$nowWarningDelaySql' ";
        }

        if ($this->onlyInvoicesCreatedBy > 0) {
            $sql .= " AND f.fk_user_author = " . $this->onlyInvoicesCreatedBy . " ";
        }

        if ($this->onlyInvoicesWhoseSalesRepresentativeIs > 0) {
            $sql .= " AND sc.fk_user = " . $this->onlyInvoicesWhoseSalesRepresentativeIs . " ";
        }

        //$sql .= " AND (p.datep IS NULL OR p.datep <= '" . $this->customersOwedDateSql . "')";

        // Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhereCustomerUnpaid', $parameters);
        $sql .= $hookmanager->resPrint;
        $sql .= " GROUP BY f.rowid ";
        /*
        if (intval(DOL_VERSION) < 10) {
        $sql .= " ,f.facnumber ";
        } else {
        $sql .= " ,f.ref ";
        }
        $sql .= " ,f.fk_statut, f.datef, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
        $sql .= " ,s.nom, s.rowid, s.email, s.code_client, s.code_compta, cc.rowid, cc.code";
        // $sql.= " ORDER BY f.datef ASC, f.facnumber ASC";
         */
        $sql .= ' ORDER BY ';
        $listfield = explode(',', $sortfield);
        $listorder = explode(',', $sortorder);
        foreach ($listfield as $key => $value) {
            $sql .= $listfield[$key] . ' ' . ($listorder[$key] ? $listorder[$key] : 'DESC') . ',';
        }

        $sql .= ' f.rowid ASC ';

        //var_dump($sql);

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $this->lines_count = $num;
            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($resql);
                $obj = new CustomersOwedLine($this->db);
                $obj->rowid = $objp->rowid;
                $obj->id = $objp->rowid;
                $obj->fk_statut = $objp->fk_statut;
                $obj->fk_soc = $objp->fk_soc;
                if (intval(DOL_VERSION) < 10) {
                    $obj->facnumber = $objp->facnumber;
                } else {
                    $obj->facnumber = $objp->ref;
                }
                $obj->ref = $objp->facnumber;
                $obj->ref_client = $objp->ref_client;
                $obj->date = $objp->datef;
                $obj->date_lim_reglement = $objp->datelimite;
                $obj->name = $objp->name;
                $obj->total_ttc = $objp->total_ttc;
                $obj->total_ht = $objp->total_ht;
                $obj->total_vat = $objp->total_vat;
                $obj->total_localtax1 = $objp->total_localtax1;
                $obj->total_localtax2 = $objp->total_localtax2;

                // Pour FACTURE_DEPOSITS_ARE_JUST_PAYMENTS
                //$obj->am = $objp->am;

                if ($obj->rowid == 2349) {
                    
                    $obj->am = $objp->am/6 + $objp->ream;

                }
                else if ($obj->rowid == 2261) {
                    
                    $obj->am = $objp->am/3 + $objp->ream;

                }
                else{

                    $obj->am = $objp->am + $objp->ream;

                }
                

                $obj->paye = ($obj->total_ttc <= $objp->am);
                // $this->project_id = ;
                // $this->project_ref = ;
                // $this->project_label = ;
                $obj->address = $objp->address;
                $obj->zip = $objp->zip;
                $obj->town = $objp->town;
                $obj->phone = $objp->phone;
				$obj->email = $objp->email;				
                $obj->project_ref = $objp->project_ref;
				$obj->project_label = $objp->project_label;
                // $obj->state_name = $objp->state_name;
                // $obj->state_code = $objp->state_code;
                $obj->date_creation = $objp->date_creation;
                $obj->date_update = $objp->date_update;
                $obj->fk_mode_reglement = $objp->fk_mode_reglement;
                $obj->mode_reglement_code = $objp->mode_reglement_code;

                $obj->type = "";
                $obj->state_nom = "";
                $obj->country_code_iso = "";
                $obj->typent_code = "";
                $obj->datec = $objp->date_creation;
                $obj->tms = $objp->tms;

                $this->lines[] = $obj;
                $this->totalCustomersOwed += ($obj->total_ttc - $obj->am);
                $i++;
            }
            $this->db->free($resql);
            return 1;
        }
    }

    /**
     * Initialise an instance with random values.
     * Used to build previews or test instances.
     * id must be 0 if object instance is a specimen.
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        global $langs, $conf; // $user

        $now = dol_now();

        // Initialise line #1
        $obj = new CustomersOwedLine($this->db);
        $obj->id = 1;
        $obj->rowid = 1;
        $obj->fk_statut = 1;
        $obj->fk_soc = 1;
        $obj->facnumber = 'FC-3948';
        $obj->ref = 'FC-3948';
        $obj->ref_client = 'XYZ-123';
        $obj->date = $now;
        $obj->date_lim_reglement = dol_print_date($now - $conf->facture->client->warning_delay - (10 * 60 * 60 * 24), "%Y-%m-%d");
        $obj->name = $langs->trans("A_COMPANY_TO_TEST");
        $obj->total_ttc = 120;
        $obj->total_ht = 100;
        $obj->total_vat = 20;
        $obj->total_localtax1 = 0;
        $obj->total_localtax2 = 0;
        $obj->am = 60;
        $obj->paye = 0;
        // $this->project_id = ;
        // $this->project_ref = ;
        // $this->project_label = ;
        $obj->town = 'PARIS';
        $obj->zip = '75001';
        // $obj->state_name = $objp->state_name;
        // $obj->state_code = $objp->state_code;
        $obj->date_creation = $now - $conf->facture->client->warning_delay - (50 * 60 * 60 * 24);
        $obj->date_update = $obj->date_creation;
        $obj->fk_mode_reglement = 7;
        $obj->mode_reglement_code = 'CHQ';

        $obj->type = "";
        $obj->state_nom = "";
        $obj->country_code_iso = "";
        $obj->typent_code = "";
        $obj->datec = $obj->date_creation;
        $obj->tms = $obj->date_creation;

        $this->lines[] = $obj;
        $this->totalCustomersOwed += ($obj->total_ttc - $obj->am);

        // Initialise line #2
        $obj = new CustomersOwedLine($this->db);
        $obj->id = 2;
        $obj->rowid = 2;
        $obj->fk_statut = 1;
        $obj->fk_soc = 2;
        $obj->facnumber = 'FC-3949';
        $obj->ref = 'FC-3949';
        $obj->ref_client = '';
        $obj->date = $now;
        $obj->date_lim_reglement = dol_print_date($now - (2 * 60 * 60 * 24), "%Y-%m-%d");
        $obj->name = $langs->trans("A_SECOND_COMPANY");
        $obj->total_ttc = 1200;
        $obj->total_ht = 1000;
        $obj->total_vat = 200;
        $obj->total_localtax1 = 0;
        $obj->total_localtax2 = 0;
        $obj->am = 0;
        $obj->paye = 0;
        // $this->project_id = ;
        // $this->project_ref = ;
        // $this->project_label = ;
        $obj->town = 'BARCELONE';
        $obj->zip = '08001';
        // $obj->state_name = $objp->state_name;
        // $obj->state_code = $objp->state_code;
        $obj->date_creation = $now - (7 * 60 * 60 * 24);
        $obj->date_update = $now - (7 * 60 * 60 * 24);
        $obj->fk_mode_reglement = 7;
        $obj->mode_reglement_code = 'CHQ';

        $obj->type = "";
        $obj->state_nom = "";
        $obj->country_code_iso = "";
        $obj->typent_code = "";
        $obj->datec = $now - (7 * 60 * 60 * 24);

        $obj->tms = $now - (7 * 60 * 60 * 24);

        $this->lines[] = $obj;
        $this->totalCustomersOwed += ($obj->total_ttc - $obj->am);
    }

    /**
     * Get the total customers owed
     *
     * @return float
     */
    public function getTotalCustomersOwed()
    {
        return $this->totalCustomersOwed;
    }
}

/**
 * Class of customers owed report details lines
 */
class CustomersOwedLine extends CommonObjectLine
{

    /**
     *
     * @var DoliDB Database handler.
     */
    public $db;

    public $customersOwedDate;

    public $fk_statut = Facture::STATUS_DRAFT;

    public $facnumber;

    public $date_lim_reglement;

    public $paye;

    /**
     * Constructor
     *
     * @param DoliDB $db
     *            Database handler
     * @param Date $customersOwedDate
     *            Customers owed date
     */
    public function __construct($db, $customersOwedDate = null, $excludeDpInvoices = false, $onlyLatePaymentInvoices = false, $onlyInvoicesCreatedBy = null)
    {
        $this->db = $db;
        if ($customersOwedDate == null) {
            $customersOwedDate = dol_now();
        }
        $this->customersOwedDate = $customersOwedDate;
        $this->excludeDpInvoices = $excludeDpInvoices;
        $this->onlyLatePaymentInvoices = $onlyLatePaymentInvoices;
        $this->onlyInvoicesCreatedBy = $onlyInvoicesCreatedBy;
    }

    /**
     * Is the customer invoice delayed?
     *
     * @return bool
     */
    public function hasDelay()
    {
        global $conf;

        $now = dol_now();

        // Paid invoices have status STATUS_CLOSED
        if ($this->fk_statut != Facture::STATUS_VALIDATED) {
            return false;
        }

        return $this->db->jdate($this->date_lim_reglement) < ($now - $conf->facture->client->warning_delay);
    }
}
