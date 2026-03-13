<?php
defined('BASEPATH') or exit('No direct script access allowed');

function real_customers_portal_footer()
{
	
	/**
	 * @deprecated 2.3.0
	 * Moved from themes/[THEME]/views/scripts.php
	 * Use app_sale_agent_footer hook instead
	 */
	do_action_deprecated('broker_portal_after_js_scripts_load', [], '2.3.0', 'real_customers_portal_footer');

	hooks()->do_action('real_customers_portal_footer');
}

/**
 * real_client_init_tail
 * @return [type] 
 */
function real_client_init_tail()
{
	$CI = &get_instance();
	$CI->load->view('clients/scripts');
}

/**
 * rel check staff type
 * @return [type] 
 */
function rel_check_staff_type($staff_id = false)
{
	// staff_type: company, staff
	$staff_type = 'staff';
	$company_id = 0;
	$is_company_admin = 1;

	$CI = & get_instance();
	if(!$staff_id){
		$staff_id = get_staff_user_id();
	}

	$staff = $CI->db->select('*')
	->where('staffid', $staff_id)
	->from(db_prefix() . 'staff')
	->get()
	->row();
	if ($staff) {
		$staff_type = $staff->staff_type;
		$company_id = $staff->company_id;
	}
	if($company_id  > 0){
		$is_company_admin = 0;
	}


	$data = [];
	$data['staff_type'] = $staff_type;
	$data['company_id'] = $company_id;
	$data['is_company_admin'] = $is_company_admin;

	return $data;
}

/**
 * rel handle company attachments pdf
 * @param  [type] $contractid 
 * @param  string $index name 
 * @return [type]             
 */
function rel_handle_company_attachments_pdf($id, $index_name = 'attachments')
{
	$uploaded_files = [];
	$path = COMPANY_PDF_UPLOAD . $id . '/';
	$CI             = &get_instance();

	if (isset($_FILES[$index_name]['name'])
		&& ($_FILES[$index_name]['name'] != '' || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)) {
		if (!is_array($_FILES[$index_name]['name'])) {
			$_FILES[$index_name]['name']     = [$_FILES[$index_name]['name']];
			$_FILES[$index_name]['type']     = [$_FILES[$index_name]['type']];
			$_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
			$_FILES[$index_name]['error']    = [$_FILES[$index_name]['error']];
			$_FILES[$index_name]['size']     = [$_FILES[$index_name]['size']];
		}

		_file_attachments_index_fix($index_name);
		for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
					// Get the temp file path
			$tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];

					// Make sure we have a filepath
			if (!empty($tmpFilePath) && $tmpFilePath != '') {
				if (_perfex_upload_error($_FILES[$index_name]['error'][$i])
					|| !_upload_extension_allowed($_FILES[$index_name]['name'][$i])) {
					continue;
			}

			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES[$index_name]['name'][$i]);
			$newFilePath = $path . $filename;

						// Upload the file into the temp dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {

				$CI           = & get_instance();
				$attachment   = [];
				$attachment[] = [
					'file_name' => $filename,
					'filetype'  => $_FILES[$index_name]['type'][$i],
				];
				$CI->misc_model->add_attachment_to_database($id, 'rel_com_freelance', $attachment);

			}
		}
	}
}

return true;
}


/**
 * rel get construction company name
 * @param  [type]  $company_id 
 * @param  boolean $prevent_empty_company   
 * @return [type]                           
 */
function rel_get_construction_company_name($company_id, $prevent_empty_company = false)
{
	$_company_id='';
	if ($company_id !== '') {
		$_company_id = $company_id;
	}
	$CI = & get_instance();

	$company = $CI->db->select('name')
	->where('id', $_company_id)
	->from(db_prefix() . 'real_companies')
	->get()
	->row();
	if ($company) {
		return $company->name;
	}

	return '';
}

/**
 * rel get construction company id
 * @param  [type] $staff_id 
 * @return [type]           
 */
function rel_check_staff_in_company($staff_id = false)
{
	$CI = & get_instance();
	if(!$staff_id){
		$staff_id = get_staff_user_id();
	}

	$staff = $CI->db->select('company_id')
	->where('staffid', $staff_id)
	->from(db_prefix() . 'staff')
	->get()
	->row();
	if ($staff) {
		if($staff->company_id != null && $staff->company_id > 0){
			return $staff->company_id;
		}
		return false;
	}
	return false;
}


/**
 * rel get construction company hash
 * @param  [type] $company_id 
 * @return [type]                          
 */
function rel_get_construction_company_hash($company_id)
{
	$hash = '';
	$CI = & get_instance();

	$company = $CI->db->select('hash')
	->where('id', $company_id)
	->from(db_prefix() . 'real_companies')
	->get()
	->row();
	if ($company) {
		return $company->hash;
	}

	return '';
}

function rel_get_staff_code()
{
	$staff_code = '';
	$CI = & get_instance();

	$staff = $CI->db->select('*')
	->where('staffid', get_staff_user_id())
	->from(db_prefix() . 'staff')
	->get()
	->row();
	if ($staff) {
		$staff_code = get_staff_full_name().' '. $staff->staff_identifi;
	}

	return $staff_code;
}


function real_remove_underscore($text)
{
	if(!is_null($text) && $text != ''){
		$text = str_replace("_", " ", $text);
		$text = str_replace(",", ", ", $text);
	}else{
		$text = '---';
	}
	return $text;
}

if (!function_exists('new_html_entity_decode')) {
    
    function new_html_entity_decode($str){
        return html_entity_decode($str ?? '');
    }
}

/**
 * rel listing type
 * @return [type] 
 */
function real_listing_type()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Housing',
			'label' => _l('rel_Housing'),
		],
		[
			'name' => 'Business',
			'label' => _l('rel_Business'),
		],
		[
			'name' => 'Agriculture',
			'label' => _l('rel_Agriculture'),
		],
		[
			'name' => 'Government',
			'label' => _l('rel_Government'),
		],
	];

	return $array_data;
}

/**
 * rel transaction type
 * @return [type] 
 */
function real_transaction_type()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Sale',
			'label' => _l('rel_Sale'),
		],
		[
			'name' => 'Rent',
			'label' => _l('rel_Rent'),
		],
		
	];

	return $array_data;
}

/**
 * rel property style
 * @return [type] 
 */
function real_property_style()
{
	$array_data = [];
	$array_data = [

		
		[
			'name' => 'Administrative',
			'label' => _l('rel_Administrative'),
			'color' => '#cbebe2',
			'class' => 'color-cbebe2',

		],
		[
			'name' => 'Land',
			'label' => _l('rel_Land'),
			'color' => '#e9f2ef',
			'class' => 'color-e9f2ef',

		],
		[
			'name' => 'Duplexes',
			'label' => _l('rel_Duplexes'),
			'color' => '#eaf6ff',
			'class' => 'color-eaf6ff',

		],
		[
			'name' => 'Chalet',
			'label' => _l('rel_Chalet'),
			'color' => '#d4effe',
			'class' => 'color-d4effe',

		],
		[
			'name' => 'Condominium',
			'label' => _l('rel_Condominium'),
			'color' => '#9bd7ff',
			'class' => 'color-9bd7ff',

		],
		[
			'name' => 'Commercial',
			'label' => _l('rel_Commercial'),
			'color' => '#b6e1ff',
			'class' => 'color-b6e1ff',

		],
		[
			'name' => 'Garage',
			'label' => _l('rel_Garage'),
			'color' => '#cfd4ef',
			'class' => 'color-cfd4ef',

		],
		[
			'name' => 'Office',
			'label' => _l('rel_Office'),
			'color' => '#9edbdd',
			'class' => 'color-9edbdd',

		],
		[
			'name' => 'Twin_House',
			'label' => _l('rel_Twin_House'),
			'color' => '#c0e8eb',
			'class' => 'color-c0e8eb',

		],
		[
			'name' => 'Apartment',
			'label' => _l('rel_Apartment'),
			'color' => '#f1d7ed',
			'class' => 'color-f1d7ed',

		],
		[
			'name' => 'Restaurant',
			'label' => _l('rel_Restaurant'),
			'color' => '#fde9f0',
			'class' => 'color-fde9f0',

		],
		[
			'name' => 'Penthouse',
			'label' => _l('rel_Penthouse'),
			'color' => '#feacc4',
			'class' => 'color-feacc4',

		],
		[
			'name' => 'Townhouse',
			'label' => _l('rel_Townhouse'),
			'color' => '#ffdae3',
			'class' => 'color-ffdae3',

		],
		[
			'name' => 'Villa',
			'label' => _l('rel_Villa'),
			'color' => '#fed1e5',
			'class' => 'color-fed1e5',

		],
		[
			'name' => 'Detached',
			'label' => _l('rel_Detached'),
			'color' => '#ffcfab',
			'class' => 'color-ffcfab',

		],
		[
			'name' => 'Warehouse',
			'label' => _l('rel_Warehouse'),
			'color' => '#ffded7',
			'class' => 'color-ffded7',

		],
		[
			'name' => 'Bungalow',
			'label' => _l('rel_Bungalow'),
			'color' => '#ffdadd',
			'class' => 'color-ffdadd',
		],
		[
			'name' => 'Whole_Building',
			'label' => _l('rel_Whole_Building'),
			'color' => '#fef0bf',
			'class' => 'color-fef0bf',
		],
		[
			'name' => 'Hotel',
			'label' => _l('rel_Hotel'),
			'color' => '#ffeac5',
			'class' => 'color-ffeac5',
		],
		
		
	];

	return $array_data;
}

/**
 * rel property condition
 * @return [type] 
 */
function rel_property_condition()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Completed',
			'label' => _l('rel_Completed'),
		],
		[
			'name' => 'Fixer',
			'label' => _l('rel_Fixer'),
		],
		[
			'name' => 'Pre_Construction',
			'label' => _l('rel_Pre_Construction'),
		],
		[
			'name' => 'Under_Construction',
			'label' => _l('rel_Under_Construction'),
		],
		[
			'name' => 'Under_Renovation',
			'label' => _l('rel_Under_Renovation'),
		],
		
	];

	return $array_data;
}

/**
 * rel street dir pre
 * @return [type] 
 */
function rel_street_dir_pre()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Central',
			'label' => _l('rel_Central'),
		],
		[
			'name' => 'East',
			'label' => _l('rel_East'),
		],
		[
			'name' => 'Extension',
			'label' => _l('rel_Extension'),
		],
		[
			'name' => 'Lower',
			'label' => _l('rel_Lower'),
		],
		[
			'name' => 'North',
			'label' => _l('rel_North'),
		],
		[
			'name' => 'North_East',
			'label' => _l('rel_North_East'),
		],
		[
			'name' => 'North_West',
			'label' => _l('rel_North_West'),
		],
		[
			'name' => 'South',
			'label' => _l('rel_South'),
		],
		[
			'name' => 'South_East',
			'label' => _l('rel_South_East'),
		],
		[
			'name' => 'South_West',
			'label' => _l('rel_South_West'),
		],
		[
			'name' => 'Upper',
			'label' => _l('rel_Upper'),
		],
		[
			'name' => 'West',
			'label' => _l('rel_West'),
		],
		
	];

	return $array_data;
}

/**
 * rel street dir pre
 * @return [type] 
 */
