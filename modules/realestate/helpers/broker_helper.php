<?php
defined('BASEPATH') or exit('No direct script access allowed');

function app_broker_portal_footer()
{
	
	/**
	 * @deprecated 2.3.0
	 * Moved from themes/[THEME]/views/scripts.php
	 * Use app_sale_agent_footer hook instead
	 */
	do_action_deprecated('broker_portal_after_js_scripts_load', [], '2.3.0', 'app_broker_portal_footer');

	hooks()->do_action('app_broker_portal_footer');
}

/**
 * app broker portal head
 * @param  [type] $language 
 * @return [type]           
 */
function app_broker_portal_head($language = null)
{
	// $language param is deprecated
	if (is_null($language)) {
		$language = $GLOBALS['language'];
	}

	if (file_exists(FCPATH . 'assets/css/custom.css')) {
		echo '<link href="' . base_url('assets/css/custom.css') . '" rel="stylesheet" type="text/css" id="custom-css">' . PHP_EOL;
	}

	hooks()->do_action('app_broker_portal_head');
}

/**
 * get template part broker portal
* @param      string   $name    The name
 * @param      array    $data    The data
 * @param      boolean  $return  The return
 *
 * @return     string   The template part.
 */
function get_template_part_broker_portal($name, $data = [], $return = false)
{
	if ($name === '') {
		return '';
	}

	$CI   = & get_instance();
	$path = 'brokers_portals/template_parts/';

	if ($return == true) {
		return $CI->load->view($path . $name, $data, true);
	}

	$CI->load->view($path . $name, $data);
}

/**
 * init broker_portal area assets.
 */
function init_broker_portal_area_assets()
{
	// Used by themes to add assets
	hooks()->do_action('app_broker_portal_assets');

	hooks()->do_action('app_client_assets_added');
}

/**
 * { register theme broker_portal assets hook }
 *
 * @param      <type>   $function  The function
 *
 * @return     boolean  
 */
function register_theme_broker_portal_assets_hook($function)
{
	if (hooks()->has_action('app_broker_portal_assets', $function)) {
		return false;
	}

	return hooks()->add_action('app_broker_portal_assets', $function, 1);
}


/**
 * { app theme head hook }
 */
function app_theme_broker_portal_head_hook()
{
	$CI = &get_instance();
	ob_start();
	echo get_custom_fields_hyperlink_js_function();

	if (get_option('use_recaptcha_customers_area') == 1
		&& get_option('recaptcha_secret_key') != ''
		&& get_option('recaptcha_site_key') != '') {
		echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
}

$isRTL = (is_rtl_broker(true) ? 'true' : 'false');

$locale = get_locale_key($GLOBALS['language']);

$maxUploadSize = file_upload_max_size();

$date_format = get_option('dateformat');
$date_format = new_explode('|', $date_format);
$date_format = $date_format[0]; ?>
<script>
	<?php if (is_staff_logged_in()) {
		?>
		var admin_url = '<?php echo admin_url(); ?>';
		<?php
	} ?>

	var site_url = '<?php echo site_url(''); ?>',
	app = {},
	cfh_popover_templates  = {};

	app.isRTL = '<?php echo new_html_entity_decode($isRTL); ?>';
	app.is_mobile = '<?php echo is_mobile(); ?>';
	app.months_json = '<?php echo json_encode([_l('January'), _l('February'), _l('March'), _l('April'), _l('May'), _l('June'), _l('July'), _l('August'), _l('September'), _l('October'), _l('November'), _l('December')]); ?>';

	app.browser = "<?php echo strtolower($CI->agent->browser()); ?>";
	app.max_php_ini_upload_size_bytes = "<?php echo new_html_entity_decode($maxUploadSize); ?>";
	app.locale = "<?php echo new_html_entity_decode($locale); ?>";

	app.options = {
		calendar_events_limit: "<?php echo get_option('calendar_events_limit'); ?>",
		calendar_first_day: "<?php echo get_option('calendar_first_day'); ?>",
		tables_pagination_limit: "<?php echo get_option('tables_pagination_limit'); ?>",
		enable_google_picker: "<?php echo get_option('enable_google_picker'); ?>",
		google_client_id: "<?php echo get_option('google_client_id'); ?>",
		google_api: "<?php echo get_option('google_api_key'); ?>",
		default_view_calendar: "<?php echo get_option('default_view_calendar'); ?>",
		timezone: "<?php echo get_option('default_timezone'); ?>",
		allowed_files: "<?php echo get_option('allowed_files'); ?>",
		date_format: "<?php echo new_html_entity_decode($date_format); ?>",
		time_format: "<?php echo get_option('time_format'); ?>",
	};

	app.lang = {
		file_exceeds_maxfile_size_in_form: "<?php echo _l('file_exceeds_maxfile_size_in_form'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)',
		file_exceeds_max_filesize: "<?php echo _l('file_exceeds_max_filesize'); ?>" + ' (<?php echo bytesToSize('', $maxUploadSize); ?>)',
		validation_extension_not_allowed: "<?php echo _l('validation_extension_not_allowed'); ?>",
		sign_document_validation: "<?php echo _l('sign_document_validation'); ?>",
		dt_length_menu_all: "<?php echo _l('dt_length_menu_all'); ?>",
		drop_files_here_to_upload: "<?php echo _l('drop_files_here_to_upload'); ?>",
		browser_not_support_drag_and_drop: "<?php echo _l('browser_not_support_drag_and_drop'); ?>",
		confirm_action_prompt: "<?php echo _l('confirm_action_prompt'); ?>",
		datatables: <?php echo json_encode(get_datatables_language_array()); ?>,
		discussions_lang: <?php echo json_encode(get_project_discussions_language_array()); ?>,
	};
	window.addEventListener('load',function(){
		custom_fields_hyperlink();
	});
</script>
<?php

_do_clients_area_deprecated_js_vars($date_format, $locale, $maxUploadSize, $isRTL);

$contents = ob_get_contents();
ob_end_clean();
echo new_html_entity_decode($contents);
}


