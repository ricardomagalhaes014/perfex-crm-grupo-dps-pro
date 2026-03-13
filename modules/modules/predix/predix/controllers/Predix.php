<?php

defined('BASEPATH') or exit('No direct script access allowed');

const ROLE = "role";
const CONTENT = "content";
const USER = "user";
const SYS = "system";
const ASSISTANT = "assistant";

class Predix extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('predix_model');
    }

    public function index()
    {
        show_404();
    }

    public function chat()
    {
        if (!has_permission('predix', '', 'view_chat')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_chat');
        $this->load->view('chat', $data);
    }

    public function addUserChatMessage()
    {
        if (!has_permission('predix', '', 'view_chat')) {
            access_denied('predix');
        }

        if ($this->input->post()) {

            $message = stripslashes($this->input->post('message'));
            $secured_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $chatId = $this->predix_model->addUserChatMessage([
                'user_id' => get_staff_user_id(),
                'human_message' => $secured_message,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $data = [
                "id" => $chatId,
                'message' => $secured_message
            ];

            echo json_encode($data);
            die;
        }
    }

    public function generateChatAiResponse($chat_id)
    {

        if (!has_permission('predix', '', 'view_chat')) {
            access_denied('predix');
        }

        $open_ai_key = get_option('predix_openai_secret_key');
        $open_ai = new OpenAi($open_ai_key);

        $userChatHistory = $this->predix_model->getUserChatHistory();
        $history = [];

        $chatId = $chat_id;

        foreach ($userChatHistory as $chat) {
            $history[] = [ROLE => USER, CONTENT => $chat['human_message']];
            $history[] = [ROLE => ASSISTANT, CONTENT => $chat['ai_response'] ?: ''];
        }

        $getUserChat = $this->predix_model->getUserChatData($chatId);
        $history[] = [ROLE => USER, CONTENT => $getUserChat->human_message];

        $opts = [
            'model' => get_option('predix_chat_model'),
            'messages' => $history,
            'temperature' => 1.0,
            'max_tokens' => (int)get_option('predix_text_limit'),
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        $txt = "";
        if (get_option('predix_use_streams_for_chat') == 1) {

            $opts['stream'] = true;

            header('Content-type: text/event-stream');
            header('Cache-Control: no-cache');

            $complete = $open_ai->chat($opts, function ($curl_info, $data) use (&$txt) {

                header('Content-type: text/event-stream');
                header('Cache-Control: no-cache');

                if ($obj = json_decode($data) and $obj->error->message != "") {
                    $txt .= 'Please provide OpenAI API Secret Key : PrediX->Settings->OpenAI Secret Key';
                } else {
                    echo $data;
                    $clean = str_replace("data: ", "", $data);
                    $arr = json_decode($clean, true);
                    if ($data != "data: [DONE]\n\n" and isset($arr["choices"][0]["delta"]["content"])) {
                        $txt .= $arr["choices"][0]["delta"]["content"];
                    }
                }

                echo PHP_EOL;
                ob_flush();
                flush();
                return strlen($data);
            });

        } else {
            $chatResponse = $open_ai->chat($opts);
            $chatResponse = json_decode($chatResponse);

            if (isset($chatResponse->error)) {
                $txt .= 'Please provide OpenAI API Secret Key : PrediX->Settings->OpenAI Secret Key';
            } else {
                $txt .= $chatResponse->choices[0]->message->content;
            }

            $this->predix_model->updateUserChat($getUserChat->id, ['ai_response' => $txt, 'created_at' => date('Y-m-d H:i:s')]);

            echo json_encode(['ai_response' => nl2br($txt)]);
            die;

        }
        $this->predix_model->updateUserChat($getUserChat->id, ['ai_response' => $txt, 'created_at' => date('Y-m-d H:i:s')]);

    }

    public function getUserChatHistory()
    {
        if (!has_permission('predix', '', 'view_chat')) {
            access_denied('predix');
        }

        $chatHistory = $this->predix_model->getUserChatHistory();

        foreach ($chatHistory as &$row) {
            if (!is_null($row['human_message'])) {
                $row['human_message'] = nl2br($row['human_message']);
            }

            if (!is_null($row['ai_response'])) {
                $row['ai_response'] = nl2br($row['ai_response']);
            }
        }

        echo json_encode($chatHistory);
        die;

    }

    public function deleteUserChatHistory()
    {

        if (!has_permission('predix', '', 'delete_chat')) {
            access_denied('predix');
        }

        $this->predix_model->deleteUserChatHistory();
        http_response_code(204);
    }

    public function image_generator()
    {

        if (!has_permission('predix', '', 'view_image_gen')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_image_gen');
        $data['generatedImages'] = $this->predix_model->getUserGeneratedImages();

        $this->load->view('image_generator', $data);
    }

    public function generateImageWithAI()
    {

        if (!has_permission('predix', '', 'create_image_gen')) {
            access_denied('predix');
        }

        if ($this->input->post()) {

            $imagePrompt = $this->input->post('image_input');
            $imagePrompt = $this->security->xss_clean($imagePrompt);

            $numberOfImages = $this->input->post('number_of_images');
            $numberOfImages = $this->security->xss_clean($numberOfImages);

            $imageSize = $this->input->post('size_of_image');
            $imageSize = $this->security->xss_clean($imageSize);

            $imageModel = $this->input->post('image_model');
            $imageModel = $this->security->xss_clean($imageModel);

            if (!is_admin() && $numberOfImages > get_option('predix_image_generator_maximum_images_generate')) {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Maximum number of images to create is : ' . get_option('predix_image_generator_maximum_images_generate')
                ]);
                die;
            }

            $open_ai_key = get_option('predix_openai_secret_key');
            $open_ai = new OpenAi($open_ai_key);

            $requestConfigurations = [
                "prompt" => $imagePrompt,
                "n" => (int)$numberOfImages,
                "size" => $imageSize,
            ];

            if (!empty($imageModel) && $imageModel != 'default') {
                $requestConfigurations['model'] = $imageModel;
            }

            $result = $open_ai->image($requestConfigurations);

            $result = (array)json_decode($result);

            if (isset($result['error'])) {
                echo json_encode([
                    'status' => 0,
                    'message' => $result['error']->message
                ]);
                die;
            }

            $imagesCreated = [];
            if ($numberOfImages > 0) {

                foreach ($result['data'] as $image) {

                    $fileContent = predix_download_content_with_curl($image->url);
                    $file = 'predix_' . md5(time());

                    $fileId = $this->predix_model->addUserGeneratedImage([
                        'image_url' => $image->url,
                        'user_id' => get_staff_user_id(),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $path = FCPATH . 'modules/predix/uploads/generated_images/' . $fileId . '/';
                    _maybe_create_upload_path($path);

                    file_put_contents($path . $file . '.png', $fileContent);

                    $fileUrl = substr(module_dir_url('predix/uploads/generated_images/' . $fileId . '/' . $file . '.png'), 0, -1);

                    $this->predix_model->updateUserGeneratedImage($fileId, [
                        'image_url' => $fileUrl
                    ]);

                    $imagesCreated[] = [
                        'id' => $fileId,
                        'file_url' => $fileUrl
                    ];

                }
            }

            echo json_encode($imagesCreated);
            die;

        }
    }

    public function deleteImageGenerated($image_id)
    {

        if (!has_permission('predix', '', 'delete_image_gen')) {
            access_denied('predix');
        }

        if (!$image_id) {
            redirect(admin_url('predix/image_generator'));
        }

        $response = $this->predix_model->deleteUserGeneratedImage($image_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('image')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('image')));
        }

        redirect(admin_url('predix/image_generator'));
    }

    public function audio_transcription()
    {

        if (!has_permission('predix', '', 'view_transcription')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_transcription');
        $data['generatedAudioTranscripts'] = $this->predix_model->getUserTranscriptionAudioFiles();

        $this->load->view('audio_transcription', $data);
    }

    public function audioTranscriptionWithAi()
    {

        if (!has_permission('predix', '', 'create_transcription')) {
            access_denied('predix');
        }

        if (isset($_FILES['AudioToTranslate']) && _perfex_upload_error($_FILES['AudioToTranslate']['error'])) {
            header('HTTP/1.0 400 Bad error');
            echo _perfex_upload_error($_FILES['AudioToTranslate']['error']);
            die;
        }

//        if ($this->input->post()){
        $open_ai = new OpenAi(get_option('predix_openai_secret_key'));

        if (isset($_FILES['AudioToTranslate']['name'])) {
            // Get the temp file path
            $tmpFilePath = $_FILES['AudioToTranslate']['tmp_name'];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {

                if ($_FILES['AudioToTranslate']['size'] > get_option('predix_audio_transcription_max_size')) {
                    echo json_encode([
                        'status' => 0,
                        'message' => _l('predix_audio_translation_file_maximum_size')
                    ]);
                    die;
                } elseif (!predix_transcriptions_upload_extension_allowed($_FILES['AudioToTranslate']['name'])) {
                    echo json_encode([
                        'status' => 0,
                        'message' => _l('predix_audio_translation_file_extensions_err')
                    ]);
                    die;
                } else {

                    $audioId = $this->predix_model->insertAudioTranscriptionFile([
                        'user_id' => get_staff_user_id(),
                        'audio_file_size' => $_FILES['AudioToTranslate']['size'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $path = FCPATH . 'modules/predix/uploads/audio_transcription/' . $audioId . '/';

                    _maybe_create_upload_path($path);
                    $filename = $_FILES['AudioToTranslate']['name'];
                    $newFilePath = $path . $filename;

                    // Upload the file into the temp dir
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                        $tmp_file = $_FILES['AudioToTranslate']['tmp_name'];
                        $file_name = basename($_FILES['AudioToTranslate']['name']);

                        $c_file = curl_file_create($path . $file_name);

                        $result = $open_ai->transcribe([
                            "model" => "whisper-1",
                            "file" => $c_file,
                        ]);
                        $result = json_decode($result);

                        if (isset($result->error)) {

                            $this->predix_model->deleteUserTranscriptionAudioFile($audioId, false);

                            echo json_encode([
                                'status' => 0,
                                'message' => $result->error->message
                            ]);
                            die;
                        }

                        $this->predix_model->updateAudioTranscriptionFile($audioId, [
                            'audio_file_path' => $newFilePath,
                            'transcription_text' => $result->text,
                        ]);

                        echo json_encode([
                            'status' => 1,
                            'audio_id' => $audioId,
                            'audio_web_link' => substr(module_dir_url('predix/uploads/audio_transcription/' . $audioId . '/' . $file_name), 0, -1),
                            'audio_file_name' => $file_name,
                            'transcription_text' => $result->text
                        ]);
                        die;

                    }

                    echo json_encode([
                        'status' => 0,
                        'message' => 'Failed'
                    ]);
                    die;

                }
            }
            echo json_encode([
                'status' => 0,
                'message' => 'Failed'
            ]);
            die;

        }
        echo json_encode([
            'status' => 0,
            'message' => 'Failed'
        ]);
        die;
    }

    public function deleteAudioTranscription($transcriptionId)
    {
        if (!has_permission('predix', '', 'delete_transcription')) {
            access_denied('predix');
        }

        if (!$transcriptionId) {
            redirect(admin_url('predix/audio_transcription'));
        }

        $response = $this->predix_model->deleteUserTranscriptionAudioFile($transcriptionId);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('predix_transcription')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('predix_transcription')));
        }

        redirect(admin_url('predix/audio_transcription'));
    }

    public function audio_translation()
    {

        if (!has_permission('predix', '', 'view_translation')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_translation');
        $data['generatedAudioTranslations'] = $this->predix_model->getUserTranslatedAudioFiles();

        $this->load->view('audio_translation', $data);
    }

    public function audioTranslationWithAI()
    {

        if (!has_permission('predix', '', 'create_translation')) {
            access_denied('predix');
        }

        if (isset($_FILES['AudioToTranslate']) && _perfex_upload_error($_FILES['AudioToTranslate']['error'])) {
            header('HTTP/1.0 400 Bad error');
            echo _perfex_upload_error($_FILES['AudioToTranslate']['error']);
            die;
        }

//        if ($this->input->post()){
        $open_ai = new OpenAi(get_option('predix_openai_secret_key'));

        if (isset($_FILES['AudioToTranslate']['name'])) {
            // Get the temp file path
            $tmpFilePath = $_FILES['AudioToTranslate']['tmp_name'];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {

                if ($_FILES['AudioToTranslate']['size'] > get_option('predix_audio_translation_max_size')) {
                    echo json_encode([
                        'status' => 0,
                        'message' => _l('predix_audio_translation_file_maximum_size')
                    ]);
                    die;
                } elseif (!predix_translations_upload_extension_allowed($_FILES['AudioToTranslate']['name'])) {
                    echo json_encode([
                        'status' => 0,
                        'message' => _l('predix_audio_translation_file_extensions_err')
                    ]);
                    die;
                } else {

                    $audioId = $this->predix_model->insertAudioTranslateFile([
                        'user_id' => get_staff_user_id(),
                        'audio_file_size' => $_FILES['AudioToTranslate']['size'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $path = FCPATH . 'modules/predix/uploads/audio_translation/' . $audioId . '/';

                    _maybe_create_upload_path($path);
                    $filename = $_FILES['AudioToTranslate']['name'];
                    $newFilePath = $path . $filename;

                    // Upload the file into the temp dir
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                        $tmp_file = $_FILES['AudioToTranslate']['tmp_name'];
                        $file_name = basename($_FILES['AudioToTranslate']['name']);

                        $c_file = curl_file_create($path . $file_name);

                        $result = $open_ai->translate([
                            "model" => "whisper-1",
                            "file" => $c_file,
                        ]);
                        $result = json_decode($result);

                        if (isset($result->error)) {

                            $this->predix_model->deleteUserTranslatedAudioFile($audioId, false);

                            echo json_encode([
                                'status' => 0,
                                'message' => $result->error->message
                            ]);
                            die;
                        }

                        $this->predix_model->updateAudioTranslateFile($audioId, [
                            'audio_file_path' => $newFilePath,
                            'translated_text' => $result->text,
                        ]);

                        echo json_encode([
                            'status' => 1,
                            'audio_id' => $audioId,
                            'audio_web_link' => substr(module_dir_url('predix/uploads/audio_translation/' . $audioId . '/' . $file_name), 0, -1),
                            'audio_file_name' => $file_name,
                            'translated_text' => $result->text
                        ]);
                        die;

                    }

                    echo json_encode([
                        'status' => 0,
                        'message' => 'Failed'
                    ]);
                    die;

                }
            }
            echo json_encode([
                'status' => 0,
                'message' => 'Failed'
            ]);
            die;

        }
        echo json_encode([
            'status' => 0,
            'message' => 'Failed'
        ]);
        die;
    }

    public function deleteAudioTranslation($translationId)
    {

        if (!has_permission('predix', '', 'delete_translation')) {
            access_denied('predix');
        }

        if (!$translationId) {
            redirect(admin_url('predix/audio_translation'));
        }

        $response = $this->predix_model->deleteUserTranslatedAudioFile($translationId);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('predix_translation')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('predix_translation')));
        }

        redirect(admin_url('predix/audio_translation'));

    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('predix');
        }

        if ($this->input->post()) {
            if (!is_admin()) {
                access_denied('settings');
            }
            $this->load->model('payment_modes_model');
            $this->load->model('settings_model');

            $logo_uploaded = (handle_company_logo_upload() ? true : false);
            $favicon_uploaded = (handle_favicon_upload() ? true : false);
            $signatureUploaded = (handle_company_signature_upload() ? true : false);

            $post_data = $this->input->post();
            $tmpData = $this->input->post(null, false);

            if (isset($post_data['settings']['email_header'])) {
                $post_data['settings']['email_header'] = $tmpData['settings']['email_header'];
            }

            if (isset($post_data['settings']['email_footer'])) {
                $post_data['settings']['email_footer'] = $tmpData['settings']['email_footer'];
            }

            if (isset($post_data['settings']['email_signature'])) {
                $post_data['settings']['email_signature'] = $tmpData['settings']['email_signature'];
            }

            if (isset($post_data['settings']['smtp_password'])) {
                $post_data['settings']['smtp_password'] = $tmpData['settings']['smtp_password'];
            }

            $post_data['settings']['predix_image_generator_allowed_image_sizes'] = implode(',', $post_data['settings']['predix_image_generator_allowed_image_sizes']);

            unset(
                $post_data['settings']['predix_audio_transcription_model'],
                $post_data['settings']['predix_audio_translation_model']
            );

            $success = $this->settings_model->update($post_data);

            if ($success > 0) {
                set_alert('success', _l('settings_updated'));
            }

            if ($logo_uploaded || $favicon_uploaded) {
                set_debug_alert(_l('logo_favicon_changed_notice'));
            }

            redirect(admin_url('predix/settings'), 'refresh');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_settings');

        $this->load->model('staff_model');
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);

        $this->load->view('settings', $data);
    }

    public function template_documents()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('predix', 'templates/documents/table'));
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_documents');
        $this->load->view('templates/documents/manage', $data);
    }

    public function create_template_document()
    {
        if ($this->input->post()) {

            $response = $this->predix_model->addDocument($this->input->post() + ['created_at' => date('Y-m-d H:i:s'), 'user_id' => get_staff_user_id()]);

            if (is_numeric($response)) {
                $message = _l('added_successfully', _l('predix_documents'));

                echo json_encode([
                    'status' => '1',
                    'message' => $message
                ]);
                die;
            } else {
                $message = 'Failed To Create Document';
                echo json_encode([
                    'status' => '0',
                    'message' => $message
                ]);
                die;
            }
        }
    }

    public function view_template_document($document_id)
    {
        $data['title'] = _l('predix') . ' - ' . _l('predix_documents');

        $data['document_data'] = $this->predix_model->getDocument($document_id);
        $this->load->view('templates/documents/view', $data);
    }

    public function delete_template_document($document_id)
    {
        if (!$document_id) {
            redirect(admin_url('predix/template_documents'));
        }

        $response = $this->predix_model->deleteDocument($document_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('predix_documents')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('predix_documents')));
        }

        redirect(admin_url('predix/template_documents'));
    }

    public function template_categories()
    {
        if (!has_permission('predix', '', 'view_template_categories')) {
            access_denied('predix');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('predix', 'templates/categories/table'));
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_template_categories');
        $this->load->view('templates/categories/manage', $data);
    }

    public function create_template_category($template_category = '')
    {
        if (!has_permission('predix', '', 'create_template_categories')) {
            access_denied('predix');
        }

        if ($this->input->post() && $template_category === '') {

            $response = $this->predix_model->addTemplateCategory($this->input->post() + ['created_at' => date('Y-m-d H:i:s')]);

            if (is_numeric($response)) {
                set_alert('success', _l('added_successfully', _l('predix_template_categories')));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_create'));
            }

            redirect(admin_url('predix/template_categories'));

        } elseif ($this->input->post() && $template_category !== '') {
            $response = $this->predix_model->updateTemplateCategory($template_category, $this->input->post());

            if ($response == true) {
                set_alert('success', _l('updated_successfully', _l('predix_template_categories')));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_update'));
            }

            redirect(admin_url('predix/template_categories'));
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_template_categories');
        if ($template_category !== '') {
            $data['category_data'] = $this->predix_model->getTemplateCategory($template_category);
        }

        $this->load->view('templates/categories/create', $data);
    }

    public function delete_template_category($template_category_id)
    {
        if (!has_permission('predix', '', 'delete_template_categories')) {
            access_denied('predix');
        }

        if (!$template_category_id) {
            redirect(admin_url('predix/template_categories'));
        }

        $response = $this->predix_model->deleteTemplateCategory($template_category_id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('predix_template_categories')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('predix_template_categories')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('predix_template_categories')));
        }

        redirect(admin_url('predix/template_categories'));
    }

    public function update_template_category_status($id, $status)
    {
        if ($this->input->is_ajax_request()) {
            $this->predix_model->changeTemplateCategoryStatus($id, $status);
        }
    }

    public function templates()
    {
        if (!has_permission('predix', '', 'view_template_categories')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_template_categories');
        $data['template_categories'] = $this->predix_model->getTemplateCategories();
        $data['templates'] = $this->predix_model->getTemplates();

        $this->load->view('templates/templates/manage', $data);
    }

    public function create_template($template_id = '')
    {
        if (!has_permission('predix', '', 'create_templates')) {
            access_denied('predix');
        }

        if ($this->input->post() && $template_id === '') {

            $postData = $this->input->post();

            $customInputs = [];
            if (
                !empty(array_filter($postData['custom_input_name'])) ||
                !empty(array_filter($postData['custom_input_label'])) ||
                !empty(array_filter($postData['custom_input_field_type']))
            ) {

                if (is_array($postData['custom_input_name'])) {

                    foreach ($postData['custom_input_name'] as $key => $custom_input_name) {

                        $customInputs[] = [
                            'input_name' => $custom_input_name,
                            'input_label' => $postData['custom_input_label'][$key],
                            'input_field_type' => $postData['custom_input_field_type'][$key]
                        ];
                    }
                }
            }

            unset(
                $postData['custom_input_name'],
                $postData['custom_input_label'],
                $postData['custom_input_field_type']
            );
            $postData['custom_inputs'] = json_encode($customInputs);

            $response = $this->predix_model->addTemplate($postData + ['created_at' => date('Y-m-d H:i:s')]);

            if (is_numeric($response)) {
                set_alert('success', _l('added_successfully', _l('predix_templates')));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_create'));
            }

            redirect(admin_url('predix/create_template/' . $response));

        } elseif ($this->input->post() && $template_id !== '') {

            $postData = $this->input->post();

            $customInputs = [];
            if (
                !empty(array_filter($postData['custom_input_name'])) ||
                !empty(array_filter($postData['custom_input_label'])) ||
                !empty(array_filter($postData['custom_input_field_type']))
            ) {

                if (is_array($postData['custom_input_name'])) {

                    foreach ($postData['custom_input_name'] as $key => $custom_input_name) {

                        $customInputs[] = [
                            'input_name' => $custom_input_name,
                            'input_label' => $postData['custom_input_label'][$key],
                            'input_field_type' => $postData['custom_input_field_type'][$key]
                        ];
                    }
                }
            }

            unset(
                $postData['custom_input_name'],
                $postData['custom_input_label'],
                $postData['custom_input_field_type']
            );
            $postData['custom_inputs'] = json_encode($customInputs);

            $response = $this->predix_model->updateTemplate($template_id, $postData);

            if ($response == true) {
                set_alert('success', _l('updated_successfully', _l('predix_templates')));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_update'));
            }

            redirect(admin_url('predix/create_template/' . $template_id));
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_templates');
        if ($template_id !== '') {
            $data['template_data'] = $this->predix_model->getTemplate($template_id);
        }
        $data['template_categories'] = $this->predix_model->getTemplateCategories();

        $this->load->view('templates/templates/create', $data);
    }

    public function delete_template($template_id)
    {
        if (!has_permission('predix', '', 'delete_templates')) {
            access_denied('predix');
        }

        if (!$template_id) {
            redirect(admin_url('predix/templates'));
        }

        $response = $this->predix_model->deleteTemplate($template_id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('predix_templates')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('predix_templates')));
        }

        redirect(admin_url('predix/templates'));
    }

    public function use_template($template_id)
    {
        if (!has_permission('predix', '', 'view_templates')) {
            access_denied('predix');
        }

        $data['title'] = _l('predix') . ' - ' . _l('predix_templates');
        $data['template_data'] = $this->predix_model->getTemplate($template_id);

        $this->load->view('templates/templates/generate', $data);
    }

    public function handle_template_call($template_id)
    {

        $templateData = $this->predix_model->getTemplate($template_id);
        $templateCustomInputs = json_decode($templateData->custom_inputs);

        $templateCustomPrompt = $templateData->custom_prompt;

        if ($this->input->post()) {

            $postData = $this->input->post();

            foreach ($templateCustomInputs as $customInput) {
                $templateCustomPrompt = str_replace('{{' . $customInput->input_name . '}}', $postData[$customInput->input_name], $templateCustomPrompt);
            }

            $templateCustomPrompt .= '. this text should be written on a tone of voice '. $postData['tone_of_voice'] . ' and should be in the this language : '. $postData['language'];


            $openAi = new OpenAi(get_option('predix_openai_secret_key'));

            $result = $openAi->completion([
                'model' => get_option('predix_chat_model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $templateCustomPrompt
                    ]
                ],
                'temperature' => 1.0,
                'max_tokens' => (int)$postData['max_result_length'],
                'frequency_penalty' => 0,
                'presence_penalty' => 0
            ]);

            $result = json_decode($result);

            $text = '';
            foreach ($result->choices as $choice):
                $text .= $choice->message->content;
            endforeach;

            echo json_encode([
                'status' => '1',
                'message' => nl2br($text)
            ]);
            die;

        }

        echo json_encode([
            'status' => '0',
            'message' => 'Failed'
        ]);
        die;

    }

}
