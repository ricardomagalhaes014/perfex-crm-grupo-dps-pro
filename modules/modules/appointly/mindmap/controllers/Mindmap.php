<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Carbon\Carbon;

class Mindmap extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mindmap_model');
        $this->load->model('staff_model');
        $this->load->model('clients_model');
    }

    /* List all mindmap */
    public function index()
    {
        if (!has_permission('mindmap', '', 'view')) {
            access_denied('mindmap');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mindmap', 'table'));
        }

        $data['switch_grid'] = false;

        if ($this->session->userdata('mindmap_grid_view') == 'true') {
            $data['switch_grid'] = true;
        }

        
        $data['staffs'] = $this->staff_model->get();

        $data['groups'] = $this->mindmap_model->get_groups();
        $data['mindmap_groups']    = $this->mindmap_model->get_groups();
        $data['projects']    = $this->mindmap_model->get_projects();
       
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['contacts'] = $this->clients_model->get_contacts('',array('active'=>1));
        $data['title'] = _l('mindmaps');
        $this->app_scripts->add('mind-elixir-js','modules/mindmap/assets/js/mind-elixir.js');
        $this->app_scripts->add('mindmap-js','modules/mindmap/assets/js/mindmap.js');
        $this->load->view('manage', $data);
    }

    public function table()
    {
        if (!has_permission('mindmap', '', 'view')) {
            access_denied('mindmap');
        }

        $this->app->get_table_data(module_views_path('mindmap', 'table'));
    }

    public function project_table()
    {
       
        if (!has_permission('mindmap', '', 'view')) {
            access_denied('mindmap');
        }

        $this->app->get_table_data(module_views_path('mindmap', 'project_table'));
    }
    public function grid()
    {
        echo $this->load->view('mindmap/grid', [], true);
    }

    /**
     * Task ajax request modal
     * @param  mixed $id
     * @return mixed
     */
    public function get_mindmap_data($id)
    {
        $mindmap = $this->mindmap_model->get($id);


        if (!$mindmap) {
            header('HTTP/1.0 404 Not Found');
            echo 'Mindmap not found';
            die();
        }
        $this->load->model('staff_model');

        $data['mindmap']               = $mindmap;
        $data['mindmap']->mindmap_content = json_decode($data['mindmap']->mindmap_content);
        $data['staff'] = $this->staff_model->get($data['mindmap']->staffid);
        $data['group'] = $this->mindmap_model->get_groups($data['mindmap']->mindmap_group_id);
        

        $html =  $this->load->view('view_mindmap_template', $data, true);
        echo $html;
    }

    public function mindmap_create($id = '')
    {
        if (!has_permission('mindmap', '', 'view')) {
            access_denied('mindmap');
        }

        if ($this->input->post()) {
            

            if ($id == '') {
                if (!has_permission('mindmap', '', 'create')) {
                    access_denied('mindmap');
                }
                $id = $this->mindmap_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('mindmap')));
                    // redirect(admin_url('mindmap'));
                    redirect(admin_url('mindmap/mindmap_create/' . $id));
                }
            } else {
                if (!has_permission('mindmap', '', 'edit')) {
                    access_denied('mindmap');
                }
                $success = $this->mindmap_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('mindmap')));
                    redirect(admin_url('mindmap/mindmap_create/' . $id));
                }
                //redirect(admin_url('mindmap/mindmap_create/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('mindmap_add_new', _l('mindmap'));
        } else {
            $data['mindmap']        = $this->mindmap_model->get($id);
            $data['mindmap']->mindmap_content = json_decode($data['mindmap']->mindmap_content);
            

            $title = _l('mindmap_edit', _l('mindmap'));
        }
        
        $data['mindmap_groups']    = $this->mindmap_model->get_groups();
        $data['projects']    = $this->mindmap_model->get_projects();
        $data['title']                 = $title;
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['contacts'] = $this->clients_model->get_contacts('',array('active'=>1));
        $this->app_scripts->add('mind-elixir-js','modules/mindmap/assets/js/mind-elixir.js');
        $this->app_scripts->add('html2canvas','modules/mindmap/assets/js/html2canvas.js');
        $this->app_scripts->add('jspdf.debug','modules/mindmap/assets/js/jspdf.debug.js');
        //$this->app_scripts->add('comments.min','assets/plugins/jquery-comments/js/jquery-comments.min.js');
		$this->app_scripts->add('comments.min','modules/mindmap/assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $this->load->view('mindmap', $data);
    }


    /* Mindmap function to handle preview views. */
    public function preview($id = 0)
    {
        if (!has_permission('mindmap', '', 'view')) {
            access_denied('mindmap');
        }
        $data['mindmap']        = $this->mindmap_model->get($id);
        $data['mindmap']->mindmap_content = json_decode($data['mindmap']->mindmap_content);

        if (!$data['mindmap']) {
            blank_page(_l('mindmap_not_found'), 'danger');
        }
        if($this->input->post('preview')){
            $post_data = $this->input->post();
            unset($post_data['preview']);
            unset($post_data['color']);
            if (!has_permission('mindmap', '', 'edit')) {
                    access_denied('mindmap');
                }

                $success = $this->mindmap_model->update($post_data, $id);
                if ($success && ($this->input->server('REQUEST_METHOD') === 'GET')) {
                    set_alert('success', _l('updated_successfully', _l('mindmap')));
                }
        }

        $title = _l('preview_mindmap');
        $data['title']                 = $title;
        $data['mindmap_group']    = $this->mindmap_model->get_groups($data['mindmap']->mindmap_group_id);
        $data['mindmap_groups']    = $this->mindmap_model->get_groups();
        $data['projects']    = $this->mindmap_model->get_projects();
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['contacts'] = $this->clients_model->get_contacts('',array('active'=>1));
        $this->app_scripts->add('mind-elixir-js','modules/mindmap/assets/js/mind-elixir.js');
        $this->app_scripts->add('html2canvas','modules/mindmap/assets/js/html2canvas.js');
        $this->app_scripts->add('jspdf.debug','modules/mindmap/assets/js/jspdf.debug.js');
        // $this->app_scripts->add('comments.min','assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $this->app_scripts->add('comments.min','modules/mindmap/assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $this->app_scripts->add('circle-progress-js','assets/plugins/jquery-circle-progress/circle-progress.min.js');
      
        $this->load->view('preview', $data);
    }


    /* Delete from database */
    public function delete($id)
    {
        if (!has_permission('mindmap', '', 'delete')) {
            access_denied('mindmap');
        }
        if (!$id) {
            redirect(admin_url('mindmap'));
        }
        $response = $this->mindmap_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('mindmap_deleted', _l('mindmap')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mindmap_lowercase')));
        }
        redirect(admin_url('mindmap'));
    }

    public function switch_grid($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'false';
        } else {
            $set = 'true';
        }

        $this->session->set_userdata([
            'mindmap_grid_view' => $set,
        ]);
        if ($manual == false) {
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    /*********Mindmap group**********/
    public function groups(){
        if (!is_admin()) {
            access_denied('Mindmap');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('mindmap', 'admin/groups_table'));
        }
        $data['title'] = _l('mindmap_group');
        $this->load->view('mindmap/admin/groups_manage', $data);
    }

    public function group()
    {
        if (!is_admin() && get_option('staff_members_create_inline_mindmap_group') == '0') {
            access_denied('mindmap');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->mindmap_model->add_group($this->input->post());
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $id ? _l('added_successfully', _l('mindmap_group')) : '',
                    'id'      => $id,
                    'name'    => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->mindmap_model->update_group($data, $id);
                $message = _l('updated_successfully', _l('mindmap_group'));
                echo json_encode(['success' => $success, 'message' => $message]);
            }
        }
    }


    public function delete_group($id)
    {
        if (!$id) {
            redirect(admin_url('mindmap'));
        }
        $response = $this->mindmap_model->delete_group($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('mindmap_group')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('mindmap_group')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('mindmap_group')));
        }
        redirect(admin_url('mindmap/groups'));
    }


    public function download($id) {
       
        $title = _l('preview_mindmap');
        $data['title']                 = $title;
        //$data['mindmap_group']    = $this->mindmap_model->get_groups($data['mindmap']->mindmap_group_id);
        $data['mindmap_groups']    = $this->mindmap_model->get_groups();
        $data['projects']    = $this->mindmap_model->get_projects();
        $data['mindmap']        = $this->mindmap_model->get($id);
        // $this->pdf->load_view('preview',$data);
        // $this->pdf->render();
        // $this->pdf->stream("preview.pdf");

        // Load pdf library
        $customer_id = 1;
        $this->load->library('pdf');
        $this->pdf->load_view('mindmap_pdf',$data);

    }

    public function update_mindmap(){
        $post_data = $_POST;
         
        $post_data['mindmap_content'] = json_encode($post_data['mindmap_content']);
        
        $id = $_POST['id'];
        if (!$id) {
            redirect(admin_url('mindmap'));
        }
        $success = $this->mindmap_model->update($post_data, $id);
        if ($success && ($this->input->server('REQUEST_METHOD') === 'GET')) {
            set_alert('success', _l('updated_successfully', _l('mindmap')));
        }
    }

    /******** discussion comments *********/
      public function get_discussion_comments($id, $type)
    {
      
        echo json_encode($this->mindmap_model->get_discussion_comments($id, $type));
    }

    public function add_discussion_comment($discussion_id, $type)
    {
        echo json_encode($this->mindmap_model->add_discussion_comment(
            $this->input->post(null, false),
            $discussion_id,
            $type
        ));
    }

    public function update_discussion_comment()
    {
        echo json_encode($this->mindmap_model->update_discussion_comment($this->input->post(null, false)));
    }
    public function update_discussion_comment_rating()
    {
        echo json_encode($this->mindmap_model->update_discussion_comment_rating($this->input->post(null, false)));
    }
    public function delete_discussion_comment($id)
    {
        echo json_encode($this->mindmap_model->delete_discussion_comment($id));
    }

    public function sendEmail(){
        $companyname = !empty(get_option('companyname')) ? get_option('companyname') : 'Perfex';
        $staff_id = get_staff_user_id();
        $staff    = $this->staff_model->get($_POST['from']);
        $hash    = $this->mindmap_model->get($_POST['mindmap_id']);
       
        $contact  = $this->clients_model->get_contact($_POST['eml'],array('active'=>1));
        // $pdfdoc   = $_POST['fileDataURI'];	
        $from     = $_POST['from'];
        $subject  = $_POST['subject'];
        $subject  = $_POST['subject'];
        $content  = str_replace("{crmcompanyname}",$companyname,$_POST['content']);

          $eml  = $contact->email;		
        // $eml  = 'dev.raghvendra11@gmail.com';        
        // $b64file        = trim( str_replace( 'data:application/pdf;base64,', '', $pdfdoc ) );
        // $b64file        = str_replace( ' ', '+', $b64file );
        // $decoded_pdf    = base64_decode( $b64file );		

        $mail = new PHPMailer;
        $mail->setFrom($staff->email, $companyname );
        $mail->addAddress($eml);
        $url = '<br><br>View: <a href="'.site_url('/mindmap/view_mindmap/mind/' . $hash->id . '/' .  $hash->hash).'">'.$hash->title.' Mindmap</a>';
        // $mail->addAddress( $to);
        $mail->Subject  = $subject;
        $mail->Body     = $content.$url;
        // $mail->addStringAttachment($decoded_pdf, "nalog.pdf");		
        $mail->isHTML( true );
        if($mail->send()){
             set_alert('success', _l('mindmap_email_send', _l('mindmap')));
             echo "true";
         }

    }
    public function view($id, $hash)
    {

        //$this->load->model('mindmap_model');
        $mindmap = $this->mindmap_model->get_by_hash($id,$hash);
        $this->load->view('mindmap_pdf', $mindmap);
        
    }

   
}   