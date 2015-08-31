<?php
/**
 *
 * SWA Action Hooks
 *
 * @swa_user_registration_action : User Registration Action (AJAX)
 *
 * @swa_change_password_action : Change Password Action (AJAX)
 *
 * @swa_forgot_password_action : Forgot Password Action (AJAX)
 *
 */

add_action( 'wp_ajax_swa_user_registration_action', 'swa_user_registration_action' );
add_action( 'wp_ajax_nopriv_swa_user_registration_action', 'swa_user_registration_action' );
function swa_user_registration_action()
{
	parse_str($_REQUEST['data'], $regFields);

	$salutation = ( isset( $regFields['salutation'] ) ? $regFields['salutation'] : '');
	$firstName = ( isset( $regFields['firstname'] ) ? $regFields['firstname'] : '');
	$lastName = ( isset( $regFields['lastname'] ) ? $regFields['lastname'] : '');
	$suffix = ( isset( $regFields['suffix'] ) ? $regFields['suffix'] : '');
	$gender = ( isset( $regFields['gender'] ) ? $regFields['gender'] : '');
	$workPhone = ( isset( $regFields['workPhone'] ) ? $regFields['workPhone'] : '');
	$mobile = ( isset( $regFields['mobile'] ) ? $regFields['mobile'] : '');
	$fax = ( isset( $regFields['fax'] ) ? $regFields['fax'] : '');
	$email = ( isset( $regFields['email'] ) ? $regFields['email'] : '');
	$companyName = ( isset( $regFields['companyName'] ) ? $regFields['companyName'] : '');
	$password = ( isset( $regFields['pass_confirmation'] ) ? $regFields['pass_confirmation'] : '');
	$confirmPassword = ( isset( $regFields['pass'] ) ? $regFields['pass'] : '');
	$line1 = ( isset( $regFields['line1'] ) ? $regFields['line1'] : '');
	$line2 = ( isset( $regFields['line2'] ) ? $regFields['line2'] : '');
	$line3 = ( isset( $regFields['line3'] ) ? $regFields['line3'] : '');
	$city = ( isset( $regFields['city'] ) ? $regFields['city'] : '');
	$state = ( isset( $regFields['state'] ) ? $regFields['state'] : '');
	$postalCode = ( isset( $regFields['postalCode'] ) ? $regFields['postalCode'] : '');
	$country = ( isset( $regFields['country'] ) ? $regFields['country'] : '');

	// Get the registration form post values.
	$registrationFields = array(
		'token' => '',
		'salutation' => $salutation,
		'firstName' => $firstName,
		'lastName' => $lastName,
		'suffix' => $suffix,
		'gender' => $gender,
		'workPhone' => $workPhone,
		'mobile' => $mobile,
		'fax' => $fax,
		'email' => $email,
		'companyName' => $companyName,
		'password' => $password,
		'confirmPassword' => $confirmPassword,
		'line1' => $line1,
		'line2' => $line2,
		'line3' => $line3,
		'city' => $city,
		'state' => $state,
		'postalCode' => $postalCode,
		'country' => $country
	);

	// check nonce
	$nonce = $_REQUEST['swaNonce'];
	if ( ! wp_verify_nonce( $nonce, 'swa-nonce' ) )
		die ( 'Busted!');
	// nonce good, lets keep going.

	// get a new access token and then submit the request to reset password
	$swa_rest_url = get_option('swa_rest_url');
	$swa_username = get_option('swa_username');
	$swa_password = get_option('swa_password');

	$objSWA = new SugarRestApiCall($swa_rest_url, $swa_username, $swa_password);

	$registeredNewUser = $objSWA->swaRegisterNewUser($registrationFields);

	// generate the response
	$response = json_encode( $registeredNewUser );

	// response output
	header( "Content-Type: application/json" );
	echo $response;

	// IMPORTANT: don't forget to "exit"
	die;
}

add_action( 'wp_ajax_swa_change_password_action', 'swa_change_password_action' ); // Change Password
add_action( 'wp_ajax_nopriv_swa_change_password_action', 'swa_change_password_action' ); // Change Password
function swa_change_password_action()
{
	$swa_rest_url = get_option('swa_rest_url');
	$swa_username = get_option('swa_username');
	$swa_password = get_option('swa_password');

	$objSWA = new SugarRestApiCall($swa_rest_url, $swa_username, $swa_password);

	$getContactInfo = $objSWA->getUserInformation( $_SESSION['swa_user_id'] );

	$password = $getContactInfo->portal_password;

	if( $password == stripslashes_deep( $_REQUEST['add-profile-old-password'] ) )
	{
		if( stripslashes_deep( $_REQUEST['add-profile-new-password']) == stripslashes_deep($_REQUEST['add-profile-confirm-password'] ) ) {

			$new_password = stripslashes_deep($_REQUEST['add-profile-new-password']);
			$updateUserInfo = array(
				'id' => $_SESSION['swa_user_id'],
				'portal_password' => $new_password
			);

			$isChangePassword = $objSWA->set_entry('Contacts',$updateUserInfo);

			if($isChangePassword != NULL)
			{
				$redirect_url = $_REQUEST['sugar_current_url'].'&success=true';
				wp_redirect( $redirect_url );
			}
		}
		else
		{
			$redirect_url = $_REQUEST['sugar_current_url'].'&error=1';
			wp_redirect( $redirect_url );
		}
	}
	else
	{
		$redirect_url = $_REQUEST['sugar_current_url'].'&error=2';
		wp_redirect( $redirect_url );
	}
}

add_action( 'wp_ajax_swa_forgot_password_action', 'swa_forgot_password_action' );   // Change Password
add_action( 'wp_ajax_nopriv_swa_forgot_password_action', 'swa_forgot_password_action' );   // Change Password
function swa_forgot_password_action()
{
	$username = $_POST['user_name'];

	// check nonce
	$nonce = $_POST['swaNonce'];
	if ( ! wp_verify_nonce( $nonce, 'swa-nonce' ) )
		die ( 'Busted!');

	// nonce good, lets keep going.
	// get a new access token and then submit the request to reset password
	$swa_rest_url = get_option('swa_rest_url');
	$swa_username = get_option('swa_username');
	$swa_password = get_option('swa_password');

	$objSWA = new SugarRestApiCall($swa_rest_url, $swa_username, $swa_password);

	$registerUser = $objSWA->swaForgotPassword($username);

	// generate the response
	$response = json_encode( $registerUser );

	// response output
	header( "Content-Type: application/json" );
	echo $response;

	// IMPORTANT: don't forget to "exit"
	exit;
}