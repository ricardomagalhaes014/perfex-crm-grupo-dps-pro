<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/REST_Controller.php';
class Tasks extends REST_Controller
{   
    function __construct(){
       parent::__construct();
       $this->load->model('lead_manager_api_model');
       $this->load->model('lead_manager_model');
       $this->load->model('tasks_model');
       $this->load->helper('lead_manager_api');
      
      
    }
    public function get_lead_task($rel_id)
    {   
       
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $status = json_decode($this->input->post('status'));
        $q = $this->input->post('q');
        $data = $this->lead_manager_api_model->get_lead_task_data1($rel_id, 'lead', $status, $limit, $offset);
        foreach ($data['records'] as $key => $task) {
           
            $data['records'][$key]['startdate'] = _d($task['startdate']);
            $data['records'][$key]['duedate'] = _d($task['duedate']);
            $data['records'][$key]['assignees_details'] =format_task_members_by_ids_and_names($task['assignees_ids'],$task['assignees']);
        }
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
   

    public function get_task_data($taskid)
    {
        $tasks_where = [];

        if (!has_permission('tasks', '', 'view')) {
            $tasks_where = get_tasks_where_string(false);
        }

        $task = $this->tasks_model->get($taskid, $tasks_where);

        if (!$task) {
            $this->response([
                'status'=>FALSE,
                'message'=>'Task not found',
                'data'=> $repeat_evary ,
            ], REST_Controller::HTTP_OK);  
           
        }

        $data['checklistTemplates'] = $this->tasks_model->get_checklist_templates();
        $data['task']               = $task;
        //$data['id']                 = $task->id;
        //$data['staff']              = $this->staff_model->get('', ['active' => 1]);
        //$data['reminders']          = $this->tasks_model->get_reminders($taskid);

        $data['staff_reminders'] = $this->tasks_model->get_staff_members_that_can_access_task($taskid);

        $data['project_deadline'] = null;
        if ($task->rel_type == 'project') {
            $data['project_deadline'] = get_project_deadline($task->rel_id);
        }
        $this->response([
            'status'=>TRUE,
            'message'=>'Task data is here',
            'data'=> $data,
        ], REST_Controller::HTTP_OK);  
    }

    public function save_task($id=''){
        //die('sa');
        \modules\lead_manager\core\Apiinit::check_url('lead_manager');
       // die('fkd');
        // form validation
        $this->form_validation->set_rules('name', 'Task Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Task Name'));
        $this->form_validation->set_rules('startdate', 'Task Start Date', 'trim|required', array('is_unique' => 'This %s already exists please enter another Task Start Date'));
        $this->form_validation->set_rules('is_public', 'Publicly available task', 'trim', array('is_unique' => 'Public state can be 1. Skip it completely to set it at non-public'));
        if ($this->form_validation->run() == FALSE)
        {
            // form validation error
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors() 
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $insert_data = [
                'name' => $this->input->post('name', TRUE),
                'startdate' => $this->input->post('startdate', TRUE),
                'is_public' => $this->input->post('is_public', TRUE),
                'billable' => $this->input->post('billable', TRUE),
                'hourly_rate' => $this->input->post('hourly_rate', TRUE),
                'milestone' =>$this->input->post('milestone', TRUE),
                'duedate' =>$this->input->post('duedate', TRUE),
                'priority' => $this->input->post('priority', TRUE),
                'repeat_every' => $this->input->post('repeat_every', TRUE),
                'repeat_every_custom' => $this->input->post('repeat_every_custom', TRUE),
                'repeat_type_custom' => $this->input->post('repeat_type_custom', TRUE),
                'cycles' => $this->input->post('cycles', TRUE),
                'rel_type' => $this->input->post('rel_type', TRUE),
                'rel_id' => $this->input->post('rel_id', TRUE),
                'tags' => $this->input->post('tags', TRUE),
                'description' =>html_purify( $this->input->post('description', TRUE))
                ];
               
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->input->post('custom_fields', TRUE);
            }
          
            if($id==''){
                $id = $this->tasks_model->add($insert_data);
                $_id     = false;
                $message = '';
                if($id){
                
                    $_id           = $id;
                    $message       = _l('added_successfully', _l('task'));
                    $uploadedFiles = handle_task_attachments_array($id);
                    $this->load->model('misc_model');
                    if ($uploadedFiles && is_array($uploadedFiles)) {
                        foreach ($uploadedFiles as $file) {
                            $this->misc_model->add_attachment_to_database($id, 'task', [$file]);
                        }
                    }
                    // success
                    $this->response([
                        'status' => TRUE,
                        'message' =>$message,
                        'data'=>['id'=>$_id]
                    ], REST_Controller::HTTP_OK);
                }else{
                    // error
                    $message = array(
                    'status' => FALSE,
                    'message' => 'Task add failed.'
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                }
            }else{
                if (!has_permission('tasks', '', 'edit')) {
                    $message = array(
                        'status' => FALSE,
                        'message' =>  _l('access_denied')
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                  
                }
                $success = $this->tasks_model->update($insert_data, $id);
                if($success){
                    $this->response([
                        'status' => TRUE,
                        'message'=>_l('updated_successfully', _l('task')),
                        //'data'=>[]
                    ], REST_Controller::HTTP_OK);
                }else{
                    $this->response([
                        'status' => FALSE,
                        'message'=>'update fail',
                        //'data'=>[]
                    ], REST_Controller::HTTP_OK);
                }  
            }   
        }
    }

    public function task_repeat_types(){
        $repeat_evary =[
            ['value'=>'1-week','recurring_type'=>'week','label'=> _l('weeks')],
            ['value'=>'2-week','recurring_type'=>'week','label'=> '2'.' '._l('weeks')],
            ['value'=>'1-month','recurring_type'=>'month','label'=> '1'.' '._l('month')],
            ['value'=>'2-month','recurring_type'=>'month','label'=> '2'.' '._l('months')],
            ['value'=>'3-month','recurring_type'=>'month','label'=> '3'.' '._l('months')],
            ['value'=>'6-month','recurring_type'=>'month','label'=> '6'.' '._l('months')],
            ['value'=>'1-year','recurring_type'=>'year','label'=> '6'.' '._l('year')],
            ['value'=>'custom','recurring_type'=>'year','label'=> '6'.' '._l('recurring_custom')],
        ];
        $this->response([
            'status'=>TRUE,
            'message'=>'Task Repeat types',
            'data'=> $repeat_evary ,
        ], REST_Controller::HTTP_OK);  

    }

    public function get_tasks_priorities_list(){
        $this->response([
            'status'=>TRUE,
            'message'=>'Task Priority List',
            'data'=> get_tasks_priorities() ,
        ], REST_Controller::HTTP_OK);  
    }

    public function get_task_rel_type(){
        $rel_type =[
            ['value'=>'project','label'=>_l('project')],
            ['value'=>'invoice','label'=> _l('invoice')],
            ['value'=>'customer','label'=>_l('client')],
            ['value'=>'estimate','label'=>_l('estimate')],
            ['value'=>'contract','label'=>_l('contract')],
            ['value'=>'ticket','label'=>_l('ticket')],
            ['value'=>'expense','label'=>_l('expense')],
            ['value'=>'lead','label'=> _l('lead')],
            ['value'=>'proposal','label'=>_l('proposal')],
        ];
    
        $this->response([
            'status'=>TRUE,
            'message'=>'Task rel_type List',
            'data'=>  $rel_type ,
        ], REST_Controller::HTTP_OK);  
    }

    public function get_repeat_type_custom_list(){
        $repeat_custom_type =[
            ['value'=>'day','label'=>_l('task_recurring_days')],
            ['value'=>'week','label'=>  _l('task_recurring_weeks')],
            ['value'=>'month','label'=>_l('task_recurring_months')],
            ['value'=>'year','label'=>_l('task_recurring_years')],
        ];
        $this->response([
            'status'=>TRUE,
            'message'=>'Task Repeat custom type List',
            'data'=>   $repeat_custom_type ,
        ], REST_Controller::HTTP_OK);  
      
    }
    
    public function get_task_status($id=""){
        $CI       = &get_instance();
        $data = $CI->tasks_model->get_statuses();
        if($id==""){
            $this->response([
                'status'=>TRUE,
                'message'=>$data,
            ], REST_Controller::HTTP_OK);  
        }else{
            $success=false;
            $status = [
                'id'         => 0,
                'bg_color'   => '#333',
                'text_color' => '#333',
                'name'       => '[Status Not Found]',
                'order'      => 1,
            ]; 
            foreach ($data as $s) {
                if ($s['id'] == $id) {
                    $success = TRUE;
                    $status = $s;
                    break;
                }
            }
            $this->response([
                'status'=>$success,
                'message'=>$status,
            ], REST_Controller::HTTP_OK);  
        }
       
        
    }

    public function get_task_priorities($id=""){
        $success = TRUE;
        $data = get_tasks_priorities();
        if($id==""){
            $this->response([
                'status'=>TRUE,
                'message'=>$data,
            ], REST_Controller::HTTP_OK);   
        }else{
            $success = FALSE;
            $priority_d = [
                'id'         => 0,
                'name'       => '[Status Not Found]',
                'color'      => '#333',
            ];
            foreach ($data as $priority) {
                if ($priority['id'] == $id) {
                    $success = TRUE;
                    $priority_d =  $priority;
                    break;
                }
            }
            $this->response([
                'status'=>$success,
                'message'=>$priority_d,
            ], REST_Controller::HTTP_OK);
        }
    }


    public function delete_task_api($id)
    {
        if (!has_permission('tasks', '', 'delete')) {
            access_denied('tasks');
        }
        $status = FALSE;
        $success = $this->tasks_model->delete_task($id);
        $message = _l('problem_deleting', _l('task_lowercase'));
        if ($success) {
            $status = TRUE;
            $message = _l('deleted', _l('task')); 
        } 
        $this->response([
            'status'=>$status,
            'message'=>$message,
        ], REST_Controller::HTTP_OK);    
    }
    public function mark_task()
    {   
        $this->_lm_allow_methods(['POST']);
        $status =  $this->input->post('status');
        $id = $this->input->post('id');
       // echo format_task_status($status,true,true);die;
        $success= FALSE;
        $message = 'Update failed';
        if (
            $this->tasks_model->is_task_assignee(get_staff_user_id(), $id)
            || $this->tasks_model->is_task_creator(get_staff_user_id(), $id)
            || has_permission('tasks', '', 'edit')
        ) {
            $success = $this->tasks_model->mark_as($status, $id);
            if ($success) {
                $success = TRUE;
                $message = _l('task_marked_as_success', format_task_status($status, true, true));
            }  
        } 
        $this->response([
            'status'=>$success,
            'message'=>$message,
        ], REST_Controller::HTTP_OK);    
    }
    
    public function change_task_priority()
    {   $this->_lm_allow_methods(['POST']);
        if (has_permission('tasks', '', 'edit')) {
            $priority_id = $this->input->post('priority_id'); 
            $id = $this->input->post('id'); 
            $data = hooks()->apply_filters('before_update_task', ['priority' => $priority_id], $id);

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'tasks', $data);
           
            $success = $this->db->affected_rows() > 0 ? true : false;
            $message= 'Priority not changed';
            if($success){
               $message =  'Priority changed';
            }
            hooks()->do_action('after_update_task', $id);
            $this->response([
                'status'=>$success,
                'message'=> $message,
            ], REST_Controller::HTTP_OK);    
        } else {
            $this->response([
                'status'=>FALSE,
                'message'=>_l('access_denied'),
            ], REST_Controller::HTTP_OK);    
           
        }
    }

    

}