<?php defined('BASEPATH') or exit('No direct script access allowed');

class Video_library extends AdminController
{
    private $googleOauthURL = '';
    private $access_token = '';
    public function __construct()
    {
        parent::__construct();
        $this->app_scripts->add('library-js', module_dir_url('video_library', '/assets/js/video_library.js'));
        $this->load->model('video_library_modal');
        $this->load->library('GoogleDriveApi');
        // Google OAuth URL 
        $this->googleOauthURL = 'https://accounts.google.com/o/oauth2/auth?scope=' . urlencode(GOOGLE_OAUTH_SCOPE) . '&redirect_uri=' . REDIRECT_URI . '&response_type=code&client_id=' . GOOGLE_CLIENT_ID . '&access_type=online';
    }
    public function index()
    {
        $this->app_css->add('jquery-comments-css', base_url('assets/plugins/jquery-comments/css/jquery-comments.css'));

        $this->app_scripts->add('jquery-comments-js', base_url('assets/plugins/jquery-comments/js/jquery-comments.js'));
        $data['video_data_show'] = $this->video_library_modal->show_video();
        $data['data_category'] = $this->video_library_modal->show_category();
        $this->load->view('admin/libraries/video_library', $data);
    }
    public function get_video_data()
    {
        $data['data_category'] = $this->video_library_modal->show_category();
        $this->load->view('admin/libraries/add_video_data', $data);
    }
    public function video_category_table()
    {
        $this->app->get_table_data(module_views_path('video_library', 'admin/libraries/table/video_category_table'));
    }

    public function categeory()
    {
        $data['data_category'] = $this->video_library_modal->show_category();
        $this->load->view('admin/libraries/categeory', $data);
    }

    public function add_video_categeory()
    {
        $this->load->view('admin/libraries/add_categeory',);
    }

    public function add_category_data()
    {
        $data = array(
            'category' => $this->input->post('add_category'),
        );
        if ($this->video_library_modal->add_category($data)) {
            echo true;
        } else {
            echo false;
        }
    }
    public function update_category_data()
    {
        $data = $this->input->post();
        if ($this->video_library_modal->update_category_data($data)) {
            echo true;
        } else {
            echo false;
        }
    }



    public function add_video($id = '')
    {

        if (is_numeric($id) && has_permission('video_library', '', 'edit')) {
            if ($this->input->post()) {
                $post_data = $this->input->post();
                $this->video_library_modal->update_video($post_data, $id);
                if ($id) {
                    if (isset($_FILES['upload_video'])) {
                        handle_video_library_video_upload($id);
                    }

                    set_alert('success', _l('new_video_library_added_alert'));
                    redirect(admin_url('video_library'));
                } else {
                    set_alert('danger', _l('new_video_library_added_failed_alert'));
                    redirect(admin_url('video_library'));
                }
            }
            $data['video'] = $this->video_library_modal->edit_video($id);
            $data['data_category'] = $this->video_library_modal->show_category();
            $data['title'] = _l('vl_edit_video');
        } else {
            if ($this->input->post()) {
                $post_data = $this->input->post();
                $id = $this->video_library_modal->upload_video($post_data);
                if ($id) {
                    $this->session->set_userdata('v_id', $id);
                    handle_video_library_video_upload($id);
                    if ($id) {
                        $filedataa = $this->video_library_modal->edit_video($id);
                        $val = get_option('is_vl_google_drive');
                        if ($filedataa->upload_type != 'link' && $val == 'yes') {
                            redirect($this->googleOauthURL);
                            $this->uploadFileGoogleDrive($id);
                        }
                    }
                    set_alert('success', _l('new_video_library_added_alert'));
                    redirect(admin_url('video_library'));
                } else {
                    set_alert('danger', _l('new_video_library_added_alert_failed'));
                    redirect(admin_url('video_library'));
                }
            }
            $data['data_category'] = $this->video_library_modal->show_category();
            $data['projects'] = $this->projects_model->get();
            $data['title'] = _l('vl_add_video');
        }
        $data['projects'] = $this->projects_model->get();
        $this->load->view('admin/libraries/simple_add_video', $data);
    }