/**
 * is_rtl_broker
 * @param  boolean $client_area 
 * @return boolean              
 */
function is_rtl_broker($client_area = false)
{
	$CI = & get_instance();
	
	if ($client_area == true) {
		// Client not logged in and checked from clients area
		if (get_option('rtl_support_client') == 1) {
			return true;
		}
	} elseif (is_broker_logged_in()) {
		if (isset($GLOBALS['current_user'])) {
			$direction = $GLOBALS['current_user']->direction;
		} else {
			$CI->db->select('direction')->from(db_prefix() . 'real_broker_staffs')->where('id', get_broker_id());
			$direction = $CI->db->get()->row()->direction;
		}

		if ($direction == 'rtl') {
			return true;
		} elseif ($direction == 'ltr') {
			return false;
		} elseif (empty($direction)) {
			if (get_option('rtl_support_admin') == 1) {
				return true;
			}
		}

		return false;
	} elseif ($client_area == false) {
		if (get_option('rtl_support_admin') == 1) {
			return true;
		}
	}

	return false;
}

/**
 * is broker logged in
 * @return boolean 
 */
function is_broker_logged_in()
{
	return get_instance()->session->has_userdata('broker_logged_in');
}

/**
 * get candidate id
 * @return [type] 
 */
function get_broker_id()
{
	if (!is_broker_logged_in()) {
		return false;
	}

	return get_instance()->session->userdata('broker_id');
}

/**
 * get candidate id
 * @return [type] 
 */
function get_business_broker_id()
{
	if (!is_broker_logged_in()) {
		return false;
	}

	return get_instance()->session->userdata('business_broker_id');
}

/**
 * broker profile image url
 * @param  [type] $broker_id 
 * @param  string $type         
 * @return [type]               
 */
function broker_profile_image_url($broker_id, $type = 'small')
{
	$url  = base_url('assets/images/user-placeholder.jpg');
	$CI   = &get_instance();
	$path = $CI->app_object_cache->get('broker-profile-image-path-' . $broker_id);

	if (!$path) {
		$CI->app_object_cache->add('broker-profile-image-path-' . $broker_id, $url);

		$CI->db->select('profile_image');
		$CI->db->from(db_prefix() . 'real_broker_staffs');
		$CI->db->where('id', $broker_id);
		$broker = $CI->db->get()->row();

		if ($broker && !empty($broker->profile_image)) {

			$path = BROKER_PATH_PROFILE_UPLOAD . $broker_id . '/' . $type . '_' . $broker->profile_image;
			$CI->app_object_cache->set('broker-profile-image-path-' . $broker_id, $path);
		}
	}

	if ($path && file_exists($path)) {
		$url = base_url($path);
	}

	return $url;
}

