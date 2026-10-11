<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */
// Isolated test doubles: this runner never boots Dolibarr or sends mail.
error_reporting(E_ALL & ~E_DEPRECATED);
set_error_handler(static function ($severity, $message) { if (error_reporting() & $severity) { throw new RuntimeException($message); } });
define('DOL_DOCUMENT_ROOT', getenv('CORE_FIXTURE_ROOT'));
define('DOL_VERSION', '20.0.0');
define('MAIN_DB_PREFIX', 'erp_');
class CommonObject { public $error = ''; public $id; public $rowid; public $entity; }
class CommonObjectLine extends CommonObject {}
class Facture { const STATUS_DRAFT = 0; const STATUS_VALIDATED = 1; const STATUS_CLOSED = 2; const TYPE_DEPOSIT = 3; const TYPE_CREDIT_NOTE = 2; }
class Project {}
class DolibarrModules {
	public function _init($sql, $options) { foreach ($sql as $query) { $this->db->query($query); } return 1; }
	public function _remove($sql, $options) { return 1; }
}
class TestUser {
	public $id = 7; public $socid = 0; public $all = false; public $allow = true;
	public function hasRight(...$parts) { return $this->allow && ($parts !== array('societe', 'client', 'voir') || $this->all); }
}
class TestLangs {
	public function trans($key, ...$args) { return $key; }
	public function transnoentities($key, ...$args) { return $key; }
	public function loadLangs($keys) {}
}
class TestDb {
	public $queries = array(); public $rows = array(); public $fail = false;
	public function query($sql) { $this->queries[] = $sql; return $this->fail ? false : (object) array('rows' => strpos($sql, 'SELECT COUNT(') === 0 ? array((object) array('total_rows' => 3, 'total_owed' => 308)) : $this->rows); }
	public function fetch_object($result) { return array_shift($result->rows); }
	public function free($result) {}
	public function escape($value) { return str_replace("'", "''", $value); }
	public function sanitize($value) { return $value; }
	public function idate($value) { return date('Y-m-d H:i:s', $value); }
	public function plimit($limit, $offset) { return ' LIMIT '.$limit.' OFFSET '.$offset; }
	public function lasterror() { return 'simulated query failure'; }
	public function begin() {} public function commit() {} public function rollback() {}
}
function dol_now() { return strtotime('2026-10-11 12:00:00 UTC'); }
function dol_print_date($timestamp, $format) { return date($format === 'day' ? 'Y-m-d' : str_replace('%', '', $format), $timestamp); }
function isModEnabled($module) { return in_array($module, array('customersowed', 'facture', 'multicompany', 'cron'), true); }
function getEntity($element) { return '1,2'; }
// Minimal native-search double for text and comparison scenarios, not a core integration test.
function natural_search($field, $value, $numeric = 0) {
	global $db;
	if ($numeric && preg_match('/^(>=|<=|>|<|=)?(-?\d+(?:\.\d+)?)$/D', $value, $match)) {
		return ' AND '.$field.' '.($match[1] ?: '=').' '.$match[2];
	}
	return " AND ".$field." LIKE '%".$db->escape($value)."%'";
}
function price2num($value, $mode) { return (float) $value; }
$conf = (object) array('entity' => 1, 'facture' => (object) array('client' => (object) array('warning_delay' => 0)));
$langs = new TestLangs(); $user = new TestUser(); $hookmanager = null; $db = new TestDb();
require __DIR__.'/../class/customersowed.class.php';
require __DIR__.'/../core/modules/modCustomersOwed.class.php';
$report = new CustomersOwed($db, strtotime('2026-10-10 UTC'));
$queries = array();
foreach (array('restricted' => 1, 'shared' => 0, 'external' => 1) as $scenario => $socid) {
	$user->all = $scenario === 'shared'; $user->socid = $scenario === 'external' ? 1 : 0;
	if ($report->fetch_lines($socid) !== 1) { throw new RuntimeException('fetch failed'); }
	$queries[$scenario] = end($db->queries);
}
$user->socid = 0; $user->all = true;
$report->selectedEntities = array(2);
$report->fetch_lines(); $queries['entity'] = end($db->queries);
$report->selectedEntities = array();
$report->fetch_lines(null, null, null, $user, 1, 1);
$queries['page'] = end($db->queries); $queries['count'] = $db->queries[count($db->queries)-2];
if ($report->total_rows !== 3 || $report->totalCustomersOwed !== 308.0) { throw new RuntimeException('pagination loses full totals'); }
foreach (array('text' => array('f.ref' => 'F1'), 'amount' => array('rtp' => '>60'), 'date' => array('f.datef' => strtotime('2026-10-01 UTC')), 'type' => array('f.type' => '2')) as $scenario => $filters) {
	$report->searchFilters = $filters;
	if ($report->fetch_lines(null, 'rtp', 'DESC', $user, 20, 0) !== 1) { throw new RuntimeException('filter failed'); }
	$queries['filter_'.$scenario] = end($db->queries);
	$queries['count_'.$scenario] = $db->queries[count($db->queries)-2];
}
$report->searchFilters = array('injected.field' => '1');
if ($report->fetch_lines() !== -1) { throw new RuntimeException('unknown filter accepted'); }
$report->searchFilters = array();
$report->selectedEntities = array(9);
if ($report->fetch_lines() !== -1) { throw new RuntimeException('unauthorized entity accepted'); }
$report->selectedEntities = array();
if ($report->fetch_lines(null, 'f.ref; DROP TABLE erp_facture') !== -1) { throw new RuntimeException('invalid sort accepted'); }
$user->allow = false;
if ($report->fetch_lines() !== -1) { throw new RuntimeException('permission bypass'); }
$user->allow = true; $user->socid = 1;
if ($report->fetch_lines(2) !== -1) { throw new RuntimeException('external third party bypass'); }
$user->socid = 0;
$db->rows = array((object) array_fill_keys(array('entity','fk_soc','fk_statut','paye','ref_client','name','email','code_client','code_compta','phone','address','zip','town','total_ht','total_vat','total_ttc','total_localtax1','total_localtax2','fk_mode_reglement','mode_reglement_code','project_ref','project_label','date_creation','date_update','tms','rowid','ref','datef','datelimite','am','ream','type','country_code'), 0));
$db->rows[0]->total_ttc = 100; $db->rows[0]->am = 10; $db->rows[0]->ream = 5;
$mailRow = clone $db->rows[0]; $mailRow->rowid = 1;
$report->fetch_lines(); $report->fetch_lines();
if ($report->totalCustomersOwed !== 85.0 || $report->lines_count !== 1) { throw new RuntimeException('totals accumulate across reads'); }
$db->rows = array(); $db->fail = true;
if ($report->fetch_lines() !== -1 || $report->lines_count !== 0 || $report->totalCustomersOwed !== 0) { throw new RuntimeException('failed fetch leaks old state'); }
$db->fail = false; $db->queries = array();
$module = new modCustomersOwed($db); $module->init();
$migration = $db->queries;
$rights = array_map(static function ($right) { return $right[0]; }, $module->rights);
foreach ($module->const as $constant) { if ($constant[6] !== 0) { throw new RuntimeException('configuration deleted on disable'); } }
function dol_mkdir($path) { return is_dir($path) || mkdir($path, 0770, true) ? 1 : -1; }
function getMultidirOutput($object, $module, $forobject) { global $nativeDirectory; return $nativeDirectory; }
$nativeDirectory = DOL_DOCUMENT_ROOT.'/documents';
if ($report->getReportDirectory($user) !== false) { throw new RuntimeException('missing directory accepted'); }
$conf->customersowed->multidir_output = array(1 => $nativeDirectory);
$nativeDirectory = 'error-output-not-configured';
if ($report->getReportDirectory($user) !== false) { throw new RuntimeException('native error interpreted as path'); }
$nativeDirectory = DOL_DOCUMENT_ROOT.'/documents';
$directory = $report->getReportDirectory($user);
if ($directory === false || basename($directory) !== '7') { throw new RuntimeException('actor directory missing'); }
$conf->entity = 2;
if ($report->getReportDirectory($user) !== false) { throw new RuntimeException('fallback to another entity'); }
$conf->entity = 1;
require __DIR__.'/../class/customersowedpdflayout.trait.php';
class TestPdf {
	public $margin; public $calls = array();
	public function SetAutoPageBreak($enabled, $margin) {}
	public function setPageOrientation($orientation, $enabled, $margin) { $this->margin = $margin; }
}
class TestLayout {
	use CustomersOwedPdfLayout;
	public $marge_basse = 10; public $marge_gauche = 10; public $page_hauteur = 297; public $page_largeur = 210;
	public $tabTop = 30; public $line_height = 5; public $tab_height;
}
function getDolGlobalInt($key) { return 0; }
function pdf_pagefoot(&$pdf, $langs, $free, $company, $bottom, $left, $height, $object, $details, $hide, $width) {
	global $footerHeight;
	$pdf->calls[] = $hide;
	return $footerHeight;
}
$footerHeight = 40; $mysoc = new stdClass(); $pdf = new TestPdf(); $layout = new TestLayout();
if (!$layout->prepareReportLayout($pdf, $report, $langs) || $pdf->margin !== 40.0 || $pdf->calls) { throw new RuntimeException('footer reservation/clone failed'); }
$layout->_pagefoot($pdf, $report, $langs, 1); $layout->_pagefoot($pdf, $report, $langs, 0);
if ($pdf->calls !== array(1, 0) || $layout->tabTop + $layout->tab_height >= 297 - 40) { throw new RuntimeException('footer lifecycle failed'); }
$footerHeight = 260;
if ($layout->prepareReportLayout($pdf, $report, $langs)) { throw new RuntimeException('oversized footer accepted'); }
class Translate extends TestLangs { public function __construct($unused, $conf) {} public function setDefaultLang($lang) {} }
class User extends TestUser {
	public $statut = 1; public $entity = 1; public $email = 'fixture@example.invalid';
	public function __construct($db) {}
	public function fetch($id) { $this->id = $id; return 1; }
	public function getrights() {}
}
function dol_syslog($text, $level = 0) {}
function getDolGlobalString($key, $default = '') { return $key === 'CUSTOMERSOWEDREPORT_ADDON_PDF' ? 'standard' : $default; }
$mysoc->default_lang = 'en_US';
require __DIR__.'/../class/actions_customersowed.class.php';
class TestMailActions extends ActionsCustomersOwed {
	public $attempts = 0; public $failSend = false;
	public function sendMailWithCustomersowedPdf($user, $report, $directory, $model, $sortfield = null, $sortorder = null, $email = null, $socid = null, $exclude = null, $late = null, $author = null, $sales = null, $recipient = null) {
		$this->attempts++;
		return $this->failSend ? -1 : 0;
	}
}
$db->rows = array($mailRow); $user = new User($db);
$mail = new TestMailActions($db);
if ($mail->sendMails() !== 0 || $mail->sendMails() !== 0 || $mail->attempts !== 1) { throw new RuntimeException('cron replay duplicates delivery'); }
$user->id = 8; $mail->failSend = true;
if ($mail->sendMails() !== -1) { throw new RuntimeException('failed SMTP ignored'); }
$mail->failSend = false;
if ($mail->sendMails() !== -1 || $mail->error !== 'CustomersOwedMailUncertain' || $mail->attempts !== 2) { throw new RuntimeException('uncertain delivery retried'); }
class AccessDenied extends RuntimeException {}
function accessforbidden() { throw new AccessDenied(); }
$user->id = 7; $report->fetch_lines();
$file = $directory.'/'.$report->pdfreportfilename;
file_put_contents($file, '%PDF-fixture');
if ($report->recordDocumentManifest($file) !== 1) { throw new RuntimeException('manifest missing'); }
$parameters = array('modulepart' => 'customersowed', 'fuser' => $user, 'entity' => 1, 'original_file' => $file);
$action = ''; $object = new stdClass();
$mail->checkSecureAccess($parameters, $object, $action, null);
$user->id = 8;
try { $mail->checkSecureAccess($parameters, $object, $action, null); throw new RuntimeException('other actor download accepted'); } catch (AccessDenied $expected) {}
$user->id = 7; $db->rows = array();
try { $mail->checkSecureAccess($parameters, $object, $action, null); throw new RuntimeException('revoked scope download accepted'); } catch (AccessDenied $expected) {}
echo json_encode(array('queries' => $queries, 'migration' => $migration, 'rights' => array_values($rights)), JSON_THROW_ON_ERROR);
