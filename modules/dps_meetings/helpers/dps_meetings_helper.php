<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('dps_meetings_build_url')) {
    function dps_meetings_build_url($provider, $roomName = null, $manualUrl = null)
    {
        if (!empty($manualUrl)) {
            return $manualUrl;
        }

        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '-', (string) $roomName);
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        if ($slug === '') {
            $slug = 'meeting-' . time();
        }

        switch ($provider) {
            case 'meet':
                return 'https://meet.google.com/new';
            case 'zoom':
                return 'https://zoom.us/start/videomeeting';
            case 'custom':
                return $manualUrl;
            case 'jitsi':
            default:
                return 'https://meet.jit.si/' . rawurlencode($slug);
        }
    }
}

if (!function_exists('dps_meetings_status_badge')) {
    function dps_meetings_status_badge($status)
    {
        $map = [
            'scheduled' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'no_show'   => 'warning',
        ];

        $class = isset($map[$status]) ? $map[$status] : 'default';

        return '<span class="label label-' . $class . '">' . html_escape(_l('dps_meeting_status_' . $status)) . '</span>';
    }
}