/**
 * broker pusher trigger notification
 * @param  array  $users 
 * @return [type]        
 */
function broker_pusher_trigger_notification($users = [])
{
	if (get_option('pusher_realtime_notifications') == 0) {
		return false;
	}

	if (!is_array($users) || count($users) == 0) {
		return false;
	}

	$channels = [];
	foreach ($users as $id) {
		array_push($channels, 'broker-notifications-channel-' . $id);
	}

	$channels = array_unique($channels);

	$CI = &get_instance();

	$CI->load->library('app_pusher');

	$CI->app_pusher->trigger($channels, 'notification', []);
}

/**
 * broker add notification
 * @param  [type] $values 
 * @return [type]         
 */
function broker_add_notification($values)
{
	$CI = & get_instance();
	foreach ($values as $key => $value) {
		$data[$key] = $value;
	}
	if (is_broker_logged_in()) {
		$data['fromuserid']    = 0;
		$data['fromclientid']  = get_broker_id();
		$data['from_fullname'] = get_broker_name(get_broker_id());
	} else {
		$data['fromuserid']    = get_staff_user_id();
		$data['fromclientid']  = 0;
		$data['from_fullname'] = get_staff_full_name(get_staff_user_id());
	}

	if (isset($data['fromcompany'])) {
		$data['fromuserid']    = 0;
		$data['from_fullname'] = '';
	}

	$data['date'] = date('Y-m-d H:i:s');
	$data         = hooks()->apply_filters('broker_notification_data', $data);

	// Prevent sending notification to non active users.
	if (isset($data['touserid']) && $data['touserid'] != 0) {
		$CI->db->where('id', $data['touserid']);
		$user = $CI->db->get(db_prefix() . 'real_broker_staffs')->row();
		if (!$user || $user && $user->active == 0) {
			return false;
		}
	}

	$CI->db->insert(db_prefix() . 'real_notifications', $data);

	if ($notification_id = $CI->db->insert_id()) {
		hooks()->do_action('broker_notification_created', $notification_id);
	}

	return true;
}

/**
	 * asm company init head
	 * @param  boolean $aside 
	 * @return [type]         
	 */
function broker_init_head($aside = true)
{
	$CI = &get_instance();
	$CI->load->view('brokers_portals/head');
	$CI->load->view('brokers_portals/template_parts/header', ['startedTimers' => $CI->misc_model->get_staff_started_timers()]);
	if ($aside == true) {
		$CI->load->view('brokers_portals/template_parts/aside');
	}
}

/**
 * broker init tail
 * @return [type] 
 */
function broker_init_tail()
{
	$CI = &get_instance();
	$CI->load->view('brokers_portals/scripts');
}

/**
 * Maybe upload broker profile image
 * @param  string $staff_id staff_id or current logged in staff id will be used if not passed
 * @return boolean
 */
