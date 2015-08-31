<?php

define('sugarEntry', TRUE);
class SugarRestApiCall
{
	var $token;
	var $username;
	var $password;
	var $url;

	function __construct( $url, $username, $password )
	{
		$this->username = $username;
		$this->password = $password;
		$this->url = $url;
		$this->token = $this->login();
	}

	function call( $url, $oauthtoken = '', $type = 'GET', $parameters = array(), $encodeData = true, $do_not_json_decode = false )
	{

		$type = strtoupper($type);

		$curl_request = curl_init($url);

		if ($type == 'POST')
		{
			curl_setopt($curl_request, CURLOPT_POST, 1);
		}
		elseif ($type == 'PUT')
		{
			curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
		}
		elseif ($type == 'DELETE')
		{
			curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
		}

		curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl_request, CURLOPT_HEADER, $do_not_json_decode);
		curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

		if ( !empty( $oauthtoken ) )
		{
			$token = array( "oauth-token: {$oauthtoken}" );
			curl_setopt( $curl_request, CURLOPT_HTTPHEADER, $token );
		}

		if ( !empty( $parameters ) )
		{
			if ( $encodeData )
			{
				$parameters = json_encode( $parameters );
			}

			curl_setopt( $curl_request, CURLOPT_POSTFIELDS, $parameters );
		}

		$result = curl_exec( $curl_request );

		if ( curl_error( $curl_request ) )
		{
			throw new Exception( "CURL Connection not Successfully Done." );
			exit;
		}

		if ( $do_not_json_decode )
		{
			$result = explode( "\r\n\r\n", $result, 2 );
			$response = $result[1];
			// $response = $result;
		}
		else
		{
			$response = json_decode( $result );
		}

		curl_close( $curl_request );

		if ( property_exists( $response, "error_message") && $response->error_message == 'The access token provided is invalid.' )
		{
			$this->token = $this->login();
			$response = $this->call( $url, $this->token, $type, $parameters, false );
		}
		try
		{
			if ( empty( $response ) )
			{
				throw new Exception( "Response not received from SugarCRM." );
			}
		}
		catch ( Exception $e )
		{
			echo $e->getMessage();
		}