    public function search_category_data()
    {
        $post_data['categories'] = $this->input->post('categories');
        $post_data['title'] = $this->input->post('title');
        $result = $this->video_library_modal->search_title_category($post_data);
        $data = '<div class="row">';
        foreach ($result as $data_show) {
            $data .= ' <div class="col-md-4">
            <div class="v_o_wr">
            <div class="wrap_video_cl">
            <div class="actn_edit">';
            if (has_permission('video_library', '', 'delete') && has_permission('video_library', '', 'view')) {
                $data .=  '<div class="wrap_actn_b"> <a class="trash_btn_c" href="' . admin_url('video_library/delete_video/') . $data_show['id'] . ' ">';
                $data .=  '<span>
                <i class="fa fa-trash-o" aria-hidden="true"></i> </span> delete 
                </a>
                </div>';
            }
            if (has_permission('video_library', '', 'edit') && has_permission('video_library', '', 'view')) {
                $data .= '<div class="wrap_actn_b">';
                $data .= '<a class="pencil_btn_c" href="' . admin_url('video_library/edit_video/') . $data_show['id'] . '">';
                $data .= '<span>
                <i class="fa fa-pencil" aria-hidden="true"></i></span> edit
                </a>
                </div>';
            }
            $data .= '</div><h1>' . $data_show['title'] . '</h1>';
            $data .= '<a class="player_btn" data-fancybox href="#myVideo_' . $data_show['id'] . '">';
            $data .= '<span>
            <img src="' . base_url('modules/video_library/assets/image/youtube_thumb.png') . '" alt="img not found"/>
            </span>
            </a>';
            $data .= '<div class="card">';
            $data .= '<video width="640" height="320" controls id="myVideo_' . $data_show['id'] . '" style="display:none;">';
            $data .= '<source src="' . base_url() . 'uploads/video_library/' . $data_show['upload_video'] . '" type="video/mp4">';
            $data .= '</video>
            </div>
            </div>';
            $discussion_count = video_discussion_count($data_show['id']);
            $data .= ' <div class="video_cat">';
            $data .= ' <p>' . $data_show['description'] . '</p>';
            $data .= '<a href="javascript:void(0)" class="discussion_link" data-id="' . $data_show['id'] . '"><i class="fa fa-comment"></i>' . $discussion_count . '</a>';
            $data .= '</div>
            </div>
            </div>';
        }
        $data .= '</div>';
        if (count($result) > 0) {
            echo $data;
        } else {
            echo '<h2 style="text-align:center;margin-top:10px;margin-bottom:10px;">Record Not Found</h2>';
        }
    }

