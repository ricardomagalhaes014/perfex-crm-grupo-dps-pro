<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Video_library_modal extends App_Model

{
    public function __construct()

    {
        parent::__construct();
    }

    public function add_category($data)
    {
        $query = $this->db->insert(db_prefix() . 'video_category', $data);
        if ($query) {
            return true;
        } else {
            return false;
        }
    }

    public function show_category()
    {
        $category_data = $this->db->select('*')->from(db_prefix() . 'video_category')->order_by('id', 'desc')->get()->result_array();
        if ($category_data) {
            return $category_data;
        } else {
            return false;
        }
    }
    public function upload_video($data)
    {
        if (isset($data['link'])) {
            $data['upload_video'] = $data['link'];
            unset($data['link']);
        }
        $this->db->insert(db_prefix() . 'upload_video', $data);
        return $this->db->insert_id();
    }

    public function show_video()
    {
        $video_data = $this->db->select('*')->from(db_prefix() . 'upload_video')->order_by('id', 'desc')->get()->result_array();
        if ($video_data) {
            return $video_data;
        } else {
            return false;
        }
    }

    public function search_title_category($data)
    {
        $this->db->select('*')->from(db_prefix() . 'upload_video');
        if (!empty($data['categories'])) {
            $this->db->where_in('category', $data['categories']);
        }
        if (!empty($data['title'])) {
            $this->db->like('title', $data['title']);
        }
        $this->db->order_by('id', 'desc');
        $data = $this->db->get()->result_array();
        return $data;
    }

    public function search_title($title)
    {

        $data = $this->db->select('*')->from(db_prefix() . 'upload_video')->where("title LIKE '%$title%'")->order_by('id', 'desc')->get()->result_array();
        return $data;
    }

    public function edit_video($edit_id)
    {
        $data = $this->db->select('*')->from(db_prefix() . 'upload_video')->where('id', $edit_id)->get()->row();
        return $data;
    }

    public function delete_video_file($edit_id)
    {
        $data = $this->db->select('*')->from(db_prefix() . 'upload_video')->where('id', $edit_id)->get()->row();
        if (isset($data) && !empty($data)) {
            if (is_file(VIDEO_LIBRARY_UPLOADS_FOLDER . $data->upload_video)) {
                unlink(VIDEO_LIBRARY_UPLOADS_FOLDER . $data->upload_video);
                $this->db->where('id', $edit_id);
                $this->db->update(db_prefix() . 'upload_video', ['upload_video' => NULL]);
                return true;
            }
        }
    }
    public function update_video($video_data, $vdo_id)
    {
        if (isset($video_data['link'])) {
            $video_data['upload_video'] = $video_data['link'];
            unset($video_data['link']);
        }
        $this->db->where('id', $vdo_id);
        $q = $this->db->update(db_prefix() . 'upload_video', $video_data);
        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function data_verify($data_id)
    {
        $video_data = $this->db->select('*')->from(db_prefix() . 'upload_video')->where('id', $data_id)->get()->row();
        if ($video_data) {
            return $video_data;
        } else {
            return false;
        }
    }

    //  Delete video 

    public function delete_video($del_id)
    {
        $data = $this->db->get_where(db_prefix() . 'upload_video', ['id' => $del_id])->row();
        if (isset($data) && !empty($data)) {
            if (is_file(VIDEO_LIBRARY_UPLOADS_FOLDER . $data->upload_video)) {
                unlink(VIDEO_LIBRARY_UPLOADS_FOLDER . $data->upload_video);
            }
        }
        $this->db->where('id', $del_id);
        $q = $this->db->delete(db_prefix() . 'upload_video');
        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    // update category
    public function update_category($edit_id)
    {
        $cate_data = $this->db->select('*')->from(db_prefix() . 'video_category')->where('id', $edit_id)->get()->result_array();
        if ($cate_data) {
            return $cate_data;
        } else {
            return false;
        }
    }
    public function update_category_data($video_data)
    {
        //print_r($video_data); die;
        $id = $video_data['video_id'];
        unset($video_data['video_id']);
        $this->db->where('id', $id);
        $q = $this->db->update(db_prefix() . 'video_category', $video_data);
        if ($q) {
            return true;
        } else {
            return false;
        }
    }

    public function delete_category($cat_id)
    {
        $data = $this->db->get_where(db_prefix() . 'upload_video', ['category' => $cat_id])->result_array();
        if (isset($data) && !empty($data)) {
            foreach ($data as $video) {
                if (is_file(VIDEO_LIBRARY_UPLOADS_FOLDER . $video->upload_video)) {
                    unlink(VIDEO_LIBRARY_UPLOADS_FOLDER . $video->upload_video);
                }
                $this->db->where('id', $video->id);
                $this->db->delete(db_prefix() . 'upload_video');
            }
        }
        $this->db->where('id', $cat_id);
        $q = $this->db->delete(db_prefix() . 'video_category');
        if ($q) {
            return true;
        } else {
            return false;
        }
    }
    public function get_video_comments($id, $type)
    {
        $this->db->where('video_id', $id);
        $this->db->where('discussion_type', $type);
        $comments = $this->db->get(db_prefix() . 'video_library_videos_comments')->result_array();
        $i                    = 0;
        $allCommentsIDS       = [];
        $allCommentsParentIDS = [];
        foreach ($comments as $comment) {
            $str = '';
            $allCommentsIDS[] = $comment['id'];
            if (!empty($comment['parent'])) {
                $allCommentsParentIDS[] = $comment['parent'];
            }
            if ($comment['contact_id'] != 0) {
                if (is_client_logged_in()) {
                    if ($comment['contact_id'] == get_contact_user_id()) {
                        $comments[$i]['created_by_current_user'] = true;
                    } else {
                        $comments[$i]['created_by_current_user'] = false;
                    }
                } else {
                    $comments[$i]['created_by_current_user'] = false;
                }
                $comments[$i]['profile_picture_url'] = contact_profile_image_url($comment['contact_id']);
            } else {
                if (is_client_logged_in()) {
                    $comments[$i]['created_by_current_user'] = false;
                } else {
                    if (is_staff_logged_in()) {
                        if ($comment['user_id'] == get_staff_user_id()) {
                            $comments[$i]['created_by_current_user'] = true;
                        } else {
                            $comments[$i]['created_by_current_user'] = false;
                        }
                    } else {
                        $comments[$i]['created_by_current_user'] = false;
                    }
                }
                if (is_admin($comment['user_id'])) {
                    $comments[$i]['created_by_admin'] = true;
                } else {
                    $comments[$i]['created_by_admin'] = false;
                }
                $comments[$i]['profile_picture_url'] = staff_profile_image_url($comment['user_id']);
            }
            if (!is_null($comment['file_name'])) {
                $comments[$i]['file_url'] = VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . $id . '/' . $comment['file_name'];
            }
            $comments[$i]['created'] = (strtotime($comment['created']) * 1000);
            if (!empty($comment['modified'])) {
                $comments[$i]['modified'] = (strtotime($comment['modified']) * 1000);
            }
            $i++;
        }
        foreach ($allCommentsParentIDS as $parent_id) {
            if (!in_array($parent_id, $allCommentsIDS)) {
                foreach ($comments as $key => $comment) {
                    if ($comment['parent'] == $parent_id) {
                        $comments[$key]['parent'] = null;
                    }
                }
            }
        }
        return $comments;
    }

    public function add_discussion_comment($data, $video_id, $type)
    {
        $_data['video_id']        = $video_id;
        $_data['discussion_type']   = $type;
        if (isset($data['content'])) {
            $_data['content'] = $data['content'];
        }
        if (isset($data['parent']) && $data['parent'] != null) {
            $_data['parent'] = $data['parent'];
        }
        if (is_client_logged_in()) {
            $_data['user_id']    = get_client_user_id();
            $_data['contact_id'] = get_contact_user_id();
            $_data['user_type']  =  'customer';
            $_data['fullname']   = get_contact_full_name($_data['contact_id']);
        } else {
            $_data['user_id']    = get_staff_user_id();
            $_data['contact_id'] = 0;
            $_data['user_type']  = 'staff';
            $_data['fullname']   = get_staff_full_name($_data['user_id']);
        }
        $_data                   = handle_video_comment_attachments($video_id, $data, $_data);
        $_data['created']        = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'video_library_videos_comments', $_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($type == 'regular') {
            } else {
                $discussion                   = $this->get_file($video_id);
                $discussion->show_to_customer = $discussion->visible_to_customer;
            }
            return $this->get_discussion_comment($insert_id);
        }
        return false;
    }
    public function update_discussion_comment($data)
    {
        $comment = $this->get_discussion_comment($data['id']);
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'video_library_videos_comments', [
            'modified' => date('Y-m-d H:i:s'),
            'content'  => $data['content'],
        ]);
        if ($this->db->affected_rows() > 0) {
        }
        return $this->get_discussion_comment($data['id']);
    }
    public function get_discussion_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'video_library_videos_comments')->row();
        if ($comment->contact_id != 0) {
            if (is_client_logged_in()) {
                if ($comment->contact_id == get_contact_user_id()) {
                    $comment->created_by_current_user = true;
                } else {
                    $comment->created_by_current_user = false;
                }
            } else {
                $comment->created_by_current_user = false;
            }
            $comment->profile_picture_url = contact_profile_image_url($comment->contact_id);
        } else {
            if (is_client_logged_in()) {
                $comment->created_by_current_user = false;
            } else {
                if (is_staff_logged_in()) {
                    if ($comment->user_id == get_staff_user_id()) {
                        $comment->created_by_current_user = true;
                    } else {
                        $comment->created_by_current_user = false;
                    }
                } else {
                    $comment->created_by_current_user = false;
                }
            }
            if (is_admin($comment->user_id)) {
                $comment->created_by_admin = true;
            } else {
                $comment->created_by_admin = false;
            }
            $comment->profile_picture_url = staff_profile_image_url($comment->user_id);
        }
        $comment->created = (strtotime($comment->created) * 1000);
        if (!empty($comment->modified)) {
            $comment->modified = (strtotime($comment->modified) * 1000);
        }
        if (!is_null($comment->file_name)) {
            $comment->file_url = VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . $comment->video_id . '/' . $comment->file_name;
        }
        return $comment;
    }
    public function video_discussion_count($video_id = '')
    {
        $this->db->select('count(id) as total');
        $this->db->from(db_prefix() . 'video_library_videos_comments');
        $this->db->where(['video_id' => $video_id]);
        $ret = $this->db->get()->row();
        return isset($ret->total) ? $ret->total : 0;
    }
    public function delete_discussion_comment($id, $logActivity = true)
    {
        $discussion = $this->get_discussion_comment($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'video_library_videos_comments');
        if ($this->db->affected_rows() > 0) {
            $this->_delete_discussion_comments($id, 'regular');

            return true;
        }

        return false;
    }
    private function _delete_discussion_comments($id, $type)
    {
        $this->db->where('video_id', $id);
        $this->db->where('discussion_type', $type);
        $comments = $this->db->get(db_prefix() . 'video_library_videos_comments')->result_array();
        foreach ($comments as $comment) {
            $this->delete_discussion_comment_attachment($comment['file_name'], $id);
        }
        $this->db->where('video_id', $id);
        $this->db->where('discussion_type', $type);
        $this->db->delete(db_prefix() . 'video_library_videos_comments');
    }
    public function delete_discussion_comment_attachment($file_name, $video_id)
    {
        $path = VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . $video_id;
        if (!is_null($file_name)) {
            if (file_exists($path . '/' . $file_name)) {
                unlink($path . '/' . $file_name);
            }
        }
        if (is_dir($path)) {
            $other_attachments = list_files($path);
            if (count($other_attachments) == 0) {
                delete_dir($path);
            }
        }
    }
    /*
======================================
Drive Upload Update 
=======================================
*/
    public function update_google_drive_id($drive_file_meta, $vi_id)
    {
        $this->db->where('id', $vi_id);
        return $this->db->update(db_prefix() . 'upload_video', ['google_drive_upload_id' => $drive_file_meta['id']]);
    }
}
