<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Carbon\Carbon;

class Whiteboard_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_staff_counts($staffid){
        $count = 0;

        $sql = "SELECT count(`staffid`) as total_count
                from ".db_prefix()."whiteboard where staffid= '".$staffid."' " ;
        $query = $this->db->query($sql);
        $row = $query->row();
        if (isset($row)){
            $count = $row->total_count;
        }

        return $count;
    }

    /**
     * Get groups
     * @param  mixed $id group id (Optional)
     * @return mixed     object or array
     */
    public function get_groups($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'whiteboard_groups')->row();
        }
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'whiteboard_groups')->result_array();
    }

    /**
     * Add new group
     * @param mixed $data All $_POST data
     * @return boolean
     */
    public function add_group($data)
    {
        $data['description'] = nl2br($data['description']);
        $this->db->insert(db_prefix() . 'whiteboard_groups', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('whiteboard Group Added [ID: ' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }
    /**
     * Get Projects
     * @param  mixed project (Optional)
     * @return mixed     object or array
     */
    public function get_projects()
    {
        return $this->db->get(db_prefix() . 'projects')->result_array();
    }
    /**
     * Update group
     * @param  mixed $data All $_POST data
     * @param  mixed $id   group id to update
     * @return boolean
     */
    public function update_group($data, $id)
    {
        $data['description'] = nl2br($data['description']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'whiteboard_groups', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('whiteboard Group Updated [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return mixed
     * Delete type from database, if used return array with key referenced
     */
    public function delete_group($id)
    {
        if (is_reference_in_table('whiteboard_group_id', db_prefix() . 'whiteboard', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'whiteboard_groups');
        if ($this->db->affected_rows() > 0) {
            log_activity('Group Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer (optional)
     * @return object
     * Get single
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'whiteboard')->row();
        }
        return $this->db->get(db_prefix() . 'whiteboard')->result_array();
    }

    /**
     * Add new
     * @param mixed $data All $_POST dat
     * @return mixed
     */
    public function add($data)
    {
        
        $data['staffid']      = $data['staffid'] == '' ? 0 : $data['staffid'];
        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['hash'] = app_generate_hash();
        $this->db->insert(db_prefix() . 'whiteboard', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New whiteboard Added [ID:' . $insert_id . ']');

            return $insert_id;
        }
        return false;
    }

    /**
     * Update
     * @param  mixed $data All $_POST data
     * @param  mixed $id    id
     * @return boolean
     */
    public function update($data, $id)
    {
        $data['staffid']      = $data['staffid'] == '' ? 0 : $data['staffid'];
        $data['dateaupdated'] = date('Y-m-d H:i:s');
        $exist = $this->get($id);
        if(!isset($exist->hash)){
             $data['hash'] = app_generate_hash();
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'whiteboard', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('whiteboard Updated [ID:' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Delete
     * @param  mixed $id id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'whiteboard');
        if ($this->db->affected_rows() > 0) {
            log_activity('whiteboard Deleted [ID:' . $id . ']');
            return true;
        }
        return false;
    }


    public function get_rate($rating, $id){
        $str = '';
        if($rating == 0) {
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">0 / 5</span>';
            }elseif($rating == 0.5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" checked /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">0.5 / 5</span>';
            }elseif($rating == 1.5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" checked /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">1.5 / 5</span>';
            }elseif($rating == 1){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" checked /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">1 / 5</span>';
            }elseif($rating == 2){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" checked /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">2 / 5</span>';
            }elseif($rating == 2.5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" checked /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">2.5 / 5</span>';
            }elseif($rating == 3){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" checked /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">3 / 5</span>';
            }elseif($rating == 3.5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" checked /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">3.5 / 5</span>';
            }elseif($rating == 4){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" checked /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">4 / 5</span>';
            }elseif($rating == 4.5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" checked /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">4.5 / 5</span>';
            }elseif($rating == 5){
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" checked /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">5 / 5</span>';
            }else{
                $str .= '<input type="radio" id="'.$id.'star55" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="5" /><label class = "full" for="'.$id.'star55" title="Awesome - 5 stars"></label>                     <input type="radio" id="'.$id.'star44halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4.5" /><label class="half" for="'.$id.'star44halff" title="Pretty good - 4.5 stars"></label>                      <input type="radio" id="'.$id.'star44" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="4" /><label class = "full" for="'.$id.'star44" title="Pretty good - 4 stars"></label>                      <input type="radio" id="'.$id.'star33halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3.5" /><label class="half" for="'.$id.'star33halff" title="Meh - 3.5 stars"></label>                      <input type="radio" id="'.$id.'star33" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="3" /><label class = "full" for="'.$id.'star33" title="Meh - 3 stars"></label>                      <input type="radio" id="'.$id.'star22halff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2.5" /><label class="half" for="'.$id.'star22halff" title="Kinda bad - 2.5 stars"></label>                        <input type="radio" id="'.$id.'star22" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="2" /><label class = "full" for="'.$id.'star22" title="Kinda bad - 2 stars"></label>                        <input type="radio" id="'.$id.'star11half" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1.5" /><label class="half" for="'.$id.'star11half" title="Meh - 1.5 stars"></label>                        <input type="radio" id="'.$id.'star11" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="1" /><label class = "full" for="'.$id.'star11" title="Sucks big time - 1 star"></label>                        <input type="radio" id="'.$id.'starhalff" name="rating_'.$id.'" onclick="update_rating('.$id.');" value="0.5" /><label class="half" for="'.$id.'starhalff" title="Sucks big time - 0.5 stars"></label><span id="rt_val_'.$id.'" style="float: right;padding: 8px;">0 / 5</span>';
            }
        return $str;
    }

    public function get_discussion_comments($id, $type)
    {
        $this->db->where('whiteboard_id', $id);
        $this->db->where('discussion_type', $type);
        $comments             = $this->db->get(db_prefix() . 'whiteboardcomments')->result_array();
        $i                    = 0;
        $allCommentsIDS       = [];
        $allCommentsParentIDS = [];
        
        foreach ($comments as $comment) {                       
        
        $str = '';
        //$comments[$i]['rating'] = (!empty($comment['rating']) && $comment['rating'] != '') ? $comment['rating'] : 0;
        if(!empty($comments[$i]['rating'])) {
            $comments[$i]['rating'] = $this->get_rate($comments[$i]['rating'], $comment['id']);
        }else{
            $comments[$i]['rating'] = $this->get_rate(0, $comment['id']);
        }
            
        
        
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
                        if ($comment['staff_id'] == get_staff_user_id()) {
                            $comments[$i]['created_by_current_user'] = true;
                        } else {
                            $comments[$i]['created_by_current_user'] = false;
                        }
                    } else {
                        $comments[$i]['created_by_current_user'] = false;
                    }
                }
                if (is_admin($comment['staff_id'])) {
                    $comments[$i]['created_by_admin'] = true;
                } else {
                    $comments[$i]['created_by_admin'] = false;
                }
                $comments[$i]['profile_picture_url'] = staff_profile_image_url($comment['staff_id']);
            }
            if (!is_null($comment['file_name'])) {
                $comments[$i]['file_url'] = site_url('uploads/whiteboard/' . $id . '/' . $comment['file_name']);
            }
            $comments[$i]['created'] = (strtotime($comment['created']) * 1000);
            if (!empty($comment['modified'])) {
                $comments[$i]['modified'] = (strtotime($comment['modified']) * 1000);
            }
            $i++;
        }

        // Ticket #5471
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

    public function update_discussion_comment($data)
    {
        $comment = $this->get_discussion_comment($data['id']);
        $this->db->where('id', $data['id']);
        if (!empty($data['updated_rating']) && isset($data['updated_rating']) && $data['updated_rating'] != 0) { 
            $_data['rating'] = $data['updated_rating'];        }
        $this->db->update(db_prefix() . 'whiteboardcomments', [
            'modified' => date('Y-m-d H:i:s'),
            'content'  => $data['content'],
            'rating'  => $_data['rating'],
        ]);
        if ($this->db->affected_rows() > 0) {
           //$this->_update_discussion_last_activity($comment->whiteboard_id, $comment->discussion_type);
        }

        return $this->get_discussion_comment($data['id']);
    }

     public function update_discussion_comment_rating($data)
    {
        
        $this->db->where('id', $data['id']);
        if (!empty($data['rating']) && isset($data['rating']) && $data['rating'] != 0) { 
            $_data['rating'] = $data['rating'];        }
        $this->db->update(db_prefix() . 'whiteboardcomments', [
            'modified' => date('Y-m-d H:i:s'),
            'rating'  => $_data['rating'],
        ]);
        if ($this->db->affected_rows() > 0) {
           //$this->_update_discussion_last_activity($comment->whiteboard_id, $comment->discussion_type);
        }

        return $this->get_discussion_comment($data['id']);
    }

    public function delete_discussion_comment($id, $logActivity = true)
    {
        $comment = $this->get_discussion_comment($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'whiteboardcomments');
        if ($this->db->affected_rows() > 0) {
            $this->delete_discussion_comment_attachment($comment->file_name, $comment->whiteboard_id);
            if ($logActivity) {
                $additional_data = '';
                if ($comment->discussion_type == 'regular') {
                    $discussion = $this->get_discussion($comment->whiteboard_id);
                    $not        = 'project_activity_deleted_discussion_comment';
                    $additional_data .= $discussion->subject . '<br />' . $comment->content;
                } else {
                    $discussion = $this->get_file($comment->whiteboard_id);
                    $not        = 'project_activity_deleted_file_discussion_comment';
                    $additional_data .= $discussion->subject . '<br />' . $comment->content;
                }

                if (!is_null($comment->file_name)) {
                    $additional_data .= $comment->file_name;
                }

                $this->log_activity($discussion->project_id, $not, $additional_data);
            }
        }

        $this->db->where('parent', $id);
        $this->db->update(db_prefix() . 'whiteboardcomments', [
            'parent' => null,
        ]);

        if ($this->db->affected_rows() > 0 && $logActivity) {
            $this->_update_discussion_last_activity($comment->whiteboard_id, $comment->discussion_type);
        }

        return true;
    }

    public function delete_discussion_comment_attachment($file_name, $whiteboard_id)
    {
        $path = WHITEBOARD_DISCUSSION_ATTACHMENT_FOLDER . $whiteboard_id;
        if (!is_null($file_name)) {
            if (file_exists($path . '/' . $file_name)) {
                unlink($path . '/' . $file_name);
            }
        }
        if (is_dir($path)) {
            // Check if no attachments left, so we can delete the folder also
            $other_attachments = list_files($path);
            if (count($other_attachments) == 0) {
                delete_dir($path);
            }
        }
    }
    public function get_discussion($id, $project_id = '')
    {
        if ($project_id != '') {
            $this->db->where('project_id', $project_id);
        }
        $this->db->where('id', $id);
        if (is_client_logged_in()) {
            $this->db->where('show_to_customer', 1);
            $this->db->where('project_id IN (SELECT id FROM ' . db_prefix() . 'projects WHERE clientid=' . get_client_user_id() . ')');
        }
        $discussion = $this->db->get(db_prefix() . 'projectdiscussions')->row();
        if ($discussion) {
            return $discussion;
        }

        return false;
    }

    public function add_discussion_comment($data, $whiteboard_id, $type)
    {
        $discussion               = $this->get_discussion($whiteboard_id);
        $_data['whiteboard_id']   = $whiteboard_id;
        $_data['discussion_type'] = $type;
        if (isset($data['content'])) {
            $_data['content'] = $data['content'];
        }               if (!empty($data['rating']) && isset($data['rating']) && $data['rating'] != 0) {            $_data['rating'] = $data['rating'];        }
        if (isset($data['parent']) && $data['parent'] != null) {
            $_data['parent'] = $data['parent'];
        }
        if (is_client_logged_in()) {
            $_data['contact_id'] = get_contact_user_id();
            $_data['fullname']   = get_contact_full_name($_data['contact_id']);
            $_data['staff_id']   = 0;
        } else {
            $_data['contact_id'] = 0;
            $_data['staff_id']   = get_staff_user_id();
            $_data['fullname']   = get_staff_full_name($_data['staff_id']);
        }
        $_data            = handle_whiteboard_discussion_comment_attachments($whiteboard_id, $data, $_data);
        $_data['created'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'whiteboardcomments', $_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($type == 'regular') {
                $discussion = $this->get_discussion($whiteboard_id);
                // $not_link   = 'projects/view/' . $discussion->project_id . '?group=project_discussions&whiteboard_id=' . $whiteboard_id;
            } else {
                $discussion                   = $this->get_file($whiteboard_id);
                // $not_link                     = 'projects/view/' . $discussion->project_id . '?group=project_files&file_id=' . $whiteboard_id;
                $discussion->show_to_customer = $discussion->visible_to_customer;
            }

            // $this->send_project_email_template($discussion->project_id, 'project_new_discussion_comment_to_staff', 'project_new_discussion_comment_to_customer', $discussion->show_to_customer, [
            //     'staff' => [
            //         'whiteboard_id'         => $whiteboard_id,
            //         'discussion_comment_id' => $insert_id,
            //         'discussion_type'       => $type,
            //     ],
            //     'customers' => [
            //         'customer_template'     => true,
            //         'whiteboard_id'         => $whiteboard_id,
            //         'discussion_comment_id' => $insert_id,
            //         'discussion_type'       => $type,
            //     ],
            // ]);


            // $this->log_activity($discussion->project_id, 'project_activity_commented_on_discussion', $discussion->subject, $discussion->show_to_customer);

            // $notification_data = [
            //     'description' => 'not_commented_on_project_discussion',
            //     'link'        => $not_link,
            // ];

            // if (is_client_logged_in()) {
            //     $notification_data['fromclientid'] = get_contact_user_id();
            // } else {
            //     $notification_data['fromuserid'] = get_staff_user_id();
            // }

            // $members       = $this->get_project_members($discussion->project_id);
            // $notifiedUsers = [];
            // foreach ($members as $member) {
            //     if ($member['staff_id'] == get_staff_user_id() && !is_client_logged_in()) {
            //         continue;
            //     }
            //     $notification_data['touserid'] = $member['staff_id'];
            //     if (add_notification($notification_data)) {
            //         array_push($notifiedUsers, $member['staff_id']);
            //     }
            // }
            // pusher_trigger_notification($notifiedUsers);

            // $this->_update_discussion_last_activity($whiteboard_id, $type);

            return $this->get_discussion_comment($insert_id);
        }

        return false;
    }
    public function get_discussion_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'whiteboardcomments')->row();
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
                    if ($comment->staff_id == get_staff_user_id()) {
                        $comment->created_by_current_user = true;
                    } else {
                        $comment->created_by_current_user = false;
                    }
                } else {
                    $comment->created_by_current_user = false;
                }
            }
            if (is_admin($comment->staff_id)) {
                $comment->created_by_admin = true;
            } else {
                $comment->created_by_admin = false;
            }
            $comment->profile_picture_url = staff_profile_image_url($comment->staff_id);
        }
        $comment->created = (strtotime($comment->created) * 1000);
        if (!empty($comment->modified)) {
            $comment->modified = (strtotime($comment->modified) * 1000);
        }
        if (!is_null($comment->file_name)) {
            $comment->file_url = site_url('uploads/whiteboard/' . $comment->whiteboard_id . '/' . $comment->file_name);
        }
        
        $comment->rating = $this->get_rate(!empty($comment->rating) ? $comment->rating : 0, $comment->id);

        return $comment;
    }
}
