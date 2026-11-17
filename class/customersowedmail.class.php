<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/** Presentation of the report email; delivery remains in Dolibarr CMailFile. */
class CustomersOwedMail
{
	/**
	 * Use the new layout only for the unchanged legacy default. Never persist it
	 * over the entity's configured template.
	 * @param string $configured Configured body
	 * @param string $legacy Translated legacy body
	 * @return string
	 */
	public static function selectTemplate($configured, $legacy)
	{
		$normalize = static function ($body) {
			return trim((string) preg_replace('/\s+/u', ' ', str_replace(array('<br/>', '&lt;br/>'), "\n", $body)));
		};
		if ($normalize($configured) !== $normalize($legacy)) {
			return $configured;
		}
		$template = file_get_contents(__DIR__.'/../templates/report-mail.html');
		return is_string($template) && $template !== '' ? $template : $legacy;
	}

	/**
	 * @param CustomersOwed $report Authorized report, with payments/credits aggregated
	 * @param Translate $langs Output language
	 * @param DoliDB $db Database handler for native date conversion
	 * @param bool $withLinks Include authenticated ERP links for internal reports
	 * @return string
	 */
	public static function buildInvoiceTable($report, $langs, $db, $withLinks)
	{
		$cell = 'padding:12px 8px;border-bottom:1px solid #e4e9ef;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;';
		$html = '<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;color:#253449;text-align:left"><thead><tr style="background-color:#eef4fb">';
		foreach (array('InvoiceRef', 'ThirdParty', 'DateDue', 'AmountTTC', 'Received', 'CustomersOwedMailBalance') as $index => $key) {
			$html .= '<th scope="col" style="'.$cell.'text-align:'.($index >= 3 ? 'right' : 'left').'">'.dol_escape_htmltag($langs->transnoentities($key)).'</th>';
		}
		$html .= '</tr></thead><tbody>';
		$total = $received = $balance = 0;
		foreach ($report->lines as $index => $line) {
			$ref = dol_escape_htmltag($line->facnumber);
			$name = dol_escape_htmltag($line->name);
			if ($withLinks) {
				$root = rtrim(DOL_MAIN_URL_ROOT, '/');
				$ref = '<a style="color:#245680;text-decoration:underline" href="'.dol_escape_htmltag($root.'/compta/facture/card.php?id='.(int) $line->id).'">'.$ref.'</a>';
				$name = '<a style="color:#245680;text-decoration:underline" href="'.dol_escape_htmltag($root.'/societe/card.php?socid='.(int) $line->fk_soc).'">'.$name.'</a>';
			}
			$due = price2num($line->total_ttc - $line->am, 'MT');
			$values = array($ref, $name, dol_escape_htmltag(dol_print_date($db->jdate($line->date_lim_reglement), 'day', 0, $langs)),
				price($line->total_ttc, 0, $langs), price($line->am, 0, $langs), price($due, 0, $langs));
			$html .= '<tr style="background-color:'.($index % 2 ? '#f5f7fa' : '#ffffff').'">';
			foreach ($values as $column => $value) {
				$html .= '<td style="'.$cell.($column >= 3 ? 'text-align:right;white-space:nowrap;' : 'overflow-wrap:anywhere;').'">'.$value.'</td>';
			}
			$html .= '</tr>';
			$total = price2num($total + $line->total_ttc, 'MT');
			$received = price2num($received + $line->am, 'MT');
			$balance = price2num($balance + $due, 'MT');
		}
		if (!$report->lines) {
			$html .= '<tr><td colspan="6" style="'.$cell.'">'.dol_escape_htmltag($langs->transnoentities('NoRecordFound')).'</td></tr>';
		}
		$html .= '</tbody><tfoot><tr style="background-color:#eef4fb;font-weight:bold"><td colspan="3" style="'.$cell.'">'.dol_escape_htmltag($langs->transnoentities('Total')).'</td>';
		foreach (array($total, $received, $balance) as $amount) {
			$html .= '<td style="'.$cell.'text-align:right;white-space:nowrap">'.price($amount, 0, $langs).'</td>';
		}
		return $html.'</tr></tfoot></table>';
	}
}
