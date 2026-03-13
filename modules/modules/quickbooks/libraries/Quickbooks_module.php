<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once module_dir_path('quickbooks', 'vendor/quickbooks/v3-php-sdk/src/config.php');

use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class Quickbooks_moduleException extends Exception {
}

class Quickbooks_module {

	/**
	 *
	 * @var CI_Controller
	 */
	private $CI;

	/**
	 * @var DataService
	 */
	private $dataService;

	private $ch = null; // Curl handler
	protected $ci;

	private $settings = array(
		'scheme' => 'http',
		'port' => 80,
		'timeout' => 30,
		'debug' => false,
		'curl_options' => array(),
	);

	public function __construct() {

		$this->CI = &get_instance();

		if (!$this->CI->session instanceof CI_Session) {
			log_message("error", "Attempting to load Quickbooks library in absence of CI_Session.");
		}

		$this->setDataService();
	}

	/**
	 * Set Data Service
	 */
	private function setDataService() {

		$lbReturn = false;

		try {
			$this->dataService = DataService::Configure(array(
				'auth_mode' => 'oauth2',
				'ClientID' => get_clientID(),
				'ClientSecret' => get_clientSecret(),
				'accessTokenKey' => get_option('qb_access_token'),
				'refreshTokenKey' => get_option('qb_refresh_token'),
				'QBORealmID' => get_option('qb_realmid'),
				'baseUrl' => get_option('qb_test_mode_enabled') == '1' ? 'development' : 'production',
			));

			$lbReturn = true;

		} catch (Exception $ex) {
			log_message('error', "There was a problem while initializing DataService.\n" . $ex->getMessage());
		}

		return $lbReturn;
	}

	/**
	 * Get Data Service
	 *
	 * @return QuickBooksOnline\API\DataService\DataService
	 * @throws Exception
	 */
	public function getDataService() {
		if (!$this->dataService instanceof DataService) {
			log_message("error", "The DataService object of the Quickbooks SDK is not ready.");
		} else {
			return $this->dataService;
		}
	}

	/**
	 * Get access token from QB
	 * @param  boolean $manual
	 * @return bool
	 */
	public function get_access_token($manual = false) {
		if ((get_option('qb_active') == '1' && time() > (get_option('qb_expiration_date') - 10 * 60)) || $manual == true) {
			try {
				$clientSecret = get_clientSecret();
				$clientId = get_clientID();
				$theRefreshTokenValue = get_option('qb_refresh_token');
				$oauth2LoginHelper = new OAuth2LoginHelper($clientId, $clientSecret);
				$accessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken($theRefreshTokenValue);
				update_option('qb_access_token', $accessTokenObj->getAccessToken());
				update_option('qb_refresh_token', $accessTokenObj->getRefreshToken());
				update_option('qb_expiration_date', time() + 60 * 60);
				update_option('qb_is_expired', false);

				return true;

			} catch (Exception $ex) {
				log_message('error', $ex->getMessage());
			}

		}
		return false;
	}

	/*-------------------------------------------------
		// QUICKBOOKS CURL
	*/

	/**
	 * Log a string.
	 *
	 * @param string $msg The message to log
	 */
	private function log($msg) {
		if (!is_null($msg)) {
			log_message('debug', 'MpesaHandler: ' . $msg);
		}
	}

	/**
	 * Utility function used to create the curl object with common settings.
	 */
	private function create_curl($domain, $s_url, $request_method = 'GET', $query_params = null) {
		$full_url = $domain . $s_url . (is_null($query_params) ? $query_params : '?' . $query_params);
		$this->log('create_curl( ' . $full_url . ' )');
		// Create or reuse existing curl handle
		if (null === $this->ch) {
			$this->ch = curl_init();
		}
		if ($this->ch === false) {
			throw new Quickbooks_moduleException('Could not initialise cURL!');
		}
		$ch = $this->ch;
		// curl handle is not reusable unless reset
		if (function_exists('curl_reset')) {
			curl_reset($ch);
		}
		// Set cURL opts and execute request
		curl_setopt($ch, CURLOPT_URL, $full_url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . get_option('qb_access_token'),
			'Accept: application/json',
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
		if ($request_method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
		} elseif ($request_method === 'GET') {
			curl_setopt($ch, CURLOPT_POST, 0);
		} // Otherwise let the user configure it
		// Set custom curl options
		if (!empty($this->settings['curl_options'])) {
			foreach ($this->settings['curl_options'] as $option => $value) {
				curl_setopt($ch, $option, $value);
			}
		}
		return $ch;
	}
	/**
	 * Utility function to execute curl and create capture response information.
	 */
	private function exec_curl($ch) {
		$response = array();
		$response['body'] = curl_exec($ch);
		$response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->log('exec_curl response: ' . print_r($response, true));
		if ($response['body'] === false) {
			$this->log('exec_curl error: ' . curl_error($ch));
		}
		return $response;
	}

	public function trigger($s_url, $query_params = null, $post_params = array(), $debug = false) {
		$domain = get_option('qb_test_mode_enabled') == '1' ? 'https://sandbox-quickbooks.api.intuit.com' : 'https://quickbooks.api.intuit.com';
		$ch = $this->create_curl($domain, $s_url, 'POST', $query_params);
		$this->log('trigger POST: ' . json_encode($post_params));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_params));
		$response = $this->exec_curl($ch);
		if ($response['status'] === 200 && $debug === false) {
			return true;
		} elseif ($debug === true || $this->settings['debug'] === true) {
			return $response;
		} else {
			return false;
		}
		//curl_close($ch);
	}

}