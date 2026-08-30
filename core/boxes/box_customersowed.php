<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Laurent BOUQUET <dolibarr@joliciel.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file	modulebuilder/template/core/boxes/customersowedwidget1.php
 * \ingroup customersowed
 * \brief   Widget provided by CustomersOwed
 *
 * Put detailed description here.
 */

/** Includes */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class box_customersowed extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "customersowed";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "customersowed@customersowed";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('customersowed');

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $conf, $langs;
		$langs->load("boxes");
		$langs->load('customersowed@customersowed');

		parent::__construct($db, $param);

		$this->boxlabel = $langs->transnoentitiesnoconv("CustomersOwedBox");

		$this->param = $param;

		//$this->enabled = $conf->global->FEATURES_LEVEL > 0;		 // Condition when module is enabled or not
		$this->hidden = ! ($user->rights->customersowed->read);   // Condition when module is visible by user (test on permission)
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @return void
	 */
	public function loadBox()
	{
		global $langs, $conf, $db;		
		$langs->load("boxes");

		$customers_owed_report_path = dol_buildpath('/custom/customersowed/class/customersowed.class.php');

		if (file_exists($customers_owed_report_path)) {
			$customers_owed_report_url = DOL_URL_ROOT.'/custom/customersowed/view/customers_owed_report.php';
			include_once $customers_owed_report_path;			
		}
		else {
			$customers_owed_report_path = dol_buildpath('/customersowed/class/customersowed.class.php');
			if (file_exists($customers_owed_report_path)) {
				$customers_owed_report_url = DOL_URL_ROOT.'/customersowed/view/customers_owed_report.php';
				include_once $customers_owed_report_path;
			}
		}
		
		$customersOwedDate = dol_now(); 
		$customersOwedReport = new CustomersOwed($db, $customersOwedDate, true);
		$customersOwedReport->fetch_lines();
		$totalCustomersOwed = $customersOwedReport->getTotalCustomersOwed();
		
		// Populate the head at runtime
		$this->info_box_head = array(
			// Title text
			'text' => $langs->trans("ModuleCustomersOwedName"),
			// Add a link
			'sublink' => $customers_owed_report_url,
			// Sublink icon placed after the text
			'subpicto' => 'object_customersowed@customersowed',
			// Sublink icon HTML alt text
			'subtext' => $langs->trans("ModuleCustomersOwedName"),
			// Sublink HTML target
			'target' => '',
			// HTML class attached to the picto and link
			'subclass' => 'center',
			// Limit and truncate with "…" the displayed text lenght, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => false,			
		);

		// Populate the contents at runtime
		//$text = $langs->trans("RemainderToTakeAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y"), price($totalCustomersOwed)); //CustomersOwedBoxDescription					
		$this->info_box_contents[0][0] = array( 
			'tr' => '',
			'td' => 'class="liste_total left"',
			'text' => '<a href="'.$customers_owed_report_url.'">'.$langs->trans("BillsCustomersUnpaidAtDateOf", dol_print_date($customersOwedDate, "%d/%m/%Y")).'</a>',
			'asis' => 1,
		);
		$this->info_box_contents[0][1] = array(
			'td' => 'class="liste_total nowraponall right amount"',
			'text' => price($totalCustomersOwed, 0, $langs, 1, -1, -1, $conf->currency),
		);


	}

	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @return void
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
