<?php defined('BASEPATH') or exit('No direct script access allowed');

require('modules/appointly/vendor/autoload.php');

class Googleplus
{
    /**
     * @var Google_Client
     */
    public $client;

    /**
     * Googleplus constructor.
     */
    public function __construct()
    {
        $this->initGoogleClient();
        $this->thenVerifySSL();
    }

    /**
     * Retorna a instância do cliente Google
     *
     * @return Google_Client
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Retorna a URL de login do Google
     *
     * @return string
     */
    public function loginUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Autentica o utilizador
     *
     * @return mixed
     */
    public function getAuthenticate()
    {
        return $this->client->authenticate();
    }

    /**
     * Retorna o token de acesso
     *
     * @return array
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    /**
     * Define o token de acesso
     *
     * @param string|array $token
     * @return void
     */
    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    /**
     * Atualiza o token
     *
     * @param string $token
     * @return void
     */
    public function refreshToken($token)
    {
        $this->client->refreshToken($token);
    }

    /**
     * Verifica se o token expirou
     *
     * @return bool
     */
    public function isAccessTokenExpired()
    {
        return $this->client->isAccessTokenExpired();
    }

    /**
     * Revoga o token de acesso atual
     *
     * @return bool
     */
    public function revokeToken()
    {
        $ci = &get_instance();
        $token = $this->getTokenType('access_token');

        $this->client->setAccessToken($token);

        $ci->db->where('staff_id', get_staff_user_id());
        $ci->db->delete(db_prefix() . 'appointly_google');

        return $this->client->revokeToken();
    }

    /**
     * Obtém o tipo de token (access_token ou refresh_token)
     *
     * @param string $type
     * @return string|null
     */
    public function getTokenType($type)
    {
        $ci = &get_instance();
        $ci->db->select($type);
        $ci->db->where('staff_id', get_staff_user_id());
        $result = $ci->db->get(db_prefix() . 'appointly_google')->row_array();
        return $result[$type] ?? null;
    }

    /**
     * Desativa verificação SSL para o cliente HTTP (útil em ambientes de desenvolvimento)
     *
     * @return void
     */
    private function thenVerifySSL()
    {
        $httpClient = new GuzzleHttp\Client([
            'verify' => false,
        ]);

        $this->client->setHttpClient($httpClient);
    }

    /**
     * Inicializa o cliente Google
     *
     * @return void
     */
    private function initGoogleClient()
    {
        $this->client = new Google_Client();
        $this->client->setAccessType("offline");
        $this->client->setApprovalPrompt("force");
        $this->client->setApplicationName('Appointly Google Calendar API');
        $this->client->setClientId(get_option('google_client_id'));
        $this->client->setClientSecret(get_option('appointly_google_client_secret'));
        $this->client->setRedirectUri(base_url() . 'appointly/google/auth/oauth');
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
    }
}