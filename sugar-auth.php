<?php
/**
 *
 * Plugin Name: SugarCRM WordPress Authentication
 * Description: SugarCRM User Authentication Plugin for WordPress.
 * Version: 1.0
 * Author: Chris Foster
 * Author URI: http://www.tencitiesmedia.com
 * License: The MIT License (MIT)
 *
 * Copyright (c) 2015 Chris Foster
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 */

// Include our Sugar class - for Sugar version 7.x
include( plugin_dir_path( __FILE__ ) . 'include/sugar-class.php' );

// Include out Sugar WordPress Actions
include( plugin_dir_path( __FILE__ ) . 'include/sugar-action.php' );

// Enqueue and Localize Scripts
function swa_enqueue_script() {
	// enqueue a plugin level script file.
	wp_enqueue_script( 'swa-ajax', plugin_dir_url( __FILE__ ) . 'js/sugar-auth.js', array(''), '1.0.0', true );

	// Localize WordPress ajax for front end usage.
	wp_localize_script( 'swa-ajax', 'swaajax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'swaNonce' => wp_create_nonce( 'swa-nonce' )
		)
	);
}
add_action( 'wp_enqueue_scripts', 'swa_enqueue_script' );

// Add a Settings Page to the WordPress Dashboard
function swa_sugar_portal_settings_page() {
	// create admin side menu
	add_menu_page( 'Sugar Settings', 'Sugar Settings', 'administrator', 'swa_settings', 'swa_settings_page' );

	// call register settings function
	add_action( 'admin_init', 'swa_register_settings' );
}
add_action( 'admin_menu', 'swa_sugar_portal_settings_page' );

// Register Settings Fields for Settings Page
function swa_register_settings()
{
	// register our settings
	register_setting( 'swa-settings', 'swa_name' );
	register_setting( 'swa-settings', 'swa_rest_url' );
	register_setting( 'swa-settings', 'swa_username' );
	register_setting( 'swa-settings', 'swa_password' );
}

// Add Settings Page
function swa_settings_page()
{
	// Dashboard Settings Page
	?>
	<div class="wrap">
		<h2>SugarCRM Authentication Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'swa-settings' ); ?>
			<?php do_settings_sections( 'swa-settings' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Portal Name</th>
					<td><input type="text" class="regular-text" value="<?php echo get_option( 'swa_name' ); ?>" name="swa_name" /></td>
				</tr>

				<tr valign="top">
					<th scope="row">REST URL</th>
					<td><input type="text" class="regular-text" value="<?php echo get_option( 'swa_rest_url' ); ?>" name="swa_rest_url" /></td>
				</tr>

				<tr valign="top">
					<th scope="row">Username</th>
					<td><input type="text" value="<?php echo get_option( 'swa_username' ); ?>" name="swa_username" /></td>
				</tr>

				<tr valign="top">
					<th scope="row">Password</th>
					<td><input type="password" value="<?php echo get_option( 'swa_password' ); ?>" name="swa_password" /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>

	<?php
	$swa_rest_url = get_option( 'swa_rest_url' );
	$swa_username = get_option( 'swa_username' );
	$swa_password = get_option( 'swa_password' );

	if ( class_exists( 'SugarRestApiCall' ) ) :

		$objSWA = new SugarRestApiCall( $swa_rest_url, $swa_username, $swa_password );

		if ( $objSWA->login() != null ) : ?>

			<div class="updated settings-error" id="setting-error-settings_updated">
				<p><strong>Connection to REST API Successful!</strong></p>
			</div>

		<?php else : ?>

			<div class="error settings-error" id="setting-error-settings_updated">
				<p><strong>Connection to REST API Failed. Check SugarCRM Version, REST URL, Username and Password.</strong></p>
			</div>
		<?php endif;

	else : ?>

		<div class="error settings-error" id="setting-error-settings_updated">
			<p><strong>PHP Error: The 'SugarRestApiCall' Class was not reachable. Verify that you are including 'sugar-class-7.php'</strong></p>
		</div>

	<?php endif;

}