function handle_broker_profile_image_upload($broker_id = '')
{
	if (!is_numeric($broker_id)) {
		$broker_id = get_broker_id();
	}

	$hookData = hooks()->apply_filters('before_handle_broker_profile_image_upload', [
		'broker_id' => $broker_id,
		'index_name' => 'profile_image',
		'handled_externally' => false, // e.g. module upload to s3
		'handled_externally_successfully' => false,
		'files' => $_FILES
	]);

	if ($hookData['handled_externally']) {
		return $hookData['handled_externally_successfully'];
	}

	if (isset($_FILES['profile_image']['name']) && $_FILES['profile_image']['name'] != '') {
		hooks()->do_action('before_upload_staff_profile_image');
		$path = BROKER_PROFILE_UPLOAD . $broker_id . '/';

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

			$allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);

			if (!in_array($extension, $allowed_extensions)) {
				set_alert('warning', _l('file_php_extension_blocked'));

				return false;
			}
			_maybe_create_upload_path($path);
			$filename    = unique_filename($path, $_FILES['profile_image']['name']);
			$newFilePath = $path . '/' . $filename;
			// Upload the file into the company uploads dir
			if (move_uploaded_file($tmpFilePath, $newFilePath)) {
				$CI                       = & get_instance();
				$config                   = [];
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'thumb_' . $filename;
				$config['maintain_ratio'] = true;
				$config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
				$config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
				$CI->image_lib->clear();
				$config['image_library']  = 'gd2';
				$config['source_image']   = $newFilePath;
				$config['new_image']      = 'small_' . $filename;
				$config['maintain_ratio'] = true;
				$config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
				$config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
				$CI->db->where('id', $broker_id);
				$CI->db->update(db_prefix() . 'real_broker_staffs', [
					'profile_image' => $filename,
				]);
				// Remove original image
				unlink($newFilePath);

				return true;
			}
		}
	}

	return false;
}

/**
 * broker profile image
 * @param  [type] $id        
 * @param  array  $classes   
 * @param  string $type      
 * @param  array  $img_attrs 
 * @return [type]            
 */