function rel_street_type()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Access',
			'label' => _l('rel_Access'),
		],
		[
			'name' => 'Alley',
			'label' => _l('rel_Alley'),
		],
		[
			'name' => 'Alleyway',
			'label' => _l('rel_Alleyway'),
		],
		[
			'name' => 'Amble',
			'label' => _l('rel_Amble'),
		],
		[
			'name' => 'Anchorage',
			'label' => _l('rel_Anchorage'),
		],
		[
			'name' => 'Approach',
			'label' => _l('rel_Approach'),
		],
		[
			'name' => 'Arcade',
			'label' => _l('rel_Arcade'),
		],
		[
			'name' => 'Artery',
			'label' => _l('rel_Artery'),
		],
		[
			'name' => 'Avenue',
			'label' => _l('rel_Avenue'),
		],
		[
			'name' => 'Barracks',
			'label' => _l('rel_Barracks'),
		],
		[
			'name' => 'Base',
			'label' => _l('rel_Base'),
		],
		[
			'name' => 'Basin',
			'label' => _l('rel_Basin'),
		],
		[
			'name' => 'Beach',
			'label' => _l('rel_Beach'),
		],
		[
			'name' => 'Bend',
			'label' => _l('rel_Bend'),
		],
		[
			'name' => 'Block',
			'label' => _l('rel_Block'),
		],
		[
			'name' => 'Boulevard',
			'label' => _l('rel_Boulevard'),
		],
		[
			'name' => 'Brace',
			'label' => _l('rel_Brace'),
		],
		[
			'name' => 'Brae',
			'label' => _l('rel_Brae'),
		],
		[
			'name' => 'Break',
			'label' => _l('rel_Break'),
		],
		[
			'name' => 'Bridge',
			'label' => _l('rel_Bridge'),
		],
		[
			'name' => 'Broadway',
			'label' => _l('rel_Broadway'),
		],
		[
			'name' => 'Brow',
			'label' => _l('rel_Brow'),
		],
		[
			'name' => 'Bypass',
			'label' => _l('rel_Bypass'),
		],
		[
			'name' => 'Byway',
			'label' => _l('rel_Byway'),
		],
		[
			'name' => 'Camp',
			'label' => _l('rel_Camp'),
		],
		[
			'name' => 'Caravan_Park',
			'label' => _l('rel_Caravan_Park'),
		],
		[
			'name' => 'Causway',
			'label' => _l('rel_Causway'),
		],
		[
			'name' => 'Centre',
			'label' => _l('rel_Centre'),
		],
		[
			'name' => 'Centreway',
			'label' => _l('rel_Centreway'),
		],
		[
			'name' => 'Chase',
			'label' => _l('rel_Chase'),
		],
		[
			'name' => 'Circle',
			'label' => _l('rel_Circle'),
		],
		[
			'name' => 'Circlet',
			'label' => _l('rel_Circlet'),
		],
		[
			'name' => 'Circuit',
			'label' => _l('rel_Circuit'),
		],
		[
			'name' => 'Circus',
			'label' => _l('rel_Circus'),
		],
		[
			'name' => 'Close',
			'label' => _l('rel_Close'),
		],
		[
			'name' => 'Colonnade',
			'label' => _l('rel_Colonnade'),
		],
		[
			'name' => 'Common',
			'label' => _l('rel_Common'),
		],
		[
			'name' => 'Community',
			'label' => _l('rel_Community'),
		],
		[
			'name' => 'Concourse',
			'label' => _l('rel_Concourse'),
		],
		[
			'name' => 'Copse',
			'label' => _l('rel_Copse'),
		],
		[
			'name' => 'Corner',
			'label' => _l('rel_Corner'),
		],
		[
			'name' => 'Corso',
			'label' => _l('rel_Corso'),
		],
		[
			'name' => 'Court',
			'label' => _l('rel_Court'),
		],
		[
			'name' => 'Courtyard',
			'label' => _l('rel_Courtyard'),
		],
		[
			'name' => 'Cove',
			'label' => _l('rel_Cove'),
		],
		[
			'name' => 'Crescent',
			'label' => _l('rel_Crescent'),
		],
		[
			'name' => 'Crest',
			'label' => _l('rel_Crest'),
		],
		[
			'name' => 'Cross',
			'label' => _l('rel_Cross'),
		],
		[
			'name' => 'Crossing',
			'label' => _l('rel_Crossing'),
		],
		[
			'name' => 'Crossroad',
			'label' => _l('rel_Crossroad'),
		],
		[
			'name' => 'Crossway',
			'label' => _l('rel_Crossway'),
		],
		[
			'name' => 'Cruiseway',
			'label' => _l('rel_Cruiseway'),
		],
		[
			'name' => 'Cul_De_Sac',
			'label' => _l('rel_Cul_De_Sac'),
		],
		[
			'name' => 'Cutting',
			'label' => _l('rel_Cutting'),
		],
		[
			'name' => 'Dale',
			'label' => _l('rel_Dale'),
		],
		[
			'name' => 'Dell',
			'label' => _l('rel_Dell'),
		],
		[
			'name' => 'Deviation',
			'label' => _l('rel_Deviation'),
		],
		[
			'name' => 'Dip',
			'label' => _l('rel_Dip'),
		],
		[
			'name' => 'Distributor',
			'label' => _l('rel_Distributor'),
		],
		[
			'name' => 'Drive',
			'label' => _l('rel_Drive'),
		],
		[
			'name' => 'Driveway',
			'label' => _l('rel_Driveway'),
		],
		[
			'name' => 'Edge',
			'label' => _l('rel_Edge'),
		],
		[
			'name' => 'Elbow',
			'label' => _l('rel_Elbow'),
		],
		[
			'name' => 'End',
			'label' => _l('rel_End'),
		],
		[
			'name' => 'Entrance',
			'label' => _l('rel_Entrance'),
		],
		[
			'name' => 'Esplanade',
			'label' => _l('rel_Esplanade'),
		],
		[
			'name' => 'Estate',
			'label' => _l('rel_Estate'),
		],
		[
			'name' => 'Expressway',
			'label' => _l('rel_Expressway'),
		],
		[
			'name' => 'Extension',
			'label' => _l('rel_Extension'),
		],
		[
			'name' => 'Fairway',
			'label' => _l('rel_Fairway'),
		],
		[
			'name' => 'Firetrack',
			'label' => _l('rel_Firetrack'),
		],
		[
			'name' => 'Firetrail',
			'label' => _l('rel_Firetrail'),
		],
		[
			'name' => 'Flat',
			'label' => _l('rel_Flat'),
		],
		[
			'name' => 'Follow',
			'label' => _l('rel_Follow'),
		],
		[
			'name' => 'Footway',
			'label' => _l('rel_Footway'),
		],
		[
			'name' => 'Foreshore',
			'label' => _l('rel_Foreshore'),
		],
		[
			'name' => 'Formation',
			'label' => _l('rel_Formation'),
		],
		[
			'name' => 'Freeway',
			'label' => _l('rel_Freeway'),
		],
		[
			'name' => 'Front',
			'label' => _l('rel_Front'),
		],
		[
			'name' => 'Frontage',
			'label' => _l('rel_Frontage'),
		],
		[
			'name' => 'Gap',
			'label' => _l('rel_Gap'),
		],
		[
			'name' => 'Garden',
			'label' => _l('rel_Garden'),
		],
		[
			'name' => 'Gardens',
			'label' => _l('rel_Gardens'),
		],
		[
			'name' => 'Gate',
			'label' => _l('rel_Gate'),
		],
		[
			'name' => 'Gates',
			'label' => _l('rel_Gates'),
		],
		[
			'name' => 'Glade',
			'label' => _l('rel_Glade'),
		],
		[
			'name' => 'Glen',
			'label' => _l('rel_Glen'),
		],
		[
			'name' => 'Grange',
			'label' => _l('rel_Grange'),
		],
		[
			'name' => 'Green',
			'label' => _l('rel_Green'),
		],
		[
			'name' => 'Ground',
			'label' => _l('rel_Ground'),
		],
		[
			'name' => 'Grove',
			'label' => _l('rel_Grove'),
		],
		[
			'name' => 'Gully',
			'label' => _l('rel_Gully'),
		],
		[
			'name' => 'Heights',
			'label' => _l('rel_Heights'),
		],
		[
			'name' => 'Highroad',
			'label' => _l('rel_Highroad'),
		],
		[
			'name' => 'Highway',
			'label' => _l('rel_Highway'),
		],
		[
			'name' => 'Hill',
			'label' => _l('rel_Hill'),
		],
		[
			'name' => 'Interchange',
			'label' => _l('rel_Interchange'),
		],
		[
			'name' => 'Intersection',
			'label' => _l('rel_Intersection'),
		],
		[
			'name' => 'Island',
			'label' => _l('rel_Island'),
		],
		[
			'name' => 'Junction',
			'label' => _l('rel_Junction'),
		],
		[
			'name' => 'Key',
			'label' => _l('rel_Key'),
		],
		[
			'name' => 'Keys',
			'label' => _l('rel_Keys'),
		],
		[
			'name' => 'Landing',
			'label' => _l('rel_Landing'),
		],
		[
			'name' => 'Lane',
			'label' => _l('rel_Lane'),
		],
		[
			'name' => 'Laneway',
			'label' => _l('rel_Laneway'),
		],
		[
			'name' => 'Lees',
			'label' => _l('rel_Lees'),
		],
		[
			'name' => 'Line',
			'label' => _l('rel_Line'),
		],
		[
			'name' => 'Link',
			'label' => _l('rel_Link'),
		],
		[
			'name' => 'Little',
			'label' => _l('rel_Little'),
		],
		[
			'name' => 'Lookout',
			'label' => _l('rel_Lookout'),
		],
		[
			'name' => 'Loop',
			'label' => _l('rel_Loop'),
		],
		[
			'name' => 'Lower',
			'label' => _l('rel_Lower'),
		],
		[
			'name' => 'Mall',
			'label' => _l('rel_Mall'),
		],
		[
			'name' => 'Meander',
			'label' => _l('rel_Meander'),
		],
		[
			'name' => 'Mew',
			'label' => _l('rel_Mew'),
		],
		[
			'name' => 'Mews',
			'label' => _l('rel_Mews'),
		],
		[
			'name' => 'Motorway',
			'label' => _l('rel_Motorway'),
		],
		[
			'name' => 'Mount',
			'label' => _l('rel_Mount'),
		],
		[
			'name' => 'Nook',
			'label' => _l('rel_Nook'),
		],
		[
			'name' => 'Outlook',
			'label' => _l('rel_Outlook'),
		],
		[
			'name' => 'Parade',
			'label' => _l('rel_Parade'),
		],
		[
			'name' => 'Park',
			'label' => _l('rel_Park'),
		],
		[
			'name' => 'Parkland',
			'label' => _l('rel_Parkland'),
		],
		[
			'name' => 'Parkway',
			'label' => _l('rel_Parkway'),
		],
		[
			'name' => 'Part',
			'label' => _l('rel_Part'),
		],
		[
			'name' => 'Pass',
			'label' => _l('rel_Pass'),
		],
		[
			'name' => 'Path',
			'label' => _l('rel_Path'),
		],
		[
			'name' => 'Pathway',
			'label' => _l('rel_Pathway'),
		],
		[
			'name' => 'Piazza',
			'label' => _l('rel_Piazza'),
		],
		[
			'name' => 'Place',
			'label' => _l('rel_Place'),
		],
		[
			'name' => 'Plateau',
			'label' => _l('rel_Plateau'),
		],
		[
			'name' => 'Plaza',
			'label' => _l('rel_Plaza'),
		],
		[
			'name' => 'Pocket',
			'label' => _l('rel_Pocket'),
		],
		[
			'name' => 'Point',
			'label' => _l('rel_Point'),
		],
		[
			'name' => 'Port',
			'label' => _l('rel_Port'),
		],
		[
			'name' => 'Promenade',
			'label' => _l('rel_Promenade'),
		],
		[
			'name' => 'Quad',
			'label' => _l('rel_Quad'),
		],
		[
			'name' => 'Quadrangle',
			'label' => _l('rel_Quadrangle'),
		],
		[
			'name' => 'Quadrant',
			'label' => _l('rel_Quadrant'),
		],
		[
			'name' => 'Quay',
			'label' => _l('rel_Quay'),
		],
		[
			'name' => 'Quays',
			'label' => _l('rel_Quays'),
		],
		[
			'name' => 'Ramble',
			'label' => _l('rel_Ramble'),
		],
		[
			'name' => 'Ramp',
			'label' => _l('rel_Ramp'),
		],
		[
			'name' => 'Range',
			'label' => _l('rel_Range'),
		],
		[
			'name' => 'Raodhouse',
			'label' => _l('rel_Raodhouse'),
		],
		[
			'name' => 'Reach',
			'label' => _l('rel_Reach'),
		],
		[
			'name' => 'Reserve',
			'label' => _l('rel_Reserve'),
		],
		[
			'name' => 'Rest',
			'label' => _l('rel_Rest'),
		],
		[
			'name' => 'Retreat',
			'label' => _l('rel_Retreat'),
		],
		[
			'name' => 'Ride',
			'label' => _l('rel_Ride'),
		],
		[
			'name' => 'Ridge',
			'label' => _l('rel_Ridge'),
		],
		[
			'name' => 'Ridgeway',
			'label' => _l('rel_Ridgeway'),
		],
		[
			'name' => 'Right_Of_Way',
			'label' => _l('rel_Right_Of_Way'),
		],
		[
			'name' => 'Ring',
			'label' => _l('rel_Ring'),
		],
		[
			'name' => 'Rise',
			'label' => _l('rel_Rise'),
		],
		[
			'name' => 'River',
			'label' => _l('rel_River'),
		],
		[
			'name' => 'Riverway',
			'label' => _l('rel_Riverway'),
		],
		[
			'name' => 'Riviera',
			'label' => _l('rel_Riviera'),
		],
		[
			'name' => 'Road',
			'label' => _l('rel_Road'),
		],
		[
			'name' => 'Roads',
			'label' => _l('rel_Roads'),
		],
		[
			'name' => 'Roadside',
			'label' => _l('rel_Roadside'),
		],
		[
			'name' => 'Roadway',
			'label' => _l('rel_Roadway'),
		],
		[
			'name' => 'Ronde',
			'label' => _l('rel_Ronde'),
		],
		[
			'name' => 'Rosebowl',
			'label' => _l('rel_Rosebowl'),
		],
		[
			'name' => 'Rotary',
			'label' => _l('rel_Rotary'),
		],
		[
			'name' => 'Round',
			'label' => _l('rel_Round'),
		],
		[
			'name' => 'Route',
			'label' => _l('rel_Route'),
		],
		[
			'name' => 'Row',
			'label' => _l('rel_Row'),
		],
		[
			'name' => 'Rowe',
			'label' => _l('rel_Rowe'),
		],
		[
			'name' => 'Rue',
			'label' => _l('rel_Rue'),
		],
		[
			'name' => 'Run',
			'label' => _l('rel_Run'),
		],
		[
			'name' => 'Service_Way',
			'label' => _l('rel_Service_Way'),
		],
		[
			'name' => 'Siding',
			'label' => _l('rel_Siding'),
		],
		[
			'name' => 'Slope',
			'label' => _l('rel_Slope'),
		],
		[
			'name' => 'Sound',
			'label' => _l('rel_Sound'),
		],
		[
			'name' => 'Spur',
			'label' => _l('rel_Spur'),
		],
		[
			'name' => 'Square',
			'label' => _l('rel_Square'),
		],
		[
			'name' => 'Stairs',
			'label' => _l('rel_Stairs'),
		],
		[
			'name' => 'State_Highway',
			'label' => _l('rel_State_Highway'),
		],
		[
			'name' => 'Station',
			'label' => _l('rel_Station'),
		],
		[
			'name' => 'Steps',
			'label' => _l('rel_Steps'),
		],
		[
			'name' => 'Strand',
			'label' => _l('rel_Strand'),
		],
		[
			'name' => 'Street',
			'label' => _l('rel_Street'),
		],
		[
			'name' => 'Strip',
			'label' => _l('rel_Strip'),
		],
		[
			'name' => 'Subway',
			'label' => _l('rel_Subway'),
		],
		[
			'name' => 'Tarn',
			'label' => _l('rel_Tarn'),
		],
		[
			'name' => 'Terrace',
			'label' => _l('rel_Terrace'),
		],
		[
			'name' => 'Thoroughfare',
			'label' => _l('rel_Thoroughfare'),
		],
		[
			'name' => 'Tollway',
			'label' => _l('rel_Tollway'),
		],
		[
			'name' => 'Top',
			'label' => _l('rel_Top'),
		],
		[
			'name' => 'Tor',
			'label' => _l('rel_Tor'),
		],
		[
			'name' => 'Towers',
			'label' => _l('rel_Towers'),
		],
		[
			'name' => 'Track',
			'label' => _l('rel_Track'),
		],
		[
			'name' => 'Trail',
			'label' => _l('rel_Trail'),
		],
		[
			'name' => 'Triangle',
			'label' => _l('rel_Triangle'),
		],
		[
			'name' => 'Trunkway',
			'label' => _l('rel_Trunkway'),
		],
		[
			'name' => 'Turn',
			'label' => _l('rel_Turn'),
		],
		[
			'name' => 'Underpass',
			'label' => _l('rel_Underpass'),
		],
		[
			'name' => 'Vale',
			'label' => _l('rel_Vale'),
		],
		[
			'name' => 'Viaduct',
			'label' => _l('rel_Viaduct'),
		],
		[
			'name' => 'View',
			'label' => _l('rel_View'),
		],
		[
			'name' => 'Villas',
			'label' => _l('rel_Villas'),
		],
		[
			'name' => 'Vista',
			'label' => _l('rel_Vista'),
		],
		[
			'name' => 'Wade',
			'label' => _l('rel_Wade'),
		],
		[
			'name' => 'Walk',
			'label' => _l('rel_Walk'),
		],
		[
			'name' => 'Walkway',
			'label' => _l('rel_Walkway'),
		],
		[
			'name' => 'Way',
			'label' => _l('rel_Way'),
		],
		[
			'name' => 'Wharf',
			'label' => _l('rel_Wharf'),
		],
		[
			'name' => 'Wynd',
			'label' => _l('rel_Wynd'),
		],
		[
			'name' => 'Yard',
			'label' => _l('rel_Yard'),
		],

	];

	return $array_data;
}

/**
 * rel Egypt Provincial divisions
 * @return [type] 
 */
function rel_Egypt_Provincial_divisions()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'Alexandria_Governorate',
			'label' => _l('rel_Alexandria_Governorate'),
		],
		[
			'name' => 'Aswan_Governorate',
			'label' => _l('rel_Aswan_Governorate'),
		],
		[
			'name' => 'Asyut_Governorate',
			'label' => _l('rel_Asyut_Governorate'),
		],
		[
			'name' => 'Beheira_Governorate',
			'label' => _l('rel_Beheira_Governorate'),
		],
		[
			'name' => 'Beni_Suef_Governorate',
			'label' => _l('rel_Beni_Suef_Governorate'),
		],
		[
			'name' => 'Cairo_Governorate',
			'label' => _l('rel_Cairo_Governorate'),
		],
		[
			'name' => 'Dakahlia_Governorate',
			'label' => _l('rel_Dakahlia_Governorate'),
		],
		[
			'name' => 'Damietta_Governorate',
			'label' => _l('rel_Damietta_Governorate'),
		],
		[
			'name' => 'Faiyum_Governorate',
			'label' => _l('rel_Faiyum_Governorate'),
		],
		[
			'name' => 'Gharbia_Governorate',
			'label' => _l('rel_Gharbia_Governorate'),
		],
		[
			'name' => 'Giza_Governorate',
			'label' => _l('rel_Giza_Governorate'),
		],
		[
			'name' => 'Ismailia_Governorate',
			'label' => _l('rel_Ismailia_Governorate'),
		],
		[
			'name' => 'Kafr_El_Sheikh_Governorate',
			'label' => _l('rel_Kafr_El_Sheikh_Governorate'),
		],
		[
			'name' => 'Luxor_Governorate',
			'label' => _l('rel_Luxor_Governorate'),
		],
		[
			'name' => 'Matruh_Governorate',
			'label' => _l('rel_Matruh_Governorate'),
		],
		[
			'name' => 'Minya_Governorate',
			'label' => _l('rel_Minya_Governorate'),
		],
		[
			'name' => 'Monufia_Governorate',
			'label' => _l('rel_Monufia_Governorate'),
		],
		[
			'name' => 'New_Valley_Governorate',
			'label' => _l('rel_New_Valley_Governorate'),
		],
		[
			'name' => 'North_Sinai_Governorate',
			'label' => _l('rel_North_Sinai_Governorate'),
		],
		[
			'name' => 'Port_Said_Governorate',
			'label' => _l('rel_Port_Said_Governorate'),
		],
		[
			'name' => 'Qalyubia_Governorate',
			'label' => _l('rel_Qalyubia_Governorate'),
		],
		[
			'name' => 'Qena_Governorate',
			'label' => _l('rel_Qena_Governorate'),
		],
		[
			'name' => 'Red_Sea_Governorate',
			'label' => _l('rel_Red_Sea_Governorate'),
		],
		[
			'name' => 'Sharqia_Governorate',
			'label' => _l('rel_Sharqia_Governorate'),
		],
		[
			'name' => 'Sohag_Governorate',
			'label' => _l('rel_Sohag_Governorate'),
		],
		[
			'name' => 'South_Sinai_Governorate',
			'label' => _l('rel_South_Sinai_Governorate'),
		],
		[
			'name' => 'Suez_Governorate',
			'label' => _l('rel_Suez_Governorate'),
		],
	];

	return $array_data;
}

/**
 * rel levels
 * @return [type] 
 */
function rel_levels()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Multi_Split',
			'label' => _l('rel_Multi_Split'),
		],
		[
			'name' => 'One',
			'label' => _l('rel_One'),
		],
		[
			'name' => 'Two',
			'label' => _l('rel_Two'),
		],
		[
			'name' => 'Three_or_More',
			'label' => _l('rel_Three_or_More'),
		],
		
	];

	return $array_data;
}

/**
 * rel net operating income type
 * @return [type] 
 */
function rel_net_operating_income_type()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Actual',
			'label' => _l('rel_Actual'),
		],
		[
			'name' => 'Projected',
			'label' => _l('rel_Projected'),
		],
	];

	return $array_data;
}

/**
 * rel sale includes
 * @return [type] 
 */
function rel_sale_includes()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'Building_and_Land',
			'label' => _l('rel_Building_and_Land'),
		],
		[
			'name' => 'Furniture_Fixtures',
			'label' => _l('rel_Furniture_Fixtures'),
		],
		[
			'name' => 'Leases',
			'label' => _l('rel_Leases'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		
	];

	return $array_data;
}

/**
 * rel number of tenants
 * @return [type] 
 */
function rel_number_of_tenants()
{

	$array_data = [];
	$array_data = [
		[
			'name' => '2_3_Tenants',
			'label' => _l('rel_2_3_Tenants'),
		],
		[
			'name' => '4_8_Tenants',
			'label' => _l('rel_4_8_Tenants'),
		],
		[
			'name' => '8_or_More_Tenants',
			'label' => _l('rel_8_or_More_Tenants'),
		],
		[
			'name' => 'Single_User',
			'label' => _l('rel_Single_User'),
		],
		
	];

	return $array_data;
}

/**
 * rel spa
 * @return [type] 
 */
function rel_spa()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'yes',
			'label' => _l('rel_yes'),
		],
		[
			'name' => 'no',
			'label' => _l('rel_no'),
		],
		
	];

	return $array_data;
}

/**
 * rel balcony
 * @return [type] 
 */
function rel_balcony()
{
	$array_data = [];
	$array_data = [

		[
			'name' => '0',
			'label' => _l('rel_0'),
		],
		[
			'name' => '1',
			'label' => _l('rel_1'),
		],
		[
			'name' => '2',
			'label' => _l('rel_2'),
		],
		[
			'name' => '3',
			'label' => _l('rel_3'),
		],
		[
			'name' => '4',
			'label' => _l('rel_4'),
		],
		[
			'name' => '5_and_More',
			'label' => _l('rel_5_and_More'),
		],
		
	];

	return $array_data;
}

/**
 * rel sqFt heated source
 * @return [type] 
 */
function rel_sqFt_heated_source()
{

	$array_data = [];
	$array_data = [
		[
			'name' => 'Appraisal',
			'label' => _l('rel_Appraisal'),
		],
		[
			'name' => 'Builder',
			'label' => _l('rel_Builder'),
		],
		[
			'name' => 'Measured',
			'label' => _l('rel_Measured'),
		],
		[
			'name' => 'Owner_provided',
			'label' => _l('rel_Owner_provided'),
		],
		[
			'name' => 'Public_Records',
			'label' => _l('rel_Public_Records'),
		],
	];

	return $array_data;
}

/**
 * rel fireplace description
 * @return [type] 
 */
function rel_fireplace_description()
{

	$array_data = [];
	$array_data = [
		
		[
			'name' => 'Basement',
			'label' => _l('rel_Basement'),
		],
		[
			'name' => 'Circulating',
			'label' => _l('rel_Circulating'),
		],
		[
			'name' => 'Decorative',
			'label' => _l('rel_Decorative'),
		],
		[
			'name' => 'Electric',
			'label' => _l('rel_Electric'),
		],
		[
			'name' => 'Family_Room',
			'label' => _l('rel_Family_Room'),
		],
		[
			'name' => 'Free_Standing',
			'label' => _l('rel_Free_Standing'),
		],
		[
			'name' => 'Gas',
			'label' => _l('rel_Gas'),
		],
		[
			'name' => 'Insert',
			'label' => _l('rel_Insert'),
		],
		[
			'name' => 'Living_Room',
			'label' => _l('rel_Living_Room'),
		],
		[
			'name' => 'Masonry',
			'label' => _l('rel_Masonry'),
		],
		[
			'name' => 'Master_Bedroom',
			'label' => _l('rel_Master_Bedroom'),
		],
		[
			'name' => 'Non_Wood_Burning',
			'label' => _l('rel_Non_Wood_Burning'),
		],
		[
			'name' => 'Other_Room',
			'label' => _l('rel_Other_Room'),
		],
		[
			'name' => 'Outside',
			'label' => _l('rel_Outside'),
		],
		[
			'name' => 'Stone',
			'label' => _l('rel_Stone'),
		],
		[
			'name' => 'Ventless',
			'label' => _l('rel_Ventless'),
		],
		[
			'name' => 'Wood_Burning',
			'label' => _l('rel_Wood_Burning'),
		],
	];

	return $array_data;
}

