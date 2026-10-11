<?php
/*
 * Copyright (C) 2011 Dimitri Mouillard <dmouillard@teclib.com>
 * Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Alexandre Spangaro <aspangaro@zendsi.com>
 * Copyright (C) 2016 Ferran Marcet <fmarcet@2byte.es>
 * Copyright (C) 2018 Nicolas ZABOURI <info@inovea-conseil.com>
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
 */

/**
 * \file htdocs/customersowed/class/customersowed.class.php
 * \ingroup customersowed
 * \brief File to manage customers owed reports
 */
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once __DIR__.'/customersowedcompatibility.class.php';


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
	public $module = 'customersowed';
	/** @var list<int> Selected invoice entities, constrained by native sharing. */
	public $selectedEntities = array();

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
	/** @var int Number of accessible invoices before pagination. */
	public $total_rows = 0;

	/** Resolve an aggregate report's entity and actor directory, refusing missing configuration.
	 * @param User $actor Authenticated actor
	 * @return string|false Validated directory or false
	 */
	public function getReportDirectory($actor)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$entity = (int) $conf->entity;
		if ($entity <= 0 || (int) $actor->id <= 0
			|| !isset($conf->customersowed->multidir_output[$entity])
			|| !is_string($conf->customersowed->multidir_output[$entity])
			|| trim($conf->customersowed->multidir_output[$entity]) === '') {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return false;
		}
		$this->entity = $entity; // An aggregate is owned by the entity requesting the report.
		$root = getMultidirOutput($this, 'customersowed', 0);
		if (!is_string($root) || $root === '' || strpos($root, 'error-') === 0 || is_link($root)) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return false;
		}
		$private = rtrim($root, '/\\').'/private';
		$directory = $private.'/'.((int) $actor->id);
		if (is_link($private) || is_link($directory) || dol_mkdir($directory) < 0) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return false;
		}
		$resolvedRoot = realpath($root);
		$resolvedDirectory = realpath($directory);
		if ($resolvedRoot === false || $resolvedDirectory === false
			|| strpos(str_replace('\\', '/', $resolvedDirectory).'/', str_replace('\\', '/', $resolvedRoot).'/') !== 0) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return false;
		}
		return $resolvedDirectory;
	}

	/** Persist the invoice scope of a generated PDF for download-time revalidation.
	 * @param string $file Validated generated PDF path
	 * @return int 1 on success, -1 on failure
	 */
	public function recordDocumentManifest($file)
	{
		global $user;
		$expected = $this->getReportDirectory($user);
		if ($expected === false || !is_file($file) || realpath(dirname($file)) !== realpath($expected)
			|| basename($file) !== $this->pdfreportfilename) { $this->error = 'CustomersOwedDirectoryUnavailable'; return -1; }
		$directory = dirname($file).'/.metadata';
		if (is_link($file) || is_link($directory) || dol_mkdir($directory) < 0) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return -1;
		}
		$manifest = array('date' => (int) $this->customersOwedDate,
			'invoices' => array_map(static function ($line) { return (int) $line->id; }, $this->lines));
		$target = $directory.'/'.basename($file).'.json';
		if (is_link($target) || file_put_contents($target, json_encode($manifest), LOCK_EX) === false) {
			$this->error = 'CustomersOwedDirectoryUnavailable';
			return -1;
		}
		return 1;
	}

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
		global $langs, $conf;

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

		// $this->title = $langs->transnoentities("CustomersOwedAtDateOf", dol_print_date($this->customersOwedDate, "day"));
		$this->title = $langs->transnoentities("BillsCustomersUnpaidAtDateOf", dol_print_date($this->customersOwedDate, "day"));
	}

	/**
	 * Load the report using the authenticated user's invoice and third-party scope.
	 * Payments and credits are aggregated independently to avoid join multiplication.
	 *
	 * @param int|null $socid Optional third-party filter
	 * @param string|null $sortfield Comma-separated supported columns
	 * @param string|null $sortorder Comma-separated ASC/DESC directions
	 * @return int 1 on success, -1 on invalid input or database/access error
	 */
	public function fetch_lines($socid = null, $sortfield = null, $sortorder = null, $actor = null, $limit = 0, $offset = 0)
	{
		global $conf, $langs, $hookmanager;
		global $user;
		$reportUser = $actor ?: $user;
		$this->lines = array();
		$this->lines_count = 0;
		$this->total_rows = 0;
		$this->totalCustomersOwed = 0;
		$this->error = '';
		if (!CustomersOwedCompatibility::getFeatures()['CustomersOwedReportCapability']['available'] || !isModEnabled('customersowed')
			|| !$reportUser->hasRight('customersowed', 'read')
			|| !$reportUser->hasRight('facture', 'lire') || !$reportUser->hasRight('societe', 'lire')) {
			$this->error = $langs->trans('NotEnoughPermissions');
			return -1;
		}
		$socid = (int) $socid;
		if (!empty($reportUser->socid)) {
			if ($socid > 0 && $socid !== (int) $reportUser->socid) {
				$this->error = $langs->trans('NotEnoughPermissions');
				return -1;
			}
			$socid = (int) $reportUser->socid;
		}
		$allowedSorts = array('f.rowid', 'f.ref', 'f.ref_client', 'f.type', 'f.datef',
			'f.datec', 'f.tms', 'f.date_lim_reglement', 'f.fk_mode_reglement', 'f.total_ht',
			'f.total_tva', 'f.total_ttc', 'f.total_localtax1', 'f.total_localtax2', 's.nom', 's.zip', 's.town', 'pr.ref', 'pr.title', 'f.entity');
		$aliases = array('p.ref' => 'pr.ref', 'p.title' => 'pr.title', 'f.total' => 'f.total_ht', 'f.facnumber' => 'f.ref',
			'f.tva' => 'f.total_tva', 'f.localtax1' => 'f.total_localtax1', 'f.localtax2' => 'f.total_localtax2');
		$fields = explode(',', $sortfield ?: 'f.date_lim_reglement');
		$orders = explode(',', $sortorder ?: 'ASC');
		$orderSql = array();
		foreach ($fields as $key => $field) {
			$field = trim($field);
			$field = $aliases[$field] ?? $field;
			$order = strtoupper(trim($orders[$key] ?? $orders[0]));
			if (!in_array($field, $allowedSorts, true) || !in_array($order, array('ASC', 'DESC'), true)) {
				$this->error = $langs->trans('ErrorBadParameters');
				return -1;
			}
			$orderSql[] = $field.' '.$order;
		}
		$this->customersOwedDateSql = dol_print_date($this->customersOwedDate, '%Y-%m-%d').' 23:59:59';
		$cutoff = $this->db->escape($this->customersOwedDateSql);
		$invoiceEntities = array_map('intval', explode(',', getEntity('invoice')));
		$selectedEntities = array_values(array_intersect($invoiceEntities, $this->selectedEntities));
		if ($this->selectedEntities && !$selectedEntities) {
			$this->error = $langs->trans('NotEnoughPermissions');
			return -1;
		}
		$entities = $selectedEntities ?: $invoiceEntities;
		$sql = 'SELECT f.rowid, f.entity, f.ref, f.fk_statut, f.type, f.fk_soc, f.ref_client,';
		$sql .= ' f.datef, f.date_lim_reglement as datelimite, f.datec as date_creation, f.tms as date_update, f.tms,';
		$sql .= ' f.fk_mode_reglement, f.total_ht, f.total_tva as total_vat, f.total_ttc, f.total_localtax1, f.total_localtax2,';
		$sql .= ' s.nom as name, s.email, s.code_client, s.code_compta, s.phone, s.address, s.zip, s.town,';
		$sql .= ' cc.code as country_code, cp.code as mode_reglement_code,';
		$sql .= ' pr.ref as project_ref, pr.title as project_label,';
		$sql .= ' COALESCE(pay.amount, 0) as am, COALESCE(credit.amount, 0) as ream';
		$selectSql = $sql;
		$sql = ' FROM '.MAIN_DB_PREFIX.'facture f';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'societe s ON s.rowid = f.fk_soc';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country cc ON cc.rowid = s.fk_pays';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement cp ON cp.id = f.fk_mode_reglement';
		$projectIds = '0';
		if (isModEnabled('projet') && ($reportUser->hasRight('projet', 'lire') || $reportUser->hasRight('projet', 'all', 'lire'))) {
			$project = new Project($this->db);
			$projectIds = $project->getProjectsAuthorizedForUser($reportUser,
				$reportUser->hasRight('projet', 'all', 'lire') ? 2 : 0, 1, (int) $reportUser->socid);
			if (!is_string($projectIds) || !preg_match('/^\d+(,\d+)*$/', $projectIds)) {
				$projectIds = '0';
			}
		}
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet pr ON pr.rowid = f.fk_projet AND pr.rowid IN ('.$projectIds.')';
		$sql .= ' LEFT JOIN (SELECT pf.fk_facture, SUM(pf.amount) as amount FROM '.MAIN_DB_PREFIX.'paiement_facture pf';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX."paiement p ON p.rowid = pf.fk_paiement WHERE p.datep <= '".$cutoff."' GROUP BY pf.fk_facture) pay ON pay.fk_facture = f.rowid";
		// Applied discounts reduce the target invoice and consume the negative source credit.
		$sql .= ' LEFT JOIN (SELECT used.fk_facture, SUM(used.amount) as amount FROM (';
		$sql .= ' SELECT r.fk_facture, r.amount_ttc as amount FROM '.MAIN_DB_PREFIX.'societe_remise_except r';
		$sql .= " WHERE r.datec <= '".$cutoff."' AND r.fk_facture > 0";
		$sql .= ' UNION ALL SELECT r.fk_facture_source as fk_facture, -r.amount_ttc as amount FROM '.MAIN_DB_PREFIX.'societe_remise_except r';
		$sql .= " WHERE r.datec <= '".$cutoff."' AND r.fk_facture_source > 0 AND (r.fk_facture > 0 OR r.fk_facture_line > 0)";
		$sql .= ') used GROUP BY used.fk_facture) credit ON credit.fk_facture = f.rowid';
		$sql .= ' WHERE f.fk_statut IN ('.Facture::STATUS_VALIDATED.', '.Facture::STATUS_CLOSED.')';
		$sql .= ' AND f.total_ttc <> COALESCE(pay.amount, 0) + COALESCE(credit.amount, 0)';
		$sql .= ' AND f.entity IN ('.implode(',', $entities).')';
		$sql .= ' AND s.entity IN ('.$this->db->sanitize(getEntity('societe')).')';
		$sql .= " AND f.datef <= '".$cutoff."'";
		if (!$reportUser->hasRight('societe', 'client', 'voir') && empty($reportUser->socid)) {
			$sql .= ' AND EXISTS (SELECT 1 FROM '.MAIN_DB_PREFIX.'societe_commerciaux sc';
			$sql .= ' WHERE sc.fk_soc = s.rowid AND sc.fk_user = '.((int) $reportUser->id).')';
		}
		if ($socid > 0) {
			$sql .= ' AND f.fk_soc = '.$socid;
		}
		if ($this->excludeDpInvoices) {
			$sql .= ' AND f.type <> '.Facture::TYPE_DEPOSIT;
		}
		if ($this->onlyLatePaymentInvoices) {
			$sql .= " AND f.date_lim_reglement < '".$this->db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
		}
		if ($this->onlyInvoicesCreatedBy > 0) {
			$sql .= ' AND f.fk_user_author = '.((int) $this->onlyInvoicesCreatedBy);
		}
		if ($this->onlyInvoicesWhoseSalesRepresentativeIs > 0) {
			$sql .= ' AND EXISTS (SELECT 1 FROM '.MAIN_DB_PREFIX.'societe_commerciaux sc';
			$sql .= ' WHERE sc.fk_soc = s.rowid AND sc.fk_user = '.((int) $this->onlyInvoicesWhoseSalesRepresentativeIs).')';
		}
		if (is_object($hookmanager)) {
			$parameters = array();
			if ($hookmanager->executeHooks('printFieldListWhereCustomerUnpaid', $parameters) < 0) {
				$this->error = $hookmanager->error;
				return -1;
			}
			$sql .= $hookmanager->resPrint;
		}
		if ((int) $limit > 0) {
			$countResult = $this->db->query('SELECT COUNT(*) as total_rows, COALESCE(SUM(f.total_ttc - COALESCE(pay.amount, 0) - COALESCE(credit.amount, 0)), 0) as total_owed'.$sql);
			if (!$countResult || !is_object($countRow = $this->db->fetch_object($countResult))) {
				$this->error = $this->db->lasterror();
				return -1;
			}
			$this->total_rows = (int) $countRow->total_rows;
			$this->totalCustomersOwed = price2num($countRow->total_owed, 'MT');
			$this->db->free($countResult);
		}
		$sql = $selectSql.$sql.' ORDER BY '.implode(', ', $orderSql).', f.rowid ASC';
		if ((int) $limit > 0) { $sql .= $this->db->plimit((int) $limit, max(0, (int) $offset)); }
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while (is_object($row = $this->db->fetch_object($resql))) {
			$line = new CustomersOwedLine($this->db, $this->customersOwedDate);
			foreach (array('entity', 'fk_soc', 'fk_statut', 'ref_client', 'name', 'email', 'code_client',
				'code_compta', 'phone', 'address', 'zip', 'town', 'total_ht', 'total_vat', 'total_ttc',
				'total_localtax1', 'total_localtax2', 'fk_mode_reglement', 'mode_reglement_code',
				'project_ref', 'project_label', 'date_creation', 'date_update', 'tms') as $field) {
				$line->{$field} = $row->{$field};
			}
			$line->id = $line->rowid = (int) $row->rowid;
			$line->ref = $line->facnumber = $row->ref;
			$line->date = $row->datef;
			$line->datec = $row->date_creation;
			$line->date_lim_reglement = $row->datelimite;
			$line->am = price2num($row->am + $row->ream, 'MT');
			$line->paye = $line->total_ttc <= $line->am;
			$line->type = $row->type;
			$line->country_code = $row->country_code;
			$this->lines[] = $line;
			if ((int) $limit <= 0) { $this->totalCustomersOwed = price2num($this->totalCustomersOwed + $line->total_ttc - $line->am, 'MT'); }
		}
		$this->db->free($resql);
		$this->lines_count = count($this->lines);
		if ((int) $limit <= 0) { $this->total_rows = $this->lines_count; }
		// Filters and actor form part of the filename: scoped reports cannot overwrite one another.
		$this->pdfreportfilename = dol_print_date($this->customersOwedDate, '%Y-%m-%d').'-'.substr(hash('sha256',
			implode(',', $entities).'|'.$reportUser->id.'|'.$socid.'|'.(int) $this->excludeDpInvoices.'|'.(int) $this->onlyLatePaymentInvoices
			.'|'.(int) $this->onlyInvoicesCreatedBy.'|'.(int) $this->onlyInvoicesWhoseSalesRepresentativeIs), 0, 20).'.pdf';
		return 1;
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
	/** @var int|float|string|null Report value loaded from SQL. */
	public $address;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $am;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $code_client;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $code_compta;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $country_code;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $date;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $date_creation;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $date_update;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $datec;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $email;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $entity;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $excludeDpInvoices;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $fk_mode_reglement;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $fk_soc;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $mode_reglement_code;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $name;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $onlyInvoicesCreatedBy;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $onlyLatePaymentInvoices;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $phone;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $project_label;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $project_ref;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $ref;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $ref_client;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $tms;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $total_ht;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $total_localtax1;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $total_localtax2;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $total_ttc;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $total_vat;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $town;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $type;
	/** @var int|float|string|null Report value loaded from SQL. */
	public $zip;

	/**
	 *
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $customersOwedDate;

	public $fk_statut = Facture::STATUS_DRAFT;

	public $facnumber;
	public $state_nom = '';
	public $country_code_iso = '';
	public $typent_code = '';

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
