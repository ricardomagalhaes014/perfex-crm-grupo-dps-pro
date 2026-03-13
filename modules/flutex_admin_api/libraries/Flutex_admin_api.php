<?php

defined('BASEPATH') || exit('No direct script access allowed');
require_once __DIR__.'/../vendor/autoload.php';
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class Flutex_admin_api
{

    public function send_push_notification($notification_id)
    {
        $CI = &get_instance();

        $notification = $CI->db->where('id', $notification_id)->get(db_prefix() . 'notifications')->row();
        if (!empty($notification)) {
            $staff = $CI->db->where('staffid', $notification->touserid)->get(db_prefix() . 'staff')->row();
            if (!empty($staff)) {
                $additional_data = '';
                if (!empty($notification->additional_data)) {
                    $additional_data = unserialize($notification->additional_data);

                    $i = 0;
                    foreach ($additional_data as $data) {
                        if (strpos($data, '<lang>') !== false) {
                            $lang = get_string_between($data, '<lang>', '</lang>');
                            $name = _l($lang);
                            if (strpos($name, 'project_status_') !== FALSE) {
                                $status = get_project_status_by_id(strafter($name, 'project_status_'));
                                $name = $status['name'];
                            }
                            $additional_data[$i] = $name;
                        }
                        $i++;
                    }
                }

                $description = _l($notification->description, $additional_data);;
                $icon = 'icon.png';
                if (($notification->fromcompany == NULL && $notification->fromuserid != 0) || ($notification->fromcompany == NULL && $notification->fromclientid != 0)) {
                    if ($notification->fromuserid != 0) {
                        $icon = staff_profile_image_url($notification->fromuserid);
                    } else {
                        $icon = contact_profile_image_url($notification->fromclientid);
                    }
                }

                $title = $notification->from_fullname;
                $token = $staff->fcm_token;
                $type = $this->flutex_admin_api_notification_type($notification->link);
                $type_id = $this->flutex_admin_api_notification_type_id($notification->link);


                try{
                    $this->send_notification($token,$title,$description,$icon,$type,$type_id);
                } catch (\Throwable $th) {

                }
            }
        }
    }

    private function send_notification($fcm_token="", $title = "title", $description = "message", $icon = "icon.png", $type = "type", $type_id = "1")
    {
        $fcm_service_file = json_decode(get_option('flutex_admin_fcm_service_file_content'),true);
        $project_id = $fcm_service_file['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/".$project_id."/messages:send";
        $access_token = $this->getAccessToken();

        $options = [
            'json' => [
                "message" => [
                    "token"=> $fcm_token,
                    "data" => [
                        "title" => $title,
                        "body" => $description,
                        "image" => $icon,
                        "type" => $type,
                        "type_id" => $type_id,
                    ],
                    "notification"=> [
                        "title" => $title,
                        "body"  => $description,
                    ],
                    "webpush"=> [
                        "fcm_options" => [
                          "link"=> "https://google.com"
                    ]
                    ]
               ]
           ],
           'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ];

        $client = new GuzzleHttp\Client();
        $response = $client->post($url, $options);
    }

    private function getAccessToken()
    {
        $fcm_service_file = get_option('flutex_admin_fcm_service_file_content');

        $credentials = new ServiceAccountCredentials(
            "https://www.googleapis.com/auth/firebase.messaging",
            json_decode($fcm_service_file, true)
        );

        $accessToken = $credentials->fetchAuthToken(HttpHandlerFactory::build());
        return $accessToken['access_token'];
    }

    private function flutex_admin_api_notification_type($link)
    {
        $type = '';

        switch ($link) {
            case (strpos($link, '#taskid=') !== false):
                $type = 'task';
                break;
            case (strpos($link, '#leadid=') !== false):
                $type = 'lead';
                break;
            case (strpos($link, 'invoices/') !== false):
                $type = 'invoice';
                break;
            case (strpos($link, 'proposals/') !== false):
                $type = 'proposal';
                break;
            case (strpos($link, 'projects/') !== false):
                $type = 'project';
                break;
        }

        return $type;
    }

    private function flutex_admin_api_notification_type_id($link)
    {
        $type_id = '';

        switch ($link) {
            case (strpos($link, '#taskid=') !== false):
                $type_id = 'task';
                break;
            case (strpos($link, '#leadid=') !== false):
                $type_id = 'lead';
                break;
            case (strpos($link, 'invoices/') !== false):
                $type_id = 'invoice';
                break;
            case (strpos($link, 'proposals/') !== false):
                $type_id = 'proposal';
                break;
            case (strpos($link, 'projects/') !== false):
                $type_id = 'project';
                break;
        }

        return $type_id;
    }

    public static function pre_activate($module_name)
     {
         update_option('flutex_admin_api_enabled', '1');
     }

     public static function activate($module_name, $purchase_code='', $username='')
     {
         update_option('flutex_admin_api_enabled', '1');

         return ['status' => true, 'message' => 'Module activated successfully'];
     }

     public static function verify($module_name)
     {
         return ['status' => true];
     }

     public static function module_checker()
     {
         if (!\function_exists('flutex_admin_api_init') || !\function_exists('flutex_admin_api_activation') || !\function_exists('flutex_admin_api_deregister')) {
             get_instance()->app_modules->deactivate('flutex_admin_api');
         }
     }
}