/**
 * rel appliances included
 * @return [type] 
 */
function rel_appliances_included()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Dishwasher',
			'label' => _l('rel_Dishwasher'),
		],
		[
			'name' => 'Dryer',
			'label' => _l('rel_Dryer'),
		],
		[
			'name' => 'Microwave',
			'label' => _l('rel_Microwave'),
		],
		[
			'name' => 'Refrigerator',
			'label' => _l('rel_Refrigerator'),
		],
		[
			'name' => 'Washer',
			'label' => _l('rel_Washer'),
		],
		
	];

	return $array_data;
}

/**
 * rel utilities
 * @return [type] 
 */
function rel_utilities()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'BB_HS_Internet_Capable',
			'label' => _l('rel_BB_HS_Internet_Capable'),
		],
		[
			'name' => 'Electrical_Nearby',
			'label' => _l('rel_Electrical_Nearby'),
		],
		[
			'name' => 'Electricity_Available',
			'label' => _l('rel_Electricity_Available'),
		],
		[
			'name' => 'Natural_Gas_Available',
			'label' => _l('rel_Natural_Gas_Available'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Phone_Available',
			'label' => _l('rel_Phone_Available'),
		],
		[
			'name' => 'Private',
			'label' => _l('rel_Private'),
		],
		[
			'name' => 'Public',
			'label' => _l('rel_Public'),
		],
		[
			'name' => 'Sewer_Nearby',
			'label' => _l('rel_Sewer_Nearby'),
		],
		[
			'name' => 'Solar',
			'label' => _l('rel_Solar'),
		],
		[
			'name' => 'Telephone_Nearby',
			'label' => _l('rel_Telephone_Nearby'),
		],
		[
			'name' => 'Water_Connected',
			'label' => _l('rel_Water_Connected'),
		],
		[
			'name' => 'Water_Nearby',
			'label' => _l('rel_Water_Nearby'),
		],
		[
			'name' => 'Emergency_Power',
			'label' => _l('rel_Emergency_Power'),
		],
	];

	return $array_data;
}

/**
 * rel sewer
 * @return [type] 
 */
function rel_sewer()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Aerobic_Septic',
			'label' => _l('rel_Aerobic_Septic'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Private_Sewer',
			'label' => _l('rel_Private_Sewer'),
		],
		[
			'name' => 'Public_Sewer',
			'label' => _l('rel_Public_Sewer'),
		],
		[
			'name' => 'Septic_Needed',
			'label' => _l('rel_Septic_Needed'),
		],
		[
			'name' => 'Septic_Tank',
			'label' => _l('rel_Septic_Tank'),
		],
		[
			'name' => 'PEP_Holding_Tank',
			'label' => _l('rel_PEP_Holding_Tank'),
		],
	];

	return $array_data;
}

/**
 * rel water
 * @return [type] 
 */
function rel_water()
{
	
	$array_data = [];
	$array_data = [

		[
			'name' => 'Above_Ground',
			'label' => _l('rel_Above_Ground'),
		],

		[
			'name' => 'Canal_Lake_for_Irrigation',

			'label' => _l('rel_Canal_Lake_for_Irrigation'),
		],
		[
			'name' => 'None',

			'label' => _l('rel_None'),
		],
		[
			'name' => 'Private_Public',

			'label' => _l('rel_Private_Public'),
		],
		[
			'name' => 'Well',

			'label' => _l('rel_Well'),
		],
		[
			'name' => 'Well_Required',

			'label' => _l('rel_Well_Required'),
		],
	];

	return $array_data;
}

/**
 * rel heating and fuel
 * @return [type] 
 */
function rel_heating_and_fuel()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Baseboard',
			'label' => _l('rel_Baseboard'),
		],
		[
			'name' => 'Central',
			'label' => _l('rel_Central'),
		],
		[
			'name' => 'Central_Building',
			'label' => _l('rel_Central_Building'),
		],
		[
			'name' => 'Central_Individual',
			'label' => _l('rel_Central_Individual'),
		],
		[
			'name' => 'Electric',
			'label' => _l('rel_Electric'),
		],
		[
			'name' => 'Exhaust_Fans',
			'label' => _l('rel_Exhaust_Fans'),
		],
		[
			'name' => 'Heat_Pump',
			'label' => _l('rel_Heat_Pump'),
		],
		[
			'name' => 'Heat_Recovery_Unit',
			'label' => _l('rel_Heat_Recovery_Unit'),
		],
		[
			'name' => 'Natural_Gas',
			'label' => _l('rel_Natural_Gas'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Oil',
			'label' => _l('rel_Oil'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Partial',
			'label' => _l('rel_Partial'),
		],
		[
			'name' => 'Propane',
			'label' => _l('rel_Propane'),
		],
		[
			'name' => 'Radiant_Ceiling',
			'label' => _l('rel_Radiant_Ceiling'),
		],
		[
			'name' => 'Reverse_Cycle',
			'label' => _l('rel_Reverse_Cycle'),
		],
		[
			'name' => 'Solar',
			'label' => _l('rel_Solar'),
		],
		[
			'name' => 'Space_Heater',
			'label' => _l('rel_Space_Heater'),
		],
		[
			'name' => 'Wall_Furnace',
			'label' => _l('rel_Wall_Furnace'),
		],
		[
			'name' => 'Wall_Window_Units',
			'label' => _l('rel_Wall_Window_Units'),
		],
		[
			'name' => 'Zoned',
			'label' => _l('rel_Zoned'),
		],
	];

	return $array_data;
}

/**
 * rel air conditioning
 * @return [type] 
 */
function rel_air_conditioning()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'A_C_Office_Only',
			'label' => _l('rel_A_C_Office_Only'),
		],
		[
			'name' => 'Central_Air',
			'label' => _l('rel_Central_Air'),
		],
		[
			'name' => 'Humidity_Control',
			'label' => _l('rel_Humidity_Control'),
		],
		[
			'name' => 'Mini_Split_Unit_s',
			'label' => _l('rel_Mini_Split_Unit_s'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Wall_Window_Unit_s',
			'label' => _l('rel_Wall_Window_Unit_s'),
		],
		[
			'name' => 'Zoned',
			'label' => _l('rel_Zoned'),
		],
	];

	return $array_data;
}

/**
 * rel electrical service
 * @return [type] 
 */
function rel_electrical_service()
{

	$array_data = [];
	$array_data = [
		[
			'name' => '100_Amp_Service',
			'label' => _l('rel_100_Amp_Service'),
		],
		[
			'name' => '150_Amp_Service',
			'label' => _l('rel_150_Amp_Service'),
		],
		[
			'name' => '200__Amp_Service',
			'label' => _l('rel_200__Amp_Service'),
		],
		[
			'name' => '3_Phase',
			'label' => _l('rel_3_Phase'),
		],
		[
			'name' => '110_Volts',
			'label' => _l('rel_110_Volts'),
		],
		[
			'name' => '220_Volts',
			'label' => _l('rel_220_Volts'),
		],
		[
			'name' => '440_Volts',
			'label' => _l('rel_440_Volts'),
		],
		[
			'name' => 'Generator',
			'label' => _l('rel_Generator'),
		],
		[
			'name' => 'Generator_Hook_Up',
			'label' => _l('rel_Generator_Hook_Up'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Separate_Meters',
			'label' => _l('rel_Separate_Meters'),
		],
	];

	return $array_data;
}

/**
 * rel security features
 * @return [type] 
 */
function rel_security_features()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Above_Ground',
			'label' => _l('rel_Above_Ground'),
		],
		[
			'name' => 'Closed_Circuit_Camera_s',
			'label' => _l('rel_Closed_Circuit_Camera_s'),
		],
		[
			'name' => 'Fire_Alarm',
			'label' => _l('rel_Fire_Alarm'),
		],
		[
			'name' => 'Fire_Sprinkler_System',
			'label' => _l('rel_Fire_Sprinkler_System'),
		],
		[
			'name' => 'Gated_Community',
			'label' => _l('rel_Gated_Community'),
		],
		[
			'name' => 'Key_Card_Entry',
			'label' => _l('rel_Key_Card_Entry'),
		],
		[
			'name' => 'Secured_Garage_Parking',
			'label' => _l('rel_Secured_Garage_Parking'),
		],
		[
			'name' => 'Security_Gate',
			'label' => _l('rel_Security_Gate'),
		],
		[
			'name' => 'Security_Lights',
			'label' => _l('rel_Security_Lights'),
		],
		[
			'name' => 'Security_System',
			'label' => _l('rel_Security_System'),
		],
		[
			'name' => 'Security_System_Leased',
			'label' => _l('rel_Security_System_Leased'),
		],
		[
			'name' => 'Security_System_Owned',
			'label' => _l('rel_Security_System_Owned'),
		],
		[
			'name' => 'Smoke_Detector_s',
			'label' => _l('rel_Smoke_Detector_s'),
		],

	];

	return $array_data;
}

/**
 * rel accessibility features
 * @return [type] 
 */
function rel_accessibility_features()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Accessible_Approach',
			'label' => _l('rel_Accessible_Approach'),
		],
		[
			'name' => 'Accessible_Bedroom',
			'label' => _l('rel_Accessible_Bedroom'),
		],
		[
			'name' => 'Accessible_Living_Area',
			'label' => _l('rel_Accessible_Living_Area'),
		],
		[
			'name' => 'Accessible_Closets',
			'label' => _l('rel_Accessible_Closets'),
		],
		[
			'name' => 'Accessible_Common_Room',
			'label' => _l('rel_Accessible_Common_Room'),
		],
		[
			'name' => 'Accessible_Doors',
			'label' => _l('rel_Accessible_Doors'),
		],
		[
			'name' => 'Accessible_Elec_and_Envir_Controls',
			'label' => _l('rel_Accessible_Elec_and_Envir_Controls'),
		],
		[
			'name' => 'Accessible_Elevator_Installed',
			'label' => _l('rel_Accessible_Elevator_Installed'),
		],
		[
			'name' => 'Accessible_Entrance',
			'label' => _l('rel_Accessible_Entrance'),
		],
		[
			'name' => 'Accessible_for_Hearing_Impairment',
			'label' => _l('rel_Accessible_for_Hearing_Impairment'),
		],
		[
			'name' => 'Accessible_Full_Bath',
			'label' => _l('rel_Accessible_Full_Bath'),
		],
		[
			'name' => 'Accessible_Guest_Bathroom',
			'label' => _l('rel_Accessible_Guest_Bathroom'),
		],
		[
			'name' => 'Accessible_Hallway_s',
			'label' => _l('rel_Accessible_Hallway_s'),
		],
		[
			'name' => 'Accessible_Kitchen',
			'label' => _l('rel_Accessible_Kitchen'),
		],
		[
			'name' => 'Accessible_Kitchen_Appliances',
			'label' => _l('rel_Accessible_Kitchen_Appliances'),
		],
		[
			'name' => 'Accessible_Stairway',
			'label' => _l('rel_Accessible_Stairway'),
		],
		[
			'name' => 'Accessible_Washer_Dryer',
			'label' => _l('rel_Accessible_Washer_Dryer'),
		],
		[
			'name' => 'Ceiling_Track_for_Chair_Lift',
			'label' => _l('rel_Ceiling_Track_for_Chair_Lift'),
		],
		[
			'name' => 'Central_Living_Area',
			'label' => _l('rel_Central_Living_Area'),
		],
		[
			'name' => 'Customized_Wheelchair_Accessible',
			'label' => _l('rel_Customized_Wheelchair_Accessible'),
		],
		[
			'name' => 'Exterior_Wheelchair_Lift',
			'label' => _l('rel_Exterior_Wheelchair_Lift'),
		],
		[
			'name' => 'Grip_Accessible_Features',
			'label' => _l('rel_Grip_Accessible_Features'),
		],
		[
			'name' => 'Handicap_Modified',
			'label' => _l('rel_Handicap_Modified'),
		],
		[
			'name' => 'Stair_Lift',
			'label' => _l('rel_Stair_Lift'),
		],
	];

	return $array_data;
}

/**
 * rel floor covering
 * @return [type] 
 */
function rel_floor_covering()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Bamboo',
			'label' => _l('rel_Bamboo'),
		],
		[
			'name' => 'Brick_Stone',
			'label' => _l('rel_Brick_Stone'),
		],
		[
			'name' => 'Carpet',
			'label' => _l('rel_Carpet'),
		],
		[
			'name' => 'Ceramic_Tile',
			'label' => _l('rel_Ceramic_Tile'),
		],
		[
			'name' => 'Concrete',
			'label' => _l('rel_Concrete'),
		],
		[
			'name' => 'Cork',
			'label' => _l('rel_Cork'),
		],
		[
			'name' => 'Engineered_Hardwood',
			'label' => _l('rel_Engineered_Hardwood'),
		],
		[
			'name' => 'Epoxy',
			'label' => _l('rel_Epoxy'),
		],
		[
			'name' => 'Forestry_Stewardship_Certified',
			'label' => _l('rel_Forestry_Stewardship_Certified'),
		],
		[
			'name' => 'Granite',
			'label' => _l('rel_Granite'),
		],
		[
			'name' => 'Laminate',
			'label' => _l('rel_Laminate'),
		],
		[
			'name' => 'Linoleum',
			'label' => _l('rel_Linoleum'),
		],
		[
			'name' => 'Marble',
			'label' => _l('rel_Marble'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Parquet',
			'label' => _l('rel_Parquet'),
		],
		[
			'name' => 'Porcelain_Tile',
			'label' => _l('rel_Porcelain_Tile'),
		],
		[
			'name' => 'Quarry_Tile',
			'label' => _l('rel_Quarry_Tile'),
		],
		[
			'name' => 'Reclaimed_Wood',
			'label' => _l('rel_Reclaimed_Wood'),
		],
		[
			'name' => 'Recycled_Composite_Flooring',
			'label' => _l('rel_Recycled_Composite_Flooring'),
		],
		[
			'name' => 'Slate',
			'label' => _l('rel_Slate'),
		],
		[
			'name' => 'Terrazzo',
			'label' => _l('rel_Terrazzo'),
		],
		[
			'name' => 'Tile',
			'label' => _l('rel_Tile'),
		],
		[
			'name' => 'Vinyl',
			'label' => _l('rel_Vinyl'),
		],

	];

	return $array_data;
}

/**
 * rel ceiling type
 * @return [type] 
 */
function rel_ceiling_type()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Acoustical',
			'label' => _l('rel_Acoustical'),
		],
		[
			'name' => 'Drywall',
			'label' => _l('rel_Drywall'),
		],
		[
			'name' => 'Insulation_Vinyl',
			'label' => _l('rel_Insulation_Vinyl'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Plaster',
			'label' => _l('rel_Plaster'),
		],
	];

	return $array_data;
}

/**
 * rel window features
 * @return [type] 
 */
function rel_window_features()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Double_Pane_Windows',
			'label' => _l('rel_Double_Pane_Windows'),
		],
		[
			'name' => 'Display_Windows',
			'label' => _l('rel_Display_Windows'),
		],
		[
			'name' => 'Tinted_Windows',
			'label' => _l('rel_Tinted_Windows'),
		],
		[
			'name' => 'Impact_Glass_Storm_Windows',
			'label' => _l('rel_Impact_Glass_Storm_Windows'),
		],
		[
			'name' => 'Aluminum_Frames',
			'label' => _l('rel_Aluminum_Frames'),
		],
		[
			'name' => 'Wood_Frames',
			'label' => _l('rel_Wood_Frames'),
		],
		[
			'name' => 'Blinds',
			'label' => _l('rel_Blinds'),
		],
		[
			'name' => 'Drapes',
			'label' => _l('rel_Drapes'),
		],
		[
			'name' => 'ENERGY_STAR_Qualified_Windows',
			'label' => _l('rel_ENERGY_STAR_Qualified_Windows'),
		],
		[
			'name' => 'Insulated_Windows',
			'label' => _l('rel_Insulated_Windows'),
		],
		[
			'name' => 'Low_Emissivity_Windows',
			'label' => _l('rel_Low_Emissivity_Windows'),
		],
		[
			'name' => 'Rods',
			'label' => _l('rel_Rods'),
		],
		[
			'name' => 'Shades',
			'label' => _l('rel_Shades'),
		],
		[
			'name' => 'Shutters',
			'label' => _l('rel_Shutters'),
		],
		[
			'name' => 'Solar_Screens',
			'label' => _l('rel_Solar_Screens'),
		],
		[
			'name' => 'Thermal_Windows',
			'label' => _l('rel_Thermal_Windows'),
		],
		[
			'name' => 'Window_Treatments',
			'label' => _l('rel_Window_Treatments'),
		],
	];

	return $array_data;
}

/**
 * rel furnished
 * @return [type] 
 */
function rel_furnished()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'yes',
			'label' => _l('rel_yes'),
		],
		[
			'name' => 'no',
			'label' => _l('rel_no'),
		],
		[
			'name' => 'Semi',
			'label' => _l('rel_Semi'),
		],
	];

	return $array_data;
}

/**
 * rel finishing
 * @return [type] 
 */
function rel_finishing()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Lux',
			'label' => _l('rel_Lux'),
		],
		[
			'name' => 'Supe_Lux',
			'label' => _l('rel_Supe_Lux'),
		],
		[
			'name' => 'Executive',
			'label' => _l('rel_Executive'),
		],
		[
			'name' => 'Modern',
			'label' => _l('rel_Modern'),
		],
		[
			'name' => 'Custom',
			'label' => _l('rel_Custom'),
		],
		
	];

	return $array_data;
}

/**
 * rel ownership
 * @return [type] 
 */
