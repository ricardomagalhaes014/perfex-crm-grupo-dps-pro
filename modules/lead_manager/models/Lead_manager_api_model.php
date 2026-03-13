<?php

defined('BASEPATH') or exit('No direct script access allowed');



class Lead_manager_api_model extends App_Model

{   
    const STATUS_NOT_STARTED = 1;

    const STATUS_AWAITING_FEEDBACK = 2;

    const STATUS_TESTING = 3;

    const STATUS_IN_PROGRESS = 4;

    const STATUS_COMPLETE = 5;
    
    public function __construct()
    {
        parent::__construct();
    }
    public function get_user_data($id = '')
    {
        $this->db->select('lead_manager_user_api.*,staff.staffid,staff.firstname,staff.lastname');
        if ('' != $id) {
            $this->db->where('lead_manager_user_api.id', $id);
        }
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'lead_manager_user_api.staff_id=' . db_prefix() . 'staff.staffid', 'left');
        return $this->db->get(db_prefix().'lead_manager_user_api')->result_array();
    }
    public function add_user_token($data){
        $issuedAt = time();
        $expirationTime = strtotime($data['expiration_date']);
        print_r( $expirationTime);die;
        $payload = [
            'staff_id' => $data['staff_id'],
            'name'     => get_staff_full_name( $data['staff_id']),
            'iat' => $issuedAt,
            'exp' => $expirationTime, 
        ];
        //Load Authorization Library or Load in autoload config file
        $this->load->library('Authorization_Token');
        // generate a token
        $data['token'] = $this->authorization_token->generateToken($payload);
        $data['expiration_date'] = to_sql_date($data['expiration_date'], true);
        $this->db->insert(db_prefix().'lead_manager_user_api', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New User-token Added [ID: '.$insert_id.', Staff Id : '.$data['staff_id'].']');
        }
        return $insert_id;
    }
    public function update_user_token($data, $id){
        $issuedAt = time();
        $expirationTime = strtotime($data['expiration_date']);
        print_r( $expirationTime);die;
        $payload = [
            'staff_id' => $data['staff_id'],
            'name'     => get_staff_full_name($data['staff_id']),
            'iat' => $issuedAt,
            'exp' => $expirationTime, 
        ];
        //Load Authorization Library or Load in autoload config file
        $this->load->library('Authorization_Token');
        // generate a token
        $data['token'] = $this->authorization_token->generateToken($payload);
        $data['expiration_date'] = to_sql_date($data['expiration_date'], true);
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'lead_manager_user_api', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('User Token Updated [ID: '.$id.' Staff Id: '.$id.']');
            return true;
        }
        return false; 
    }
    public function delete_user_token($id){
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'lead_manager_user_api');
        if ($this->db->affected_rows() > 0) {
            log_activity('User Token Deleted [ID: '.$id.']');
            return true;
        }
        return false;
    }
    public function check_token($token)
    {
        $this->db->where('token', $token);
        $user = $this->db->get(db_prefix().'lead_manager_user_api')->row();
        if (isset($user)) {
            return true;
        }
        return false;
    }
    public function login($email,$password,$remember,$staff){
        
        if ((!empty($email)) and (!empty($password))) {
            $table = db_prefix() . 'contacts';
            $_id   = 'id';
            if ($staff == true) {
                $table = db_prefix() . 'staff';
                $_id   = 'staffid';
            }
            $this->db->where('email', $email);
            $user = $this->db->get($table)->row();
            if($user){
                if (!app_hasher()->CheckPassword($password, $user->password)) {
                    hooks()->do_action('failed_login_attempt', [
                        'user'            => $user,
                        'is_staff_member' => $staff,
                    ]);

                    log_activity('Failed Login Attempt [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');

                    return ['status'=>false,'message'=>'Password does not matched','data'=>[]];
                }  
            }else{
                hooks()->do_action('non_existent_user_login_attempt', [
                    'email'           => $email,
                    'is_staff_member' => $staff,
                ]);

                log_activity('Non Existing User Tried to Login [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');
                return ['status'=>false,'message'=>'user does not exist with this email'];
            }
            
            if ($user->active == 0) {
                hooks()->do_action('inactive_user_login_attempt', [
                    'user'            => $user,
                    'is_staff_member' => $staff,
                ]);
                log_activity('Inactive User Tried to Login [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');

                return ['status'=>false,'message'=>'Inactive user trying to login','data'=>['memberinactive'=>true]]; 
            }

            $twoFactorAuth = false;
            if ($staff == true) {
                $twoFactorAuth = $user->two_factor_auth_enabled == 0 ? false : true;

                if (!$twoFactorAuth) {
                    hooks()->do_action('before_staff_login', [
                        'email'  => $email,
                        'userid' => $user->$_id,
                    ]);

                    $user_data = [
                        'staff_user_id'   => $user->$_id,
                        'staff_logged_in' => true,
                    ];
                } else {
                    $user_data                = [];
                    $user_data['tfa_staffid'] = $user->staffid;
                    if ($remember) {
                        $user_data['tfa_remember'] = true;
                    }
                }
            } else {
                hooks()->do_action('before_client_login', [
                    'email'           => $email,
                    'userid'          => $user->userid,
                    'contact_user_id' => $user->$_id,
                ]);

                $user_data = [
                    'client_user_id'   => $user->userid,
                    'contact_user_id'  => $user->$_id,
                    'client_logged_in' => true,
                ];
            }
            //set user token
            $user_token = $this->db->where('staff_id', $user->staffid)->get(db_prefix() . 'lead_manager_user_api')->row();
          
            $this->load->library('Authorization_Token');
            $issuedAt = time();
            $expirationTime = $issuedAt + 60 * 60 * 24 * 60;
            $payload = array(
                'staff_id' => $user->staffid,
                'name'     => $user->firstname . ' ' . $user->lastname,
                'iat' => $issuedAt,
                'exp' => $expirationTime, 
            );
            if ($user_token) {
                if(strtotime($user_token->expiration_date) < time()){
                    $token = $this->authorization_token->generateToken($payload);
                    $expiration_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' + 60 day'));
                    $data['token'] =  $token;
                    $data['expiration_date'] = to_sql_date($expiration_date, true);
                    $this->db->where('staff_id', $user->staffid);
                    $this->db->update(db_prefix().'lead_manager_user_api', $data);
                    $user->token = $token;
                }else{
                    $user->token =  $user_token->token;
                }
               
            } else {
                $token = $this->authorization_token->generateToken($payload);
                $expiration_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' + 60 day'));
                $insertData = ['staff_id' => $user->staffid, 'token' => $token, 'expiration_date' =>to_sql_date( $expiration_date,true)];
                $this->db->insert(db_prefix() . 'lead_manager_user_api', $insertData);
                $user->token = $token;
            }

            $this->session->set_userdata($user_data);
            if (!$twoFactorAuth) {
                $this->update_login_info($user->$_id, $staff);
            } 
            else {
                $user->two_factor_auth=true;
            }
            $this->load->model('staff_model');
            $user->twilio_number=get_staff_own_twilio_number();
            $user->permissions=$this->staff_model->get_staff_permissions($user->staffid);
            return ['status'=>true,'message'=>'success','data'=>$user];

        }else{
            return ['status'=>false,'message'=>'Email OR password could not be emapty'];
        }  
    }

    private function update_login_info($user_id, $staff)
    {
        $table = db_prefix() . 'contacts';
        $_id   = 'id';
        if ($staff == true) {
            $table = db_prefix() . 'staff';
            $_id   = 'staffid';
        }
        $this->db->set('last_ip', $this->input->ip_address());
        $this->db->set('last_login', date('Y-m-d H:i:s'));
        $this->db->where($_id, $user_id);
        $this->db->update($table);
    }


    public function get_table($name,$id=''){
        \modules\lead_manager\core\Apiinit::check_url('api');
        switch ($name) {
            case 'projects':
                $this->load->model('Projects_model');

                return $this->Projects_model->get($id);
                break;
            case 'tasks':
                $this->load->model('Tasks_model');

                return $this->Tasks_model->get($id);
                break;
            case 'staffs':
                $this->load->model('Staff_model');

                return $this->Staff_model->get($id);
                break;
            case 'tickets':
                $this->load->model('Tickets_model');

                return $this->Tickets_model->get($id);
                break;
            case 'leads':
                $this->load->model('Leads_model');

                return $this->Leads_model->get($id);
                break;
            case 'clients':
                $this->load->model('Clients_model');

                return $this->Clients_model->get($id);
                break;
            case 'contracts':
                $this->load->model('Contracts_model');

                return $this->Contracts_model->get($id);
                break;
            case 'invoices':
                $this->load->model('Invoices_model');
                $data = $this->Invoices_model->get($id);
                if (!empty($data) && !empty($id)) {
                    $data->items = $this->get_api_custom_data($data->items, 'items', '', true);
                }

                return $data;
                break;
            case 'estimates':
                $this->load->model('Estimates_model');
                $data = $this->Estimates_model->get($id);
                if (!empty($data) && !empty($id)) {
                    $data->items = $this->get_api_custom_data($data->items, 'items', '', true);
                }

                return $data;
                break;
            case 'departments':
                $this->load->model('Departments_model');

                return $this->Departments_model->get($id);
                break;
            case 'payments':
                $this->load->model('Payments_model');

                return $this->Payments_model->get($id);
                break;
            case 'roles':
                $this->load->model('Roles_model');

                return $this->Roles_model->get($id);
                break;
            case 'proposals':
                $this->load->model('Proposals_model');
                $data = $this->Proposals_model->get($id);
                if (!empty($data) && !empty($id)) {
                    $data->items = $this->get_api_custom_data($data->items, 'items', '', true);
                }

                return $data;
                break;
            case 'knowledge':
                $this->load->model('Knowledge_base_model');

                return $this->Knowledge_base_model->get($id);
                break;
            case 'goals':
                $this->load->model('Goals_model');

                return $this->Goals_model->get($id);
                break;
            case 'currencies':
                $this->load->model('Currencies_model');

                return $this->Currencies_model->get($id);
                break;
            case 'annex':
                $this->load->model('Annex_model');

                return $this->Annex_model->get($id);
                break;
            case 'contacts':
                $this->load->model('Clients_model');

                return $this->clients_model->get_contact($id);
                break;
            case 'all_contacts':
                $this->load->model('Clients_model');

                return $this->clients_model->get_contacts($id);
                break;
            case 'invoices':
                $this->load->model('invoices_model');

                return $this->invoices_model->get($id);
                break;
            case 'invoice_items':
                $this->load->model('invoice_items_model');

                return $this->invoice_items_model->get($id);
                break;
            case 'milestones':
                return $this->get_milestones_api($id);
                break;
            case 'expenses':
                return $this->get_expenses_api($id);
                break;
            case 'creditnotes':
                $this->load->model('Credit_notes_model');
                $data = $this->Credit_notes_model->get($id);
                if (!empty($data) && !empty($id)) {
                    $data->items = $this->get_api_custom_data($data->items,"items", '', true);
                }
                return $data;
                break;
            default:
                return '';
                break;
        }
        // switch ($name) {
        //     case 'leads':
        //         $this->load->model('Leads_model');
        //         //return $this->Leads_model->get($id);
        //         break;
        //     default:
        //         return '';
        //         break;
        // }
    }

    public function get_lead_data($id = '', $where = [],$limit=10,$offset=0){
        $this->load->model('Leads_model');
        $this->db->select('*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.id,' . db_prefix() . 'leads_status.name as status_name,' . db_prefix() . 'leads_sources.name as source_name');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id=' . db_prefix() . 'leads.status', 'left');
        $this->db->join(db_prefix() . 'leads_sources', db_prefix() . 'leads_sources.id=' . db_prefix() . 'leads.source', 'left');

        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'leads.id', $id);
            $lead = $this->db->get(db_prefix() . 'leads')->row();
            if ($lead) {
                if ($lead->from_form_id != 0) {
                    $lead->form_data = $this->Leads_model->get_form([
                        'id' => $lead->from_form_id,
                    ]);
                }
                $lead->attachments = $this->get_lead_attachments_data($id);
                
                $lead->public_url  = leads_public_url($id);
            }
            return $lead;
        }
        return $this->db->get(db_prefix() . 'leads',$limit,$offset)->result_array();
    }
    
    public function get_api_custom_data($data, $custom_field_type, $id = '', $is_invoice_item = false)
    {
        $this->db->where('active', 1);
        $this->db->where('fieldto', $custom_field_type);

        $this->db->order_by('field_order', 'asc');
        $fields       = $this->db->get(db_prefix().'customfields')->result_array();
        $customfields = [];
        if ('' === $id) {
            foreach ($data as $data_key => $value) {
                $data[$data_key]['customfields'] = [];
                $value_id                        = $value['id'] ?? '';
                if ('customers' == $custom_field_type) {
                    $value_id = $value['userid'];
                }
                if ('tickets' == $custom_field_type) {
                    $value_id = $value['ticketid'];
                }
                if ('staff' == $custom_field_type) {
                    $value_id = $value['staffid'];
                }
                foreach ($fields as $key => $field) {
                    $customfields[$key]        = new StdClass();
                    $customfields[$key]->id    = $field['id'];
                    $customfields[$key]->label = $field['name'];
                    if('items' == $custom_field_type && !$is_invoice_item) {
                        $custom_field_type = 'items_pr';
                        $value_id          = $value['itemid'] ?? $value['id'];
                    }
                    $customfields[$key]->value = get_custom_field_value($value_id, $field['id'], $custom_field_type, false);
                }
                $data[$data_key]['customfields'] = $customfields;
            }
        }
        if ('' !== $id && is_numeric($id)) {
            $data->customfields = new StdClass();
            foreach ($fields as $key => $field) {
                $customfields[$key]        = new StdClass();
                $customfields[$key]->id = $field['id'];
                $customfields[$key]->label = $field['name'];
                if ('items' == $custom_field_type && !$is_invoice_item) {
                    $custom_field_type = 'items_pr';
                }
                $customfields[$key]->value = get_custom_field_value($id, $field['id'], $custom_field_type, false);
            }
            $data->customfields = $customfields;
        }

        return $data;
    }

    public function get_lead_profile_data($leadid){

    }

    public function getZoomMeetingDetails($id='',$where=[],$limit=10,$offset=0){
        
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->row();
        } else {
            $this->db->select('id,name,email,staff_name,meeting_date,status,remark,is_client,meeting_id');
            $swhere = '';
            if (has_permission('lead_manager', '', 'view_own')) {
                $swhere .='staff_id =' . get_staff_user_id().''; 
            }
            if($this->input->post('is_client_no')){
                $swhere .=' AND is_client=0';
            }
            if($this->input->post('is_client_yes')){
                if($this->input->post('is_client_no')){
                    $swhere .=' AND is_client=0 OR is_client=1';
                }else{
                    $swhere .=' AND is_client=1';
                }
            }
            if($this->input->post('status_waiting')){
                if($this->input->post('status_end')){
                    $swhere .=' AND status=0 OR status=1';
                   
                }else{
                    $swhere .=' AND status=1';
                  
                }
            }
            if($this->input->post('status_end')){
                $swhere .=' AND status=1';
              
            }
            if ($this->input->post('period_from')) {
                $swhere .=" AND meeting_date >= '" . to_sql_date($this->input->post('period_from')) . "'";
                
            }
            if ($this->input->post('period_to')) {
                $swhere .= " AND meeting_date <= '" . to_sql_date($this->input->post('period_to')) . "'+ INTERVAL 1 DAY";
            
            }
        
            $this->db->where($where);
            $this->db->where($swhere);
            if ($this->input->post('q')) {
                $q = $this->input->post('q');
                $q = trim($q);
            } 
            if(!empty($q)){
                $this->db->group_start();
                $this->db->like('CONVERT(name USING utf8)',$q);
                $this->db->or_like('CONVERT(email USING utf8)', $q);
                $this->db->or_like('CONVERT(staff_name USING utf8)', $q);
                $this->db->or_like('CONVERT(meeting_date USING utf8)', $q);
                $this->db->or_like('CONVERT(status USING utf8)', $q);
                $this->db->or_like('CONVERT(remark USING utf8)', $q);
                $this->db->or_like('CONVERT(is_client USING utf8)', $q);
                $this->db->or_like('CONVERT(email USING utf8)', $q);
                $this->db->or_like('CONVERT(meeting_id USING utf8)', $q); 
            }

            $tempdb = clone $this->db;
            $data['total'] = $tempdb->from(db_prefix().'lead_manager_zoom_meeting')->count_all_results(); 
            $data['records'] =$this->db->order_by('id','DESC')->limit($limit,$offset)->get(db_prefix().'lead_manager_zoom_meeting')->result_array();
           // echo $this->db->last_query();die;
            return $data;
           
        }
    }
    
    public function get_proposal_data($rel_id,$rel_type,$limit=10,$offset=0){
        $where = 'rel_id = '. $rel_id.' AND rel_type = "'.$rel_type.'"';
        $data['total'] = $this->db->where($where)->get(db_prefix().'proposals')->num_rows();
        $data['records'] = $this->db->where($where)->order_by('id','desc')->get(db_prefix().'proposals',$limit,$offset)->result_array();
        return $data;
    }

    public function get_lead_task_data($rel_id,$rel_type,$status,$limit,$offset){
        $where='rel_id='.$rel_id.' AND rel_type="'.$rel_type.'"';
        $q  = '';
        
        if(!empty($status)){
            $where.= ' AND status IN ('. implode(',', $status ).')';
        }
        
        $data['total'] = $this->db->where($where)->get(db_prefix().'tasks')->num_rows();
        $data['records'] = $this->db->where($where)->order_by('id','desc')->get(db_prefix().'tasks',$limit,$offset)->result_array();
        return $data;
        
    }

    public function get_lead_task_data1($rel_id,$rel_type,$limit=10,$offset=0){
        
        $where='rel_id='.$rel_id.' AND rel_type="'.$rel_type.'"';
        $q  = '';
        
        if(!empty($status)){
            $where.= ' AND status IN ('. implode(',', $status ).')';
        }
       
       
        if ($this->input->post('q')) {
            $q = $this->input->post('q');
            $q = trim($q);
        } 
       
        $this->db->select(
           'tasks.id as id,
           tasks.name as task_name,
           status,
           startdate,
           duedate,
           '.get_sql_select_task_asignees_full_names().' as assignees,
           (SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'tasks.id and rel_type="task" ORDER by tag_order ASC) as tags,
           priority,
           billed,
           recurring,
           (SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as is_assigned,'
           .get_sql_select_task_assignees_ids() . ' as assignees_ids,
           (SELECT MAX(id) FROM ' . db_prefix() . 'taskstimers WHERE task_id=' . db_prefix() . 'tasks.id and staff_id=' . get_staff_user_id() . ' and end_time IS NULL) as not_finished_timer_by_current_staff,
           (SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as current_user_is_assigned,
           (SELECT CASE WHEN addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0 THEN 1 ELSE 0 END) as current_user_is_creator'
        );
        $this->db->where($where);
        if(!empty($q)){
            $this->db->group_start();
            $this->db->like('tasks.id',$q);
            $this->db->or_like('tasks.name', $q);
            $this->db->or_like('tasks.status', $q);
            $this->db->or_like('tasks.name', $q);
            $this->db->or_like('tasks.startdate', $q);
            $this->db->or_like('tasks.duedate', $q);
            $this->db->or_like('tasks.priority', $q);
            $this->db->or_like('tasks.billed', $q);
            $this->db->or_like('tasks.recurring', $q);
            $this->db->or_like(get_sql_select_task_asignees_full_names(), $q);
            $this->db->or_like('(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'tasks.id and rel_type="task" ORDER by tag_order ASC)', $q);
            $this->db->or_like('CONVERT(( SELECT MAX(id) FROM '.db_prefix().'taskstimers WHERE task_id = '.db_prefix().'tasks.id AND staff_id = 1 AND end_time IS NULL) USING utf8
            )', $q);
            $this->db->or_like('CONVERT( (SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') USING utf8)', $q);
            $this->db->or_like(get_sql_select_task_assignees_ids(), $q);
            $this->db->or_like("CONVERT( (SELECT CASE WHEN addedfrom=" . get_staff_user_id() . " AND is_added_from_contact=0 THEN 1 ELSE 0 END) USING utf8)", $q);
            $this->db->or_like(tasks_rel_name_select_query(), $q);
            $this->db->group_end();
        }
        
        $tempdb = clone $this->db;
        $data['total'] = $tempdb->from(db_prefix().'tasks')->count_all_results(); 
        $data['records'] =$this->db->order_by('id','ASC')->limit($limit,$offset)->get(db_prefix().'tasks')->result_array();
        return $data;
    }

    public function get_statuses()
    {  
        $statuses = hooks()->apply_filters('before_get_task_statuses', [
            [
                'id'             => self::STATUS_NOT_STARTED,
                'color'          => '#989898',
                'name'           => _l('task_status_1'),
                'order'          => 1,
                'filter_default' => true,
            ],
            [
                'id'             => self::STATUS_IN_PROGRESS,
                'color'          => '#03A9F4',
                'name'           => _l('task_status_4'),
                'order'          => 2,
                'filter_default' => true,
            ],
            [
                'id'             => self::STATUS_TESTING,
                'color'          => '#2d2d2d',
                'name'           => _l('task_status_3'),
                'order'          => 3,
                'filter_default' => true,
            ],
            [
                'id'             => self::STATUS_AWAITING_FEEDBACK,
                'color'          => '#adca65',
                'name'           => _l('task_status_2'),
                'order'          => 4,
                'filter_default' => true,
            ],
            [
                'id'             => self::STATUS_COMPLETE,
                'color'          => '#84c529',
                'name'           => _l('task_status_5'),
                'order'          => 100,
                'filter_default' => false,
            ],
        ]);

        usort($statuses, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $statuses;
    }

    public function get_lead_attachments_data($id){
        $this->load->model('Leads_model');
        $attachments =  $this->Leads_model->get_lead_attachments($id);
        foreach ($attachments as $key => $attachment) {
          $attachments[$key][ 'attachment_url'] =  site_url('download/file/lead_attachment/'.$attachment['id']) ;
          $attachment[$key]['isDeletable ']= ($attachment['staffid'] == get_staff_user_id() || is_admin())?TRUE :FALSE;
        }
       return $attachments;
    }

    public function get_lead_activity_log_data($leadid){
        $this->load->model('Leads_model');
        $activity_log =  $this->leads_model->get_lead_activity_log($leadid);
        foreach ($activity_log as $key => $log) {
            $activity_log[$key]['profile_image'] = $log['staffid'] != 0 ? staff_profile_image_url($log['staffid']) :'';
            $activity_log[$key]['profile_url'] = $log['staffid'] != 0 ?  admin_url('profile/'.$log["staffid"]) :'';
            $activity_log[$key]['date'] = time_ago($log['date']); 
            $activity_log[$key]['additional_data'] =!empty($log['additional_data']) ? unserialize($log['additional_data']):[];
            $additional_data='';
            if(!empty($log['additional_data'])){
                $additional_data = unserialize($log['additional_data']);
                $activity_log[$key]['description'] =  ($log['staffid'] == 0) ? _l($log['description'],$additional_data) : $log['full_name'] .' - '._l($log['description'],$additional_data);
            }else {
               
                if($log['custom_activity'] == 0){
                $activity_log[$key]['description'] = $log['full_name'] . ' - '._l($log['description']);
                } else {
                $activity_log[$key]['description'] = $log['full_name'] . ' - '._l($log['description'],'',false);
                }
            }
            //$activity_log[$key]['description'] = $log['full_name'] . ' - '._l($log['description']);
        }
        return $activity_log;
    }

    public function get_reminder_data($rel_id,$rel_type,$limit=10,$offset=0){
        $where='rel_id='.$rel_id.' AND rel_type="'.$rel_type.'"';
        $q  = '';
        
        if(!empty($status)){
            $where.= ' AND status IN ('. implode(',', $status ).')';
        }
       
       
        if ($this->input->post('q')) {
            $q = $this->input->post('q');
            $q = trim($q);
        } 
       
        $this->db->select(
            'id,
            description,
            date,
            staff,
            isnotified,
            firstname,
            lastname,
            creator,
            rel_type'
        );
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'reminders.staff');
        $this->db->where($where);
        if(!empty($q)){
            $this->db->group_start();
            $this->db->like('CONVERT(description USING utf8)',$q);
            $this->db->or_like('CONVERT(date USING utf8)',$q);
            $this->db->or_like('CONVERT(staff USING utf8)',$q);
            $this->db->or_like('convert(isnotified USING utf8)',$q);
            $this->db->or_like('CONVERT(firstname USING utf8)',$q);
            $this->db->or_like('CONVERT(lastname USING utf8)',$q);
            $this->db->or_like('convert(id USING utf8)',$q);
            $this->db->or_like('CONVERT(creator USING utf8)',$q);
            $this->db->or_like('CONVERT(rel_type USING utf8)',$q);
            $this->db->group_end();
        }
       
        $tempdb = clone $this->db;
        $data['total'] = $tempdb->from(db_prefix().'reminders')->count_all_results(); 
        $data['records'] =$this->db->order_by('date','DESC')->limit($limit,$offset)->get(db_prefix().'reminders')->result_array();
        return $data;
    }

    public function get_notes_data($id,$rel_id,$rel_type){
        $this->load->model('misc_model');
        if($id==''){
            $notes        = $this->misc_model->get_notes( $rel_id, $rel_type);
            foreach ($notes as $key=>$note) {
                $data[$key]['id']=$note['id'];
                $data[$key]['date_contacted']=$note['date_contacted'];
                $data[$key]['dateadded']=$note['dateadded'];
                $data[$key]['addedfrom']=$note['addedfrom'];
                $data[$key]['addedfrom_profle_url']= admin_url('profile/'.$note["addedfrom"]);
                $data[$key]['addedfrom_fullname']= get_staff_full_name($note['addedfrom']);
                $data[$key]['addedfrom_profile_image']= staff_profile_image_url($note['addedfrom']);
                $data[$key]['description']= app_happy_text($note['description']);
                $data[$key]['isDeletable'] = ($note['addedfrom'] == get_staff_user_id() || is_admin()) ?TRUE:FALSE; 
            }

          
        }else{
            $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'notes.addedfrom');
            $this->db->where('rel_id', $rel_id);
            $this->db->where('rel_type', $rel_type);
            $this->db->where('id',$id);
            $note = $this->db->get(db_prefix() . 'notes')->row_array();

            $data['id']=$note['id'];
            $data['date_contacted']=$note['date_contacted'];
            $data['dateadded']=$note['dateadded'];
            $data['addedfrom']=$note['addedfrom'];
            $data['addedfrom_profle_url']= admin_url('profile/'.$note["addedfrom"]);
            $data['addedfrom_fullname']= get_staff_full_name($note['addedfrom']);
            $data['addedfrom_profile_image']= staff_profile_image_url($note['addedfrom']);
            $data['description']= app_happy_text($note['description']);
            $data['isDeletable'] = ($note['addedfrom'] == get_staff_user_id() || is_admin()) ?TRUE:FALSE; 
        }
       
        return $data;
    }

    public function get_email_data($direction,$status,$limit=10,$offset=0){
        $where = ''.db_prefix().'lead_manager_mailbox.staffid=1 AND status = "'.$status.'"';
        if(!empty($direction)){
            $where.=' AND direction = "'.$direction.'"';
        }
        
        $this->db->select('
            '.db_prefix().'lead_manager_mailbox.id as eid,
            fromName,
            to_email,
            subject,
            mail_date,
            is_read,
            is_attachment,
            is_favourite,
            is_bookmark,
            '.db_prefix().'lead_manager_mailbox_attachments.file_name AS FILE,
            email_size,
            from_email
            '
        );
        $this->db->where($where);
        if ($this->input->post('q')) {
            $q = $this->input->post('q');
            $q = trim($q);
        } 
        if(!empty($q)){
            $this->db->group_start();
            $this->db->like('CONVERT(fromName USING utf8)',$q);
            $this->db->or_like('CONVERT(to_email USING utf8)',$q);
            $this->db->or_like('CONVERT(subject USING utf8)',$q);
            $this->db->or_like('CONVERT(mail_date USING utf8)',$q);
            $this->db->or_like('CONVERT(is_read USING utf8)',$q);
            $this->db->or_like('CONVERT(is_attachment USING utf8)',$q);
            $this->db->or_like('CONVERT(is_favourite USING utf8)',$q);
            $this->db->or_like('CONVERT(is_bookmark USING utf8)',$q);
            $this->db->or_like('CONVERT('.db_prefix().'lead_manager_mailbox_attachments.file_name USING utf8)',$q);
            $this->db->or_like('CONVERT(email_size USING utf8)',$q);
            $this->db->or_like('CONVERT(from_email USING utf8)',$q);
            $this->db->or_like('CONVERT(message USING utf8)',$q);
            $this->db->or_like('CONVERT('.db_prefix().'lead_manager_mailbox_attachments.mailbox_id USING utf8)',$q);
            $this->db->group_end();
        }

        $this->db->join(db_prefix().'lead_manager_mailbox_attachments',db_prefix().'lead_manager_mailbox_attachments.staff_id='.db_prefix().'lead_manager_mailbox.staffid AND '.db_prefix().'lead_manager_mailbox_attachments.mailbox_id='.db_prefix().'lead_manager_mailbox.id','left');
      
        $tempdb = clone $this->db;
        $data['total'] =  $tempdb->get(db_prefix().'lead_manager_mailbox')->num_rows();  
       //echo  $tempdb->last_query();die;
        $data['records'] =$this->db->order_by(''.db_prefix().'lead_manager_mailbox.id','DESC')->group_by('tbllead_manager_mailbox.id')->limit($limit,$offset)->get(db_prefix().'lead_manager_mailbox')->result_array();

        foreach ($data['records'] as $key => $r) {
            $data['records'][$key]['email_size']= formatSizeUnits($r['email_size']);
            $data['records'][$key]['file_url'] =$r['is_attachment'] ? admin_url('lead_manager/download_attachemnts/'.$r['eid']):'';
            $data['records'][$key]['mail_date']= _dt($r['mail_date']);
        }
        return $data;
    }

}