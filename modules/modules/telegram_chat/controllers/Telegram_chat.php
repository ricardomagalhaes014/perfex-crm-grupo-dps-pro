<?php

defined('BASEPATH') or exit('No direct script access allowed');
set_time_limit(0);
class Telegram_chat extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('telegram_model');
    }

    public function index()
    {
        $currentUserID = $GLOBALS['current_user']->staffid;
        $userTelegramInfo = $this->telegram_model->get($currentUserID);
        $data['title'] = 'Telegram';
        $data['userTeleInfo'] = $userTelegramInfo;
        $this->load->view('telegram_chat/settings', $data);
    }

    function addTelegramInfo() {
                $currentUserID = $GLOBALS['current_user']->staffid;
                $obj = array(
                    'chat_id' =>    $this->input->post('chat_id'), 
                    'bot_token'=>   $this->input->post('bot_token'),
                    'user_id' =>    $GLOBALS['current_user']->staffid,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
            );
            $userInfo = $this->telegram_model->get($currentUserID);
            if(isset($userInfo) && $userInfo->id) {
            $this->telegram_model->update($obj, $userInfo->id);
            }else {
                $this->telegram_model->add($obj);
            }
            set_alert('success', _l('telegram_settings_added', _l('telegram')));
            redirect(admin_url('telegram_chat'));
    }

    
}