function rel_ownership()
{
	$array_data = [];
	$array_data = [
		
		[
			'name' => 'Corporation',
			'label' => _l('rel_Corporation'),
		],
		[
			'name' => 'Franchise',
			'label' => _l('rel_Franchise'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Partnership',
			'label' => _l('rel_Partnership'),
		],
		[
			'name' => 'Sole_Proprietor',
			'label' => _l('rel_Sole_Proprietor'),
		],
		[
			'name' => 'Sole_Ownership',
			'label' => _l('rel_Sole_Ownership'),
		],
		[
			'name' => 'Joint_Tenancy',
			'label' => _l('rel_Joint_Tenancy'),
		],
		
	];

	return $array_data;
}

/**
 * rel realtor information
 * @return [type] 
 */
function rel_realtor_information()
{
	$array_data = [];
	$array_data = [

		[
			'name' => '3rd_Party_Approval_Req',
			'label' => _l('rel_3rd_Party_Approval_Req'),
		],
		[
			'name' => 'Assoc_Approval_Required',
			'label' => _l('rel_Assoc_Approval_Required'),
		],
		[
			'name' => 'Brochure_Available',
			'label' => _l('rel_Brochure_Available'),
		],
		[
			'name' => 'CDD_Addendum_Required',
			'label' => _l('rel_CDD_Addendum_Required'),
		],
		[
			'name' => 'Confidentiality_Letter_Req',
			'label' => _l('rel_Confidentiality_Letter_Req'),
		],
		[
			'name' => 'Contract_for_Deed',
			'label' => _l('rel_Contract_for_Deed'),
		],
		[
			'name' => 'Currently_Leased',
			'label' => _l('rel_Currently_Leased'),
		],
		[
			'name' => 'Docs_Available',
			'label' => _l('rel_Docs_Available'),
		],
		[
			'name' => 'Engineering_Report',
			'label' => _l('rel_Engineering_Report'),
		],
		[
			'name' => 'Environ_Report_Available',
			'label' => _l('rel_Environ_Report_Available'),
		],
		[
			'name' => 'Floodway',
			'label' => _l('rel_Floodway'),
		],
		[
			'name' => 'Floor_Plan_Available',
			'label' => _l('rel_Floor_Plan_Available'),
		],
		[
			'name' => 'Foreign_Seller',
			'label' => _l('rel_Foreign_Seller'),
		],
		[
			'name' => 'In_Foreclosure',
			'label' => _l('rel_In_Foreclosure'),
		],
		[
			'name' => 'Lease_Restrictions',
			'label' => _l('rel_Lease_Restrictions'),
		],
		[
			'name' => 'Leasing_Not_Allowed',
			'label' => _l('rel_Leasing_Not_Allowed'),
		],
		[
			'name' => 'List_Agent_is_Owner',
			'label' => _l('rel_List_Agent_is_Owner'),
		],
		[
			'name' => 'List_Agent_is_Related_to_Owner',
			'label' => _l('rel_List_Agent_is_Related_to_Owner'),
		],
		[
			'name' => 'No_Sign',
			'label' => _l('rel_No_Sign'),
		],
		[
			'name' => 'Pre_Foreclosure',
			'label' => _l('rel_Pre_Foreclosure'),
		],
		[
			'name' => 'Remediation_Accomplished',
			'label' => _l('rel_Remediation_Accomplished'),
		],
		[
			'name' => 'Remediation_in_Progress',
			'label' => _l('rel_Remediation_in_Progress'),
		],
		[
			'name' => 'Right_of_First_Refusal',
			'label' => _l('rel_Right_of_First_Refusal'),
		],
		[
			'name' => 'Scrub_Jay_Check_w_County',
			'label' => _l('rel_Scrub_Jay_Check_w_County'),
		],
		[
			'name' => 'See_Attachments',
			'label' => _l('rel_See_Attachments'),
		],
		[
			'name' => 'Seller_May_Build',
			'label' => _l('rel_Seller_May_Build'),
		],
		[
			'name' => 'Seller_Must_Build',
			'label' => _l('rel_Seller_Must_Build'),
		],
		[
			'name' => 'Survey_Available',
			'label' => _l('rel_Survey_Available'),
		],
		[
			'name' => 'Termite_Bond_Warranty',
			'label' => _l('rel_Termite_Bond_Warranty'),
		],
		[
			'name' => 'Will_Sell_Lot',
			'label' => _l('rel_Will_Sell_Lot'),
		],
		[
			'name' => 'Will_Subdivide',
			'label' => _l('rel_Will_Subdivide'),
		],
		[
			'name' => 'Zoning_Grandfathered_In',
			'label' => _l('rel_Zoning_Grandfathered_In'),
		],


	];

	return $array_data;
}

/**
 * rel realtor information confidential
 * @return [type] 
 */
function rel_realtor_information_confidential()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Bonus_to_Selling_Office',
			'label' => _l('rel_Bonus_to_Selling_Office'),
		],
		[
			'name' => 'Go_To_Site',
			'label' => _l('rel_Go_To_Site'),
		],
		[
			'name' => 'In_Foreclosure',
			'label' => _l('rel_In_Foreclosure'),
		],
		[
			'name' => 'Pre_Foreclosure',
			'label' => _l('rel_Pre_Foreclosure'),
		],

	];

	return $array_data;
}

/**
 * rel disclosures
 * @return [type] 
 */
function rel_disclosures()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Condo_Disclosure_Available',
			'label' => _l('rel_Condo_Disclosure_Available'),
		],
		[
			'name' => 'Environmental_Disclosure',
			'label' => _l('rel_Environmental_Disclosure'),
		],
		[
			'name' => 'HOA_PUD_Condo_Disclosure',
			'label' => _l('rel_HOA_PUD_Condo_Disclosure'),
		],
		[
			'name' => 'Land_Sales_Disclosure',
			'label' => _l('rel_Land_Sales_Disclosure'),
		],
		[
			'name' => 'Lead_paint',
			'label' => _l('rel_Lead_paint'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'PACE_Loan_Disclosure',
			'label' => _l('rel_PACE_Loan_Disclosure'),
		],
		[
			'name' => 'Seller_Property_Disclosure',
			'label' => _l('rel_Seller_Property_Disclosure'),
		],
		[
			'name' => 'Superfund',
			'label' => _l('rel_Superfund'),
		],

	];

	return $array_data;
}

/**
 * rel possession
 * @return [type] 
 */
function rel_possession()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Negotiable',
			'label' => _l('rel_Negotiable'),
		],
		[
			'name' => 'Not_Negotiable',
			'label' => _l('rel_Not_Negotiable'),
		],
		
	];

	return $array_data;
}

/**
 * rel_rent_status
 * @return [type] 
 */
function rel_rent_status()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Occupied',
			'label' => _l('rel_Occupied'),
		],
		[
			'name' => 'Vacant',
			'label' => _l('rel_Vacant'),
		],
	];

	return $array_data;
}

/**
 * rel property listing status
 * @param  string $status 
 * @return [type]         
 */
function rel_property_listing_status($status='')
{

	$statuses = [
		[
			'id'             => 'pending',
			'color'          => '#3f51b5',
			'name'           => _l('rel_pending'),
			'order'          => 1,
			'filter_default' => true,
		],
		[
			'id'             => 'new',
			'color'          => '#28b8daed',
			'name'           => _l('rel_new'),
			'order'          => 1,
			'filter_default' => true,
		],
		[
			'id'             => 'active',
			'color'          => '#03A9F4',
			'name'           => _l('rel_active'),
			'order'          => 2,
			'filter_default' => true,
		],
		[
			'id'             => 'cancelled',
			'color'          => '#2196f3',
			'name'           => _l('rel_cancelled'),
			'order'          => 3,
			'filter_default' => true,
		],
		[
			'id'             => 'closed_sale',
			'color'          => '#3db8da',
			'name'           => _l('rel_closed_sale'),
			'order'          => 4,
			'filter_default' => true,
		],
		[
			'id'             => 'pending_sale',
			'color'          => '#84c529',
			'name'           => _l('rel_pending_sale'),
			'order'          => 5,
			'filter_default' => false,
		],
		[
			'id'             => 'sold',
			'color'          => '#84c529',
			'name'           => _l('rel_sold'),
			'order'          => 6,
			'filter_default' => false,
		],
		
		[
			'id'             => 'rented',
			'color'          => '#d71a1a',
			'name'           => _l('rel_rented'),
			'order'          => 7,
			'filter_default' => false,
		],
		[
			'id'             => 'temp_off_market',
			'color'          => '#ffa500',
			'name'           => _l('rel_temp_off_market'),
			'order'          => 8,
			'filter_default' => false,
		],
		[
			'id'             => 'withdrawn',
			'color'          => '#ffa566',
			'name'           => _l('rel_withdrawn'),
			'order'          => 9,
			'filter_default' => false,
		],
		[
			'id'             => 'expired',
			'color'          => '#ffa577',
			'name'           => _l('rel_expired'),
			'order'          => 10,
			'filter_default' => false,
		],
		[
			'id'             => 'backup',
			'color'          => '#ffa577',
			'name'           => _l('rel_backup'),
			'order'          => 11,
			'filter_default' => false,
		],
		
	];

	usort($statuses, function ($a, $b) {
		return $a['order'] - $b['order'];
	});

	return $statuses;
}

/**
 * rel room type
 * @return [type] 
 */
function rel_room_type()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Additional_Bedroom',
			'label' => _l('rel_Additional_Bedroom'),
		],
		[
			'name' => 'Balcony_Porch_Lanai',
			'label' => _l('rel_Balcony_Porch_Lanai'),
		],
		[
			'name' => 'Basement',
			'label' => _l('rel_Basement'),
		],
		[
			'name' => 'Bathroom_1',
			'label' => _l('rel_Bathroom_1'),
		],
		[
			'name' => 'Bathroom_2',
			'label' => _l('rel_Bathroom_2'),
		],
		[
			'name' => 'Bathroom_3',
			'label' => _l('rel_Bathroom_3'),
		],
		[
			'name' => 'Bathroom_4',
			'label' => _l('rel_Bathroom_4'),
		],
		[
			'name' => 'Bathroom_5',
			'label' => _l('rel_Bathroom_5'),
		],
		[
			'name' => 'Bedroom_1',
			'label' => _l('rel_Bedroom_1'),
		],
		[
			'name' => 'Bedroom_2',
			'label' => _l('rel_Bedroom_2'),
		],
		[
			'name' => 'Bedroom_3',
			'label' => _l('rel_Bedroom_3'),
		],
		[
			'name' => 'Bedroom_4',
			'label' => _l('rel_Bedroom_4'),
		],
		[
			'name' => 'Bedroom_5',
			'label' => _l('rel_Bedroom_5'),
		],
		[
			'name' => 'Bonus_Room',
			'label' => _l('rel_Bonus_Room'),
		],
		[
			'name' => 'Breezeway',
			'label' => _l('rel_Breezeway'),
		],
		[
			'name' => 'Dinette',
			'label' => _l('rel_Dinette'),
		],
		[
			'name' => 'Dining_Room',
			'label' => _l('rel_Dining_Room'),
		],
		[
			'name' => 'Double_Master_Bedroom',
			'label' => _l('rel_Double_Master_Bedroom'),
		],
		[
			'name' => 'Family_Room',
			'label' => _l('rel_Family_Room'),
		],
		[
			'name' => 'Florida_Room',
			'label' => _l('rel_Florida_Room'),
		],
		[
			'name' => 'Foyer',
			'label' => _l('rel_Foyer'),
		],
		[
			'name' => 'Game_Room',
			'label' => _l('rel_Game_Room'),
		],
		[
			'name' => 'Great_Room',
			'label' => _l('rel_Great_Room'),
		],
		[
			'name' => 'Gym',
			'label' => _l('rel_Gym'),
		],
		[
			'name' => 'Inside_Utility',
			'label' => _l('rel_Inside_Utility'),
		],
		[
			'name' => 'Interior_In_Law_Suite',
			'label' => _l('rel_Interior_In_Law_Suite'),
		],
		[
			'name' => 'Kitchen',
			'label' => _l('rel_Kitchen'),
		],
		[
			'name' => 'Laundry',
			'label' => _l('rel_Laundry'),
		],
		[
			'name' => 'Library',
			'label' => _l('rel_Library'),
		],
		[
			'name' => 'Living_Room',
			'label' => _l('rel_Living_Room'),
		],
		[
			'name' => 'Loft',
			'label' => _l('rel_Loft'),
		],
		[
			'name' => 'Master_Bathroom',
			'label' => _l('rel_Master_Bathroom'),
		],
		[
			'name' => 'Master_Bedroom',
			'label' => _l('rel_Master_Bedroom'),
		],
		[
			'name' => 'Media_Room',
			'label' => _l('rel_Media_Room'),
		],
		[
			'name' => 'Office',
			'label' => _l('rel_Office'),
		],
		[
			'name' => 'Sauna',
			'label' => _l('rel_Sauna'),
		],
		[
			'name' => 'Studio',
			'label' => _l('rel_Studio'),
		],
		[
			'name' => 'Study_Den',
			'label' => _l('rel_Study_Den'),
		],
		[
			'name' => 'Workshop',
			'label' => _l('rel_Workshop'),
		],
		[
			'name' => 'Washroom',
			'label' => _l('rel_Washroom'),
		],

		
	];

	return $array_data;
}

/**
 * rel room levels
 * @return [type] 
 */
function rel_room_levels()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Basement',
			'label' => _l('rel_Basement'),
		],
		[
			'name' => 'First',
			'label' => _l('rel_First'),
		],
		[
			'name' => 'Second',
			'label' => _l('rel_Second'),
		],
		[
			'name' => 'Third',
			'label' => _l('rel_Third'),
		],
		[
			'name' => 'Upper',
			'label' => _l('rel_Upper'),
		],
		
	];

	return $array_data;
}

/**
 * rel room benefits
 * @return [type] 
 */
function rel_room_benefits()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Closet',
			'label' => _l('rel_Closet'),
		],
		[
			'name' => 'Washroom',
			'label' => _l('rel_Washroom'),
		],
		[
			'name' => 'Balcony',
			'label' => _l('rel_Balcony'),
		],
	];

	return $array_data;
}

/**
 * rel handle property listing attachments
 * @param  [type] $id 
 * @return [type]     
 */
function real_handle_property_listing_attachments($id)
{
	if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
		header('HTTP/1.0 400 Bad error');
		echo _perfex_upload_error($_FILES['file']['error']);
		die;
	}
	$path = PROPERTY_UPLOAD . $id . '/';
	$CI   = & get_instance();

	if (isset($_FILES['file']['name'])) {

        // Get the temp file path
		$tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {

			_maybe_create_upload_path($path);

			$filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

			$newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {

				$CI                       = & get_instance();
				$config                   = [];
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'thumb_' . $new_filename;
				$config['maintain_ratio'] = true;
				$config['width']          = 300;
				$config['height']         = 300;
				$config['maintain_ratio'] = true;
				$config['create_thumb']   = true;

				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
				$CI->image_lib->clear();
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'small_' . $new_filename;
				$config['maintain_ratio'] = true;
				$config['width']          = 40;
				$config['height']         = 40;
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();

				$attachment   = [];
				$attachment[] = [
					'file_name' => $new_filename,
					'filetype'  => $_FILES['file']['type'],
				];

				$CI->misc_model->add_attachment_to_database($id, 'commodity_item_file', $attachment);

			}
		}
	}

}


/**
 * rel handle property listing attachments1
 * @param  [type] $id 
 * @return [type]     
 */
function real_handle_property_listing_attachments1($id)
{

	if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
		header('HTTP/1.0 400 Bad error');
		echo _perfex_upload_error($_FILES['file']['error']);
		die;
	}
	$path = PROPERTY_PDF_UPLOAD . $id . '/';
	$CI   = & get_instance();

	if (isset($_FILES['file']['name'])) {

        // Get the temp file path
		$tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {

			_maybe_create_upload_path($path);
			$filename    = $_FILES['file']['name'];
			$newFilePath = $path . $filename;
            // Upload the file into the temp dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {

				$CI           = & get_instance();
				$attachment   = [];
				$attachment[] = [
					'file_name' => $filename,
					'filetype'  => $_FILES['file']['type'],
				];
				$CI->misc_model->add_attachment_to_database($id, 'real_listing_pdf', $attachment);

			}
		}
	}

}

/**
 * rel convert to school name
 * @param  string $school_ids 
 * @return [type]             
 */
function rel_convert_to_school_name($school_ids='', $detail = true)
{
	$school_name = '';
	if($school_ids != '' && !is_null($school_ids)){

		$CI           = & get_instance();

		$CI->db->where('id IN ('.$school_ids.')');
		$schools = $CI->db->get(db_prefix().'real_schools')->result_array();
		foreach ($schools as $school) {
			if($school_name != ''){
				if($detail){
					$school_name .= '; '.$school['name'];
				}else{
					$school_name .= ',<br>'.$school['name'];
				}
			}else{

				$school_name .= $school['name'];
			}
		}
	}else{
		$school_name = '---';
	}

	return $school_name;
}

/**
 * rel convert to landmark name
 * @param  string $landmark_ids 
 * @return [type]               
 */
function rel_convert_to_landmark_name($landmark_ids='', $detail=true)
{
	$landmark_name = '';
	if($landmark_ids != '' && !is_null($landmark_ids)){

		$CI           = & get_instance();

		$CI->db->where('id IN ('.$landmark_ids.')');
		$landmarks = $CI->db->get(db_prefix().'real_landmarks')->result_array();
		foreach ($landmarks as $landmark) {
			if($landmark_name != ''){
				if($detail){
					$landmark_name .= '; '.$landmark['name'];
				}else{
					$landmark_name .= ',<br>'.$landmark['name'];
				}
			}else{

				$landmark_name .= $landmark['name'];
			}
		}
	}else{
		$landmark_name = '---';
	}

	return $landmark_name;
}

/**
 * rel convert to hopspital name
 * @param  string  $hopspital_ids 
 * @param  boolean $detail        
 * @return [type]                 
 */
function real_convert_to_hopspital_name($hopspital_ids='', $detail = true)
{
	$hopspital_name = '';
	if($hopspital_ids != '' && !is_null($hopspital_ids)){

		$CI           = & get_instance();

		$CI->db->where('id IN ('.$hopspital_ids.')');
		$hopspitals = $CI->db->get(db_prefix().'real_hopspitals')->result_array();
		foreach ($hopspitals as $hopspital) {
			if($hopspital_name != ''){
				if($detail){
					$hopspital_name .= '; '.$hopspital['name'];
				}else{
					$hopspital_name .= ',<br>'.$hopspital['name'];
				}
			}else{

				$hopspital_name .= $hopspital['name'];
			}
		}
	}else{
		$hopspital_name = '---';
	}

	return $hopspital_name;
}

/**
 * real get contact infor
 * @param  [type] $id 
 * @return [type]     
 */
