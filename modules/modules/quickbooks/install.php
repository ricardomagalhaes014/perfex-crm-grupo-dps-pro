<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*Credits*/
/*----------------------------------------
Intuit & Quickbooks for v3-php-sdk
Thephpleague for the league/oauth2-client
------------------------------------------- */

//check if the module has been registered, if not run all migrations
$CI->db->update(db_prefix() . 'modules', ['installed_version' => '0.0.0'], ['module_name' => 'quickbooks']);
if ($CI->db->affected_rows() == 0) {
	$CI->db->insert(db_prefix() . 'modules', ['module_name' => 'quickbooks', 'installed_version' => '0.0.0']);
}

$CI->app_modules->upgrade_database('quickbooks');

/**
 * Modify core files
 * @return [type] [description]
 */
function qb_modFile($fname, $searchF, $replaceW) {
	$fhandle = fopen($fname, "r");
	$content = fread($fhandle, filesize($fname));
	if (strstr($content, $searchF)) {
		$content = str_replace($searchF, $replaceW, $content);
		$fhandle = fopen($fname, "w");
		fwrite($fhandle, $content);
	}
	fclose($fhandle);
	return true;
}

//Add items non importable field: qb_incomeAccountRef
$fname1 = LIBSPATH . "import/Import_items.php";
$searchF1 = "notImportableFields = ['id'];";
$replaceW1 = "notImportableFields = ['id', 'qb_incomeAccountRef'];";
qb_modFile($fname1, $searchF1, $replaceW1);

//Modify tax model
$fname2 = APPPATH . "models/Taxes_model.php";
$searchF2 = "public function get(\$id = '') {";
$replaceW2 = "public function get(\$id = '') {\n\$this->db->select('id, name, taxrate');";
qb_modFile($fname2, $searchF2, $replaceW2);
