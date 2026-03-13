<?php

defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('gdpr_model');

$consentContacts = get_option('gdpr_enable_consent_for_contacts');
$aColumns        = [ 'firstname', 'lastname'];
if (is_gdpr() && $consentContacts == '1') {
    array_push($aColumns, '1');
}

$aColumns = array_merge($aColumns, [
    'email',
    'company',
    db_prefix() . 'contacts.phonenumber as phonenumber',
    'title',
]);
if(isExistMailChimp()){
        array_push($aColumns, '1');
        array_push($aColumns, '1');
      }
      
      
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'contacts';
$join         = ['JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid=' . db_prefix() . 'contacts.userid'];

$custom_fields = get_table_custom_fields('contacts');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'contacts.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where = [];

if (!has_permission('customers', '', 'view')) {
    array_push($where, 'AND ' . db_prefix() . 'contacts.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
}

if ($this->ci->input->post('custom_view')) {
    $filter = $this->ci->input->post('custom_view');
    if (startsWith($filter, 'consent_')) {
        array_push($where, 'AND ' . db_prefix() . 'contacts.id IN (SELECT contact_id FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $this->ci->db->escape_str(strafter($filter, 'consent_')) . ' and action="opt-in" AND date IN (SELECT MAX(date) FROM ' . db_prefix() . 'consents WHERE purpose_id=' . $this->ci->db->escape_str(strafter($filter, 'consent_')) . ' AND contact_id=' . db_prefix() . 'contacts.id))');
    }
}

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'contacts.id as id', db_prefix() . 'contacts.userid as userid', 'is_primary', '(SELECT count(*) FROM ' . db_prefix() . 'contacts c WHERE c.userid=' . db_prefix() . 'contacts.userid) as total_contacts', db_prefix() . 'clients.registration_confirmed as registration_confirmed']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $rowName = '<img src="' . contact_profile_image_url($aRow['id']) . '" class="client-profile-image-small mright5">' . $aRow['firstname'];
    $row[] = $rowName;

    $row[] = $aRow['lastname'];
    
    if (is_gdpr() && $consentContacts == '1') {

        $consentHTML = '<p class="bold"><a href="#" onclick="view_contact_consent(' . $aRow['id'] . '); return false;">' . _l('view_consent') . '</a></p>';

        $consents    = $this->ci->gdpr_model->get_consent_purposes($aRow['id'], 'contact');

        foreach ($consents as $consent) {

            $consentHTML .= '<p style="margin-bottom:0px;">' . $consent['name'] . (!empty($consent['consent_given']) ? '<i class="fa fa-check text-success pull-right"></i>' : '<i class="fa fa-remove text-danger pull-right"></i>') . '</p>';

        }

        $row[] = $consentHTML;

    }

    $row[] = '<a href="mailto:' . $aRow['email'] . '">' . $aRow['email'] . '</a>';

    if (!empty($aRow['company'])) {
        $row[] = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '">' . $aRow['company'] . '</a>';
    } else {
        $row[] = '';
    }

    $row[] = '<a href="tel:' . $aRow['phonenumber'] . '">' . $aRow['phonenumber'] . '</a>';

    $row[] = $aRow['title'];
    if(isExistMailChimp()){
        $contact_audience_ids = get_contact_assigned_audience_ids($aRow['id']);
        if(empty($contact_audience_ids)){
            $subscribed_disable = "disabled";
        }else{
            $subscribed_disable = "";
        }
        if(empty($contact_audience_ids)){
            $row[] = '<a class="btn btn-danger" disabled style="margin-left:10px;" data-id="'.$aRow['id'].'"><i class="fa fa-eye"></i></a>';
        }else{
            $row[] = '<a href="#" class="btn btn-primary subscribed_unsubscribed_icon" style="margin-left:10px;" data-id="'.$aRow['id'].'"><i class="fa fa-eye"></i></a>';
        }

        if(empty($contact_audience_ids)){
           $row[]='<a href="'.admin_url().'mailchimp/link_contact_to_mailchimp/'.$aRow["id"].'" class="btn btn-info link-to-mailchimp-btn">' . _l('add_to') . ' <img src="'.site_url().'modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" /></a>'; 
        }else{
            $row[] = '<a href="#" class="linked-mailchimp-btn mailchimp-linked-txt-color"><img src="'.site_url().'modules/mailchimp/assets/logo.svg" />' . _l(' mailchimp_linked') . '</a>';
        }
    }
    
    
    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    if ($aRow['registration_confirmed'] == 0) {
        $row['DT_RowClass'] .= ' alert-info requires-confirmation';
        $row['Data_Title']  = _l('customer_requires_registration_confirmation');
        $row['Data_Toggle'] = 'tooltip';
    }

    $row = hooks()->apply_filters('all_contacts_table_row', $row, $aRow);

    $output['aaData'][] = $row;
}
