<?php
function getStaff($id = null)
{
  $CI = get_instance();
  if (!is_admin()) {
    access_denied('Access Denied');
  }
  $CI->db->select('*');
  $CI->db->where('active', 1);
  if (!empty($id)) {
    $CI->db->where('staffid', $id);
  }

  return $CI->db->get(db_prefix() . 'staff')->result_array();
}

function getStaffId()
{
  $CI = get_instance();
  $CI->load->library('Authorization_Token');
  $is_valid_token = $CI->authorization_token->validateToken();
  if ($is_valid_token['status']) {
    return $is_valid_token['data']->staff_id;
  } else {
    return false;
  }
}

function get_total_unread_sms($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $query = '';
        if ($where['is_client'] == 'no') {
            $query = $CI->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . ") AND is_client = 0 AND is_read='no' AND sms_direction='incoming'");
        } else {
            $query = $CI->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . ") AND is_client = 1 AND is_read='no' AND sms_direction='incoming'");
        }
        return $query->row()->unread;
    }
}

function format_task_members_by_ids_and_names($ids, $names)
{
    $output = [];
    $assignees   = explode(',', $names);
    $assigneeIds = explode(',', $ids);
    foreach ($assignees as $key => $assigned) {
        $assignee_id = $assigneeIds[$key];
        $assignee_id = trim($assignee_id);
        if ($assigned != '') {
              $output[]=[
                'name'=>$assigned,
                'profile_url'=>admin_url('profile/' . $assignee_id),
                'profile_image'=>staff_profile_image_url($assignee_id),

              ];
        }
    }
    return $output;
}
