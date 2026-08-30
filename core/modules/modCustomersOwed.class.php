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
		$this->numero = 423100; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'customersowed';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		$this->family = "financial";

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
		$this->version = '1.4.3';

		// Key used in llx_const table to save module status enabled/disabled (where CUSTOMERSOWEDREPORT is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Name of image file used for this module.
		$this->picto = 'customersowed@customersowed';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'triggers' => 1, // Set this to 1 if module has its own trigger directory (core/triggers)
			'login' => 0, // Set this to 1 if module has its own login method file (core/login)
			'substitutions' => 1, // Set this to 1 if module has its own substitution function file (core/substitutions)
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
					'hookcontext1',
					'hookcontext2'),
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
		$this->phpmin = array(
			5,
			3); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(
			6,
			0); // Minimum version of Dolibarr required by module
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
				1),
			2 => array(
				'CUSTOMERSOWEDREPORT_MAIL_CONTAINS_URL',
				'chaine',
				'1',
				'The sending of mail by the periodic task can contain URL.',
				0,
				'current',
				1));
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
				'test' => '$conf->customersowed->enabled',
				'priority' => 50));

		// Permissions
		$this->rights = array(); // Permission array used by this module

		$r = 0;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("ReadCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->customersowed->level1->level2)

		$r ++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("CreateCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->customersowed->level1->level2)

		$r ++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("DeleteCustomersOwed"); // Permission label
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->customersowed->level1->level2)

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
				'enabled' => '$conf->customersowed->enabled && $conf->facture->enabled',
				'perms' => '$user->rights->customersowed->read',
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
				'enabled' => '$conf->customersowed->enabled && $conf->facture->enabled',
				'perms' => '$user->rights->customersowed->read',
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

		$result = $this->_load_tables('/customersowed/sql/');
		if ($result < 0)
			return - 1; // Do not activate module if not allowed errors found on module SQL queries (the _load_table run sql with run_sql with error allowed parameter to 'default')

		// Consts who depend on the language
		$langs->loadLangs(array(
			"admin",
			"customersowed@customersowed",
			"bills",
			"members"));

		// Set default model
		$MODELNAME = 'joliciel';
		$MODEL_TARGET_MODULE = 'customersowed';

		$res = dolibarr_set_const($this->db, "CUSTOMERSOWEDREPORT_ADDON_PDF", $MODELNAME, 'chaine', 0, '', $conf->entity);

		$sql = array(
			"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $MODELNAME . "' AND type = '" . $MODEL_TARGET_MODULE . "' AND entity = " . $conf->entity,
			"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, libelle, entity) VALUES('" . $MODELNAME . "', '" . $MODEL_TARGET_MODULE . "', '" . $MODELNAME . "', " . $conf->entity . " )");

		return $this->_init($sql, $options);
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
		global $conf;

		// Remove default model
		$MODEL_TARGET_MODULE = 'customersowed';
		$sql = array(
			"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE type = '" . $MODEL_TARGET_MODULE . "' AND entity = " . $conf->entity . ";");

		return $this->_remove($sql, $options);
	}
}