/**
 * Catch WordPress Login and Authenticate With Sugar Credentials
 */
function swa_login( $user, $username, $password ) {

	// Set the rest url variable
	$swa_rest_url = get_option( 'swa_rest_url' );


	// Check that a username and password were entered.
	if ( $username == '' || $password == '' ) return;

	/*
	 * Lets not even worry about sugar authentication if its a true WordPress user. (ex. admin)
	 * If the username entered is not an email address, check if username exists within
	 * WordPress. If it does, then just return that user object and skip the Sugar Authentication.
	 *
	 * You can completely remove this if statement if you do not want to allow WordPress users to login.
	 *
	 */
	if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) === false ) {

		if ( username_exists( $username ) ) {
			$wpUserObj  = new WP_User();
			$userID     = username_exists( $username );
			$user       = $wpUserObj->get_data_by( 'id', $userID );

			return $user;
		}
	}

	// grab the username and password from the login attempt
	$login_username = $username;
	$login_password = $password;

	$objSWA = new SugarRestApiCall( $swa_rest_url, $login_username, $login_password );

	$isLogin = $objSWA->SugarLogin( $login_username, $login_password );

	if ( isset( $isLogin->error_message ) ) {
		$user = new WP_Error( 'denied', __( $isLogin->error_message ) );
		do_action('wp_login_failed', $user);
		exit;
	} else if ( ( $isLogin->access_token != null ) && ( $login_username != null ) && ( $login_password != null ) ) {
		// External user exists, try to load the user info from the WordPress user table
		$userObj = new WP_User();

		if ( email_exists( $login_username ) ) {
			$user = $userObj->get_data_by( 'email', $login_username );
		} else {
			// If you get here, it means the email does not exist in the WordPress user table.

			// You can prevent the creation of new WordPress users by uncommenting the following line.
			// $user = new WP_Error( 'denied', __("ERROR: This user does not have access.") );

			// If we continue and create a new WordPress user, we will use the sugar user email and
			// password for the WordPress user.

			// Setup the minimum required user information for this example
			$userData = array( 'user_email' => $login_username,
			                   'user_login' => $login_username,
			                   'user_pass' => $login_password
			);

			$new_user_id = wp_insert_user( $userData ); // A new user has been created

			// Now lets load the created user object
			$user = new WP_User ($new_user_id);

		}

		// Get and set the primary email session variable.
		$getPrimaryEmail = $objSWA->getUserInformation( $_SESSION[ 'swa_account_id' ] );

		// Set the session
		$_SESSION[ 'swa_primary_email' ] = $getPrimaryEmail->email1;

	} else {
		// User does not exist within Sugar, send back an error message
		$user = new WP_Error( 'denied', __("ERROR: Invalid login credentials. Please register.") );
		do_action('wp_login_failed', $user);
		exit;
	}

	// Uncomment this line if you do not want to fall back on WordPress authentication
	// remove_action('authenticate', 'wp_authenticate_username_password', 20);

	return $user;

}
add_filter( 'authenticate', 'swa_login', 10, 3 );

/**
 * Start a session so we can store some session variables to use throughout the plugin.
 */
function swa_start_session()
{
	if ( ! session_id() )
	{
		session_start();
	}
}
add_action( 'init', 'swa_start_session', 1 );

/**
 * Clear Session Variables on Logout
 */
function swa_logout()
{
	unset( $_SESSION[ 'swa_user_id' ] );
	unset( $_SESSION[ 'swa_portal_name' ] );
	unset( $_SESSION[ 'swa_account_id' ] );
	unset( $_SESSION[ 'swa_user_account_name' ] );
	unset( $_SESSION[ 'swa_primary_email' ] );
}
add_action('wp_logout', 'swa_logout');

/**
 * Uninstall the Plugin and remove the registered fields
 */
function swa_uninstall()
{
	delete_option( 'swa_name' );
	delete_option( 'swa_rest_url' );
	delete_option( 'swa_username' );
	delete_option( 'swa_password' );
}
register_uninstall_hook( __FILE__, 'swa_uninstall' );