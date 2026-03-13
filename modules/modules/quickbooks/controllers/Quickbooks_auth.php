<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Quickbooks_auth extends AdminController {

	/**
	 * QB OAuth Endpoint
	 * Credit to: https://github.com/jespinal
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * QB Oauth Endpoint start
	 *
	 * @throws Exception
	 */
	public function index() {
		// Note: this controller is likely to be accessed through a popup window,
		// so I tried to keep it simple. Once the authorization is completed, you'll
		// have the access token in storage, hence, you can redirect to another controller
		// where you want to do some magic :)

		$clientSecret = get_clientSecret();
		$clientId = get_clientID();
		$resource_owner_details = get_option('qb_test_mode_enabled') == '1' ? 'https://developer.api.intuit.com/.well-known/openid_sandbox_configuration' : 'https://developer.api.intuit.com/.well-known/openid_configuration';

		$loProvider = new \League\OAuth2\Client\Provider\GenericProvider([
			'clientId' => $clientId, // Your Intuit App's details provides you this info
			'clientSecret' => $clientSecret, // Same as above
			'redirectUri' => site_url('quickbooks/quickbooks_auth'),
			'urlAuthorize' => 'https://appcenter.intuit.com/connect/oauth2',
			'urlAccessToken' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
			'urlResourceOwnerDetails' => $resource_owner_details,
			'scopes' => 'com.intuit.quickbooks.accounting',
			'state' => app_generate_hash(),
			'response_type' => 'code',
		]);

		// If we don't have an authorization code then get one
		if (!$this->input->get('code', true)) {

			// Fetch the authorization URL from the provider; this returns the
			// urlAuthorize option and generates and applies any necessary parameters
			$lsAuthorizationUrl = $loProvider->getAuthorizationUrl();

			// Get the state generated for you and store it to the session.
			$this->session->set_userdata('oauth2state', $loProvider->getState());

			// Redirect the user to the authorization URL.
			header('Location: ' . $lsAuthorizationUrl);
			exit;
		} else {
			// Check given state against previously stored one to mitigate CSRF attack
			if (strcmp($this->session->userdata('oauth2state'), $this->input->get('state', true)) != 0) {
				log_message('error', "The state is not correct from Intuit Server. Consider your app is hacked.");
			}

			// Try to get an access token using the authorization code grant.
			try {
				$loAccessToken = $loProvider->getAccessToken('authorization_code', [
					'code' => $this->input->get('code', true),
				]);
			} catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $ex) {
				log_message('error', $ex->getMessage());
				exit;
			}

			// Storing access token and some other important data.

			update_option('qb_access_token', $loAccessToken->getToken());
			update_option('qb_refresh_token', $loAccessToken->getRefreshToken());
			update_option('qb_expiration_date', $loAccessToken->getExpires());
			update_option('qb_is_expired', $loAccessToken->hasExpired());
			update_option('qb_realmid', $this->input->get('realmId', true));
			update_option('qb_refresh_token_expires_in', time() + 100 * 24 * 60 * 60);

		}

		// Closing popup and refreshing parent window
		print '<script type="text/javascript">
    	window.opener.location.href = window.opener.location.href;
    	window.close();
    	</script>';
	}

	public function disconnect() {
		if ($this->input->is_ajax_request()) {
			$this->load->model('quickbooks_model');
			$result = $this->quickbooks_model->disconnect();
			if ($result) {
				update_option('qb_access_token', '');
				update_option('qb_refresh_token', '');
				update_option('qb_expiration_date', '');
				update_option('qb_is_expired', '');
				update_option('qb_realmid', '');
				update_option('qb_refresh_token_expires_in', '');

				echo json_encode(array(
					'success' => true,
					'message' => 'Disconnected from ' . _l('quickbooks') . ' successfully',
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => 'Diconnection failed with ' . _l('quickbooks'),
				));
			}
			die;
		}

		return true;
	}
}