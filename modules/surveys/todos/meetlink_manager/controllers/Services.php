<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Services extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('meeting_services_model');
    }
    public function index()
    {
        if (!has_permission('meetlink_manager', '', 'view_service')) {
            access_denied('Meeting Services');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('meetlink_manager', 'tables/meeting_services'));
        }
        $data['title'] = _l('meeting_services');
        $this->load->view('services/list', $data);
    }

    public function add(){
        if (!has_permission('meetlink_manager', '', 'create_service')) {
            access_denied('Create Meeting Services');
        }
        $this->load->library('form_validation');
        if ($this->input->is_ajax_request()) {
            
            $data              = $this->input->post();
            $original_service = (object) [];

            if (!empty($data['m_service_id'])) {
                if (!has_permission('meetlink_manager', '', 'edit_service')) {
                    access_denied('Edit Meeting Services');
                }
                $original_service = $this->meeting_services_model->get($data['m_service_id']);
                if (strtolower($original_service->service_name) != strtolower($data['service_name'])) {
                    $this->form_validation->set_rules('service_name', 'Service name', 'required|is_unique[meeting_services.service_name]');
                    if (false == $this->form_validation->run()) {
                        echo json_encode([
                            'success' => false,
                            'message' => validation_errors(),
                        ]);
        
                        return;
                    }
                }
            } else {
                $this->form_validation->set_rules('service_name', 'Service name', 'required|is_unique[meeting_services.service_name]');
                if (false == $this->form_validation->run()) {
                    echo json_encode([
                        'success' => false,
                        'message' => validation_errors(),
                    ]);
    
                    return;
                }
            }
           
            
          
            if ('' == $data['m_service_id']) {
                $data['created_by'] = get_staff_user_id();
                $data['created_datetime'] = date('Y-m-d H:i:s');
                unset($data['m_service_id']);
                $id      = $this->meeting_services_model->insert($data);
                $message = $id ? _l('added_successfully', _l('meeting_services')) : '';
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $message,
                    'id'      => $id,
                    'name'    => $data['service_name'],
                ]);
            } else {
                $id = $data['m_service_id'];
                unset($data['m_service_id']);

                $success = $this->meeting_services_model->update($id,$data);
               
                $message = '';
                if (true == $success) {
                    $message = _l('updated_successfully', _l('meeting_services'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);
            }
        }
    }
    
   
    public function delete_services($id)
    {
        if (!has_permission('meetlink_manager', '', 'delete_service')) {
            access_denied('Delete Meeting Services');
        }
        if (!$id) {
            redirect(admin_url('meetlink_manager/services'));
        }
        $response = $this->meeting_services_model->service_delete($id);

        if (true == $response) {
            set_alert('success', _l('deleted', _l('meeting_services')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('meeting_services')));
        }
        redirect(admin_url('meetlink_manager/services'));
    }

}