function real_get_contact_infor($id, $related_type)
{
	$staff_code = '';
	$staff_name = '';
	$staff_email = '';
	$staff_phone = '';
	$staffid = '';
	$enable_chat = 1;
	if($related_type == 'staff' || $related_type == 'company'){
		$staff = get_staff($id);
		if($staff){
			$staff_code = $staff->staff_identifi;
		}
	}else{
		$staff = get_broker_staff($id);
		$staff_code = $staff->code;
	}
	if($staff){
		$staff_name = $staff->firstname.' '.$staff->lastname;
		$staff_email = $staff->email;
		$staff_phone = $staff->phonenumber;
	}

	$data = [];
	$data['staff_code'] = $staff_code;
	$data['staff_name'] = $staff_name;
	$data['staff_email'] = $staff_email;
	$data['staff_phone'] = $staff_phone;
	$data['staffid'] = $id;
	$data['enable_chat'] = $enable_chat;
	 return $data;
}

/**
 * General function for all datatables, performs search,additional select,join,where,orders
 * @param  array $aColumns           table columns
 * @param  mixed $sIndexColumn       main column in table for bettter performing
 * @param  string $sTable            table name
 * @param  array  $join              join other tables
 * @param  array  $where             perform where in query
 * @param  array  $additionalSelect  select additional fields
 * @param  string $sGroupBy group results
 * @return array
 */
function real_data_grid_init($page, $where = [], $itemPerPage =  0)
{

    $tables_pagination_limit = $itemPerPage;

    $CI            = & get_instance();

    $where = implode(' ', $where);
    
    $where = trim($where);
    if (startsWith($where, 'AND') || startsWith($where, 'OR')) {
        if (startsWith($where, 'OR')) {
            $where = substr($where, 2);
        } else {
            $where = substr($where, 3);
        }
        $where = 'WHERE ' . $where;
    }

    if($tables_pagination_limit != -1){

        $offset = ($page - 1)  * $tables_pagination_limit;


        $query = 'SELECT * FROM '.db_prefix().'items '.$where.' ORDER BY id desc LIMIT '.$tables_pagination_limit.' OFFSET '.$offset;
    }else{

        $query = 'SELECT * FROM '.db_prefix().'items '.$where.' ORDER BY id desc';
    }

    $items = $CI->db->query($query)->result_array();

    return $items;

}

/**
 * real handle property listing attachments2
 * @param  [type] $id 
 * @return [type]     
 */
function real_handle_property_listing_attachments2($id)
{
	if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
		header('HTTP/1.0 400 Bad error');
		echo _perfex_upload_error($_FILES['file']['error']);
		die;
	}
	$path = PROPERTY_MAIN_IMAGE_UPLOAD . $id . '/';
	$CI   = & get_instance();
	if (isset($_FILES['file']['name'])) {
		// delete old main photo
		$folder_name = PROPERTY_MAIN_IMAGE_UPLOAD;
		if (file_exists($folder_name .$id)) {
			delete_dir($folder_name .$id);
		}

        // Get the temp file path
		$tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {

			_maybe_create_upload_path($path);

			$filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

			$newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {

				$CI                       = & get_instance();
				$config                   = [];
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'thumb_' . $new_filename;
				$config['maintain_ratio'] = true;
				$config['width']          = 300;
				$config['height']         = 300;
				$config['maintain_ratio'] = true;
				$config['create_thumb']   = true;

				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
				$CI->image_lib->clear();
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'small_' . $new_filename;
				$config['maintain_ratio'] = true;
				$config['width']          = 40;
				$config['height']         = 40;
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();


				$CI->db->where('id', $id);
                $CI->db->update(db_prefix() . 'items', [
                    'primary_image' => $new_filename,
                ]);
			}
		}
	}
}

function real_handle_property_listing_attachments3($id)
{
	if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
		header('HTTP/1.0 400 Bad error');
		echo _perfex_upload_error($_FILES['file']['error']);
		die;
	}
	$path = PROPERTY_VIDEO_UPLOAD . $id . '/';
	$CI   = & get_instance();

	if (isset($_FILES['file']['name'])) {

        // Get the temp file path
		$tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {

			_maybe_create_upload_path($path);

			$filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

			$newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {

				$attachment   = [];
				$attachment[] = [
					'file_name' => $new_filename,
					'filetype'  => $_FILES['file']['type'],
				];

				$CI->misc_model->add_attachment_to_database($id, 'property_video', $attachment);
			}
		}
	}
}

/**
 * main photo
 * @param  [type] $id 
 * @return [type]     
 */
function main_photo($id, $primary_image = false)
{
	$for_sale_url = site_url('modules/realestate/assets/images/property_for_sale.jpg');
	$for_rent_url = site_url('modules/realestate/assets/images/property_for_rent.jpg');
	$transaction_type = 'Sale';

	$CI   = & get_instance();
	$CI->load->model('realestate/realestate_model');
	$property = $CI->realestate_model->get_property_listing($id);
	if($property){
		$transaction_type = $property->transaction_type;
		$primary_image = $property->primary_image;
	}

	if (file_exists(PROPERTY_MAIN_IMAGE_UPLOAD  .''.$id.'/thumb_'.$primary_image)) {
		$site_url = site_url('modules/realestate/uploads/main_images/'.$id.'/thumb_'.$primary_image); 
	}elseif (file_exists(PROPERTY_MAIN_IMAGE_UPLOAD  .''.$id.'/'.$primary_image)) {
		$site_url = site_url('modules/realestate/uploads/main_images/'.$id.'/'.$primary_image); 
	}else{
		if($transaction_type == 'Sale'){
			$site_url = $for_sale_url;
		}else{
			$site_url = $for_rent_url;
		}
	}
	return $site_url;
}

/**
 * rel bed filter
 * @return [type] 
 */
function rel_bed_filter()
{

	$array_data = [];
	$array_data = [
		
		[
			'name' => '0',
			'label' => _l('real_Beds'),
		],
		[
			'name' => 1,
			'label' => _l('real_1_label'),
		],
		[
			'name' => 2,
			'label' => _l('real_2_label'),
		],
		[
			'name' => 3,
			'label' => _l('real_3_label'),
		],
		[
			'name' => 4,
			'label' => _l('real_4_label'),
		],
		[
			'name' => 5,
			'label' => _l('real_5_label'),
		],
		
	];

	return $array_data;
}

/**
 * rel baths filter
 * @return [type] 
 */
function rel_baths_filter()
{

	$array_data = [];
	$array_data = [
		
		[
			'name' => '0',
			'label' => _l('real_baths'),
		],
		[
			'name' => 1,
			'label' => _l('real_1_plus_label'),
		],
		[
			'name' => 2,
			'label' => _l('real_2_plus_label'),
		],
		[
			'name' => 3,
			'label' => _l('real_3_plus_label'),
		],
		[
			'name' => 4,
			'label' => _l('real_4_plus_label'),
		],
		[
			'name' => 5,
			'label' => _l('real_5_plus_label'),
		],
	];

	return $array_data;
}

/**
 * rel garages filter
 * @return [type] 
 */
function rel_garages_filter()
{

	$array_data = [];
	$array_data = [
		
		[
			'name' => '0',
			'label' => _l('real_garage'),
		],
		[
			'name' => 1,
			'label' => _l('real_1_plus_label'),
		],
		[
			'name' => 2,
			'label' => _l('real_2_plus_label'),
		],
		[
			'name' => 3,
			'label' => _l('real_3_plus_label'),
		],
		[
			'name' => 4,
			'label' => _l('real_4_plus_label'),
		],
		[
			'name' => 5,
			'label' => _l('real_5_plus_label'),
		],
	];

	return $array_data;
}

/**
 * rel lands filter
 * @return [type] 
 */
function rel_lands_min_filter()
{
	$array_data = [];
	$array_data = [
		
		[
			'name' => '0',
			'label' => _l('real_any'),
		],
		[
			'name' => '20',
			'label' => _l('real_20_m2_label'),
		],
		[
			'name' => '50',
			'label' => _l('real_50_m2_label'),
		],
		[
			'name' => '100',
			'label' => _l('real_100_m2_label'),
		],
		[
			'name' => '200',
			'label' => _l('real_200_m2_label'),
		],
		[
			'name' => '300',
			'label' => _l('real_300_m2_label'),
		],
		[
			'name' => '300',
			'label' => _l('real_300_m2_label'),
		],
		[
			'name' => '400',
			'label' => _l('real_400_m2_label'),
		],
		[
			'name' => '500',
			'label' => _l('real_500_m2_label'),
		],
		[
			'name' => '600',
			'label' => _l('real_600_m2_label'),
		],
		[
			'name' => '700',
			'label' => _l('real_700_m2_label'),
		],
		[
			'name' => '800',
			'label' => _l('real_800_m2_label'),
		],
		[
			'name' => '900',
			'label' => _l('real_900_m2_label'),
		],
		[
			'name' => '1000',
			'label' => _l('real_1000_m2_label'),
		],
		[
			'name' => '1500',
			'label' => _l('real_1500_m2_label'),
		],
		[
			'name' => '1750',
			'label' => _l('real_1750_m2_label'),
		],
		[
			'name' => '2000',
			'label' => _l('real_2000_m2_label'),
		],
		[
			'name' => '3000',
			'label' => _l('real_3000_m2_label'),
		],
		[
			'name' => '4000',
			'label' => _l('real_4000_m2_label'),
		],
		[
			'name' => '5000',
			'label' => _l('real_5000_m2_label'),
		],
		[
			'name' => '10000',
			'label' => _l('real_1_ha_label'),
		],
		[
			'name' => '20000',
			'label' => _l('real_2_ha_label'),
		],
		[
			'name' => '30000',
			'label' => _l('real_3_ha_label'),
		],
		[
			'name' => '40000',
			'label' => _l('real_4_ha_label'),
		],
		[
			'name' => '50000',
			'label' => _l('real_5_ha_label'),
		],
		
		[
			'name' => '100000',
			'label' => _l('real_10_ha_label'),
		],
		[
			'name' => '200000',
			'label' => _l('real_20_ha_label'),
		],
		[
			'name' => '300000',
			'label' => _l('real_30_ha_label'),
		],
		[
			'name' => '400000',
			'label' => _l('real_40_ha_label'),
		],
		[
			'name' => '500000',
			'label' => _l('real_50_ha_label'),
		],
		[
			'name' => '600000',
			'label' => _l('real_60_ha_label'),
		],
		[
			'name' => '700000',
			'label' => _l('real_70_ha_label'),
		],
		[
			'name' => '800000',
			'label' => _l('real_80_ha_label'),
		],
		[
			'name' => '900000',
			'label' => _l('real_90_ha_label'),
		],
		[
			'name' => '1000000',
			'label' => _l('real_100_ha_label'),
		],
		[
			'name' => '1500000',
			'label' => _l('real_150_ha_label'),
		],
		[
			'name' => '2000000',
			'label' => _l('real_200_ha_label'),
		],
		[
			'name' => '3000000',
			'label' => _l('real_300_ha_label'),
		],
		[
			'name' => '5000000',
			'label' => _l('real_500_ha_label'),
		],
		[
			'name' => '10000000',
			'label' => _l('real_1000_ha_label'),
		],
		[
			'name' => '30000000',
			'label' => _l('real_3000_ha_label'),
		],
		[
			'name' => '50000000',
			'label' => _l('real_5000_ha_label'),
		],
		[
			'name' => '100000000',
			'label' => _l('real_10000_ha_label'),
		],
		
	];

	return $array_data;
}

/**
 * rel lands filter
 * @return [type] 
 */
function rel_lands_max_filter()
{
	$array_data = [];
	$array_data = [
		
		[
			'name' => '0',
			'label' => _l('real_any'),
		],
		
		[
			'name' => '100',
			'label' => _l('real_100_m2_label'),
		],
		[
			'name' => '500',
			'label' => _l('real_500_m2_label'),
		],
		[
			'name' => '1000',
			'label' => _l('real_1000_m2_label'),
		],
		[
			'name' => '2000',
			'label' => _l('real_2000_m2_label'),
		],
		[
			'name' => '3000',
			'label' => _l('real_3000_m2_label'),
		],
		[
			'name' => '4000',
			'label' => _l('real_4000_m2_label'),
		],
		[
			'name' => '5000',
			'label' => _l('real_5000_m2_label'),
		],
		[
			'name' => '10000',
			'label' => _l('real_1_ha_label'),
		],
		[
			'name' => '20000',
			'label' => _l('real_2_ha_label'),
		],
		[
			'name' => '30000',
			'label' => _l('real_3_ha_label'),
		],
		[
			'name' => '40000',
			'label' => _l('real_4_ha_label'),
		],
		[
			'name' => '50000',
			'label' => _l('real_5_ha_label'),
		],
		
		[
			'name' => '100000',
			'label' => _l('real_10_ha_label'),
		],
		[
			'name' => '200000',
			'label' => _l('real_20_ha_label'),
		],
		[
			'name' => '300000',
			'label' => _l('real_30_ha_label'),
		],
		[
			'name' => '400000',
			'label' => _l('real_40_ha_label'),
		],
		[
			'name' => '500000',
			'label' => _l('real_50_ha_label'),
		],
		[
			'name' => '600000',
			'label' => _l('real_60_ha_label'),
		],
		[
			'name' => '700000',
			'label' => _l('real_70_ha_label'),
		],
		[
			'name' => '800000',
			'label' => _l('real_80_ha_label'),
		],
		[
			'name' => '900000',
			'label' => _l('real_90_ha_label'),
		],
		[
			'name' => '1000000',
			'label' => _l('real_100_ha_label'),
		],
		[
			'name' => '1500000',
			'label' => _l('real_150_ha_label'),
		],
		[
			'name' => '2000000',
			'label' => _l('real_200_ha_label'),
		],
		[
			'name' => '3000000',
			'label' => _l('real_300_ha_label'),
		],
		[
			'name' => '5000000',
			'label' => _l('real_500_ha_label'),
		],
		[
			'name' => '10000000',
			'label' => _l('real_1000_ha_label'),
		],
		[
			'name' => '30000000',
			'label' => _l('real_3000_ha_label'),
		],
		[
			'name' => '50000000',
			'label' => _l('real_5000_ha_label'),
		],
		[
			'name' => '100000000',
			'label' => _l('real_10000_ha_label'),
		],
		
	];

	return $array_data;
}

/**
 * rel pool features
 * @return [type] 
 */
function real_pool_features()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Above_Ground',
			'label' => _l('rel_Above_Ground'),
		],
		[
			'name' => 'Auto_Cleaner',
			'label' => _l('rel_Auto_Cleaner'),
		],
		[
			'name' => 'Child_Safety_Fence',
			'label' => _l('rel_Child_Safety_Fence'),
		],
		[
			'name' => 'Chlorine_Free',
			'label' => _l('rel_Chlorine_Free'),
		],
		[
			'name' => 'Deck',
			'label' => _l('rel_Deck'),
		],
		[
			'name' => 'Diving_Board',
			'label' => _l('rel_Diving_Board'),
		],
		[
			'name' => 'Fiber_Optic_Lighting',
			'label' => _l('rel_Fiber_Optic_Lighting'),
		],
		[
			'name' => 'Fiberglass',
			'label' => _l('rel_Fiberglass'),
		],
		[
			'name' => 'Gunite_Concrete',
			'label' => _l('rel_Gunite_Concrete'),
		],
		[
			'name' => 'Heated',
			'label' => _l('rel_Heated'),
		],
		[
			'name' => 'In_Ground',
			'label' => _l('rel_In_Ground'),
		],
		[
			'name' => 'Indoor',
			'label' => _l('rel_Indoor'),
		],
		[
			'name' => 'Infinity',
			'label' => _l('rel_Infinity'),
		],
		[
			'name' => 'Lap_Pool',
			'label' => _l('rel_Lap_Pool'),
		],
		[
			'name' => 'Lighting',
			'label' => _l('rel_Lighting'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Outside_Bath_Access',
			'label' => _l('rel_Outside_Bath_Access'),
		],
		[
			'name' => 'Pool_Alarm',
			'label' => _l('rel_Pool_Alarm'),
		],
		[
			'name' => 'Pool_Sweep',
			'label' => _l('rel_Pool_Sweep'),
		],
		[
			'name' => 'Salt_Water',
			'label' => _l('rel_Salt_Water'),
		],
		[
			'name' => 'Screen_enclosure',
			'label' => _l('rel_Screen_enclosure'),
		],
		[
			'name' => 'Self_Cleaning',
			'label' => _l('rel_Self_Cleaning'),
		],
		[
			'name' => 'Solar_Cover',
			'label' => _l('rel_Solar_Cover'),
		],
		[
			'name' => 'Solar_Heat',
			'label' => _l('rel_Solar_Heat'),
		],
		[
			'name' => 'Solar_Power_Pump',
			'label' => _l('rel_Solar_Power_Pump'),
		],
		[
			'name' => 'Tile',
			'label' => _l('rel_Tile'),
		],
		[
			'name' => 'Vinyl',
			'label' => _l('rel_Vinyl'),
		],
	];

	return $array_data;
}

/**
 * rel spa
 * @return [type] 
 */
function real_spa()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'yes',
			'label' => _l('rel_yes'),
		],
		[
			'name' => 'no',
			'label' => _l('rel_no'),
		],
		
	];

	return $array_data;
}

/**
 * rel spa features
 * @return [type] 
 */
function real_spa_features()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Heated',
			'label' => _l('rel_Heated'),
		],
		[
			'name' => 'In_Ground',
			'label' => _l('rel_In_Ground'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Swim_Spa',
			'label' => _l('rel_Swim_Spa'),
		],
		
	];

	return $array_data;
}


/**
 * rel front exposure
 * @return [type] 
 */
function real_front_exposure()
{
	$array_data = [];
	$array_data = [
		[
			'name' => 'East',
			'label' => _l('rel_East'),
		],
		[
			'name' => 'North',
			'label' => _l('rel_North'),
		],
		[
			'name' => 'Northeast',
			'label' => _l('rel_Northeast'),
		],
		[
			'name' => 'Northwest',
			'label' => _l('rel_Northwest'),
		],
		[
			'name' => 'South',
			'label' => _l('rel_South'),
		],
		[
			'name' => 'Southeast',
			'label' => _l('rel_Southeast'),
		],
		[
			'name' => 'Southwest',
			'label' => _l('rel_Southwest'),
		],
		[
			'name' => 'West',
			'label' => _l('rel_West'),
		],
		[
			'name' => 'Undetermined',
			'label' => _l('rel_Undetermined'),
		],	

	];

	return $array_data;
}

/**
 * rel easements
 * @return [type] 
 */