    public function update_video()
    {
        $video_id = $this->input->post('video_id');
        $video_detail = $this->video_library_modal->data_verify($video_id);
        $old_file_data = base_url() . 'uploads/upload_video/' . $video_detail->upload_video;
        $config['file_name'] = 'video' . '_' . time();
        $config['upload_path']          = 'uploads/upload_video/';
        $config['allowed_types']        = 'mp3|mp4';
        $config['max_size'] = '1000000';
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('upload_video')) {
            if (!empty($video_id)) {
                if (empty($video_detail->upload_video)) {
                    $video_data = array(
                        'video_title' => $this->input->post('title'),
                        'video_category' => $this->input->post('category'),
                        'description' => $this->input->post('desc'),
                    );
                    if ($this->video_library_modal->update_video($video_data, $video_id)) {
                        return redirect('video_library/index');
                    } else {

                        return redirect('video_library/index');
                    }
                } else {
                    $fd =  $this->upload->data();
                    $fn = $fd['file_name'];
                    $video_data = array(
                        'video_title' => $this->input->post('title'),
                        'video_category' => $this->input->post('category'),
                        'description' => $this->input->post('desc'),
                        'upload_video' => $fn,
                    );

                    if ($this->video_library_modal->update_video($video_data, $video_id)) {
                        unlink($old_file_data);
                        $this->session->set_flashdata('msg', 'Video updated successfully');
                        return redirect('video_library/index');
                    } else {
                        $this->session->set_flashdata('msg', 'Video not updated successfully');
                        return redirect('video_library/index');
                    }
                }
            } else {
                echo $this->upload->display_errors();
            }
        } else {
            $fd =  $this->upload->data();
            $fn = $fd['file_name'];
            $video_data = array(
                'video_title' => $this->input->post('title'),
                'video_category' => $this->input->post('category'),
                'description' => $this->input->post('desc'),
                'upload_video' => $fn,
            );
            if ($this->video_library_modal->update_video($video_data, $video_id)) {
                $this->session->set_flashdata('msg', 'Video updated successfully');
                return redirect('video_library/index');
            } else {
                $this->session->set_flashdata('msg', 'Video not updated successfully');
                return redirect('video_library/index');
            }
        }
    }
    public function delete_video($del_id)
    {
        if (has_permission('video_library', '', 'delete')) {
            if ($this->input->is_ajax_request()) {
                $response = array();
                if ($this->video_library_modal->delete_video_file($del_id)) {
                    $response['status'] = 'success';
                    $response['message'] = _l('vl_video_deleted');
                } else {
                    $response['status'] = 'danger';
                    $response['message'] = _l('vl_video_not_deleted');
                }
                die(json_encode($response));
            }
            if ($this->video_library_modal->delete_video($del_id)) {
                redirect(admin_url('video_library'));
            } else {
                redirect(admin_url('video_library'));
            }
        } else {
            set_alert('danger', 'access denied!');
        }
    }
    public function edit_category_data($edit_id)
    {
        if (has_permission('video_library', '', 'edit')) {
            $update_cate['edit_data'] = $this->video_library_modal->update_category($edit_id);
            $this->load->view('admin/libraries/update_category', $update_cate);
        } else {
            set_alert('danger', 'access denied!');
        }
    }
    public function delete_category($cat_id)
    {
        if (has_permission('video_library', '', 'delete')) {
            if ($this->video_library_modal->delete_category($cat_id)) {
                set_alert('success', 'Category deleted!');
                redirect(admin_url('video_library/categeory'));
            } else {
                set_alert('success', 'Category deletion failed!');
                redirect(admin_url('video_library/categeory'));
            }
        } else {
            set_alert('danger', 'access denied!');
        }
    }
    public function video_grid()
    {
        $data = [];
        $video_id = !empty($this->input->post('video_id')) ? $this->input->post('video_id') : 0;
        $data['video_id'] = $video_id;
        if (!empty($this->input->post('cats'))) {
            $cats = json_decode($this->input->post('cats'));
            $data['cats'] = $cats;
        }
        echo $this->load->view('admin/libraries/grid', $data, true);
        die();
    }
    public function get_video_comments($id, $type)
    {
        echo json_encode($this->video_library_modal->get_video_comments($id, $type));
    }
    public function add_discussion_comment($video_id, $type)
    {
        echo json_encode($this->video_library_modal->add_discussion_comment(
            $this->input->post(null, false),
            $video_id,
            $type
        ));
        exit;
    }
    public function update_discussion_comment()
    {
        echo json_encode($this->video_library_modal->update_discussion_comment($this->input->post(null, false)));
    }
    public function delete_discussion_comment($id)
    {
        echo json_encode($this->video_library_modal->delete_discussion_comment($id));
    }
    //----------------------------------------------------------------------------------------
    //Google Drive Api
    //------------------------------------------------------------------------------------
    public function uploadFileGoogleDrive()
    {
        $vi_id = $this->session->userdata('v_id');
        if ($vi_id) {
            $filedata = $this->video_library_modal->edit_video($vi_id);
            if ($filedata->upload_type != 'file') {
                return false;
            }
            $file_name = $filedata->upload_video;
            $target_file = VIDEO_LIBRARY_UPLOADS_FOLDER . $file_name;
            $file_content = file_get_contents($target_file);
            $mime_type = mime_content_type($target_file);
            if (!empty($this->session->userdata('google_access_token'))) {
                $this->access_token = $this->session->userdata('google_access_token');
            } else {
                $GoogleDriveApi = new GoogleDriveApi();
                $data = $GoogleDriveApi->GetAccessToken(GOOGLE_CLIENT_ID, REDIRECT_URI, GOOGLE_CLIENT_SECRET, $this->input->get('code'));
                $this->access_token = $data['access_token'];
                $this->session->set_userdata(array('google_access_token' => $this->access_token));
            }
            if (!empty($this->access_token)) {
                try {
                    // Upload file to Google drive 
                    $drive_file_id = $GoogleDriveApi->UploadFileToDrive($this->access_token, $file_content, $mime_type);
                    if ($drive_file_id) {
                        $file_meta = array(
                            'name' => basename($file_name)
                        );
                        // Update file metadata in Google drive 
                        $drive_file_meta = $GoogleDriveApi->UpdateFileMeta($this->access_token, $drive_file_id, $file_meta);
                        if ($drive_file_meta) {
                            $this->video_library_modal->update_google_drive_id($drive_file_meta, $vi_id);
                            $this->session->unset_userdata('google_access_token');
                            $status = 'success';
                            $statusMsg = '<p>File has been uploaded to Google Drive successfully!</p>';
                            $statusMsg .= '<p><a href="https://drive.google.com/open?id=' . $drive_file_meta['id'] . '" target="_blank">' . $drive_file_meta['name'] . '</a>';
                        }
                    }
                } catch (Exception $e) {
                    $statusMsg = $e->getMessage();
                }
            } else {
                $statusMsg = 'Failed to fetch access token!';
            }
        }
       set_alert('success', _l('new_video_library_added_alert'));
        redirect(admin_url('video_library'));
    }
    /*
    ==============================================================================================================
    //////Video_Drive_Uploading
    ==============================================================================================================
    */
    public function video_drive_setup()
    {
        $this->load->view('admin/libraries/video_drive_setup');
        if ($this->input->post()) {

            $setup = $this->input->post();
            update_option('vl_google_client_id', $setup['driveid'], '');
            update_option('vl_google_client_secret', $setup['drivesecret'], 1);
            update_option('vl_google_client_redirect_uri', $setup['driveurl'], 1);
            update_option('is_vl_google_drive', $setup['drivecheck'], 1);
            if (true) {
                set_alert('success', _l('vl_credentials'));
                redirect(admin_url('video_library/video_drive_setup'));
            }
        }
    }
}
