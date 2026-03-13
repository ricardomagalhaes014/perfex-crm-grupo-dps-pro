<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Meetlink_manager extends AdminController
{

  
    public function __construct()
    {

        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['meeting_services_model','leads_model','staff_model','clients_model','meetings_model','meeting_participants_model']);
        $this->load->model('payment_modes_model');
        $this->load->model('settings_model');
    }

    public function index()
    {
        if (!has_permission('meetlink_manager', '', 'view')) {
            access_denied('meetlink_manager View');
        }
        close_setup_menu();

        $data['title'] = _l('meetlink_manager');
        if (has_permission('meetlink_manager', '', 'view')) {
            if ($this->input->is_ajax_request()) {
                $this->app->get_table_data(module_views_path('meetlink_manager', 'tables/meetings'));
            }
            $data['title']         = _l('meetlink_manager');
            $this->load->view('meetings/list', $data);
        } else {
            access_denied('meetlink_manager');
        }
    }

    public function add()
    {
        if (!has_permission('meetlink_manager', '', 'create')) {
            set_alert('danger', 'You do not have permission to create meetings.');
            redirect(admin_url('meetlink_manager'), 'refresh');
        }
    
       
        if (has_permission('meetlink_manager', '', 'create')) {
            $post=$this->input->post();
            if (!empty($post)) {
                $this->form_validation->set_rules('services_id', 'service', 'required');
                $this->form_validation->set_rules('title', 'title', 'required');
                $this->form_validation->set_rules('meeting_url', 'meeting url', 'required');
                $this->form_validation->set_rules('start_time', 'start date/time', 'required');
                if (false == $this->form_validation->run()) {
                    set_alert('danger', preg_replace("/\r|\n/", '', validation_errors()));
                } else {
                    $data = [
                        'title'             => $post['title'],
                        'meeting_date'      => date('Y-m-d', strtotime($post['start_time'])),
                        'meeting_time'      => date('H:i', strtotime($post['start_time'])),
                        'service_id'        => $post['services_id'],
                        'meeting_url'       => $post['meeting_url'],
                        'created_by'        => get_staff_user_id(),
                        'created_datetime'  => date('Y-m-d H:i:s')
                    ];
                    $participants['Lead'] = $post['lead_id'];
                    $participants['Customer'] = $post['client_id'];
                    $participants['Staff'] = $post['staffid'];
                    $inserted_id = $this->meetings_model->save($data,$participants);
                    
                    if ($inserted_id) {
                        set_alert('success', 'Meeting Added successfully');
                        redirect(admin_url('meetlink_manager'), 'refresh');
                    } else {
                        set_alert('warning', _l('Error Found - Meeting not inserted'));
                    }
                }
            }
            
            $data['title']              = _l('add_new', 'product');
            $data['action']             = _l('products');
            $data['services'] = $this->meeting_services_model->get();
            $data['leads']    = $this->leads_model->get();
            $data['clients']    = $this->clients_model->get();
            $data['staff']    = $this->staff_model->get();
            $this->load->view('meetings/add', $data);
        } else {
            access_denied('products');
        }
    }


    public function view($id){
        $data['meeting'] = $this->meetings_model->get($id);
        if(!empty($data['meeting'])){

            $lead_id = $this->meetings_model->get_lead_id($id);
            $customers_id = $this->meetings_model->get_customers_id($id);
            $staff_ids = $this->meetings_model->get_staff_id($id);
            $data['lead']    = $this->leads_model->get($lead_id);
            if(isset($customers_id[0])){
                $data['client']    = $this->clients_model->get($customers_id[0]);
            }else{
                $data['client'] = [];
            }
            $data['staffs'] = get_staff_by_ids($staff_ids);
            return $this->load->view('meetings/view', $data);

        }else{
            
        }

    }

    public function edit($id)
    {
        if (!has_permission('meetlink_manager', '', 'edit')) {
            set_alert('danger', 'You do not have permission to create meetings.');
            redirect(admin_url('meetlink_manager'), 'refresh');
        }
        if (has_permission('meetlink_manager', '', 'edit')) {
            $post = $this->input->post();
            
            // Fetch the existing meeting data based on ID
            $meeting = $this->meetings_model->get($id);
            
            if (empty($meeting)) {
                set_alert('danger', 'Meeting not found');
                redirect(admin_url('meetlink_manager'), 'refresh');
            }

            if (!empty($post)) {
                $this->form_validation->set_rules('services_id', 'service', 'required');
                $this->form_validation->set_rules('title', 'title', 'required');
                $this->form_validation->set_rules('meeting_url', 'meeting url', 'required');
                $this->form_validation->set_rules('start_time', 'start date/time', 'required');

                if (false == $this->form_validation->run()) {
                    set_alert('danger', preg_replace("/\r|\n/", '', validation_errors()));
                } else {
                    $data = [
                        'title'             => $post['title'],
                        'meeting_date'      => date('Y-m-d', strtotime($post['start_time'])),
                        'meeting_time'      => date('H:i', strtotime($post['start_time'])),
                        'service_id'        => $post['services_id'],
                        'meeting_url'       => $post['meeting_url'],
                    ];

                    $participants['Lead'] = $post['lead_id'];
                    $participants['Customer'] = $post['client_id'];
                    $participants['Staff'] = $post['staffid'];



                    $updated = $this->meetings_model->update($id, $data, $participants);

                    if ($updated) {
                        set_alert('success', 'Meeting updated successfully');
                        redirect(admin_url('meetlink_manager'), 'refresh');
                    } else {
                        set_alert('warning', _l('Error Found - Meeting not updated'));
                    }
                }
            }

            $data['title']        = _l('edit_meeting', 'Meeting');
            $data['action']       = _l('meeting');
            $data['services']     = $this->meeting_services_model->get();
            $data['leads']        = $this->leads_model->get();
            $data['clients']      = $this->clients_model->get();
            $data['staff']        = $this->staff_model->get();
            $data['meeting']      = $meeting; 
            $data['meeting_lead'] = $this->meetings_model->get_lead_id($id);
            $data['meeting_customers'] = $this->meetings_model->get_customers_id($id);
            $data['meeting_staffs'] = $this->meetings_model->get_staff_id($id);


            $this->load->view('meetings/edit', $data);
        } else {
            access_denied('meetlink_manager');
        }
    }

    public function delete($id)
    {
        // Check if the user has the necessary permission to delete meetings
        if (!has_permission('meetlink_manager', '', 'delete')) {
            set_alert('danger', 'You do not have permission to delete meetings.');
            redirect(admin_url('meetlink_manager'));
        }
    
        // Load your model
        $this->load->model('meetings_model');
    
        try {
            // Call the delete method from the model
            $deleted = $this->meetings_model->delete($id);
    
            if ($deleted) {
                // If the delete was successful
                set_alert('success', 'Meeting deleted successfully!');
            } else {
                // If delete failed
                set_alert('danger', 'Failed to delete the meeting.');
            }
    
        } catch (Exception $e) {
            // Handle any exceptions thrown in the model
            set_alert('danger', 'An error occurred while deleting the meeting: ' . $e->getMessage());
        }
    
        // Redirect back to the meetings list
        redirect(admin_url('meetlink_manager'));
    }
    
    public function settings(){
        if ($this->input->post()) {
            $post_data = $this->input->post();
            $check = hopper_verify($post_data['settings']['meetlink_manager_purchase_code']);
            if($check){
                $post_data['settings']['meetlink_manager_purchase_is_valid'] = 1;
                $success = $this->settings_model->update($post_data);
                if ($success > 0) {
                    set_alert('success', _l('settings_updated'));
                    redirect(admin_url('meetlink_manager/settings'));
                }
            }else{
                set_alert('danger', 'Purchase code is invalid');
                redirect(admin_url('meetlink_manager/settings'));
            }           
        }
        $data['title'] = _l('settings_meetlink');
        $this->load->view('settings',$data);
    }

        
}