function real_easements()
{

	$array_data = [];
	$array_data = [
		[
			'name' => 'All_Sides',
			'label' => _l('rel_All_Sides'),
		],
		[
			'name' => 'East_Side',
			'label' => _l('rel_East_Side'),
		],
		[
			'name' => 'Electric_Lines',
			'label' => _l('rel_Electric_Lines'),
		],
		[
			'name' => 'Environmental',
			'label' => _l('rel_Environmental'),
		],
		[
			'name' => 'Ingress_Egress',
			'label' => _l('rel_Ingress_Egress'),
		],
		[
			'name' => 'Less_Easement',
			'label' => _l('rel_Less_Easement'),
		],
		[
			'name' => 'North_Side',
			'label' => _l('rel_North_Side'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Pipelines',
			'label' => _l('rel_Pipelines'),
		],
		[
			'name' => 'Prescriptive_Right_of_Way',
			'label' => _l('rel_Prescriptive_Right_of_Way'),
		],
		[
			'name' => 'Preservation',
			'label' => _l('rel_Preservation'),
		],
		[
			'name' => 'South_Side',
			'label' => _l('rel_South_Side'),
		],
		[
			'name' => 'Subject_to_Easement',
			'label' => _l('rel_Subject_to_Easement'),
		],
		[
			'name' => 'Water_Rights',
			'label' => _l('rel_Water_Rights'),
		],
		[
			'name' => 'West_Side',
			'label' => _l('rel_West_Side'),
		],
			

	];

	return $array_data;
}

/**
 * rel road frontage
 * @return [type] 
 */
function real_road_frontage()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Business_District',
			'label' => _l('rel_Business_District'),
		],
		[
			'name' => 'City_Street',
			'label' => _l('rel_City_Street'),
		],
		[
			'name' => 'County_Road',
			'label' => _l('rel_County_Road'),
		],
		[
			'name' => 'Divided_Highway',
			'label' => _l('rel_Divided_Highway'),
		],
		[
			'name' => 'Easement',
			'label' => _l('rel_Easement'),
		],
		[
			'name' => 'Highway',
			'label' => _l('rel_Highway'),
		],
		[
			'name' => 'Interchange',
			'label' => _l('rel_Interchange'),
		],
		[
			'name' => 'Interstate',
			'label' => _l('rel_Interstate'),
		],
		[
			'name' => 'Main_Thoroughfare',
			'label' => _l('rel_Main_Thoroughfare'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Private_Road',
			'label' => _l('rel_Private_Road'),
		],
		[
			'name' => 'State_Road',
			'label' => _l('rel_State_Road'),
		],
		[
			'name' => 'Turn_Lanes',
			'label' => _l('rel_Turn_Lanes'),
		],
		[
			'name' => 'Alley',
			'label' => _l('rel_Alley'),
		],
		[
			'name' => 'Rail',
			'label' => _l('rel_Rail'),
		],
		[
			'name' => 'Access_Road',
			'label' => _l('rel_Access_Road'),
		],
		
	];

	return $array_data;
}

/**
 * rel road surface type
 * @return [type] 
 */
function real_road_surface_type()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Asphalt',
			'label' => _l('rel_Asphalt'),
		],
		[
			'name' => 'Brick',
			'label' => _l('rel_Brick'),
		],
		[
			'name' => 'Chip_And_Seal',
			'label' => _l('rel_Chip_And_Seal'),
		],
		[
			'name' => 'Concrete',
			'label' => _l('rel_Concrete'),
		],
		[
			'name' => 'Dirt',
			'label' => _l('rel_Dirt'),
		],
		[
			'name' => 'Gravel',
			'label' => _l('rel_Gravel'),
		],
		[
			'name' => 'Limerock',
			'label' => _l('rel_Limerock'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Paved',
			'label' => _l('rel_Paved'),
		],
		[
			'name' => 'Unimproved',
			'label' => _l('rel_Unimproved'),
		],
		
	];

	return $array_data;
}

/**
 * rel road responsibility
 * @return [type] 
 */
function real_road_responsibility()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Private_Maintained_Road',
			'label' => _l('rel_Private_Maintained_Road'),
		],
		[
			'name' => 'Public_Maintained_Road',
			'label' => _l('rel_Public_Maintained_Road'),
		],
		[
			'name' => 'Road_Maintenance_Agreement',
			'label' => _l('rel_Road_Maintenance_Agreement'),
		],
		
	];

	return $array_data;
}

/**
 * rel signage
 * @return [type] 
 */
function real_signage()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Directory',
			'label' => _l('rel_Directory'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'On_Building',
			'label' => _l('rel_On_Building'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Street_Signage',
			'label' => _l('rel_Street_Signage'),
		],
		
		
	];

	return $array_data;
}

/**
 * rel adjoining property
 * @return [type] 
 */
function real_adjoining_property()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Airport',
			'label' => _l('rel_Airport'),
		],
		[
			'name' => 'Church',
			'label' => _l('rel_Church'),
		],
		[
			'name' => 'Commercial',
			'label' => _l('rel_Commercial'),
		],
		[
			'name' => 'Hotel_Motel',
			'label' => _l('rel_Hotel_Motel'),
		],
		[
			'name' => 'Industrial',
			'label' => _l('rel_Industrial'),
		],
		[
			'name' => 'Multi_Family',
			'label' => _l('rel_Multi_Family'),
		],
		[
			'name' => 'Natural_State',
			'label' => _l('rel_Natural_State'),
		],
		[
			'name' => 'Professional_Office',
			'label' => _l('rel_Professional_Office'),
		],
		[
			'name' => 'Railroad',
			'label' => _l('rel_Railroad'),
		],
		[
			'name' => 'Residential',
			'label' => _l('rel_Residential'),
		],
		[
			'name' => 'School',
			'label' => _l('rel_School'),
		],
		[
			'name' => 'Undeveloped',
			'label' => _l('rel_Undeveloped'),
		],
		[
			'name' => 'Vacant',
			'label' => _l('rel_Vacant'),
		],
		[
			'name' => 'Waterway',
			'label' => _l('rel_Waterway'),
		],
		
	];

	return $array_data;
}

/**
 * rel other structures
 * @return [type] 
 */
function real_other_structures()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Airplane',
			'label' => _l('rel_Airplane'),
		],
		[
			'name' => 'Annex',
			'label' => _l('rel_Annex'),
		],
		[
			'name' => 'Containers',
			'label' => _l('rel_Containers'),
		],
		[
			'name' => 'Garages',
			'label' => _l('rel_Garages'),
		],
		[
			'name' => 'Maintenance',
			'label' => _l('rel_Maintenance'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Security_Trailer',
			'label' => _l('rel_Security_Trailer'),
		],
		[
			'name' => 'Storage',
			'label' => _l('rel_Storage'),
		],
		[
			'name' => 'Workshop',
			'label' => _l('rel_Workshop'),
		],
		
	];

	return $array_data;
}

/**
 * rel other equipment
 * @return [type] 
 */
function real_other_equipment()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Compressor',
			'label' => _l('rel_Compressor'),
		],
		[
			'name' => 'Conveyor_System',
			'label' => _l('rel_Conveyor_System'),
		],
		[
			'name' => 'Dehumdifier',
			'label' => _l('rel_Dehumdifier'),
		],
		[
			'name' => 'Feeding_Stations',
			'label' => _l('rel_Feeding_Stations'),
		],
		[
			'name' => 'Fuel_Tanks',
			'label' => _l('rel_Fuel_Tanks'),
		],
		[
			'name' => 'Grease_Trap',
			'label' => _l('rel_Grease_Trap'),
		],
		[
			'name' => 'Intercom',
			'label' => _l('rel_Intercom'),
		],
		[
			'name' => 'Irrigation_Equipment',
			'label' => _l('rel_Irrigation_Equipment'),
		],
		[
			'name' => 'Livestock_Equipment',
			'label' => _l('rel_Livestock_Equipment'),
		],
		[
			'name' => 'Loading_Pens',
			'label' => _l('rel_Loading_Pens'),
		],
		[
			'name' => 'Overhead_Crane',
			'label' => _l('rel_Overhead_Crane'),
		],
		[
			'name' => 'Satellite_Dish',
			'label' => _l('rel_Satellite_Dish'),
		],
		
	];

	return $array_data;
}

/**
 * rel vegetation
 * @return [type] 
 */
function real_vegetation()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Bamboo',
			'label' => _l('rel_Bamboo'),
		],
		[
			'name' => 'Fruit',
			'label' => _l('rel_Fruit'),
		],
		[
			'name' => 'Trees',
			'label' => _l('rel_Trees'),
		],
		[
			'name' => 'Mature_Landscaping',
			'label' => _l('rel_Mature_Landscaping'),
		],
		[
			'name' => 'Oak_Trees',
			'label' => _l('rel_Oak_Trees'),
		],
		[
			'name' => 'Trees_Landscaped',
			'label' => _l('rel_Trees_Landscaped'),
		],
		[
			'name' => 'Wooded',
			'label' => _l('rel_Wooded'),
		],
		
	];

	return $array_data;
}

/**
 * rel lot features
 * @return [type] 
 */
function real_lot_features()
{

	$array_data = [];
	$array_data = [
		[
			'name' => 'Central_Business_District',
			'label' => _l('rel_Central_Business_District'),
		],
		[
			'name' => 'Corner_Lot',
			'label' => _l('rel_Corner_Lot'),
		],
		[
			'name' => 'Cul_De_Sac',
			'label' => _l('rel_Cul_De_Sac'),
		],
		[
			'name' => 'Curb_and_Gutters',
			'label' => _l('rel_Curb_and_Gutters'),
		],
		[
			'name' => 'Drainage_Canal',
			'label' => _l('rel_Drainage_Canal'),
		],
		[
			'name' => 'Farm',
			'label' => _l('rel_Farm'),
		],
		[
			'name' => 'Fire_Hydrant',
			'label' => _l('rel_Fire_Hydrant'),
		],
		[
			'name' => 'Flood_Insurance_Required',
			'label' => _l('rel_Flood_Insurance_Required'),
		],
		[
			'name' => 'Flood_Zone',
			'label' => _l('rel_Flood_Zone'),
		],
		[
			'name' => 'Historic_District',
			'label' => _l('rel_Historic_District'),
		],
		[
			'name' => 'In_City_Limits',
			'label' => _l('rel_In_City_Limits'),
		],
		[
			'name' => 'Industrial_Condo',
			'label' => _l('rel_Industrial_Condo'),
		],
		[
			'name' => 'Industrial_Park',
			'label' => _l('rel_Industrial_Park'),
		],
		[
			'name' => 'Interior_Lot',
			'label' => _l('rel_Interior_Lot'),
		],
		[
			'name' => 'Landscaped',
			'label' => _l('rel_Landscaped'),
		],
		[
			'name' => 'Near_Golf_Course',
			'label' => _l('rel_Near_Golf_Course'),
		],
		[
			'name' => 'Near_Public_Transit',
			'label' => _l('rel_Near_Public_Transit'),
		],
		[
			'name' => 'Near_Railroad_Siding',
			'label' => _l('rel_Near_Railroad_Siding'),
		],
		[
			'name' => 'Neighborhood',
			'label' => _l('rel_Neighborhood'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Out_Parcel',
			'label' => _l('rel_Out_Parcel'),
		],
		[
			'name' => 'Oversized_Lot',
			'label' => _l('rel_Oversized_Lot'),
		],
		[
			'name' => 'Railroad',
			'label' => _l('rel_Railroad'),
		],
		[
			'name' => 'Retail_Condo',
			'label' => _l('rel_Retail_Condo'),
		],
		[
			'name' => 'Retention_Areas',
			'label' => _l('rel_Retention_Areas'),
		],
		[
			'name' => 'Retention_Pond',
			'label' => _l('rel_Retention_Pond'),
		],
		[
			'name' => 'Riparian_Rights',
			'label' => _l('rel_Riparian_Rights'),
		],
		[
			'name' => 'Rolling_Slope',
			'label' => _l('rel_Rolling_Slope'),
		],
		[
			'name' => 'Rural',
			'label' => _l('rel_Rural'),
		],
		[
			'name' => 'Seaport',
			'label' => _l('rel_Seaport'),
		],
		[
			'name' => 'Shopping_Center',
			'label' => _l('rel_Shopping_Center'),
		],
		[
			'name' => 'Sidewalks',
			'label' => _l('rel_Sidewalks'),
		],
		[
			'name' => 'Sloped',
			'label' => _l('rel_Sloped'),
		],
		[
			'name' => 'Special_Taxing_District',
			'label' => _l('rel_Special_Taxing_District'),
		],
		[
			'name' => 'Street_Lights',
			'label' => _l('rel_Street_Lights'),
		],
		[
			'name' => 'Street_Paved',
			'label' => _l('rel_Street_Paved'),
		],
		[
			'name' => 'Suburb',
			'label' => _l('rel_Suburb'),
		],
		[
			'name' => 'Turn_Around',
			'label' => _l('rel_Turn_Around'),
		],
		[
			'name' => 'Undeveloped',
			'label' => _l('rel_Undeveloped'),
		],
		[
			'name' => 'Waterfront',
			'label' => _l('rel_Waterfront'),
		],
		[
			'name' => 'Wooded',
			'label' => _l('rel_Wooded'),
		],
		[
			'name' => 'Zoned_For_Horses',
			'label' => _l('rel_Zoned_For_Horses'),
		],
		
	];

	return $array_data;
}

/**
 * rel exterior construction
 * @return [type] 
 */
function real_exterior_construction()
{
	$array_data = [];
	$array_data = [

		[
			'name' => 'Asbestos',
			'label' => _l('rel_Asbestos'),
		],
		[
			'name' => 'Block',
			'label' => _l('rel_Block'),
		],
		[
			'name' => 'Brick',
			'label' => _l('rel_Brick'),
		],
		[
			'name' => 'Cedar',
			'label' => _l('rel_Cedar'),
		],
		[
			'name' => 'Cement_Siding',
			'label' => _l('rel_Cement_Siding'),
		],
		[
			'name' => 'Concrete',
			'label' => _l('rel_Concrete'),
		],
		[
			'name' => 'HardiPlank_Type',
			'label' => _l('rel_HardiPlank_Type'),
		],
		[
			'name' => 'ICFs_Insulated_Concrete_Forms',
			'label' => _l('rel_ICFs_Insulated_Concrete_Forms'),
		],
		[
			'name' => 'Log',
			'label' => _l('rel_Log'),
		],
		[
			'name' => 'Metal_Frame',
			'label' => _l('rel_Metal_Frame'),
		],
		[
			'name' => 'Metal_Siding',
			'label' => _l('rel_Metal_Siding'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'SIP_Structurally_Insulated_Panel',
			'label' => _l('rel_SIP_Structurally_Insulated_Panel'),
		],
		[
			'name' => 'Stone',
			'label' => _l('rel_Stone'),
		],
		[
			'name' => 'Stucco',
			'label' => _l('rel_Stucco'),
		],
		[
			'name' => 'Tilt_Up_Walls',
			'label' => _l('rel_Tilt_Up_Walls'),
		],
		[
			'name' => 'Vinyl_Siding',
			'label' => _l('rel_Vinyl_Siding'),
		],
		[
			'name' => 'Wood_Frame',
			'label' => _l('rel_Wood_Frame'),
		],
		[
			'name' => 'Wood_Frame_FSC',
			'label' => _l('rel_Wood_Frame_FSC'),
		],
		[
			'name' => 'Wood_Siding',
			'label' => _l('rel_Wood_Siding'),
		],
		
	];

	return $array_data;
}

/**
 * rel roof
 * @return [type] 
 */
function real_roof()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Built_Up',
			'label' => _l('rel_Built_Up'),
		],
		[
			'name' => 'Roof_Over',
			'label' => _l('rel_Roof_Over'),
		],
		[
			'name' => 'Cement',
			'label' => _l('rel_Cement'),
		],
		[
			'name' => 'Shake',
			'label' => _l('rel_Shake'),
		],
		[
			'name' => 'Concrete',
			'label' => _l('rel_Concrete'),
		],
		[
			'name' => 'Shingle',
			'label' => _l('rel_Shingle'),
		],
		[
			'name' => 'Membrane',
			'label' => _l('rel_Membrane'),
		],
		[
			'name' => 'Slate',
			'label' => _l('rel_Slate'),
		],
		[
			'name' => 'Metal',
			'label' => _l('rel_Metal'),
		],
		[
			'name' => 'Tile',
			'label' => _l('rel_Tile'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		
	];

	return $array_data;
}

/**
 * rel building features
 * @return [type] 
 */
function real_building_features()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Bathrooms',
			'label' => _l('rel_Bathrooms'),
		],
		[
			'name' => 'Clear_Span',
			'label' => _l('rel_Clear_Span'),
		],
		[
			'name' => 'Columns',
			'label' => _l('rel_Columns'),
		],
		[
			'name' => 'Common_Lighting',
			'label' => _l('rel_Common_Lighting'),
		],
		[
			'name' => 'Drive_Through',
			'label' => _l('rel_Drive_Through'),
		],
		[
			'name' => 'Dumpsters',
			'label' => _l('rel_Dumpsters'),
		],
		[
			'name' => 'Elevator',
			'label' => _l('rel_Elevator'),
		],
		[
			'name' => 'Elevator_None',
			'label' => _l('rel_Elevator_None'),
		],
		[
			'name' => 'Extra_Storage',
			'label' => _l('rel_Extra_Storage'),
		],
		[
			'name' => 'Fencing',
			'label' => _l('rel_Fencing'),
		],
		[
			'name' => 'Fiber_Optic',
			'label' => _l('rel_Fiber_Optic'),
		],
		[
			'name' => 'Freight_Elevator',
			'label' => _l('rel_Freight_Elevator'),
		],
		[
			'name' => 'Furnished',
			'label' => _l('rel_Furnished'),
		],
		[
			'name' => 'High_Bays',
			'label' => _l('rel_High_Bays'),
		],
		[
			'name' => 'Janitorial_Services',
			'label' => _l('rel_Janitorial_Services'),
		],
		[
			'name' => 'Kitchen_Facility',
			'label' => _l('rel_Kitchen_Facility'),
		],
		[
			'name' => 'Lit_Sign_on_Site',
			'label' => _l('rel_Lit_Sign_on_Site'),
		],
		[
			'name' => 'Loading_Dock',
			'label' => _l('rel_Loading_Dock'),
		],
		[
			'name' => 'Loft',
			'label' => _l('rel_Loft'),
		],
		[
			'name' => 'Medical_Disposal',
			'label' => _l('rel_Medical_Disposal'),
		],
		[
			'name' => 'On_Site_Shower',
			'label' => _l('rel_On_Site_Shower'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Outside_Storage',
			'label' => _l('rel_Outside_Storage'),
		],
		[
			'name' => 'Overhead_Doors',
			'label' => _l('rel_Overhead_Doors'),
		],
		[
			'name' => 'Ramp',
			'label' => _l('rel_Ramp'),
		],
		[
			'name' => 'Reception',
			'label' => _l('rel_Reception'),
		],
		[
			'name' => 'Seating',
			'label' => _l('rel_Seating'),
		],
		[
			'name' => 'Service_Stations',
			'label' => _l('rel_Service_Stations'),
		],
		[
			'name' => 'Solid_Surface_Counter',
			'label' => _l('rel_Solid_Surface_Counter'),
		],
		[
			'name' => 'Stone_Counter',
			'label' => _l('rel_Stone_Counter'),
		],
		[
			'name' => 'Trash_Removal',
			'label' => _l('rel_Trash_Removal'),
		],
		[
			'name' => 'Truck_Doors',
			'label' => _l('rel_Truck_Doors'),
		],
		[
			'name' => 'Truck_Well',
			'label' => _l('rel_Truck_Well'),
		],
		[
			'name' => 'Waiting_Room',
			'label' => _l('rel_Waiting_Room'),
		],
	];

	return $array_data;
}