function broker_profile_image($id, $classes = ['staff-profile-image'], $type = 'small', $img_attrs = [])
{
    $CI           = & get_instance();
    $url = base_url('assets/images/user-placeholder.jpg');
    $_attributes = '';
    foreach ($img_attrs as $key => $val) {
        $_attributes .= $key . '=' . '"' . html_escape($val) . '" ';
    }

    $blankImageFormatted = '<img src="' . $url . '" ' . $_attributes . ' class="' . implode(' ', $classes) . '" />';

    if ((string) $id === (string) get_staff_user_id() && isset($GLOBALS['current_broker'])) {
        $result = $GLOBALS['current_broker'];
    } else {
        $CI     = & get_instance();
        $result = $CI->app_object_cache->get('broker-profile-image-data-' . $id);

        if (!$result) {
            $CI->db->select('profile_image,firstname,lastname');
            $CI->db->where('id', $id);
            $result = $CI->db->get(db_prefix() . 'real_broker_staffs')->row();
            $CI->app_object_cache->add('broker-profile-image-data-' . $id, $result);
        }
    } 

    if (!$result) {
        return $blankImageFormatted;
    }

    if ($result && $result->profile_image !== null) {
        $profileImagePath = BROKER_PATH_PROFILE_UPLOAD.$id.'/' . $type . '_' . $result->profile_image;
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
 * get broker name
 * @param  string $userid 
 * @return [type]         
 */
function get_broker_name($userid = '')
{
    $tmpStaffUserId = get_broker_id();
    if ($userid == '' || $userid == $tmpStaffUserId) {
        if (isset($GLOBALS['current_broker'])) {
            return $GLOBALS['current_broker']->firstname . ' ' . $GLOBALS['current_broker']->lastname;
        }
        $userid = $tmpStaffUserId;
    }

    $CI = & get_instance();

    $staff = $CI->app_object_cache->get('broker-full-name-data-' . $userid);

    if (!$staff) {
        $CI->db->where('id', $userid);
        $staff = $CI->db->select('firstname,lastname')->from(db_prefix() . 'real_broker_staffs')->get()->row();
        $CI->app_object_cache->add('broker-full-name-data-' . $userid, $staff);
    }

    return $staff ? $staff->firstname . ' ' . $staff->lastname : '';
}

/**
 * get broker staff
 * @param  [type] $id 
 * @return [type]     
 */
function get_broker_staff($id = null)
{
    if (empty($id) && isset($GLOBALS['current_broker'])) {
        return $GLOBALS['current_broker'];
    }

    // Staff not logged in
    if (empty($id)) {
        return null;
    }

    if (!class_exists('broker_model', false)) {
        get_instance()->load->model('broker_model');
    }

    return get_instance()->broker_model->get_broker_staff($id);
}

/**
 * Function used to render <option> for relation
 * This function will do all the necessary checking and return the options
 * @param  mixed $data
 * @param  string $type   rel_type
 * @param  string $rel_id rel_id
 * @return string
 */
function broker_init_relation_options($data, $type, $rel_id = '')
{
    $_data = [];

    $has_permission_projects_view  = staff_can('view',  'projects');
    $has_permission_customers_view = staff_can('view',  'customers');
    $has_permission_contracts_view = staff_can('view',  'contracts');
    $has_permission_invoices_view  = staff_can('view',  'invoices');
    $has_permission_estimates_view = staff_can('view',  'estimates');
    $has_permission_expenses_view  = staff_can('view',  'expenses');
    $has_permission_proposals_view = staff_can('view',  'proposals');
    $is_admin                      = is_admin();
    $CI                            = & get_instance();
    $CI->load->model('projects_model');

    foreach ($data as $relation) {
        $relation_values = get_relation_values($relation, $type);
        if ($type == 'project') {
            if (!$has_permission_projects_view) {
                if (!$CI->projects_model->is_member($relation_values['id']) && $rel_id != $relation_values['id']) {
                    continue;
                }
            }
        } elseif ($type == 'lead') {
            if (staff_cant('view', 'leads')) {
                if ($relation['assigned'] != get_staff_user_id() && $relation['addedfrom'] != get_staff_user_id() && $relation['is_public'] != 1 && $rel_id != $relation_values['id']) {
                    continue;
                }
            }
        } elseif ($type == 'customer') {
            if (!$has_permission_customers_view && !have_assigned_customers() && $rel_id != $relation_values['id'] && !is_broker_logged_in() && !is_staff_logged_in() ) {
                continue;
            } elseif (have_assigned_customers() && $rel_id != $relation_values['id'] && !$has_permission_customers_view && !is_broker_logged_in() && !is_staff_logged_in()) {
                if (!is_customer_admin($relation_values['id'])) {
                    continue;
                }
            }
        } elseif ($type == 'contract') {
            if (!$has_permission_contracts_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } elseif ($type == 'invoice') {
            if (!$has_permission_invoices_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } elseif ($type == 'estimate') {
            if (!$has_permission_estimates_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } elseif ($type == 'expense') {
            if (!$has_permission_expenses_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } elseif ($type == 'proposal') {
            if (!$has_permission_proposals_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        }

        $_data[] = $relation_values;
    }

    $_data = hooks()->apply_filters('init_relation_options', $_data, compact('data', 'type', 'rel_id'));

    return $_data;
}

/**
 * Get the recently created contracts in the given days
 *
 * @param  integer $days
 * @param  integer|null $staffId
 *
 * @return integer
 */
function broker_count_recently_created_contracts($days = 7, $staffId = null)
{
	$diff1     = date('Y-m-d', strtotime('-' . $days . ' days'));
	$diff2     = date('Y-m-d', strtotime('+' . $days . ' days'));
	$staffId   = is_null($staffId) ? get_business_broker_id() : $staffId;
	$where_own = [];

	$where_own = ['broker_id' => $staffId];


	return total_rows(db_prefix() . 'contracts', 'dateadded BETWEEN "' . $diff1 . '" AND "' . $diff2 . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : ''));
}

/**
 * Get total number of active contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function broker_count_active_contracts($staffId = null)
{
	$where_own = [];
	$staffId   = is_null($staffId) ? get_business_broker_id() : $staffId;

	$where_own = ['broker_id' => $staffId];


	return total_rows(db_prefix() . 'contracts', '(DATE(dateend) >"' . date('Y-m-d') . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ') OR (DATE(dateend) IS NULL AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ')');
}

/**
 * Get total number of expired contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function broker_count_expired_contracts($staffId = null)
{
	$where_own = [];
	$staffId   = is_null($staffId) ? get_business_broker_id() : $staffId;

	$where_own = ['broker_id' => $staffId];


	return total_rows(db_prefix() . 'contracts', array_merge(['DATE(dateend) <' => date('Y-m-d'), 'trash' => 0], $where_own));
}

/**
 * Get total number of trash contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function broker_count_trash_contracts($staffId = null)
{
	$where_own = [];
	$staffId   = is_null($staffId) ? get_business_broker_id() : $staffId;

	$where_own = ['broker_id' => $staffId];


	return total_rows(db_prefix() . 'contracts', array_merge(['trash' => 1], $where_own));
}

