<?php

defined('BASEPATH') or exit('No direct script access allowed');

class poly_utilities_banners_helper
{
    public static function banners()
    {
        $data = get_option(POLY_BANNERS_SETTINGS);
        $data = !empty($data) ? json_decode($data, true) : [];
        $data['active'] = $data['active'] ?? 1;
        $data['active_announcements'] = $data['active_announcements'] ?? 1;
        $data['effects'] = json_encode(poly_utilities_common_helper::$transition_effects);/* Transition Effects*/
        return $data;
    }

    /**
     * Groups media banners by display areas (widgets area) based on their active status and date range.
     * 
     * @param array $banners  The list of banners (media) to be processed. Each banner should contain fields like 'active', 'date_from', 'date_to', and 'area'.
     * @param string $mode    The option key (default is POLY_BANNERS_AREA) where the grouped banners by area will be stored after processing.
     * 
     * The function performs the following steps:
     * 1. Sorts the banners by their 'created' field.
     * 2. Filters banners by their 'active' status and validity based on the current date and any date range (date_from, date_to).
     * 3. Groups the banners by their respective display areas (specified in the 'area' field, which is an array).
     * 4. Updates the option in the system with the grouped banners, encoded in JSON format.
     * 
     * Note: Only banners that are active and within the valid date range are included in the grouped result.
     */
    public static function media_by_areas($banners, $mode = POLY_BANNERS_AREA)
    {
        if (empty($banners)) {
            return;
        }

        $bannersByArea = [];
        $currentDate = date('Y-m-d');

        poly_utilities_common_helper::sortByFieldName($banners, 'created');

        foreach ($banners as $bannerItem) {

            if ($bannerItem['active'] != 1) continue;

            $date_from = isset($bannerItem['date_from']) ? $bannerItem['date_from'] : null;
            $date_to = isset($bannerItem['date_to']) ? $bannerItem['date_to'] : null;

            if (!empty($date_from) && !empty($date_to)) {
                if ($currentDate < $date_from || $currentDate > $date_to) {
                    continue;
                }
            } elseif (!empty($date_from) && empty($date_to)) {
                if ($currentDate < $date_from) {
                    continue;
                }
            } elseif (empty($date_from) && !empty($date_to)) {
                if ($currentDate > $date_to) {
                    continue;
                }
            }

            if (isset($bannerItem['area']) && is_array($bannerItem['area'])) {
                foreach ($bannerItem['area'] as $area) {
                    if (!isset($bannersByArea[$area])) {
                        $bannersByArea[$area] = [];
                    }
                    if (!array_key_exists($bannerItem['id'], array_column($bannersByArea[$area], 'id', 'id'))) {
                        $bannersByArea[$area][] = $bannerItem;
                    }
                }
            }
        }
        update_option($mode, json_encode($bannersByArea));
    }
}