/**
 * rel garage parking features
 * @return [type] 
 */
function real_garage_parking_features()
{

	$array_data = [];
	$array_data = [
		[
			'name' => '1_to_5_Spaces',
			'label' => _l('rel_1_to_5_Spaces'),
		],
		[
			'name' => '6_to_12_Spaces',
			'label' => _l('rel_6_to_12_Spaces'),
		],
		[
			'name' => '13_to_18_Spaces',
			'label' => _l('rel_13_to_18_Spaces'),
		],
		[
			'name' => '19_to_30_Spaces',
			'label' => _l('rel_19_to_30_Spaces'),
		],
		[
			'name' => 'Airplane_Hangar',
			'label' => _l('rel_Airplane_Hangar'),
		],
		[
			'name' => 'Common',
			'label' => _l('rel_Common'),
		],
		[
			'name' => 'Curb_Parking',
			'label' => _l('rel_Curb_Parking'),
		],
		[
			'name' => 'Electric_Vehicle_Charging_Stations',
			'label' => _l('rel_Electric_Vehicle_Charging_Stations'),
		],
		[
			'name' => 'Ground_Level',
			'label' => _l('rel_Ground_Level'),
		],
		[
			'name' => 'Lighted',
			'label' => _l('rel_Lighted'),
		],
		[
			'name' => 'None',
			'label' => _l('rel_None'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Over_30_Spaces',
			'label' => _l('rel_Over_30_Spaces'),
		],
		[
			'name' => 'Secured',
			'label' => _l('rel_Secured'),
		],
		[
			'name' => 'Under_Building',
			'label' => _l('rel_Under_Building'),
		],
		[
			'name' => 'Underground',
			'label' => _l('rel_Underground'),
		],
		[
			'name' => 'Valet',
			'label' => _l('rel_Valet'),
		],
		[
			'name' => 'Deeded',
			'label' => _l('rel_Deeded'),
		],
	];

	return $array_data;
}

/**
 * rel foundation
 * @return [type] 
 */
function real_foundation()
{


	$array_data = [];
	$array_data = [

		[
			'name' => 'Basement',
			'label' => _l('rel_Basement'),
		],
		[
			'name' => 'Block',
			'label' => _l('rel_Block'),
		],
		[
			'name' => 'Brick_Mortar',
			'label' => _l('rel_Brick_Mortar'),
		],
		[
			'name' => 'Concrete_Perimeter',
			'label' => _l('rel_Concrete_Perimeter'),
		],
		[
			'name' => 'Crawlspace',
			'label' => _l('rel_Crawlspace'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Pillar_Post_Pier',
			'label' => _l('rel_Pillar_Post_Pier'),
		],
		[
			'name' => 'Slab',
			'label' => _l('rel_Slab'),
		],
		[
			'name' => 'Stem_Wall',
			'label' => _l('rel_Stem_Wall'),
		],
		[
			'name' => 'Stilt_On_Piling',
			'label' => _l('rel_Stilt_On_Piling'),
		],

	];

	return $array_data;
}

/**
 * rel basement
 * @return [type] 
 */
function real_basement()
{

	$array_data = [];
	$array_data = [

		[
			'name' => 'Bath_Stubbed',
			'label' => _l('rel_Bath_Stubbed'),
		],
		[
			'name' => 'Crawl',
			'label' => _l('rel_Crawl'),
		],
		[
			'name' => 'Space',
			'label' => _l('rel_Space'),
		],
		[
			'name' => 'Daylight',
			'label' => _l('rel_Daylight'),
		],
		[
			'name' => 'Exterior_Entry',
			'label' => _l('rel_Exterior_Entry'),
		],
		[
			'name' => 'Finished',
			'label' => _l('rel_Finished'),
		],
		[
			'name' => 'Full',
			'label' => _l('rel_Full'),
		],
		[
			'name' => 'Interior_Entry',
			'label' => _l('rel_Interior_Entry'),
		],
		[
			'name' => 'Other',
			'label' => _l('rel_Other'),
		],
		[
			'name' => 'Partial',
			'label' => _l('rel_Partial'),
		],
		[
			'name' => 'Unfinished',
			'label' => _l('rel_Unfinished'),
		],
	];

	return $array_data;
}

/**
 * rel balcony
 * @return [type] 
 */
function real_balcony()
{
	$array_data = [];
	$array_data = [

		[
			'name' => '0',
			'label' => _l('real_0'),
		],
		[
			'name' => '1',
			'label' => _l('real_1'),
		],
		[
			'name' => '2',
			'label' => _l('real_2'),
		],
		[
			'name' => '3',
			'label' => _l('real_3'),
		],
		[
			'name' => '4',
			'label' => _l('real_4'),
		],
		[
			'name' => '5_and_More',
			'label' => _l('real_5_and_More'),
		],
		
	];

	return $array_data;
}

/**
 * get real property listing status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_real_property_listing_status_by_id($id, $type)
{
	$CI       = &get_instance();
	$statuses = rel_property_listing_status();

	$status = [
		'id'         => 'new',
		'color' => '#28b8daed',
		'name'       => _l('rel_new'),
		'order'      => 1,
	];

	foreach ($statuses as $s) {
		if ($s['id'] == $id) {
			$status = $s;

			break;
		}
	}

	return $status;
}

/**
 * render real property listing status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @param  integer $created_id   
 * @return [type]                
 */
function render_real_property_listing_status_html($id, $type, $status_value = '', $ChangeStatus = true, $created_id = 0)
{
	$status          = get_real_property_listing_status_by_id($status_value, $type);
	$task_statuses = rel_property_listing_status();
	
	$outputStatus    = '';

	$outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
	$outputStatus .= $status['name'];
	$canChangeStatus = (($created_id == get_staff_user_id() || is_admin() || is_broker_logged_in() ) && $created_id != 0 );

	if ($canChangeStatus && $ChangeStatus) {
		$outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
		$outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		$outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
		$outputStatus .= '</a>';

		$outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
		foreach ($task_statuses as $taskChangeStatus) {
			if ($status_value != $taskChangeStatus['id']) {
				$outputStatus .= '<li>
				<a href="#" onclick="property_listing_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
				' . _l('task_mark_as', $taskChangeStatus['name']) . '
				</a>
				</li>';
			}
		}
		$outputStatus .= '</ul>';
		$outputStatus .= '</div>';
	}

	$outputStatus .= '</span>';

	return $outputStatus;
}

/**
 * real_get_group_name
 * @param  [type] $id 
 * @return [type]     
 */
function real_get_group_name($id){
	if($id == 0 || $id == '' || $id == null){
		return '';
	}
	$CI = &get_instance();
	$CI->db->select('name');
	$CI->db->where('id',$id);
	$data = $CI->db->get(db_prefix().'items_groups')->row();
	if($data){
		return $data->name;
	}else{
		return '';
	}
}
/**
 * get group name
 * @param  integer $id 
 * @return string     
 */
function real_company_is_private($id){
	$CI = &get_instance();
	$CI->db->select('privacy');
	$CI->db->where('id',$id);
	$data = $CI->db->get(db_prefix().'real_companies')->row();
	if($data && $data->privacy == 'private'){
		return true;
	}else{
		return false;
	}
}

/**
 * get company name
 * @param  integer $id 
 * @return string     
 */
function real_get_company_name($id, $company_information = false, $remove_br_tag = true, $format_organization_info = false, $include_company_name = true){
	if($id == 0 || $id == '' || $id == null){
		return '';
	}
	$CI = &get_instance();
	$CI->db->select('*');
	$CI->db->where('id',$id);
	$data = $CI->db->get(db_prefix().'real_companies')->row();
	if($company_information){
		$countryCode = '';
        $countryName = '';
        $format   = get_option('customer_info_format');

        if ($country = get_country($data->country ?? 0)) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }
        if($include_company_name){
        	$format = _info_format_replace('company_name', '<b style="color:black" class="company-name-formatted">' . $data->name . '</b>', $format);
        }else{
        	$format = _info_format_replace('company_name', '', $format);
        }
        $format = _info_format_replace('customer_id', '', $format);

        $format = _info_format_replace('street', $data->address, $format);
        $format = _info_format_replace('city', $data->city, $format);
        $format = _info_format_replace('state', $data->state, $format);
        $format = _info_format_replace('zip_code', $data->zip, $format);
        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);
        $format = _info_format_replace('phone', '', $format);
        $format = _info_format_replace('vat_number', '', $format);
        $format = _info_format_replace('vat_number_with_label', '', $format);

        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        // Remove multiple coma
        $format = preg_replace('/,{2,}/m', '', $format);
        if($remove_br_tag){
        	$format = preg_replace('/<br ?\/?>/', ', ', $format);
        }
        $format = trim($format);
        if($format_organization_info){
        	return $format;
        }
		$data->company_address = $format;
		return $data;
	}

	if($data){
		return $data->name;
	}else{
		return '';
	}
}

/**
 * get base currency id
 * @return [type] 
 */
function get_base_currency_id()
{
	$get_base_currency =  get_base_currency();
	if($get_base_currency){
		$base_currency = $get_base_currency->id;
	}else{
		$base_currency = 0;
	}
	return $base_currency;
}

/**
 * merter to hectare
 * @param  [type] $merter 
 * @return [type]         
 */
function merter_to_hectare($merter)
{	
	$merter = (float)$merter;
	$converting = $merter. ' m2';
	if(($merter) >= 10000){
		$converting = $merter / 10000  .' ha';
	}
	return $converting;
}

/**
 * company profile image
 * @param  [type] $id        
 * @param  array  $classes   
 * @param  string $type      
 * @param  array  $img_attrs 
 * @return [type]            
 */
function company_profile_image($id, $classes = ['img-responsive'], $img_attrs = [])
{
    $CI           = & get_instance();
    $url = base_url('assets/images/user-placeholder.jpg');
    $_attributes = '';
    foreach ($img_attrs as $key => $val) {
        $_attributes .= $key . '=' . '"' . html_escape($val) . '" ';
    }

    $blankImageFormatted = '';

    $CI     = & get_instance();
    $result = $CI->app_object_cache->get('company-profile-image-data-' . $id);

    if (!$result) {
    	$CI->db->select('profile_image,name');
    	$CI->db->where('id', $id);
    	$result = $CI->db->get(db_prefix() . 'real_companies')->row();
    	$CI->app_object_cache->add('company-profile-image-data-' . $id, $result);
    }

    if (!$result) {
        return $blankImageFormatted;
    }

    if ($result && $result->profile_image !== null) {
        $profileImagePath = COMPANY_PATH_PROFILE_UPLOAD.$id.'/' . $result->profile_image;

        if (file_exists($profileImagePath)) {
            $profile_image = '<img ' . $_attributes . ' src="' . site_url($profileImagePath) . '" class="' . implode(' ', $classes) . '" />';
        } else {
            return $blankImageFormatted;
        }
    } else {
        $profile_image = '';
    }

    return $profile_image;
}

/**
 * handle company profile image upload
 * @param  string $company_id 
 * @return [type]             
 */
function handle_company_profile_image_upload($company_id = '')
{
	$hookData = hooks()->apply_filters('before_handle_company_profile_image_upload', [
		'company_id' => $company_id,
		'index_name' => 'profile_image',
		'handled_externally' => false, // e.g. module upload to s3
		'handled_externally_successfully' => false,
		'files' => $_FILES
	]);

	if ($hookData['handled_externally']) {
		return $hookData['handled_externally_successfully'];
	}

	if (isset($_FILES['agent_profile_image']['name']) && $_FILES['agent_profile_image']['name'] != '') {
		hooks()->do_action('before_upload_company_profile_image');
		$path = COMPANY_PROFILE_UPLOAD . $company_id . '/';

		// Get the temp file path
		$tmpFilePath = $_FILES['agent_profile_image']['tmp_name'];
		// Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {
			// Getting file extension
			$extension          = strtolower(pathinfo($_FILES['agent_profile_image']['name'], PATHINFO_EXTENSION));
			$allowed_extensions = [
				'jpg',
				'jpeg',
				'png',
			];

			$allowed_extensions = hooks()->apply_filters('company_profile_image_upload_allowed_extensions', $allowed_extensions);

			if (!in_array($extension, $allowed_extensions)) {
				set_alert('warning', _l('file_php_extension_blocked'));

				return false;
			}
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['agent_profile_image']['name']);
			$newFilePath = $path . '/' . $filename;
			// Upload the file into the company uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$CI                       = & get_instance();
				$CI->db->where('id', $company_id);
				$CI->db->update(db_prefix() . 'real_companies', [
					'profile_image' => $filename,
				]);
				return true;
			}
		}
	}

	return false;
}

/**
 * owner profile image
 * @param  [type] $id        
 * @param  array  $classes   
 * @param  array  $img_attrs 
 * @return [type]            
 */
function owner_profile_image($id, $classes = ['img-responsive'], $img_attrs = [])
{
    $CI           = & get_instance();
    $url = base_url('assets/images/user-placeholder.jpg');
    $_attributes = '';
    foreach ($img_attrs as $key => $val) {
        $_attributes .= $key . '=' . '"' . html_escape($val) . '" ';
    }

    $blankImageFormatted = '<img src="' . $url . '" ' . $_attributes . ' class="' . implode(' ', $classes) . '" />';

    $CI     = & get_instance();
    $result = $CI->app_object_cache->get('owner-profile-image-data-' . $id);

    if (!$result) {
    	$CI->db->select('profile_image,name');
    	$CI->db->where('id', $id);
    	$result = $CI->db->get(db_prefix() . 'real_property_owners')->row();
    	$CI->app_object_cache->add('owner-profile-image-data-' . $id, $result);
    }

    if (!$result) {
        return $blankImageFormatted;
    }

    if ($result && $result->profile_image !== null) {
        $profileImagePath = OWNER_PATH_PROFILE_UPLOAD.$id.'/' . $result->profile_image;

        if (file_exists($profileImagePath)) {
            $profile_image = '<img ' . $_attributes . ' src="' . site_url($profileImagePath) . '" class="' . implode(' ', $classes) . '" />';
        } else {
            return $blankImageFormatted;
        }
    } else {
        $profile_image = '<img src="' . $url . '" ' . $_attributes . ' class="' . implode(' ', $classes) . '" />';
    }

    return $profile_image;
}

/**
 * handle owner profile image upload
 * @param  string $owner_id 
 * @return [type]           
 */
function handle_owner_profile_image_upload($owner_id = '')
{
	$hookData = hooks()->apply_filters('before_handle_owner_profile_image_upload', [
		'owner_id' => $owner_id,
		'index_name' => 'profile_image',
		'handled_externally' => false, // e.g. module upload to s3
		'handled_externally_successfully' => false,
		'files' => $_FILES
	]);

	if ($hookData['handled_externally']) {
		return $hookData['handled_externally_successfully'];
	}

	if (isset($_FILES['profile_image']['name']) && $_FILES['profile_image']['name'] != '') {
		hooks()->do_action('before_upload_owner_profile_image');
		$path = OWNER_PROFILE_UPLOAD . $owner_id . '/';

		// Get the temp file path
		$tmpFilePath = $_FILES['profile_image']['tmp_name'];
		// Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {
			// Getting file extension
			$extension          = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
			$allowed_extensions = [
				'jpg',
				'jpeg',
				'png',
			];

			$allowed_extensions = hooks()->apply_filters('owner_profile_image_upload_allowed_extensions', $allowed_extensions);

			if (!in_array($extension, $allowed_extensions)) {
				set_alert('warning', _l('file_php_extension_blocked'));

				return false;
			}
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['profile_image']['name']);
			$newFilePath = $path . '/' . $filename;
			// Upload the file into the owner uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$CI                       = & get_instance();
				$CI->db->where('id', $owner_id);
				$CI->db->update(db_prefix() . 'real_property_owners', [
					'profile_image' => $filename,
				]);
				return true;
			}
		}
	}

	return false;
}

/**
 * real get owner name
 * @param  [type] $id 
 * @return [type]     
 */
function real_get_owner_name($id, $owner_information = false ){
	if($id == 0 || $id == '' || $id == null){
		return '';
	}
	$CI = &get_instance();
	$CI->db->select('*');
	$CI->db->where('id',$id);
	$data = $CI->db->get(db_prefix().'real_property_owners')->row();
	if($owner_information){
		$countryCode = '';
        $countryName = '';
        $format   = get_option('customer_info_format');

        if ($country = get_country($data->country ?? 0)) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }
        $format = _info_format_replace('company_name', '', $format);
        $format = _info_format_replace('customer_id', '', $format);

        $format = _info_format_replace('street', $data->address, $format);
        $format = _info_format_replace('city', $data->city, $format);
        $format = _info_format_replace('state', $data->state, $format);
        $format = _info_format_replace('zip_code', $data->zip, $format);
        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);
        $format = _info_format_replace('phone', '', $format);
        $format = _info_format_replace('vat_number', '', $format);
        $format = _info_format_replace('vat_number_with_label', '', $format);

        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        // Remove multiple coma
        $format = preg_replace('/,{2,}/m', '', $format);

        $format = preg_replace('/<br ?\/?>/', ', ', $format);
        $format = trim($format);

		$data->owner_address = $format;
		return $data;
	}
	if($data){
		return $data->name;
	}else{
		return '';
	}

}

/**
 * handle supporting document file
 * @param  [type] $id 
 * @return [type]     
 */
function handle_supporting_document_file($id){
	$CI           = & get_instance();
	if (isset($_FILES['introduce_yourself_file']['name']) && $_FILES['introduce_yourself_file']['name'] != '') {

		hooks()->do_action('before_upload_supporting_document_attachment', $id);
		$path = SUPPORTING_DOCUMENT_UPLOAD . $id . '/';
        // Get the temp file path
		$tmpFilePath = $_FILES['introduce_yourself_file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['introduce_yourself_file']['name']);
			$newFilePath = $path . $filename;
            // Upload the file into the company uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$attachment   = [];
				$attachment[] = [
					'file_name' => $filename,
					'filetype'  => $_FILES['introduce_yourself_file']['type'],
				];
				$CI->misc_model->add_attachment_to_database($id, 'supporting_document', $attachment);

				return true;
			}
		}
	}
	return false;
}

