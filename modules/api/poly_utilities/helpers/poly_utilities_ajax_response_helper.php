<?php

defined('BASEPATH') or exit('No direct script access allowed');

class poly_utilities_ajax_response_helper
{
    public static function response_data($data)
    {
        $csrf_token = get_instance()->security->get_csrf_hash();
        header('X-CSRF-Token: ' . $csrf_token);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    public static function response_success($message)
    {
        $csrf_token = get_instance()->security->get_csrf_hash();
        header('X-CSRF-Token: ' . $csrf_token);
        header('Content-Type: application/json');
        $response = [
            'status' => 'success',
            'code' => 200,
            'message' => $message
        ];

        echo json_encode($response);
        exit();
    }

    public static function response_error($message, $code = 400)
    {
        $response = [
            'status' => 'error',
            'code' => $code,
            'message' => $message
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    public static function response_invalid_json()
    {
        self::response_error('Invalid JSON data', 422);
    }

    public static function response_no_data_received()
    {
        self::response_error('No data received', 400);
    }

    public static function response_failed($message)
    {
        self::response_error('Failed to import settings: ' . $message, 500);
    }

    public static function response_unauthorize($message = 'Unauthorized')
    {
        $response = [
            'status' => 'error',
            'code' => 401,
            'message' => $message
        ];
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    public static function response_not_found($message)
    {
        $response = [
            'status' => 'error',
            'code' => 404,
            'message' => $message
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    public static function response_data_not_saved($message)
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 'error',
            'code' => 500,
            'message' => $message
        ];
        echo json_encode($response);
        exit();
    }

    public static function response_data_exists($message)
    {
        $response = [
            'status' => 'error',
            'code' => 409,
            'message' => $message
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