		return $response;
	}

	/**
	 *
	 * For main admin login. This login and token is used to collect the registration fields, their options and handle user registration/creation.
	 *
	 * @login
	 *
	 */
	function login()
	{
		$url = $this->url;
		$url = $url . "/oauth2/token";

		$oauth2_token_parameters = array(
			"grant_type" => "password",
			"client_id" => "sugar",
			"client_secret" => "",
			"username" => $this->username,
			"password" => $this->password,
			"platform" => "base"
		);

		$oauth2_token_result = self::call($url, '', 'POST', $oauth2_token_parameters);

		if ( empty( $oauth2_token_result->access_token ) )
		{
			return false;
		}

		return $oauth2_token_result->access_token;
	}

	/**
	 *
	 * Used for Sugar Authentication. The Portal Login uses support_portal client_id to authenticate against portal_name and portal_password within the Contact module of Sugar
	 *
	 * @PortalLogin
	 *
	 */
	function SugarLogin( $username, $password )
	{
		unset( $_SESSION[ 'sugar_user_id' ] );
		unset( $_SESSION[ 'sugar_portal_name' ] );
		unset( $_SESSION[ 'sugar_account_id' ] );
		unset( $_SESSION[ 'sugar_user_account_name' ] );
		unset( $_SESSION[ 'sugar_primary_email' ] );

		$url = $this->url;
		$url = $url . "/oauth2/token";

		$oauth2_parameters = array(
			"grant_type" => "password",
			"client_id" => "support_portal",
			"client_secret" => "",
			"username" => $username,
			"password" => $password,
			"platform" => "portal"
		);

		$oauth2_result = self::call($url, '', 'POST', $oauth2_parameters);

		if ( empty( $oauth2_result->access_token ) )
		{
			return $oauth2_result;
		}

		//Retrieve and Set Current User
		$url = $this->url . "/me";

		$retrievedUser = self::call($url, $oauth2_result->access_token, 'GET');

		$current_sugar_user = $retrievedUser->current_user;

		$_SESSION[ 'sugar_user_id' ]           = $current_sugar_user->user_id;
		$_SESSION[ 'sugar_portal_name' ]       = $current_sugar_user->portal_name;
		$_SESSION[ 'sugar_account_id' ]        = $current_sugar_user->id;
		$_SESSION[ 'sugar_user_account_name' ] = $current_sugar_user->full_name;

		return $oauth2_result;
	}

	/**
	 *
	 * @set_entry
	 *
	 */
	function set_entry( $module_name, $set_entry_dataArray )
	{
		$url = $this->url;

		if ( isset( $set_entry_dataArray['id'] ) )
		{
			$isUpdate = true;
		}
		else
		{
			$isUpdate = false;
		}
		if ($this->token != '')
		{
			if ($isUpdate == true)
			{
				$url = $url . "/{$module_name}/{$set_entry_dataArray['id']}";
				unset($set_entry_dataArray['id']);
				$response = self::call($url, $this->token, 'PUT', $set_entry_dataArray);
				return $response->id;
			}
			else
			{
				$url = $url . "/{$module_name}";
				$response = self::call($url, $this->token, 'POST', $set_entry_dataArray);
				return $response->id;
			}
		}
	}

	/**
	 *
	 * Get User Information : Will be used for Profile
	 *
	 * @getUserInformation
	 *
	 */
	function getUserInformation( $contact_id )
	{
		$url = $this->url;
		$url = $url . "/oauth2/token";

		$oauth2_token_parameters = array(
			"grant_type" => "password",
			"client_id" => "sugar",
			"client_secret" => "",
			"username" => get_option( 'swa_username' ),
			"password" => get_option( 'swa_password' ),
			"platform" => "base"
		);

		$oauth2_token_result = self::call($url, '', 'POST', $oauth2_token_parameters);

		if ( empty( $oauth2_token_result->access_token ) )
		{
			return false;
		}

		if ( $oauth2_token_result->access_token != '' )
		{
			$url = $this->url."/Contacts/".$contact_id;
			$user_response = self::call($url, $oauth2_token_result->access_token, 'GET');

			return $user_response;
		}
	}

	/**
	 *
	 * Check whether use exists or not.
	 *
	 * @getUserExists
	 *
	 */
	function getUserExists($username)
	{
		if( $this->token != '' )
		{
			$filter_arguments = array(
				"filter" => array(
					array(
						"portal_name" => array( '$equals' => $username,
						),
					),
				),
				"offset" => 0,
				"fields" => "id,portal_name",
			);

			$url = $this->url."/Contacts/filter";

			$response = self::call($url, $this->token, 'POST', $filter_arguments);

			$isUser = $response->records[0]->portal_name;

			if($isUser == $username)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 *
	 * Get user information by username. Will be used for Profile
	 *
	 * @getUserInformationByUsername
	 *
	 */
	function getUserInformationByUsername($username)
	{
		$response = '';

		if( $this->token != '' )
		{
			$filter_arguments = array(
				"filter" => array(
					array(
						"portal_name" => array(
							'$equals' => $username,
						),
					),
				),
				"offset" => 0,
				"fields" => "id,portal_name,portal_password,email,treat_hematology_patients_c,specialty_c",
			);
			$url = $this->url . "/Contacts/filter";
			$response = self::call($url, $this->token, 'GET', $filter_arguments);
		}

		$isUser = $response->records[0]->portal_name;

		if( $isUser == $username )
		{
			return $response;
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 * Get contact all email address : Can be used by main login to gather all portal_name (email addresses)
	 *
	 * @getContactAllEmail
	 *
	 */
	// Get contact all email address
	public function getContactAllEmail()
	{
		$emails = 'nothing';

		if( $this->token != '' )
		{
			$url = $this->url . "/Contacts/filter";
			$filter_arguments = array(
				"offset" => 0,
				"fields" => "id,portal_name",
			);

			$response = self::call($url, $this->token, 'GET',$filter_arguments);

			$email_records = $response->records;

			foreach($email_records as $record)
			{
				$emails[] = $record->portal_name;
			}
		}

		return $emails;
	}

	/**
	 *
	 * Register New User
	 *
	 * @swaRegisterNewUser
	 *
	 */
	public function swaRegisterNewUser($registrationFields)
	{
		// GET AN ADMINISTRATOR ACCESS TOKEN TO MAKE THINGS EASIER
		$url = $this->url;
		$url = $url . "/oauth2/token";

		$oauth2_token_parameters = array(
			"grant_type" => "password",
			"client_id" => "sugar",
			"client_secret" => "",
			"username" => $this->username,
			"password" => $this->password,
			"platform" => "base"
		);

		$oauth2_token_result = self::call($url, '', 'POST', $oauth2_token_parameters);

		if ( empty( $oauth2_token_result->access_token ) )
		{
			// If access token is empty, we have bigger problems. Check Sugar Settings in WP Admin and verify they are correct.
			return $registration_result = new WP_Error( 'denied', __("ERROR: Internal Authentication Error - Please notify the webmaster.") );
		}

		$url = $this->url . '/Contacts/aptitude_portal/register_new_user';

		$registration_result = self::call( $url, $oauth2_token_result->access_token, 'POST', $registrationFields );

		$registration_result_json = json_encode( $registration_result );

		echo $registration_result_json;

		die;

	}

	/**
	 *
	 * Forget Password / Password Request / Reset
	 *
	 * @swaForgotPassword
	 *
	 */
	public function swaForgotPassword($username)
	{
		// GET AN ADMINISTRATOR ACCESS TOKEN TO MAKE THINGS EASIER
		$url = $this->url;
		$url = $url . "/oauth2/token";

		$oauth2_token_parameters = array(
			"grant_type" => "password",
			"client_id" => "sugar",
			"client_secret" => "",
			"username" => $this->username,
			"password" => $this->password,
			"platform" => "base"
		);

		$oauth2_token_result = self::call($url, '', 'POST', $oauth2_token_parameters);

		if ( empty( $oauth2_token_result->access_token ) )
		{
			// If access token is empty, we have bigger problems. Check Sugar Settings in WP Admin and verify they are correct.
			return $registration_result = new WP_Error( 'denied', __("ERROR: Internal Authentication Error - Please notify the webmaster.") );
		}

		// Send request to reset password.
		$password_parameters = array(
			'username' => $username
		);

		$url = $this->url . '/Contacts/aptitude_portal/forgot_password';

		$request_result = self::call( $url, $oauth2_token_result->access_token, 'POST', $password_parameters );

		$request_result_json = json_encode( $request_result );

		echo $request_result_json;

		exit;

	}
}
