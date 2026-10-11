<?php
/*
 * Copyright (C) 2004-2018 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \defgroup customersowed Module CustomersOwed
 * \brief CustomersOwed module descriptor.
 *
 * \file htdocs/customersowed/core/modules/modCustomersOwed.class.php
 * \ingroup customersowed
 * \brief Description and activation file for module CustomersOwed
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module CustomersOwed
 */
class modCustomersOwed extends DolibarrModules
{
	public $license = 'GPL-3.0-or-later';

	// @codingStandardsIgnoreEnd
	/**
	 * Constructor.
	 * Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db
	 *        	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		// Load translation files required by the page
		$langs->loadLangs(array(
			"customersowed@customersowed"));

		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 423100; // Historical Joliciel ID retained to preserve installed module identity.

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'customersowed';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		$this->family = "Joliciel x Les Métiers du Bâtiment";
		$this->familyinfo = array($this->family => array('position' => '90', 'label' => $this->family));

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Module label (no space allowed), used if translation string 'ModuleCustomersOwedName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleCustomersOwedDesc' not found (MyModue is name of module).
		$this->description = "CustomersOwedDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "CustomersOwedDescription (Long)";

		$this->editor_name = 'Joliciel';
		$this->editor_url = 'https://www.joliciel.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '2.0.0';

		// Key used in llx_const table to save module status enabled/disabled (where CUSTOMERSOWEDREPORT is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Name of image file used for this module.
		$this->picto = 'customersowed@customersowed';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'triggers' => 0, // Set this to 1 if module has its own trigger directory (core/triggers)
			'login' => 0, // Set this to 1 if module has its own login method file (core/login)
			'substitutions' => 0, // Set this to 1 if module has its own substitution function file (core/substitutions)
			'menus' => 0, // Set this to 1 if module has its own menus handler directory (core/menus)
			'theme' => 0, // Set this to 1 if module has its own theme directory (theme)
			'tpl' => 0, // Set this to 1 if module overwrite template dir (core/tpl)
			'barcode' => 0, // Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'models' => 1, // Set this to 1 if module has its own models directory (core/modules/xxx)
			'css' => array(
				'/customersowed/css/customersowed.css.php'), // Set this to relative path of css file if module has its own css file
			'js' => array(
				'/customersowed/js/customersowed.js.php'), // Set this to relative path of js file if module must load a js on all pages
			'hooks' => array(
				'data' => array(
					'main', 'document'),
				'entity' => '0'), // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
			'moduleforexternal' => 1 // Set this to 1 if feature of module are opened to external users
		);

		// Data directories to create when module is enabled.
		$this->dirs = array(
			"/customersowed/temp");

		// Config pages. Put here list of php page, stored into customersowed/admin directory, to use to setup module.
		$this->config_page_url = array(
			"setup.php@customersowed");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(
			'modFacture'); // , 'modBanque' List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module class names to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->langfiles = array(
			"customersowed@customersowed");
		$this->phpmin = array(8, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(20, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('SENDBACKUP_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		// 1=>array('SENDBACKUP_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			1 => array(
				'CUSTOMERSOWEDREPORT_EXCLUDE_DP',
				'chaine',
				'1',
				'The mails sent by the periodic task do not contain down payment invoices.',
				0,
				'current',
				0),
			2 => array(
				'CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL',
				'chaine',
				'1',
				'The sending of mail by the periodic task can contain URL.',
				0,
				'current',
				0));
		/*
		 * 2 => array(
		 * 'CUSTOMERSOWEDREPORT_ONLY_SALESREPRESENTATIVE',
		 * 'chaine',
		 * '1',
		 * 'The sending of mail by the periodic task only contains the customers whose recipient of the mail is designated as the commercial',
		 * 0,
		 * 'current',
		 * 1),
		 */

