<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meetings_model extends CI_Model {
    
    private $table = 'meetings';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function get($id = NULL) {
        if ($id !== NULL) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        return $this->db->get($this->table)->result_array();
    }


    public function save($meetingData, $participants)
    {
        // Start transaction
        $this->db->trans_begin();

        try {
            // Check if service_id is valid
            if (!empty($meetingData['service_id'])) {
                $this->db->where('id', $meetingData['service_id']);
                $query = $this->db->get('meeting_services');
                if ($query->num_rows() == 0) {
                    throw new Exception('Invalid service_id');
                }
            }
            // Insert into meetings table
            $this->db->insert('meetings', $meetingData);
            $meetingId = $this->db->insert_id();

            // Insert participants
            $participantData = [];
            $participantData[] = [
                'meeting_id' => $meetingId,
                'participant_type' => 'Lead',
                'participant_id' => $participants['Lead'],
                'created_by'=> get_staff_user_id(),
                'created_datetime'=> date('Y-m-d H:i:s'),
            ];
            $participantData[] = [
                'meeting_id' => $meetingId,
                'participant_type' => 'Customer',
                'participant_id' => $participants['Customer'],
                'created_by'=> get_staff_user_id(),
                'created_datetime'=> date('Y-m-d H:i:s'),
            ];
            foreach ($participants['Staff'] as $id) {
                    $participantData[] = [
                        'meeting_id' => $meetingId,
                        'participant_type' => 'Staff',
                        'participant_id' => $id,
                        'created_by'=> get_staff_user_id(),
                        'created_datetime'=> date('Y-m-d H:i:s'),
                    ];
            }

            if (!empty($participantData)) {
                $this->db->insert_batch('meeting_participants', $participantData);
            }

            // Commit transaction
            if ($this->db->trans_status() === FALSE) {
                // Rollback transaction
                $this->db->trans_rollback();
                return FALSE;
            } else {
                $this->db->trans_commit();


                if(option_exists('meetlink_manager_menu_send_email') &&  get_option('meetlink_manager_menu_send_email') == 0){
                    $this->send_mail($meetingId);
                }
                return $meetingId;
            }

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->trans_rollback();
            throw $e;
        }
    }

    public function update($meetingId,$meetingData, $participants)
    {
        // Start transaction
        $this->db->trans_begin();

        try {
            // Check if service_id is valid
            if (!empty($meetingData['service_id'])) {
                $this->db->where('id', $meetingData['service_id']);
                $query = $this->db->get('meeting_services');
                if ($query->num_rows() == 0) {
                    throw new Exception('Invalid service_id');
                }
            }
            // Insert into meetings table
            $this->db->where('id', $meetingId);
            $this->db->update('meetings', $meetingData);


               // Delete old participants (optional: you may choose to update instead of delete)
            $this->db->where('meeting_id', $meetingId);
            $this->db->delete('meeting_participants');

            // Insert participants
            $participantData = [];
            $participantData[] = [
                'meeting_id' => $meetingId,
                'participant_type' => 'Lead',
                'participant_id' => $participants['Lead'],
                'created_by'=> get_staff_user_id(),
                'created_datetime'=> date('Y-m-d H:i:s'),
            ];
            $participantData[] = [
                'meeting_id' => $meetingId,
                'participant_type' => 'Customer',
                'participant_id' => $participants['Customer'],
                'created_by'=> get_staff_user_id(),
                'created_datetime'=> date('Y-m-d H:i:s'),
            ];
            foreach ($participants['Staff'] as $id) {
                    $participantData[] = [
                        'meeting_id' => $meetingId,
                        'participant_type' => 'Staff',
                        'participant_id' => $id,
                        'created_by'=> get_staff_user_id(),
                        'created_datetime'=> date('Y-m-d H:i:s'),
                    ];
            }


            if (!empty($participantData)) {
                $this->db->insert_batch('meeting_participants', $participantData);
            }

            // Commit transaction
            if ($this->db->trans_status() === FALSE) {
                // Rollback transaction
                $this->db->trans_rollback();
                return FALSE;
            } else {
                $this->db->trans_commit();

                if(option_exists('meetlink_manager_menu_send_email') &&  get_option('meetlink_manager_menu_send_email') == 0 && get_option('meetlink_manager_menu_update_mail') == 0){
                    $this->send_mail($meetingId);
                }
                return $meetingId;
            }

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->trans_rollback();
            throw $e;
        }
    }


    public function send_mail($meetingId) {
        try {
            // Fetch meeting data
            $meetingData = $this->get($meetingId);
        
            // Get staff, client, and lead information associated with the meeting
            $staff_ids = $this->get_staff_id($meetingId);
            $staffData = get_staff_by_ids($staff_ids);
            $client_ids = $this->get_customers_id($meetingId);
            $lead_id = $this->get_lead_id($meetingId);
        
            // Prepare common data for all email templates
            $data = [
                'meetingData' => $meetingData,
                'client_id' => $client_ids[0] ?? null,  // Using null-coalescing operator for safety
                'lead_id' => $lead_id
            ];
            
            // Send email to all staff members
            foreach ($staffData as $staff) {
                try {
                    if (!empty($staff['email'])) {
                        $data['send_email'] = $staff['email'];
                        send_mail_template('link_send_staff', 'meetlink_manager', $data);
                    }
                } catch (Exception $e) {
                    log_message('error', 'Error sending email to staff: ' . $staff['email'] . ' - ' . $e->getMessage());
                }
            }
        
            // Send email to all admins
            $admins = $this->get_admin_list();
            foreach ($admins as $admin) {
                try {
                    if (!empty($admin['email'])) {
                        $data['send_email'] = $admin['email'];
                        send_mail_template('link_send_admin', 'meetlink_manager', $data);
                    }
                } catch (Exception $e) {
                    log_message('error', 'Error sending email to admin: ' . $admin['email'] . ' - ' . $e->getMessage());
                }
            }
        
            // Send email to the primary client contact
            if (!empty($client_ids[0])) {
                try {
                    $contact_id = get_primary_contact_user_id($client_ids[0]);
                    if ($contact_id) {
                        $this->db->where('userid', $client_ids[0]);
                        $this->db->where('id', $contact_id);
                        $contact = $this->db->get(db_prefix() . 'contacts')->row();
                        
                        if (!empty($contact->email)) {
                            $data['send_email'] = $contact->email;
                            send_mail_template('link_send_client', 'meetlink_manager', $data);
                        }
                    }
                } catch (Exception $e) {
                    log_message('error', 'Error sending email to client contact: ' . $client_ids[0] . ' - ' . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Error in send_mail function for meeting ID: ' . $meetingId . ' - ' . $e->getMessage());
        }
    }
    


    public function get_admin_list(){
        $this->db->where('admin', 1);
        return $this->db->get(db_prefix().'staff')->result_array();
    }
    
    public function add($data) {
        $this->db->insert($this->table, $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New meeting added [ ID:'.$insert_id.', '.$data['title'].', Staff id '.get_staff_user_id().' ]');
            return $insert_id;
        }
        return false;
    }
    
  
    
    public function delete($id)
    {
        // Start transaction
        $this->db->trans_begin();

        try {
            // Delete participants associated with the meeting
            $this->db->where('meeting_id', $id);
            $this->db->delete('meeting_participants');

            // Check if participants were deleted successfully
            if ($this->db->affected_rows() == 0) {
                throw new Exception('No participants found or deleted for this meeting');
            }

            // Delete the meeting itself
            $this->db->where('id', $id);
            $this->db->delete('meetings');

            // Check if the meeting was deleted successfully
            if ($this->db->affected_rows() == 0) {
                throw new Exception('Meeting not found or failed to delete');
            }

            // Commit transaction if everything is successful
            if ($this->db->trans_status() === FALSE) {
                // Rollback transaction if any query fails
                $this->db->trans_rollback();
                return FALSE;
            } else {
                // Commit transaction
                $this->db->trans_commit();
                return TRUE;
            }

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->trans_rollback();
            throw $e;
        }
    }


    public function get_lead_id($id){

        
        $this->db->select('participant_id');
        $this->db->from('meeting_participants');
        $this->db->where('meeting_id', $id);
        $this->db->where('participant_type', 'Lead');
        $query = $this->db->get();
        return $query->row()->participant_id;
    }

    public function get_customers_id($id){

        $this->db->select('participant_id');
        $this->db->from('meeting_participants');
        $this->db->where('meeting_id', $id);
        $this->db->where('participant_type', 'Customer');
        $query = $this->db->get()->result_array();

        // Use array_column to pluck participant_id values
        $participant_ids = array_column($query, 'participant_id');

        return $participant_ids;
    }

    public function get_staff_id($id){

        $this->db->select('participant_id');
        $this->db->from('meeting_participants');
        $this->db->where('meeting_id', $id);
        $this->db->where('participant_type', 'Staff');
        $query = $this->db->get()->result_array();

        // Use array_column to pluck participant_id values
        $participant_ids = array_column($query, 'participant_id');

        return $participant_ids;
    }
 

}
