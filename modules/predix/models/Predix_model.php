<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Predix_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addUserChatMessage($data)
    {
        $this->db->insert(db_prefix() . 'predix_chat', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getUserChatData($chat_id)
    {
        $this->db->where('id', $chat_id);
        return $this->db->get(db_prefix() . 'predix_chat')->row();
    }

    public function updateUserChat($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'predix_chat', $data);

        return $this->db->affected_rows() > 0;
    }

    public function deleteUserChatHistory()
    {
        $this->db->where('user_id', get_staff_user_id());
        $this->db->delete(db_prefix() . 'predix_chat');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function getUserChatHistory($order = 'asc')
    {
        $this->db->where('user_id', get_staff_user_id());
        $this->db->order_by('id', $order);

        return $this->db->get(db_prefix() . 'predix_chat')->result_array();
    }

    public function addUserGeneratedImage($data)
    {
        $this->db->insert(db_prefix() . 'predix_images', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function updateUserGeneratedImage($fileId, $data)
    {
        $this->db->where('id', $fileId);
        $this->db->update(db_prefix() . 'predix_images', $data);

        return $this->db->affected_rows() > 0;
    }

    public function getUserGeneratedImages()
    {
        $this->db->where('user_id', get_staff_user_id());
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix() . 'predix_images')->result_array();
    }

    public function deleteUserGeneratedImage($imageId)
    {
        $this->db->where('id', $imageId);
        $this->db->delete(db_prefix() . 'predix_images');

        $directory = FCPATH . 'modules/predix/uploads/generated_images/' . $imageId . '/';

        if (is_dir($directory)) {
            delete_dir($directory);
        }

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function insertAudioTranslateFile($data)
    {
        $this->db->insert(db_prefix() . 'predix_translated_audio', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function updateAudioTranslateFile($audioId, $data)
    {
        $this->db->where('id', $audioId);
        $this->db->update(db_prefix() . 'predix_translated_audio', $data);

        return $this->db->affected_rows() > 0;
    }

    public function getUserTranslatedAudioFiles()
    {
        $this->db->where('user_id', get_staff_user_id());
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix() . 'predix_translated_audio')->result_array();
    }

    public function deleteUserTranslatedAudioFile($fileId, $delete_dir=true)
    {
        $this->db->where('id', $fileId);
        $this->db->delete(db_prefix() . 'predix_translated_audio');

        $directory = FCPATH . 'modules/predix/uploads/audio_translation/' . $fileId . '/';

        if ($delete_dir) {
            if (is_dir($directory)) {
                delete_dir($directory);
            }
        }

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function insertAudioTranscriptionFile($data)
    {
        $this->db->insert(db_prefix() . 'predix_audio_transcription', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function updateAudioTranscriptionFile($audioId, $data)
    {
        $this->db->where('id', $audioId);
        $this->db->update(db_prefix() . 'predix_audio_transcription', $data);

        return $this->db->affected_rows() > 0;
    }

    public function getUserTranscriptionAudioFiles()
    {
        $this->db->where('user_id', get_staff_user_id());
        $this->db->order_by('id', 'desc');
        return $this->db->get(db_prefix() . 'predix_audio_transcription')->result_array();
    }

    public function deleteUserTranscriptionAudioFile($fileId, $delete_dir=true)
    {
        $this->db->where('id', $fileId);
        $this->db->delete(db_prefix() . 'predix_audio_transcription');

        $directory = FCPATH . 'modules/predix/uploads/audio_transcription/' . $fileId . '/';

        if ($delete_dir) {
            if (is_dir($directory)) {
                delete_dir($directory);
            }
        }

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function addTemplateCategory($data)
    {
        $this->db->insert(db_prefix() . 'predix_template_categories', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getTemplateCategory($templateCategoryId)
    {
        $this->db->where('id', $templateCategoryId);
        return $this->db->get(db_prefix() . 'predix_template_categories')->row();
    }

    public function getTemplateCategories()
    {
        $this->db->where('is_enabled', '1');
        return $this->db->get(db_prefix() . 'predix_template_categories')->result_array();
    }

    public function updateTemplateCategory($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'predix_template_categories', $data);

        return $this->db->affected_rows() > 0;
    }

    public function changeTemplateCategoryStatus($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix().'predix_template_categories', [
            'is_enabled' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function deleteTemplateCategory($template_category_id)
    {

        if (is_reference_in_table('template_category_id', db_prefix() . 'predix_templates', $template_category_id)) {
            return [
                'referenced' => true,
            ];
        }

        $this->db->where('id', $template_category_id);
        $this->db->delete(db_prefix() . 'predix_template_categories');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function addTemplate($data)
    {
        $this->db->insert(db_prefix() . 'predix_templates', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getTemplate($template_id)
    {
        $this->db->select('*, predix_templates.id');
        $this->db->where('predix_templates.id', $template_id);
        $this->db->join(db_prefix() . 'predix_template_categories', db_prefix() . 'predix_template_categories.id=' . db_prefix() . 'predix_templates.template_category_id', 'left');
        return $this->db->get(db_prefix() . 'predix_templates')->row();
    }

    public function getTemplates()
    {
        return $this->db->get(db_prefix() . 'predix_templates')->result_array();
    }

    public function updateTemplate($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'predix_templates', $data);

        return $this->db->affected_rows() > 0;
    }

    public function deleteTemplate($template_id)
    {
        $this->db->where('id', $template_id);
        $this->db->delete(db_prefix() . 'predix_templates');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function addDocument($document_data)
    {
        $this->db->insert(db_prefix() . 'predix_documents', $document_data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getDocument($document_id)
    {
        $this->db->where('id', $document_id);
        return $this->db->get(db_prefix() . 'predix_documents')->row();
    }

    public function deleteDocument($document_id)
    {
        $this->db->where('id', $document_id);
        $this->db->delete(db_prefix() . 'predix_documents');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

}