		if (! isset($conf->customersowed) || ! isset($conf->customersowed->enabled)) {
			$conf->customersowed = new stdClass();
			$conf->customersowed->enabled = 0;
		}

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		$this->boxes = array(
			0 => array(
				'file' => 'box_customersowed.php@customersowed',
				'note' => $langs->trans("ModuleCustomersOwedName"),
			)
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array(
			0 => array(
				'label' => $langs->trans("CustomersOwedSendingMailWithPdf"),
				'jobtype' => 'method',
				'class' => '/customersowed/class/actions_customersowed.class.php',
				'objectname' => 'ActionsCustomersOwed',
				'method' => 'sendMails',
				'parameters' => '',
				'comment' => 'CustomersOwedSendingMailWithPdfDesc',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => 'isModEnabled("customersowed")',
				'priority' => 50));

		// Permissions
		$this->rights = array(); // Permission array used by this module

		$r = 0;
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("ReadCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';

		$r ++;
		$this->rights[$r][0] = $this->numero * 100 + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("CreateCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';

		$r ++;
		$this->rights[$r][0] = $this->numero * 100 + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("DeleteCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';

		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		if (intval(DOL_VERSION) > 6) {
			$this->menu[$r ++] = array(
				'fk_menu' => 'fk_mainmenu=billing,fk_leftmenu=customers_bills_payment',
				'mainmenu' => 'billing',
				'leftmenu' => 'customers_owed_report',
				'type' => 'left',
				'titre' => 'CustomersOwedPage',
				'url' => '/customersowed/view/customers_owed_report.php',
				'langs' => 'customersowed@customersowed',
				'position' => '500',
				'enabled' => 'isModEnabled("customersowed") && isModEnabled("facture")',
				'perms' => '$user->hasRight("customersowed", "read")',
				'target' => '',
				'user' => 2);
		} else {
			// Main menu entries
			$this->menu[$r ++] = array(
				'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=customers_bills',
				'mainmenu' => 'accountancy',
				'leftmenu' => 'customers_owed_report',
				'type' => 'left',
				'titre' => 'CustomersOwedPage',
				'url' => '/customersowed/view/customers_owed_report.php',
				'langs' => 'customersowed@customersowed',
				'position' => '500',
				'enabled' => 'isModEnabled("customersowed") && isModEnabled("facture")',
				'perms' => '$user->hasRight("customersowed", "read")',
				'target' => '',
				'user' => 2);
		}
		//
		// Exports
		$r = 1;
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options
	 *        	Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// No persistent report object exists: do not install ModuleBuilder example tables.
		$this->db->begin();
		if ($this->preserveScheduledJob() < 0) { $this->db->rollback(); return -1; }
		foreach (array('user_rights' => 'fk_user', 'usergroup_rights' => 'fk_usergroup') as $table => $actor) {
			foreach (array(423100 => 42310001, 423101 => 42310002, 423102 => 42310003) as $old => $new) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$table.' (entity, '.$actor.', fk_id)';
				$sql .= ' SELECT old.entity, old.'.$actor.', '.$new.' FROM '.MAIN_DB_PREFIX.$table.' old';
				$sql .= ' WHERE old.fk_id = '.$old.' AND NOT EXISTS (SELECT 1 FROM '.MAIN_DB_PREFIX.$table.' existing';
				$sql .= ' WHERE existing.entity = old.entity AND existing.'.$actor.' = old.'.$actor.' AND existing.fk_id = '.$new.')';
				if (!$this->db->query($sql)) {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -1;
				}
			}
			if (!$this->db->query('DELETE FROM '.MAIN_DB_PREFIX.$table.' WHERE fk_id IN (423100, 423101, 423102)')) {
				$this->error = $this->db->lasterror(); $this->db->rollback(); return -1;
			}
		}
		// Native insert_const() preserves existing values, including 0 and empty strings.
		$this->const[] = array('CUSTOMERSOWEDREPORT_ADDON_PDF', 'chaine', 'joliciel', '', 0, 'current', 0);
		$sql = array("INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, libelle, entity)"
			." SELECT 'joliciel', 'customersowed', 'joliciel', ".((int) $conf->entity)
			." WHERE NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."document_model WHERE type = 'customersowed' AND entity = ".((int) $conf->entity).")");
		$result = $this->_init($sql, $options);
		if ($result <= 0) {
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();
		return 1;
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options
	 *        	Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		if ($this->preserveScheduledJob() < 0) { return -1; }
		return $this->_remove(array(), $options);
	}

	/** Retain the existing job's label and configuration before native init/remove.
	 * @return int 1 on success, -1 on database error
	 */
	private function preserveScheduledJob()
	{
		global $conf;
		$where = " WHERE module_name = 'customersowed' AND entity = ".(int) $conf->entity
			." AND classesname = '/customersowed/class/actions_customersowed.class.php' AND methodename = 'sendMails'";
		$result = $this->db->query('SELECT label FROM '.MAIN_DB_PREFIX.'cronjob'.$where);
		if (!$result) { $this->error = $this->db->lasterror(); return -1; }
		if (is_object($job = $this->db->fetch_object($result))) { $this->cronjobs[0]['label'] = $job->label; }
		$this->db->free($result);
		$test = $this->db->escape('isModEnabled("customersowed")');
		if (!$this->db->query('UPDATE '.MAIN_DB_PREFIX."cronjob SET test = '".$test."'".$where." AND test = '1'")) {
			$this->error = $this->db->lasterror(); return -1;
		}
		return 1;
	}
}
