<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Media extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('security');
    }

    public function index()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }

        $data = get_option('poly_utilities_banners_area');
        $data = $data ? $data : '[]';

        echo json_encode([
            'status' => 'success',
            'data' => json_decode($data)
        ]);
        exit;
    }

    public function announcements()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }

        $data = get_option('poly_utilities_banners_announcements_area');
        $data = $data ? $data : '[]';

        echo json_encode([
            'status' => 'success',
            'data' => json_decode($data)
        ]);
        exit;
    }
}
