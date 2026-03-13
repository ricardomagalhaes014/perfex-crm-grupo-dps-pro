<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Carbon\Carbon;

class Whiteboard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('whiteboard_model');
        $this->load->model('staff_model');
        $this->load->model('clients_model');
    }

    /* List all whiteboard */
    public function index()
    {

        if (!has_permission('whiteboard', '', 'view')) {
            access_denied('whiteboard');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('whiteboard', 'table'));
        }

        $data['switch_grid'] = false;

        if ($this->session->userdata('whiteboard_grid_view') == 'true') {
            $data['switch_grid'] = true;
        }

        $this->load->model('staff_model');
        $data['staffs'] = $this->staff_model->get();
        $data['groups'] = $this->whiteboard_model->get_groups();
        $data['projects']    = $this->whiteboard_model->get_projects();
        $data['title'] = _l('whiteboard');
        $data['whiteboard_groups']    = $this->whiteboard_model->get_groups();
        $this->app_scripts->add('whiteboard-js','modules/whiteboard/assets/js/whiteboard.js');
        $this->load->view('manage', $data);
    }

    public function table()
    {
        if (!has_permission('whiteboard', '', 'view')) {
            access_denied('whiteboard');
        }

        $this->app->get_table_data(module_views_path('whiteboard', 'table'));
    }
    public function whiteboard_table()
    {
       
        if (!has_permission('whiteboard', '', 'view')) {
            access_denied('whiteboard');
        }

        $this->app->get_table_data(module_views_path('whiteboard', 'whiteboard_table'));
    }

    public function grid()
    {
        echo $this->load->view('whiteboard/grid', [], true);
    }

    /**
     * Task ajax request modal
     * @param  mixed $id
     * @return mixed
     */
    public function get_whiteboard_data($id)
    {
        $whiteboard = $this->whiteboard_model->get($id);

        if (!$whiteboard) {
            header('HTTP/1.0 404 Not Found');
            echo 'whiteboard not found';
            die();
        }
        $this->load->model('staff_model');

        $data['whiteboard']               = $whiteboard;

        $data['staff'] = $this->staff_model->get($data['whiteboard']->staffid);
        $data['group'] = $this->whiteboard_model->get_groups($data['whiteboard']->whiteboard_group_id);
        $html =  $this->load->view('view_whiteboard_template', $data, true);
        echo $html;
    }
	 public function get_iframe_data($id)
    {  
        $whiteboard = $this->whiteboard_model->get($id);

        if (!$whiteboard) {
            header('HTTP/1.0 404 Not Found');
            echo 'whiteboard not found';
            die();
        }

        echo $whiteboard->whiteboard_content;
        die;
    }
    public function whiteboard_create($id = '')
    {
        if (!has_permission('whiteboard', '', 'view')) {
            access_denied('whiteboard');
        }
        if ($this->input->post()) {
            if ($id == '') {
                if (!has_permission('whiteboard', '', 'create')) {
                    access_denied('whiteboard');
                }
                $id = $this->whiteboard_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('whiteboard')));
                    redirect(base_url('whiteboard/whiteboard_create/' . $id));
                    // redirect(admin_url('whiteboard'));
                }
            } else {
                if (!has_permission('whiteboard', '', 'edit')) {
                    access_denied('whiteboard');
                }
                $success = $this->whiteboard_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('whiteboard')));
                }
                redirect(base_url('whiteboard/whiteboard_create/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('whiteboard_add_new', _l('whiteboard'));
        } else {
            $data['whiteboard']        = $this->whiteboard_model->get($id);

            $title = _l('whiteboard_edit', _l('whiteboard'));
        }
        if (!$data['whiteboard']) {
             set_alert('danger', _l('whiteboard_not_found', _l('whiteboard')));
            redirect(admin_url('whiteboard'));
        }
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['contacts'] = $this->clients_model->get_contacts('',array('active'=>1));
        $data['projects']    = $this->whiteboard_model->get_projects();
        $data['whiteboard_groups']    = $this->whiteboard_model->get_groups();
        $data['title']                 = $title;
        $this->app_scripts->add('comments.min','modules/whiteboard/assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $this->load->view('whiteboard', $data);
    }


    /* whiteboard function to handle preview views. */
    public function preview($id = 0)
    {
        if (!has_permission('whiteboard', '', 'view')) {
            access_denied('whiteboard');
        }
        $data['whiteboard']        = $this->whiteboard_model->get($id);

        if (!$data['whiteboard']) {
             set_alert('danger', _l('whiteboard_not_found', _l('whiteboard')));
            redirect(admin_url('whiteboard'));
        }
        if($this->input->post('preview') == 'preview'){

            $post_data = $this->input->post();
            unset($post_data['preview']);
            unset($post_data['color']);
            if (!has_permission('whiteboard', '', 'edit')) {
                    access_denied('whiteboard');
                }

                $success = $this->whiteboard_model->update($post_data, $id);
                if ($success && ($this->input->server('REQUEST_METHOD') === 'POST')) {
                    set_alert('success', _l('updated_successfully', _l('whiteboard')));
                }
        }
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['contacts'] = $this->clients_model->get_contacts('',array('active'=>1));
        $title = _l('preview_whiteboard');
        $data['title']                 = $title;
        $data['whiteboard_groups']    = $this->whiteboard_model->get_groups();
        $data['projects']    = $this->whiteboard_model->get_projects();
        $data['whiteboard_group']    = $this->whiteboard_model->get_groups($data['whiteboard']->whiteboard_group_id);
        $this->app_scripts->add('comments.min','modules/whiteboard/assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $this->load->view('preview', $data);
    }


    /* Delete from database */
    public function delete($id)
    {
        if (!has_permission('whiteboard', '', 'delete')) {
            access_denied('whiteboard');
        }
        if (!$id) {
            redirect(admin_url('whiteboard'));
        }
        $response = $this->whiteboard_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('whiteboard_deleted', _l('whiteboard')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('whiteboard_lowercase')));
        }
        redirect(admin_url('whiteboard'));
    }

    public function switch_grid($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'false';
        } else {
            $set = 'true';
        }

        $this->session->set_userdata([
            'whiteboard_grid_view' => $set,
        ]);
        if ($manual == false) {
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    /*********whiteboard group**********/
    public function groups(){
        if (!is_admin()) {
            access_denied('whiteboard');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('whiteboard', 'admin/groups_table'));
        }
        $data['title'] = _l('whiteboard_group');
        $this->load->view('whiteboard/admin/groups_manage', $data);
    }

    public function group()
    {
        if (!is_admin() && get_option('staff_members_create_inline_whiteboard_group') == '0') {
            access_denied('whiteboard');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->whiteboard_model->add_group($this->input->post());
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $id ? _l('added_successfully', _l('whiteboard_group')) : '',
                    'id'      => $id,
                    'name'    => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->whiteboard_model->update_group($data, $id);
                $message = _l('updated_successfully', _l('whiteboard_group'));
                echo json_encode(['success' => $success, 'message' => $message]);
            }
        }
    }


    public function delete_group($id)
    {
        if (!$id) {
            redirect(admin_url('whiteboard'));
        }
        $response = $this->whiteboard_model->delete_group($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('whiteboard_group')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('whiteboard_group')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('whiteboard_group')));
        }
        redirect(admin_url('whiteboard/groups'));
    }
    public function checkiframe()
    {
        $this->load->view('view_iframe');
    }

    public function update_whiteboard(){
        $post_data['whiteboard_content'] = $_POST['whiteboard_content'];
        $post_data['staffid'] = $_POST['staffid'];
        $id = $_POST['id'];
        if (!$id) {
            redirect(admin_url('whiteboard'));
        }
        $success = $this->whiteboard_model->update($post_data, $id);
        if ($success) {
            set_alert('success', _l('updated_successfully', _l('whiteboard')));
        }
    }

    public function sendEmail(){

        $companyname = !empty(get_option('companyname')) ? get_option('companyname') : 'Perfex';
        $staff_id = get_staff_user_id();
        $staff    = $this->staff_model->get($_POST['from']);
        $hash    = $this->whiteboard_model->get($_POST['whiteboard_id']);
       
        $contact  = $this->clients_model->get_contact($_POST['eml'],array('active'=>1));
        // $pdfdoc   = $_POST['fileDataURI'];   
        $from     = $_POST['from'];
        $subject  = $_POST['subject'];
        $subject  = $_POST['subject'];
        $content  = str_replace("{crmcompanyname}",$companyname,$_POST['content']);

          $eml  = $contact->email;      
        // $eml  = 'mohdnadeemzonv@gmail.com';        
        // $b64file        = trim( str_replace( 'data:application/pdf;base64,', '', $pdfdoc ) );
        // $b64file        = str_replace( ' ', '+', $b64file );
        // $decoded_pdf    = base64_decode( $b64file );     

        $mail = new PHPMailer;
        $mail->setFrom($staff->email, $companyname );
        $mail->addAddress($eml);
        $url = '<br><br>View: <a href="'.site_url('/whiteboard/view_whiteboard/white/' . $hash->id . '/' .  $hash->hash).'">'.$hash->title.' Whiteboard</a>';
        // $mail->addAddress( $to);
        $mail->Subject  = $subject;
        $mail->Body     = $content.$url;
        // $mail->addStringAttachment($decoded_pdf, "nalog.pdf");       
        $mail->isHTML( true );
        if($mail->send()){
             set_alert('success', _l('whiteboard_email_send', _l('whiteboard')));
             echo "true";
         }

    }
     /******** discussion comments *********/
      public function get_discussion_comments($id, $type)
    {
      
        echo json_encode($this->whiteboard_model->get_discussion_comments($id, $type));
    }

    public function add_discussion_comment($discussion_id, $type)
    {
        echo json_encode($this->whiteboard_model->add_discussion_comment(
            $this->input->post(null, false),
            $discussion_id,
            $type
        ));
    }

    public function update_discussion_comment()
    {
        echo json_encode($this->whiteboard_model->update_discussion_comment($this->input->post(null, false)));
    }
    public function update_discussion_comment_rating()
    {
        echo json_encode($this->whiteboard_model->update_discussion_comment_rating($this->input->post(null, false)));
    }
    public function delete_discussion_comment($id)
    {
        echo json_encode($this->whiteboard_model->delete_discussion_comment($id));
    }
}
