<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */
/** Environment capabilities for the report (declarations, not instance test results). */
class CustomersOwedCompatibility
{
	/** @return array<string,array{available:bool,reason:string}> */
	public static function getFeatures()
	{
		$environment = version_compare(DOL_VERSION, '20.0.0', '>=') && version_compare(PHP_VERSION, '8.0.0', '>=');
		return array(
			'CustomersOwedReportCapability' => array('available' => $environment && isModEnabled('facture'), 'reason' => 'CustomersOwedReportRequirements'),
			'CustomersOwedSharedCapability' => array('available' => $environment && isModEnabled('multicompany'), 'reason' => 'CustomersOwedSharedRequirements'),
			'CustomersOwedCronCapability' => array('available' => $environment && isModEnabled('cron') && isModEnabled('facture'), 'reason' => 'CustomersOwedCronRequirements'),
		);
	}
}
