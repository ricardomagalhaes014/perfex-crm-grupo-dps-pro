<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Modelo principal para o módulo DPS Calendar Sync.
 * Fornece funções para gerir tokens OAuth, eventos locais e comunicação com o Google Calendar.
 */
class Dps_calendar_sync_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table_tokens = db_prefix() . 'dps_calendar_tokens';
        $this->table_events = db_prefix() . 'dps_calendar_events';
    }

    /**
     * Obtém os tokens para um determinado utilizador.
     *
     * @param int $staff_id
     * @return object|null
     */
    public function get_tokens($staff_id)
    {
        return $this->db->where('staff_id', $staff_id)->get($this->table_tokens)->row();
    }

    /**
     * Guarda ou actualiza os tokens para o utilizador.
     *
     * @param int   $staff_id
     * @param array $data
     * @return bool
     */
    public function save_tokens($staff_id, $data)
    {
        if ($this->db->where('staff_id', $staff_id)->count_all_results($this->table_tokens) > 0) {
            $this->db->where('staff_id', $staff_id);
            return $this->db->update($this->table_tokens, $data);
        }
        $data['staff_id'] = $staff_id;
        return $this->db->insert($this->table_tokens, $data);
    }

    /**
     * Remove tokens de um utilizador (utilizado ao desligar a conta Google).
     *
     * @param int $staff_id
     * @return void
     */
    public function delete_tokens($staff_id)
    {
        $this->db->where('staff_id', $staff_id)->delete($this->table_tokens);
    }

    /**
     * Cria um evento localmente e opcionalmente no Google.
     *
     * @param int   $staff_id
     * @param array $data
     * @param bool  $pushGoogle
     * @return int|false  ID do evento local ou false
     */
    public function create_event($staff_id, $data, $pushGoogle = true)
    {
        $event = [
            'staff_id'   => $staff_id,
            'title'      => isset($data['title']) ? $data['title'] : '',
            'description'=> isset($data['description']) ? $data['description'] : '',
            'start_time' => isset($data['start_time']) ? $data['start_time'] : null,
            'end_time'   => isset($data['end_time']) ? $data['end_time'] : null,
            'location'   => isset($data['location']) ? $data['location'] : '',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table_events, $event);
        $id = $this->db->insert_id();
        if ($pushGoogle) {
            $googleId = $this->add_google_event($staff_id, $event);
            if ($googleId) {
                $this->db->where('id', $id)->update($this->table_events, ['google_event_id' => $googleId]);
            }
        }
        return $id;
    }

    /**
     * Actualiza um evento local e no Google se necessário.
     *
     * @param int $id
     * @param int $staff_id
     * @param array $data
     * @return bool
     */
    public function update_event($id, $staff_id, $data)
    {
        $event = $this->db->where('id', $id)->where('staff_id', $staff_id)->get($this->table_events)->row();
        if (!$event) {
            return false;
        }
        $update = [];
        if (isset($data['title'])) {
            $update['title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $update['description'] = $data['description'];
        }
        if (isset($data['start_time'])) {
            $update['start_time'] = $data['start_time'];
        }
        if (isset($data['end_time'])) {
            $update['end_time'] = $data['end_time'];
        }
        if (isset($data['location'])) {
            $update['location'] = $data['location'];
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table_events, $update);

        // Actualizar no Google se existir google_event_id
        if ($event->google_event_id) {
            $this->update_google_event($staff_id, $event->google_event_id, array_merge((array)$event, $update));
        }
        return true;
    }

    /**
     * Remove um evento local e no Google.
     *
     * @param int $id
     * @param int $staff_id
     * @return bool
     */
    public function delete_event($id, $staff_id)
    {
        $event = $this->db->where('id', $id)->where('staff_id', $staff_id)->get($this->table_events)->row();
        if (!$event) {
            return false;
        }
        if ($event->google_event_id) {
            $this->delete_google_event($staff_id, $event->google_event_id);
        }
        $this->db->where('id', $id)->delete($this->table_events);
        return true;
    }

    /**
     * Obtém todos os eventos de um utilizador.
     *
     * @param int $staff_id
     * @return array
     */
    public function get_events($staff_id)
    {
        return $this->db->where('staff_id', $staff_id)->order_by('start_time', 'DESC')->get($this->table_events)->result();
    }

    /**
     * Guarda a escolha de calendário do utilizador.
     *
     * @param int    $staff_id
     * @param string $calendar_id
     * @param string $calendar_name
     * @return void
     */
    public function save_calendar_choice($staff_id, $calendar_id, $calendar_name)
    {
        $this->save_tokens($staff_id, [
            'calendar_id'   => $calendar_id,
            'calendar_name' => $calendar_name,
        ]);
    }

    /**
     * Obtém as credenciais do cliente Google.
     *
     * @return array
     */
    public function get_client_config()
    {
        return [
            'client_id'     => get_option('dps_calendar_sync_client_id'),
            'client_secret' => get_option('dps_calendar_sync_client_secret'),
            'redirect_uri'  => get_option('dps_calendar_sync_redirect_uri'),
        ];
    }

    /**
     * Refresca o access token usando o refresh token guardado.
     * Actualiza a base de dados com o novo access token.
     *
     * @param int $staff_id
     * @return string|null Novo access token ou null em caso de falha
     */
    public function refresh_token($staff_id)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens || empty($tokens->refresh_token)) {
            return null;
        }
        $config = $this->get_client_config();
        $params = [
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $tokens->refresh_token,
            'grant_type'    => 'refresh_token',
        ];
        $response = $this->curl_post('https://oauth2.googleapis.com/token', $params);
        if (!isset($response['access_token'])) {
            return null;
        }
        $expires = isset($response['expires_in']) ? time() + $response['expires_in'] : null;
        $update = [
            'access_token' => $response['access_token'],
            'token_type'   => isset($response['token_type']) ? $response['token_type'] : null,
            'expires_at'   => $expires ? date('Y-m-d H:i:s', $expires) : null,
        ];
        $this->save_tokens($staff_id, $update);
        return $response['access_token'];
    }

    /**
     * Regista um novo evento no Google Calendar.
     *
     * @param int   $staff_id
     * @param array $event
     * @return string|null Google event id
     */
    public function add_google_event($staff_id, $event)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens) {
            return null;
        }
        // Se o access token expirou, refrescar
        if ($tokens->expires_at && strtotime($tokens->expires_at) < time()) {
            $this->refresh_token($staff_id);
            $tokens = $this->get_tokens($staff_id);
        }
        if (!$tokens || empty($tokens->access_token)) {
            return null;
        }
        // Construir payload do evento
        $data = [
            'summary'     => $event['title'],
            'description' => $event['description'],
            'start'       => [
                'dateTime' => date('c', strtotime($event['start_time'])),
                'timeZone' => date_default_timezone_get(),
            ],
            'end'         => [
                'dateTime' => date('c', strtotime($event['end_time'])),
                'timeZone' => date_default_timezone_get(),
            ],
        ];
        if (!empty($event['location'])) {
            $data['location'] = $event['location'];
        }
        $calendar_id = !empty($tokens->calendar_id) ? $tokens->calendar_id : 'primary';
        $response = $this->curl_post_json('https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendar_id) . '/events', $data, $tokens->access_token);
        if (isset($response['id'])) {
            return $response['id'];
        }
        return null;
    }

    /**
     * Actualiza um evento existente no Google Calendar.
     *
     * @param int    $staff_id
     * @param string $googleId
     * @param array  $event
     * @return bool
     */
    public function update_google_event($staff_id, $googleId, $event)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens) {
            return false;
        }
        if ($tokens->expires_at && strtotime($tokens->expires_at) < time()) {
            $this->refresh_token($staff_id);
            $tokens = $this->get_tokens($staff_id);
        }
        $data = [
            'summary'     => $event['title'],
            'description' => $event['description'],
            'start'       => [
                'dateTime' => date('c', strtotime($event['start_time'])),
                'timeZone' => date_default_timezone_get(),
            ],
            'end'         => [
                'dateTime' => date('c', strtotime($event['end_time'])),
                'timeZone' => date_default_timezone_get(),
            ],
        ];
        if (!empty($event['location'])) {
            $data['location'] = $event['location'];
        }
        $calendar_id = !empty($tokens->calendar_id) ? $tokens->calendar_id : 'primary';
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendar_id) . '/events/' . urlencode($googleId);
        $response = $this->curl_put_json($url, $data, $tokens->access_token);
        return isset($response['id']);
    }

    /**
     * Remove um evento do Google Calendar.
     *
     * @param int    $staff_id
     * @param string $googleId
     * @return bool
     */
    public function delete_google_event($staff_id, $googleId)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens) {
            return false;
        }
        if ($tokens->expires_at && strtotime($tokens->expires_at) < time()) {
            $this->refresh_token($staff_id);
            $tokens = $this->get_tokens($staff_id);
        }
        $calendar_id = !empty($tokens->calendar_id) ? $tokens->calendar_id : 'primary';
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendar_id) . '/events/' . urlencode($googleId);
        $response = $this->curl_delete($url, $tokens->access_token);
        // Google API returns empty response on success
        return is_array($response) ? true : true;
    }

    /**
     * Obter lista de calendários disponíveis do utilizador no Google.
     *
     * @param int $staff_id
     * @return array|null
     */
    public function get_calendars_list($staff_id)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens) {
            return null;
        }
        if ($tokens->expires_at && strtotime($tokens->expires_at) < time()) {
            $this->refresh_token($staff_id);
            $tokens = $this->get_tokens($staff_id);
        }
        $url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
        $response = $this->curl_get($url, $tokens->access_token);
        if (!isset($response['items'])) {
            return null;
        }
        $calendars = [];
        foreach ($response['items'] as $item) {
            $calendars[] = [
                'id'      => $item['id'],
                'summary' => isset($item['summary']) ? $item['summary'] : $item['id'],
                'primary' => isset($item['primary']) ? $item['primary'] : false,
            ];
        }
        return $calendars;
    }

    /**
     * Obter eventos do Google Calendar e gravá-los localmente (sincronização unidireccional).
     *
     * @param int $staff_id
     * @return array|null
     */
    public function fetch_google_events($staff_id)
    {
        $tokens = $this->get_tokens($staff_id);
        if (!$tokens) {
            return null;
        }
        if ($tokens->expires_at && strtotime($tokens->expires_at) < time()) {
            $this->refresh_token($staff_id);
            $tokens = $this->get_tokens($staff_id);
        }
        $calendar_id = !empty($tokens->calendar_id) ? $tokens->calendar_id : 'primary';
        $params = http_build_query([
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
            'timeMin'      => date('c', strtotime('-1 month')),
            'timeMax'      => date('c', strtotime('+6 months')),
        ]);
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendar_id) . '/events?' . $params;
        $response = $this->curl_get($url, $tokens->access_token);
        if (!isset($response['items'])) {
            return null;
        }
        $events = [];
        foreach ($response['items'] as $item) {
            $events[] = $item;
        }
        return $events;
    }

    /* ------------------------------------------------------------------ */
    /*  Métodos auxiliares para chamadas HTTP                             */
    /* ------------------------------------------------------------------ */

    public function curl_post($url, $params)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function curl_post_json($url, $data, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function curl_put_json($url, $data, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function curl_delete($url, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function curl_get($url, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }
}
