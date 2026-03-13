<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('get_service_name_by_id')) {
    function get_service_name_by_id($service_id)
    {
        // Get CodeIgniter instance
        $CI =& get_instance();
        
        // Load database if it's not already loaded
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        // Query the database to get the service name
        $CI->db->select('service_name');
        $CI->db->from(db_prefix().'meeting_services');
        $CI->db->where('id', $service_id);
        $query = $CI->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->service_name; // Return the service name
        } else {
            return null; // Return null if not found
        }
    }

    function get_lead_name_by_id($id)
    {
        // Get CodeIgniter instance
        $CI =& get_instance();
        
        // Load database if it's not already loaded
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        // Query the database to get the service name
        $CI->db->select('name');
        $CI->db->from(db_prefix().'leads');
        $CI->db->where('id', $id);
        $query = $CI->db->get();

        if ($query->num_rows() > 0) {
            return $query->row()->name; // Return the service name
        } else {
            return null; // Return null if not found
        }
    }

    function get_staff_by_ids($staff_ids = [])
    {
        $CI =& get_instance();
        if (!empty($staff_ids)) {
            // Apply WHERE IN for staff IDs
            $CI->db->where_in('staffid', $staff_ids);
            // Retrieve and return the result as an array
            return $CI->db->get(db_prefix() . 'staff')->result_array();
        }
        
        return []; // Return an empty array if no IDs are provided
    }
    function get_customer_by_ids($client_ids = [])
    {
        $CI =& get_instance();
        if (!empty($client_ids)) {
            // Apply WHERE IN for staff IDs
            $CI->db->where_in('userid', $client_ids);
            // Retrieve and return the result as an array
            return $CI->db->get(db_prefix() . 'clients')->result_array();
        }
        
        return []; // Return an empty array if no IDs are provided
    }


    function staff_meeting_ids(){
        $CI =& get_instance();
        $CI->db->select('meeting_id');
        $CI->db->where('participant_id', get_staff_user_id());
        $CI->db->where('participant_type', 'Staff');
        // Retrieve and return the result as an array
        $query = $CI->db->get(db_prefix() . 'meeting_participants')->result_array();
        $participant_ids = array_column($query, 'meeting_id');
        return $participant_ids;
    }
    function client_meeting_ids(){
        $CI =& get_instance();
        $CI->db->select('meeting_id');
        $CI->db->where('participant_id', get_client_user_id());
        $CI->db->where('participant_type', 'Customer');
        // Retrieve and return the result as an array
        $query = $CI->db->get(db_prefix() . 'meeting_participants')->result_array();
        $participant_ids = array_column($query, 'meeting_id');
        return $participant_ids;
    }

    function hopper_verify($p_code){
        $product =0;

        $code = urlencode($p_code);
        $url = "https://verify.hopperstack.com?type=install&purchase_code=" . $code . "&domain=" . $_SERVER['HTTP_HOST'] . "&product=" . $product;
  
      

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    
    }
    
}