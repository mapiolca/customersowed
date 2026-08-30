<?php

/*
 * Copyright (C) 2019 Laurent Bouquet <laurent.bouquet@joliciel.fr>
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
 * \file customersowed/class/html.formcustomersowed.class.php
 * \brief Fichier de la classe des fonctions predefinie de composants html customersowed
 */

/**
 * Class to manage building of HTML components
 */
class FormCustomersowed extends Form
{

	/**
	 *
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 *
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 *        	Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Display On Off selector
	 *
	 * @param string $htmlname
	 *        	Html control name
	 * @param integer $selected
	 *        	selected value
	 * @param integer $readonly
	 *        	Select is read only or not
	 * @return string HTML select field
	 */
	function select_OnOff($htmlname, $selected = 0, $readonly = 0)
	{
		global $langs;

		$langs->load('customersowed@customersowed');

		if ($readonly < 1) {

			$out = '<SELECT class="flat" name="' . $htmlname . '" id="' . $htmlname . '" />';

			if (($selected == $langs->trans("Yes")) || intval($selected) == 1) {
				$selected_attr = ' selected ';
			} else {
				$selected_attr = '';
			}
			$out .= '<OPTION value="1" ' . $selected_attr . '>' . $langs->trans("Oui") . '</OPTION>';

			if (($selected == $langs->trans("No")) || intval($selected) == 0) {
				$selected_attr = ' selected ';
			} else {
				$selected_attr = '';
			}
			$out .= '<OPTION value="0" ' . $selected_attr . '>' . $langs->trans("Non") . '</OPTION>';

			$out .= '</SELECT>';
		} else {
			if ($selected > 0) {
				$out = $langs->trans("Yes");
			} else {
				$out = $langs->trans("No");
			}
		}

		return $out;
	}

	/**
	 * Display Priority selector
	 * from 0 to 5
	 *
	 * @param string $htmlname
	 *        	Html control name
	 * @param integer $selected
	 *        	selected value
	 * @param integer $readonly
	 *        	Select is read only or not
	 * @return string HTML select field
	 */
	function select_Priority($htmlname, $selected = 0, $readonly = 0)
	{
		global $langs;

		$langs->load('customersowed@customersowed');

		if ($readonly < 1) {

			$out = '<SELECT class="flat" name="' . $htmlname . '" id="' . $htmlname . '" />';

			$priorities = array(
				0,
				1,
				2,
				3,
				4,
				5);
			foreach ($priorities as $priority) {
				if ($selected == $priority) {
					$selected_attr = ' selected ';
				} else {
					$selected_attr = '';
				}
				$out .= '<OPTION value="' . $priority . '" ' . $selected_attr . '>' . $priority . '</OPTION>';
			}

			$out .= '</SELECT>';
		} else {
			$out = $selected;
		}

		return $out;
	}
}