/**
 * handle proof income file
 * @param  [type] $id 
 * @return [type]     
 */
function handle_proof_income_file($id){
	$CI           = & get_instance();
	if (isset($_FILES['income_source_file']['name']) && $_FILES['income_source_file']['name'] != '') {

		hooks()->do_action('before_upload_proof_income_attachment', $id);
		$path = PROOF_INCOME_UPLOAD . $id . '/';
        // Get the temp file path
		$tmpFilePath = $_FILES['income_source_file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['income_source_file']['name']);
			$newFilePath = $path . $filename;
            // Upload the file into the company uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$attachment   = [];
				$attachment[] = [
					'file_name' => $filename,
					'filetype'  => $_FILES['income_source_file']['type'],
				];
				$CI->misc_model->add_attachment_to_database($id, 'proof_income', $attachment);

				return true;
			}
		}
	}
	return false;
}

/**
 * handle identity document file
 * @param  [type] $id 
 * @return [type]     
 */
function handle_identity_document_file($id){
	$CI           = & get_instance();
	if (isset($_FILES['identity_document_file']['name']) && $_FILES['identity_document_file']['name'] != '') {

		hooks()->do_action('before_upload_identity_document_attachment', $id);
		$path = IDENTIFY_DOCUMENT_UPLOAD . $id . '/';
        // Get the temp file path
		$tmpFilePath = $_FILES['identity_document_file']['tmp_name'];
        // Make sure we have a filepath
		if (!empty($tmpFilePath) && $tmpFilePath != '') {
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['identity_document_file']['name']);
			$newFilePath = $path . $filename;
            // Upload the file into the company uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$attachment   = [];
				$attachment[] = [
					'file_name' => $filename,
					'filetype'  => $_FILES['identity_document_file']['type'],
				];
				$CI->misc_model->add_attachment_to_database($id, 'identity_document', $attachment);

				return true;
			}
		}
	}
	return false;
}

/**
 * contact relationships
 * @return [type] 
 */
function contact_relationships()
{
	$contact_relationships = [
		[
			'name' => 'parent',
			'label' => _l('real_parent'),
		],
		[
			'name' => 'sibling',
			'label' => _l('real_sibling'),
		],
		[
			'name' => 'child',
			'label' => _l('real_child'),
		],
		[
			'name' => 'grandparent',
			'label' => _l('real_grandparent'),
		],
		[
			'name' => 'other_relative',
			'label' => _l('real_other_relative'),
		],
		[
			'name' => 'spouse',
			'label' => _l('real_spouse'),
		],
		[
			'name' => 'partner',
			'label' => _l('real_partner'),
		],
		[
			'name' => 'friend',
			'label' => _l('real_friend'),
		],
		[
			'name' => 'colleague',
			'label' => _l('real_colleague'),
		],
		[
			'name' => 'other',
			'label' => _l('real_other'),
		],

	];
	return $contact_relationships;						
}

/**
 * check assign property to broker
 * @param  [type] $property_id 
 * @return [type]              
 */
function check_assign_property_to_broker($property_id)
{
	$CI = &get_instance();
	$CI->db->select('broker_id, company_id');
	$CI->db->where('id', $property_id);
	$property = $CI->db->get(db_prefix().'items')->row();
	if(!$property){
		return false;
	}

	if(is_broker_logged_in()){
		$business_broker_id = get_business_broker_id();
		if($property->broker_id == $business_broker_id){
			return false;
		}
	}else{
		$staff_in_company = rel_check_staff_in_company();
		$get_staff_user_id = get_staff_user_id();
		if(is_admin()){
			// is admin: view all
			return true;
		}elseif($staff_in_company){
			if($property->company_id == $staff_in_company){
				return true;
			}
		}else{
			// staff not in construction company
			if(has_permission('real_business_broker', '', 'create') || has_permission('real_business_broker', '', 'edit')){
				return true;
			}
		}
	}

	return true;
}


/**
 * real property request status
 * @param  string $status 
 * @return [type]         
 */
function real_property_request_status($status='')
{

	$statuses = [

		[
			'id'             => '1',
			'color'          => '#9e9e9e',
			'name'           => _l('real_draft'),
			'order'          => 2,
			'filter_default' => true,
		],
		[
			'id'             => '9',
			'color'          => '#009688',
			'name'           => _l('real_submitted'),
			'order'          => 2,
			'filter_default' => true,
		],
		
		[
			'id'             => '3',
			'color'          => '#4caf50',
			'name'           => _l('real_sent'),
			'order'          => 2,
			'filter_default' => true,
		],
		[
			'id'             => '4',
			'color'          => '#2196f3',
			'name'           => _l('real_waiting_for_approval'),
			'order'          => 3,
			'filter_default' => true,
		],
		[
			'id'             => '2',
			'color'          => '#3db8da',
			'name'           => _l('real_approved'),
			'order'          => 4,
			'filter_default' => true,
		],
		[
			'id'             => '5',
			'color'          => '#84c529',
			'name'           => _l('real_declined'),
			'order'          => 5,
			'filter_default' => true,
		],
		[
			'id'             => '6',
			'color'          => '#84c529',
			'name'           => _l('real_complete'),
			'order'          => 6,
			'filter_default' => false,
		],
		
		[
			'id'             => '7',
			'color'          => '#ffa500',
			'name'           => _l('real_expired'),
			'order'          => 7,
			'filter_default' => false,
		],
		[
			'id'             => '8',
			'color'          => '#d71a1a',
			'name'           => _l('real_cancelled'),
			'order'          => 8,
			'filter_default' => false,
		],
		
	];

	usort($statuses, function ($a, $b) {
		return $a['order'] - $b['order'];
	});

	return $statuses;
}

/**
 * real client property request status
 * @param  string $status 
 * @return [type]         
 */
function real_client_property_request_status($status='')
{
	$statuses = [

		[
			'id'             => '1',
			'color'          => '#9e9e9e',
			'name'           => _l('real_draft'),
			'order'          => 2,
			'filter_default' => true,
		],
		[
			'id'             => '9',
			'color'          => '#009688',
			'name'           => _l('real_submitted'),
			'order'          => 2,
			'filter_default' => true,
		],
		[
			'id'             => '8',
			'color'          => '#d71a1a',
			'name'           => _l('real_cancelled'),
			'order'          => 8,
			'filter_default' => false,
		],
		
	];

	usort($statuses, function ($a, $b) {
		return $a['order'] - $b['order'];
	});

	return $statuses;
}

/**
 * get order status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_property_request_status_by_id($id, $type)
{
	$CI       = &get_instance();

	$statuses = real_property_request_status();
	$status = [
		'id'         => 0,
		'color'   => '#989898',
		'color' => '#989898',
		'name'       => _l('bt_draff'),
		'order'      => 1,
	];
	

	foreach ($statuses as $s) {
		if ($s['id'] == $id) {
			$status = $s;

			break;
		}
	}

	return $status;
}


/**
 * render property request status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_property_request_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
	$status          = get_property_request_status_by_id($status_value, $type);
	$task_statuses = real_property_request_status();

	$outputStatus    = '';

	$outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
	$outputStatus .= $status['name'];
	$canChangeStatus = (has_permission('real_buy_request', '', 'edit') || has_permission('real_rent_request', '', 'edit') || is_admin() || is_broker_logged_in());

	if ($canChangeStatus && $ChangeStatus) {
		$outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
		$outputStatus .= '<a href="#" class="dropdown-toggle text-dark dropdown-font-size" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		$outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
		$outputStatus .= '</a>';

		$outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
		foreach ($task_statuses as $taskChangeStatus) {
			if ($status_value != $taskChangeStatus['id']) {
				$outputStatus .= '<li>
				<a href="#" onclick="property_request_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
				' . _l('task_mark_as', $taskChangeStatus['name']) . '
				</a>
				</li>';
			}
		}
		$outputStatus .= '</ul>';
		$outputStatus .= '</div>';
	}

	$outputStatus .= '</span>';

	return $outputStatus;
}

/**
 * render client property request status html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_client_property_request_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
	$status          = get_property_request_status_by_id($status_value, $type);
	$task_statuses = real_client_property_request_status();

	$outputStatus    = '';

	$outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
	$outputStatus .= $status['name'];
	$canChangeStatus = (is_primary_contact());

	if ($canChangeStatus && $ChangeStatus) {
		$outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
		$outputStatus .= '<a href="#" class="dropdown-toggle text-dark dropdown-font-size" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		$outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
		$outputStatus .= '</a>';

		$outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
		foreach ($task_statuses as $taskChangeStatus) {
			if ($status_value != $taskChangeStatus['id']) {
				$outputStatus .= '<li>
				<a href="#" onclick="property_request_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
				' . _l('task_mark_as', $taskChangeStatus['name']) . '
				</a>
				</li>';
			}
		}
		$outputStatus .= '</ul>';
		$outputStatus .= '</div>';
	}

	$outputStatus .= '</span>';

	return $outputStatus;
}

/**
 * get property name
 * @param  [type] $id 
 * @return [type]     
 */
function get_property_name($id, $name = true, $rental_type = false, $property_description = false)
{
	$property_name = '';
	$CI = &get_instance();
	$CI->db->select('*');
	$CI->db->where('id',$id);
	$property = $CI->db->get(db_prefix().'items')->row();
	if($property){
		if($name){
			$property_name .= '<span class="tw-font-semibold">'.$property->description.'</span><br>';
			$property_name .= $property->street_number.' '.$property->street_dir_pre.' '.$property->street_name.' '.$property->city.' '.($property->state).' '.get_country_name($property->country);
		}
		if($rental_type){
			$property_name = '';
			if($property->transaction_type == 'Sale' || $property->transaction_type == 'sold'){
				$property_name = '';
			}else{
				$property_name = $property->rental_type;
			}
		}
		if($property_description){
			$property_name = $property->description;
		}
		return $property_name;
	}else{
		return '';
	}
}

function real_prepare_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->app_mail_template->prepare($email, $template, $mailClassParams);
    $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;

}

/**
 * get contract name
 * @param  [type] $contract_id 
 * @return [type]              
 */
function get_contract_name($contract_id)
{
	$contract_name = '';
    $CI = &get_instance();
    $CI->db->where('id', $contract_id);
    $contract = $CI->db->get(db_prefix() . 'contracts')->row();
    if($contract){
    	$contract_name = $contract->subject;
    }

    return $contract_name;
}

/**
 * real get invoice hash
 * @param  [type] $id 
 * @return [type]     
 */
function real_get_invoice_hash($id)
{
	$hash = '';
	$CI           = & get_instance();
	$CI->db->where('id',$id);

	$invoices = $CI->db->get(db_prefix().'invoices')->row();
	if($invoices){
		$hash = $invoices->hash;
	}
	return $hash;
}

/**
 * real get contract hash
 * @param  [type] $id 
 * @return [type]     
 */
function real_get_contract_hash($id)
{
	$hash = '';
	$CI           = & get_instance();
	$CI->db->where('id',$id);

	$contract = $CI->db->get(db_prefix().'contracts')->row();
	if($contract){
		$hash = $contract->hash;
	}
	return $hash;
}

if (!function_exists('new_strlen')) {
    
    function new_strlen($str){
        return strlen($str ?? '');
    }
}

if (!function_exists('new_str_replace')) {
    
    function new_str_replace($search, $replace, $subject){
        return str_replace($search, $replace, $subject ?? '');
    }
}

if (!function_exists('new_explode')) {
    
    function new_explode($delimiter, $string){
        return explode($delimiter, $string ?? '');
    }
}

/**
 * get staff phonenumber
 * @param  string $staffid 
 * @return [type]          
 */
function get_staff_phonenumber($staffid = '')
{
	if (!is_numeric($staffid)) {
        $staffid = get_staff_user_id();
    }

    $CI = & get_instance();
    $CI->db->select('phonenumber');
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->where('staffid', $staffid);
    $staff = $CI->db->get()->row();
    if ($staff) {
        return $staff->phonenumber;
    }

    return '';
}

/**
 * real get company logo
 * @param  string $uri        
 * @param  string $href_class 
 * @param  string $type       
 * @return [type]             
 */
function real_get_company_logo($uri = '', $href_class = '', $type = '')
{
    $company_logo = get_option('company_logo' . ($type == 'dark' ? '_dark' : ''));
    $company_name = get_option('companyname');
    $logoURL = site_url($uri);

    $logoURL = hooks()->apply_filters('logo_href', $logoURL);

    if ($company_logo != '') {
        $logo = '
        <img src="' . base_url('uploads/company/' . $company_logo) . '" class="img-responsive ' . ($href_class != '' ? ' ' . $href_class : '') . '" alt="' . e($company_name) . '"/>
        ';
    }  else {
        $logo = '';
    }

    return  $logo;
}

/**
 * list realestate permisstion
 * @return [type] 
 */
function list_realestate_permisstion()
{
	$realestate_permissions=[];
	$realestate_permissions[]='real_dashboard';
	$realestate_permissions[]='real_estate_agent';
	$realestate_permissions[]='real_estate_agent_staff';
	$realestate_permissions[]='real_property_owner';
	$realestate_permissions[]='real_business_broker';
	$realestate_permissions[]='real_request_broker';
	$realestate_permissions[]='real_property';
	$realestate_permissions[]='real_property_approval';
	$realestate_permissions[]='real_buy_request';
	$realestate_permissions[]='real_rent_request';
	$realestate_permissions[]='real_tenant';
	$realestate_permissions[]='real_report';
	$realestate_permissions[]='real_permission';

	return $realestate_permissions;
}

/**
 * realestate get staff id permissions
 * @return [type] 
 */
function realestate_get_staff_id_permissions()
{
	$CI = & get_instance();
	$array_staff_id = [];
	$index=0;

	$str_permissions ='';
	foreach (list_realestate_permisstion() as $per_key =>  $per_value) {
		if(new_strlen($str_permissions) > 0){
			$str_permissions .= ",'".$per_value."'";
		}else{
			$str_permissions .= "'".$per_value."'";
		}
	}

	$sql_where = "SELECT distinct staff_id FROM ".db_prefix()."staff_permissions
	where feature IN (".$str_permissions.")
	";

	$staffs = $CI->db->query($sql_where)->result_array();

	if(count($staffs)>0){
		foreach ($staffs as $key => $value) {
			$array_staff_id[$index] = $value['staff_id'];
			$index++;
		}
	}
	return $array_staff_id;
}

/**
 * realestate get staff id dont permissions
 * @return [type] 
 */
function realestate_get_staff_id_dont_permissions()
{
	$CI = & get_instance();


	$staff_in_company = rel_check_staff_in_company();
	$get_staff_user_id = get_staff_user_id();
	if(is_admin()){
			// is admin: view all
	}elseif($staff_in_company){
			// staff in company
		if(has_permission('real_permission', '', 'view')){
			$CI->db->where(db_prefix().'staff.company_id = '.$staff_in_company);
		}else{
			$CI->db->where('1=2');
		}

	}else{
			// staff not in construction company
		if(has_permission('real_permission', '', 'view')){
				// get all
			$CI->db->where(db_prefix().'staff.company_id = 0');
		}else{
			$CI->db->where('1=2');
		}
	}
	$CI->db->where('admin != ', 1);

	if(count(realestate_get_staff_id_permissions()) > 0){
		$CI->db->where_not_in('staffid', realestate_get_staff_id_permissions());
	}
	return $CI->db->get(db_prefix().'staff')->result_array();
}

/**
 * get real month listing
 * @param  [type] $plan_id 
 * @return [type]          
 */
function get_real_month_property($plan_id)
{
	$CI = &get_instance();
	$CI->load->model('realestate/realestate_model');
	$plan = $CI->realestate_model->get_plan($plan_id);
	if($plan){
		if($plan->read_only == 1){
			return 'read_only';
		}else{
			return (int)$plan->monthly_listing_number;
		}
	}
	return 'read_only';
}

/**
 * real has monthly listing
 * @return [type] 
 */
function real_has_monthly_property()
{
	$permission = true;

	$CI = &get_instance();
			// check allow create new listing
	if(is_broker_logged_in()){


	}else{
		$staff_in_company = rel_check_staff_in_company();
		$get_staff_user_id = get_staff_user_id();
		if($staff_in_company){
			$CI->db->where('id', $staff_in_company);
			$company = $CI->db->get(db_prefix().'real_companies')->row();
			if($company){
				$month_listing = get_real_month_property($company->plan_id);
				if(is_numeric($month_listing)){
					$CI->db->where('company_id', $staff_in_company);
					$CI->db->where("date_format(".db_prefix()."items.date_created, '%Y-%m') = '".date('Y-m')."'");
					$items = $CI->db->get(db_prefix().'items')->result_array();
					if(count($items) >= $month_listing){
						$permission = false;
					}else{
						$permission = true;
					}
				}else{
					$permission = 'read_only';
				}
			}else{
				$permission = 'read_only';
			}
		}
	}

	return $permission;
}

function get_remaining_property()
{
	$current_listing = 0;
	$plan_listing = 0;
	$show_on_dashboard = false;
	$plan_name = '';

	$permission = true;

	$CI = &get_instance();
	$CI->load->model('realestate/realestate_model');
	if(is_broker_logged_in()){


	}else{
		$staff_in_company = rel_check_staff_in_company();
		$get_staff_user_id = get_staff_user_id();
		if($staff_in_company){
			$CI->db->where('id', $staff_in_company);
			$company = $CI->db->get(db_prefix().'real_companies')->row();
			if($company){
				$month_listing = get_real_month_property($company->plan_id);
				if(is_numeric($month_listing)){
					$CI->db->where('company_id', $staff_in_company);
					$CI->db->where("date_format(".db_prefix()."items.date_created, '%Y-%m') = '".date('Y-m')."'");
					$items = $CI->db->get(db_prefix().'items')->result_array();
					$current_listing = count($items);
					$plan_listing = $month_listing;
				}

				$plan = $CI->realestate_model->get_plan($company->plan_id);
				if($plan){
					$plan_name = $plan->name;
				}
			}
			$show_on_dashboard = true;
		}
	}

	$remaining_quantity = 0;
	if($plan_listing > $current_listing){
		$remaining_quantity = $plan_listing - $current_listing;
	}

	return ['current_listing' => $current_listing, 'plan_listing' => $plan_listing, 'remaining_quantity' => $remaining_quantity, 'show_on_dashboard' => $show_on_dashboard, 'plan_name' => $plan_name];
}
