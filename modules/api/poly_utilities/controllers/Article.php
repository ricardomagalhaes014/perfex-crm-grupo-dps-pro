<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Article extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper("security");
    }

    public function details($slug)
    {
        if (!$slug) {
            show_404();
        }

        $menu_items = poly_utilities_custom_menu_items(POLY_MENU_CLIENTS_CUSTOM_ACTIVE);

        $object = $menu_items[$slug];

        if (isset($object['require_login']) && $object['require_login'] == "on" && !is_client_logged_in()) { // !(Require login)
            show_404();
        }

        if (!empty($object['clients']) && $object['clients'] != '[]') {
            $current_client_id = get_client_user_id();
            $clients = poly_utilities_common_helper::json_decode($object['clients'], true);
            $client_can_access = poly_utilities_common_helper::get_item_by($clients, 'id', $current_client_id);

            if (!$client_can_access)
                show_404();
        }

        $data['custom_menu'] = $object;
        $data['title'] = $object['name'];
        $this->data($data);
        $this->view('custom_menu/details_clients');
        $this->layout();
    }
}
