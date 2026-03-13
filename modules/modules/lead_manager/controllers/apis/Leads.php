<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/REST_Controller.php';
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
class Leads extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Lead_manager_api_model');
        $this->load->model('lead_manager_model');
        $this->load->model('leads_model');
        $this->load->helper('lead_manager_api');
        $this->load->helper('lead_manager');
        $this->load->library('sms/app_sms');
        $this->load->model('misc_model');
    }

    public function get_data($id = '')
    {
        // If the id parameter doesn't exist return all the
        $result = [];
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $where = [];

        $data = $this->Lead_manager_api_model->get_lead_data($id, $where, $limit, $offset);
        // Check if the data contains
        if ($data) {
            $data = $this->Lead_manager_api_model->get_api_custom_data($data, "leads", $id);
            $this->load->model('leads_model');

            if ($id == '') {
                $result['total'] = $this->db->where($where)->get(db_prefix() . 'leads')->num_rows();
            }
            $result['records'] = $data;
            $result['statusList'] = $this->leads_model->get_status();
            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'message' => 'Lead data is here',
                'data' => $result
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function update_status()
    {
        $this->_lm_allow_methods(['POST']);
        $data = $this->input->post();
        $result = $this->lead_manager_model->update_lead_status($data);
        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Lead status updated',
                'data' => ['status' => $data['status']]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Lead status Not updated'
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function get_lead_profile_data($id)
    {
        $staffid = getStaffId();
        $data = [];
       
        if (is_numeric($id)) {
            $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' .  $staffid . ' OR addedfrom=' .  $staffid . ' OR is_public=1)');

            $lead = $this->Lead_manager_api_model->get_lead_data($id, $leadWhere);

            if (!$lead) {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Lead Not Found',
                    'data' => '',
                ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code

            }

            if (total_rows(db_prefix() . 'clients', ['leadid' => $id]) > 0) {
                $data['lead_locked'] = ((!is_admin($staffid) && get_option('lead_lock_after_convert_to_customer') == 1) ? true : false);
            }

            $lead =  $this->Lead_manager_api_model->get_api_custom_data($lead, "leads", $id);
            $data['lead']       = $lead;
            $data['lead']->tags  = get_tags_in($id, 'lead');
            $data['activity_log']  = $this->leads_model->get_lead_activity_log($id);
        }
        $this->response([
            'status' => TRUE,
            'message' => 'Lead data is here',
            'data' => $data,
        ], REST_Controller::HTTP_OK);
    }

    public function get_lead_proposal($rel_id)
    {
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $data = $this->lead_manager_api_model->get_proposal_data($rel_id, 'lead', $limit, $offset);

        if (!empty($data)) {
            $this->response([
                'status' => TRUE,
                'message' => 'Lead proposal data is here',
                'data' => $data,
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No proposals founds',
                'data' => ['status' => false],
            ], REST_Controller::HTTP_OK);
        }
    }

    public function get_lead_task($rel_id)
    {
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $status = json_decode($this->input->post('status'));
        $data = $this->lead_manager_api_model->get_lead_task_data($rel_id, 'lead', $status, $limit, $offset);
        $where = 'rel_id = ' . $rel_id . ' AND rel_type = "lead"';
        $total = $this->db->where($where)->get(db_prefix() . 'proposals')->num_rows();
        if (!empty($data)) {
            $this->response([
                'status' => TRUE,
                'message' => 'Lead task data is here',
                'data' => $data,
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No proposals founds',
                'data' => ['status' => false],
            ], REST_Controller::HTTP_OK);
        }
    }

    public function save_lead($id = '')
    {

        if (!is_staff_member() || ($id != '' && !$this->leads_model->staff_can_access_lead($id))) {
            $this->response([
                'status' => FALSE,
                'message' => 'Access Denied',
                'data' => ['status' => false],
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        if ($this->input->post()) {
            $staffid = get_staff_user_id();

            $data = $this->input->post();

            if (isset($data['custom_fields'])) {
                $data['custom_fields'] = json_decode($data['custom_fields'], true);
            }
            // print_r($data);die;

            if ($id == '') {
                $id      = $this->leads_model->add($data);
                if ($id) {
                    $res_status = TRUE;
                    $message = _l('added_successfully', _l('lead'));
                    $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' .  $staffid . ' OR addedfrom=' .  $staffid . ' OR is_public=1)');
                    $lead = $this->Lead_manager_api_model->get_lead_data($id, $leadWhere);
                    $lead =  $this->Lead_manager_api_model->get_api_custom_data($lead, "leads", $id);
                    $resp_data['lead']  = $lead;
                } else {
                    $res_status = FALSE;
                    $message = 'Data not saved';
                    $resp_data = ['status' => false];
                }
            } else {
                $emailOriginal   = $this->db->select('email')->where('id', $id)->get(db_prefix() . 'leads')->row()->email;
                $proposalWarning = false;
                $message         = '';

                $success         = $this->leads_model->update($data, $id);
                if ($success) {
                    $emailNow = $this->db->select('email')->where('id', $id)->get(db_prefix() . 'leads')->row()->email;

                    $proposalWarning = (total_rows(db_prefix() . 'proposals', [
                        'rel_type' => 'lead',
                        'rel_id'   => $id,
                    ]) > 0 && ($emailOriginal != $emailNow) && $emailNow != '') ? true : false;

                    $res_status = TRUE;
                    $message = _l('updated_successfully', _l('lead'));
                } else {
                    $res_status = FALSE;
                    $message = 'Data not updated';
                }
                $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' .  $staffid . ' OR addedfrom=' .  $staffid . ' OR is_public=1)');
                $lead = $this->Lead_manager_api_model->get_lead_data($id, $leadWhere);
                $lead =  $this->Lead_manager_api_model->get_api_custom_data($lead, "leads", $id);
                $resp_data['lead']  = $lead;
            }

            $this->response([
                'status' => $res_status,
                'message' => $message,
                'data' => $resp_data,
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Please provide data',
                'data' => ['status' => false],
            ], REST_Controller::HTTP_OK);
        }
    }

    public function delete_lead($id)
    {
        if (!$id) {
            $this->response([
                'status' => TRUE,
                'message' => 'Id is required',
                'data' => '',
            ], REST_Controller::HTTP_OK);
        }
        $staffid = getStaffId();
        if (!is_lead_creator($id, $staffid) && !has_permission('leads', $staffid, 'delete')) {
            $this->response([
                'status' => TRUE,
                'message' => _l('access_denied'),
              
            ], REST_Controller::HTTP_UNAUTHORIZED);
            
        }

        $result = $this->leads_model->delete($id);
        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Lead deleted succesfully',
                'data' => ['status' => $result]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Lead not deleted'
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function get_attachments($leadid){
        $this->response([
            'status' => TRUE,
            'message' =>'Attachemnt list is here.',
            'data' => $this->lead_manager_api_model->get_lead_attachments_data($leadid),
        ], REST_Controller::HTTP_OK);
    }

    public function add_lead_attachments(){
        $id =  $this->input->post('lead_id');
        if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($id)) {
            $this->response([
                'status' => FALSE,
                'message' => _l('access_denied'),
              
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        
        handle_lead_attachments($id);
        $this->response([
            'status' => TRUE,
            'message' =>'Attachment added successfully.',
            'data' =>$this->lead_manager_api_model->get_lead_attachments_data($id),
        ], REST_Controller::HTTP_OK);
       
    }
    public function delete_lead_attachment(){
        $this->_lm_allow_methods(['POST']);
        $id = $this->input->post('id');
        $lead_id =$this->input->post('lead_id');
        if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($lead_id)) {
            $this->response([
                'status' => FALSE,
                'message' => _l('access_denied'),
              
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }
        $success = $this->leads_model->delete_lead_attachment($id);
        $status= FALSE;
        $message =_l('problem_deleting', _l('attachment'));
        if($success){
           $status = TRUE;
           $message = 'Attachment deleted successfully.';
        }
        $lead_attachments = $this->lead_manager_api_model->get_lead_attachments_data($lead_id);
    
        $this->response([
            'status' => $status,
            'message' =>$message ,
            'data' =>$lead_attachments,
        ], REST_Controller::HTTP_OK);
        
    }

    public function get_lead_activity_log($lead_id){
        $activity_log = $this->lead_manager_api_model->get_lead_activity_log_data($lead_id);
        $this->response([
            'status' => TRUE,
            'message' =>'Lead Activity log is here' ,
            'data' =>$activity_log,
        ], REST_Controller::HTTP_OK);
        
    }

    public function add_lead_activity()
    {   
        $this->_lm_allow_methods(['POST']);
        $leadid = $this->input->post('leadid');
        if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($leadid)) {
            $this->response([
                'status' => FALSE,
                'message' => _l('access_denied'),
              
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }
      
        $status = FALSE;
        $res_msg = 'Activity log not added';
        if ($this->input->post()) {
            $message = $this->input->post('activity');
            $aId     = $this->leads_model->log_lead_activity($leadid, $message);
            if ($aId) {
                $this->db->where('id', $aId);
                $this->db->update(db_prefix() . 'lead_activity_log', ['custom_activity' => 1]);
                $status = TRUE;
                $res_msg = 'Activity log added successfully';
            }
            $this->response([
                'status' => $status ,
                'message' => $res_msg,
                'data' =>  $this->lead_manager_api_model->get_lead_activity_log_data($leadid),
            ], REST_Controller::HTTP_OK);
           
        }
    }

    public function get_reminder_data($id=''){
        if(''==$id){
            $this->_lm_allow_methods(['POST']);
            $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
            $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
            $offset = ($page - 1) * $limit;
            $rel_id = $this->input->post('rel_id');
            $rel_type  = $this->input->post('rel_type');
            $reminders = $this->lead_manager_api_model->get_reminder_data($rel_id,$rel_type,$limit,$offset);
            $this->response([
                'status' => TRUE,
                'message' =>'Reminder data is here' ,
                'data' =>$reminders,
            ], REST_Controller::HTTP_OK);
        }else{
            $reminder = $this->misc_model->get_reminders($id);
            if ($reminder) {
                if ($reminder->creator == get_staff_user_id() || is_admin()) {

                    $reminder->date        = _dt($reminder->date);
                    $reminder->description = clear_textarea_breaks($reminder->description);
                    $this->response([
                        'status' => TRUE,
                        'message' =>'Reminder data is here',
                        'data' =>$reminder,
                    ], REST_Controller::HTTP_OK);
                }else{
                    $this->response([
                        'status' => FALSE,
                        'message' =>_l('access_denied') ,
                        'data' =>[],
                    ], REST_Controller::HTTP_OK);
                }
            }else{
                $this->response([
                    'status' => FALSE,
                    'message' =>'No reminder found',
                    'data' =>[],
                ], REST_Controller::HTTP_OK); 
            }
        }
        
      
    }

    public function save_reminder($id=''){
        $this->_lm_allow_methods(['POST']);
        $rel_id = $this->input->post('rel_id');
        
        if(''==$id){
            if ($this->input->post()) {
                $success = $this->misc_model->add_reminder($this->input->post(), $rel_id);
                if ($success) {
                    $this->response([
                        'status' => TRUE,
                        'message' =>'Reminder added succefully.' ,
                        
                    ], REST_Controller::HTTP_OK);
                }
            }
            $this->response([
                'status' => FALSE,
                'message' =>'Problem in adding reminder' ,
               
            ], REST_Controller::HTTP_OK);
        }else{
            $reminder = $this->misc_model->get_reminders($id);
            if ($reminder && ($reminder->creator == get_staff_user_id() || is_admin()) && $reminder->isnotified == 0) {
                $success = $this->misc_model->edit_reminder($this->input->post(), $id);
               
                if($success){
                    $this->response([
                        'status' => TRUE,
                        'message' => _l('updated_successfully', _l('reminder')) ,
                      
                    ], REST_Controller::HTTP_OK);
                }else{
                    $this->response([
                        'status' => FALSE,
                        'message' =>'Reminder update failed',
                      
                    ], REST_Controller::HTTP_OK);
                }    
            }
        }
      
    }

    public function delete_reminder_api(){
        $this->_lm_allow_methods(['POST']);
        $id=$this->input->post('id');
        $rel_id=$this->input->post('rel_id');
        if (!$id && !$rel_id) {
            $this->response([
                'status' => FALSE,
                'message' =>'No Reminder found',
            ], REST_Controller::HTTP_OK);
        }
       
        $success    = $this->misc_model->delete_reminder($id);
        $status = FALSE;
        $message    = _l('reminder_failed_to_delete');
        if ($success) {
            $status = TRUE;
            $message    = _l('reminder_deleted');
        }
        $this->response([
            'status' => $status,
            'message' => $message,
        ], REST_Controller::HTTP_OK);
    }

    public function get_note_data($id=''){

        $rel_id = $this->input->post('rel_id');
        $rel_type = $this->input->post('rel_type');
        $notes  = $this->lead_manager_api_model->get_notes_data($id, $rel_id, $rel_type);
        if($id==''){
            $this->response([
                'status' => TRUE,
                'message' =>'Notes data is here',
                'data'=>['total'=>count($notes),'records'=>$notes],
            ], REST_Controller::HTTP_OK);
        }else{
            if(!empty($notes)){
                $this->response([
                    'status' => TRUE,
                    'message' =>'Notes data is here',
                    'data'=>$notes,
                ], REST_Controller::HTTP_OK);
            }else{
                $this->response([
                    'status' => FALSE,
                    'message' =>'Note not found',
                    'data'=>[],
                ], REST_Controller::HTTP_OK);
            }
          
        }

    }
     
    /* Add new lead note */
    public function save_note($id='')
    {   
        $this->_lm_allow_methods(['POST']);
        $status=FALSE;
        $message = 'Problem in saving';
        if ($this->input->post()) {
          
            $data = $this->input->post();
            $rel_id = $data['rel_id'];
            unset($data['rel_id']);
            if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($rel_id)) {
                $this->response([
                    'status' => FALSE,
                    'message' =>_l('access_denied'),
                ], REST_Controller::HTTP_UNAUTHORIZED);
            
            }
           

            if ($data['contacted_indicator'] == 'yes') {
                $contacted_date         = to_sql_date($data['custom_contact_date'], true);
                $data['date_contacted'] = $contacted_date;
            }

            unset($data['contacted_indicator']);
            unset($data['custom_contact_date']);

            // Causing issues with duplicate ID or if my prefixed file for lead.php is used
            $data['description'] = isset($data['lead_note_description']) ? $data['lead_note_description'] : $data['description'];

            if (isset($data['lead_note_description'])) {
                unset($data['lead_note_description']);
            }
            if(''==$id){
                $note_id = $this->misc_model->add_note($data, 'lead', $rel_id);
                if ($note_id) {
                    if (isset($contacted_date)) {
                        $this->db->where('id', $rel_id);
                        $this->db->update(db_prefix() . 'leads', [
                            'lastcontact' => $contacted_date,
                        ]);
                        if ($this->db->affected_rows() > 0) {
                            $this->leads_model->log_lead_activity($rel_id, 'not_lead_activity_contacted', false, serialize([
                                get_staff_full_name(get_staff_user_id()),
                                _dt($contacted_date),
                            ]));
                        }
                    }
                    $status = TRUE;
                    $message = 'Note added successfully.';
                   
                }else{
                    $status = FALSE;
                    $message = 'Adding note failed';
                }
            }else{

                $success = $this->misc_model->edit_note($data, $id);
                if($success){
                    $status = TRUE;
                    $message = _l('note_updated_successfully');
                  
                }else{
                    $status = FALSE;
                    $message = 'Problem in updating note';  
                }
            }   
        }else{
            $status = FALSE;
            $message = 'please providde data';
           
        }
        $this->response([
            'status' => $status,
            'message' =>$message,
        ], REST_Controller::HTTP_OK);
        
    }

    public function delete_note($id)
    {
        $success = $this->misc_model->delete_note($id);
        if ($success) {
            $this->response([
                'status' => TRUE,
                'message' =>_l('deleted').' '._l('note'),
            ], REST_Controller::HTTP_OK);
        }else{
            $this->response([
                'status' => FALSE,
                'message' =>'deleting problem',
            ], REST_Controller::HTTP_OK);
        }
    }

    function send_email_box(){
        $staffid = get_staff_user_id();
        $todayDate = date("Y-m-d H:i:s");
        $mail_data = array();
        $staff_mailbox_detail = $this->lead_manager_model->get_mail_box_configuration($staffid);
        if ($this->input->post()) {
            $lead_id = get_lead_id_by_email($this->input->post('to'));
            $mail_data['staffid'] = $staffid;
            $mail_data['toid'] = $lead_id;
            $mail_data['is_client'] = 0;
            $mail_data['from_email'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_user : '';
            $mail_data['fromName'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_fromname : '';
            $mail_data['to_email'] = $this->input->post('to');
            $mail_data['subject'] = $this->input->post('subject');
            $mail_data['direction'] = 'outbound';
            $mail_data['message'] = $this->input->post('message');
            $mail_data['created_date'] = $todayDate;
            $mail_data['status'] = 'sending';
            $mail_data['is_attachment'] = isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4 ? 1 : 0;
            $mail_data['is_read'] = 1;
            $mail_data['mail_date'] = time();
            if ($this->input->post('to_cc')) {
                $mail_data['to_cc'] = $this->input->post('to_cc');
            }
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                $mail_data['email_size'] = array_sum($_FILES['attachments']['size']);
            }
            $mailbox_id = $this->lead_manager_model->addSentMailBox($mail_data);
            if ($mailbox_id) {
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                    $uploaded_files = handle_lead_manager_mail_box_attachments_array($staffid, $mailbox_id);
                    $this->lead_manager_model->insertMailboxAttachments($uploaded_files, $mailbox_id, $staffid);
                    foreach ($uploaded_files as $index => $file) {
                        $uploaded_files[$index]['read'] = true;
                        $uploaded_files[$index]['attachment'] = LEAD_MANAGER_MAILBOX_FOLDER . $mailbox_id . '/' . $file['file_name'];
                    }
                    $this->lead_manager_model->add_attachment($uploaded_files);
                }
                $response = $this->lead_manager_model->send_simple_email_lm($mail_data);
                if (is_bool($response) && $response) {
                    $this->response([
                        'status' => TRUE,
                        'message' => _l('lm_mb_mail_sent_success_alert'),
                    ], REST_Controller::HTTP_OK);
                   
                } else {
                    $this->response([
                        'status' => FALSE,
                        'message' =>$response,
                    ], REST_Controller::HTTP_OK);
                  
                   
                }
            }
            
        }
    }  

    public function get_emails(){
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $direction = $this->input->post('direction');
        $status = $this->input->post('status');
        $emails = $this->lead_manager_api_model->get_email_data($direction,$status,$limit,$offset);

        $this->response([
            'status' => TRUE,
            'message' =>'Emails data is here',
            'data'=>$emails
        ], REST_Controller::HTTP_OK);
    }

    public function view_email_data($id)
    {
        $staffid = get_staff_user_id();
        $data['mail'] = $this->lead_manager_model->view_mail_box_email($id);
        if (isset($data['mail']) && !empty($data['mail'])){
            $data['attachments'] = $this->lead_manager_model->get_mail_box_email_attachments($id);
            if( $data['mail']->is_read == 0){
                $this->lead_manager_model->update_mailbox_data(['is_read' => 1], $id);
            }
            $data['next_mail_id'] = $this->lead_manager_model->view_mail_box_email_next($id, $staffid);
            $data['prev_mail_id'] = $this->lead_manager_model->view_mail_box_email_prev($id, $staffid);
            $data['title'] = _l('lead_manger_permission_email');
            $this->response([
                'status' => TRUE,
                'message' =>'Email data is here',
                'data'=>  $data
            ], REST_Controller::HTTP_OK);
        }else{
            $this->response([
                'status' => FALSE,
                'message' =>'Email with this id not found ',
                'data'=>[],
            ], REST_Controller::HTTP_OK);
        }
    
    }

    public function mailbox_mark_as_bulk_action()
    {  
        $this->_lm_allow_methods(['POST']);
        if ($this->input->post()) {
            $post_data = $this->input->post();
            $post_data['ids']=json_decode($post_data['ids']);
            if ($post_data['action'] == 'star') {
                $post_data['is_favourite'] = 1;
            } elseif ($post_data['action'] == 'unstar') {
                $post_data['is_favourite'] = '0';
            } elseif ($post_data['action'] == 'bookmark') {
                $post_data['is_bookmark'] = 1;
            } elseif ($post_data['action'] == 'unbookmark') {
                $post_data['is_bookmark'] = '0';
            } elseif ($post_data['action'] == 'delete') {
                $resp = $this->lead_manager_model->mailbox_mark_as_bulk_delete($post_data);
                $this->response([
                    'status' => TRUE,
                    'message' =>_l('lm_mb_bulk_update_success_alert_' . $post_data['action']),
                ], REST_Controller::HTTP_OK);
            }
            $rows = $this->lead_manager_model->mailbox_mark_as_bulk($post_data);
            if ($rows) {
                $this->response([
                    'status' => TRUE,
                    'message' =>_l('lm_mb_bulk_update_success_alert_' . $post_data['action']),
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' =>_l('lm_mb_bulk_update_danger_alert'),
                ], REST_Controller::HTTP_OK);
            }
        }
        
    }
    public function get_mail_box_settings()
    {

        $staffid = get_staff_user_id();
        $data = $this->lead_manager_model->get_mail_box_configuration($staffid);
        $this->response([
            'status' => FALSE,
            'message' =>'email setting data',
            'data'=>$data
        ], REST_Controller::HTTP_OK);
        
    }
    public function save_mail_box_settings(){
        $this->_lm_allow_methods(['POST']);
        $data = $this->input->post();
        $data['settings']['is_smtp'] =  $data['is_smtp'];
        $data['settings']['is_imap'] =  $data['is_imap'];
        $response = $this->lead_manager_model->update_mail_box_configuration($data);
        if($response['status']){
            $this->response([
                'status' =>TRUE,
                'message' => $response['responseText'],
            ], REST_Controller::HTTP_OK);
        }else{
            $this->response([
                'status' => FALSE,
                'message' =>'Problem in saving',
            ], REST_Controller::HTTP_OK);
        }
    }

    public function dashboard_data(){
        $this->_lm_allow_methods(['POST']);
        $post_data = $this->input->post();
        $staff_id = (isset($post_data['staff_id']) && !empty($post_data['staff_id'])) ? $post_data['staff_id']:'';
        $request_data['staff_id'] = $staff_id;
        $request_data['days'] =  (isset($post_data['days']) && !empty($post_data['days'])) ? $post_data['days']:'1';
        $data=[];
        $this->lead_manager_model->get_total_calls($request_data);
        $data['audio_calls'] = $this->lead_manager_model->get_total_calls($request_data);
        $data['audio_calls_duration'] = $this->lead_manager_model->get_total_calls_duration($request_data);
        $data['sms'] = $this->lead_manager_model->get_total_sms($request_data);
        $data['missed_call'] = $this->lead_manager_model->get_total_missed_call($request_data);
        $data['leads_converted'] = $this->lead_manager_model->get_total_leads_converted($request_data);
        $data['zoom'] = $this->lead_manager_model->get_total_zoom_sheduled($request_data);
        $data['staff'] = $this->staff_model->get($staff_id, ['active' => 1]);
        $data['twilio'] = $this->get_active_twilio_account();
        $this->response([
            'status' => TRUE,
            'message' =>'Dashboard data is here',
            'data'=>$data,
        ], REST_Controller::HTTP_OK);
    }

    public function get_active_twilio_account()
    {
        $response = array('numbers' => 0, 'balance' => 0);
        if (get_option('call_twilio_active')) {
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            try {
                $twilio = new Client($sid, $token);
                $incomingPhoneNumbers = $twilio->incomingPhoneNumbers
                    ->read([]);
                $response['numbers'] = count($incomingPhoneNumbers);
                $account = $twilio->api->v2010->accounts($sid)
                    ->fetch();
                $response['balance'] = $this->get_active_twilio_account_curl($account->subresourceUris['balance']);
            } catch (Exception $e) {
                set_alert('warning', 'Twilio ' . $e->getMessage());
            }
        }
        return $response;
    }

    public function get_active_twilio_account_curl($url)
    {
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $sid . ":" . $token);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.twilio.com/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response);
        return $data->balance;
    }

    public function calender_filter_list(){
        $this->_lm_allow_methods(['POST']);
        $filters =[
            ['name'=> 'events','label'=>_l('events'),'isChecked'=>$this->input->post('events')?true:false],
        ];
        if(get_option('show_tasks_on_calendar') == 1){
            array_push($filters,['name'=> 'tasks','label'=> _l('tasks'),'isChecked'=>$this->input->post('tasks')?true:false]);
        }
        if(get_option('show_projects_on_calendar') == 1){
            array_push($filters,['name'=> 'projects','label'=> _l('projects'),'isChecked'=>$this->input->post('projects')?true:false]); 
        }
        if(get_option('show_invoices_on_calendar') == 1){
            array_push($filters,['name'=> 'invoices','label'=> _l('invoices'),'isChecked'=>$this->input->post('invoices')?true:false]); 
        }
        if(get_option('show_estimates_on_calendar') == 1){
            array_push($filters,['name'=> 'estimates','label'=> _l('estimates'),'isChecked'=>$this->input->post('estimates')?true:false]); 
        }
        if(get_option('show_proposals_on_calendar') == 1){
            array_push($filters,['name'=> 'proposals','label'=> _l('proposals'),'isChecked'=>$this->input->post('proposals')?true:false]); 
        }
        if(get_option('show_contracts_on_calendar') == 1){
            array_push($filters,['name'=> 'contracts','label'=> _l('contracts'),'isChecked'=>$this->input->post('contracts')?true:false]); 
        }
        if(get_option('show_customer_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'customer_reminders','label'=> _l('show_customer_reminders_on_calendar'),'isChecked'=>$this->input->post('customer_reminders')?true:false]); 
        }
        if(get_option('show_expense_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'expense_reminders','label'=> _l('calendar_expense_reminder'),'isChecked'=>$this->input->post('expense_reminders')?true:false]); 
        }
        if(get_option('show_lead_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'show_lead_reminders_on_calendar','label'=> _l('show_lead_reminders_on_calendar'),'isChecked'=>$this->input->post('show_lead_reminders_on_calendar')?true:false]); 
        }
        if(get_option('show_estimate_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'estimate_reminders','label'=> _l('show_estimate_reminders_on_calendar'),'isChecked'=>$this->input->post('estimate_reminders')?true:false]); 
        }
        if(get_option('show_invoice_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'invoice_reminders','label'=> _l('show_invoice_reminders_on_calendar'),'isChecked'=>$this->input->post('invoice_reminders')?true:false]); 
        }

        if(get_option('show_credit_note_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'credit_note_reminders','label'=> _l('show_credit_note_reminders_on_calendar'),'isChecked'=>$this->input->post('credit_note_reminders')?true:false]); 
        }
        if(get_option('show_proposal_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'proposal_reminders','label'=> _l('show_proposal_reminders_on_calendar'),'isChecked'=>$this->input->post('proposal_reminders')?true:false]); 
        }
        if(get_option('show_ticket_reminders_on_calendar') == 1){
            array_push($filters,['name'=> 'ticket_reminders','label'=> _l('calendar_ticket_reminder'),'isChecked'=>$this->input->post('ticket_reminders')?true:false]); 
        }
        $this->response([
        'status' => TRUE,
        'message' =>'Calender filter list is here',
        'data'=>$filters,
        ], REST_Controller::HTTP_OK);
  
    }

    public function get_calander_data(){
        $this->load->model('utilities_model');
        $data = $this->utilities_model->get_calendar_data(
            date('Y-m-d', strtotime($this->input->get('start'))),
            date('Y-m-d', strtotime($this->input->get('end'))),
            '',
            '',
            $this->input->get()
        );
        $this->response([
            'status' => TRUE,
            'message' =>'Calender data is here',
            'data'=> $data,
        ], REST_Controller::HTTP_OK);
    }

    public function bulk_action()
    {   
        $this->_lm_allow_methods(['POST']);
        if (!is_staff_member()) {
            $this->response([
                'status' => TRUE,
                'message' =>_l('access_denied'),
                'data'=> [],
            ], REST_Controller::HTTP_OK);
        }

        hooks()->do_action('before_do_bulk_action_for_leads');
        $total_deleted = 0;
       
        $ids                   = json_decode($this->input->post('ids'));
        $status                = $this->input->post('status');
        $source                = $this->input->post('source');
        $assigned              = $this->input->post('assigned');
        $visibility            = $this->input->post('visibility');
        $tags                  = $this->input->post('tags');
        $last_contact          = $this->input->post('last_contact');
        $lost                  = $this->input->post('lost');
        $has_permission_delete = has_permission('leads', '', 'delete');
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if ($this->input->post('mass_delete')) {
                    if ($has_permission_delete) {
                        if ($this->leads_model->delete($id)) {
                            $total_deleted++;
                        }
                    }
                } else {
                    if ($status || $source || $assigned || $last_contact || $visibility) {
                        $update = [];
                        if ($status) {
                            // We will use the same function to update the status
                            $this->leads_model->update_lead_status([
                                'status' => $status,
                                'leadid' => $id,
                            ]);
                        }
                        if ($source) {
                            $update['source'] = $source;
                        }
                        if ($assigned) {
                            $update['assigned'] = $assigned;
                        }
                        if ($last_contact) {
                            $last_contact          = to_sql_date($last_contact, true);
                            $update['lastcontact'] = $last_contact;
                        }

                        if ($visibility) {
                            if ($visibility == 'public') {
                                $update['is_public'] = 1;
                            } else {
                                $update['is_public'] = 0;
                            }
                        }

                        if (count($update) > 0) {
                            $this->db->where('id', $id);
                            $this->db->update(db_prefix() . 'leads', $update);
                        }
                    }
                    if ($tags) {
                        handle_tags_save($tags, $id, 'lead');
                    }
                    if ($lost == 'true') {
                        $this->leads_model->mark_as_lost($id);
                    }
                }
            }
        }

        if ($this->input->post('mass_delete')) {
            $this->response([
                'status' => TRUE,
                'message' =>_l('total_leads_deleted', $total_deleted),
                'data'=> [],
            ], REST_Controller::HTTP_OK);
        }else{
            $this->response([
                'status' => TRUE,
                'message' =>_l('updated'),
            ], REST_Controller::HTTP_OK);
        }
 
    }

}
