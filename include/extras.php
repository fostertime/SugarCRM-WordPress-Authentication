<?php
/**
 * extras.php
 *
 * These functions add various additional features to the operation of the Plugin.
 *
 * You can either include this entire file, or simply cut and paste the features
 * you want into the main sugar-auth.php
 *
 */

/*
 * Disables admin toolbar for subscribers.
 */
function swa_disable_admin_bar() {
	if( ! current_user_can('edit_posts') )
		add_filter('show_admin_bar', '__return_false');
}
add_action( 'after_setup_theme', 'swa_disable_admin_bar' );

/*
 * Redirect users back to homepage and does not allow access to admin/profile for subscribers.
 */
function swa_redirect_admin() {

	if( !defined('DOING_AJAX') && !current_user_can('edit_posts') ) {
		wp_redirect( home_url() );
		exit();
	}
}
add_action( 'admin_init', 'swa_redirect_admin' );

/*
 * For front end login forms, redirect a failed login back to the front end form instead of the wp-admin login.
 */
function swa_front_end_login_fail( $user ) {
	$referrer = $_SERVER['HTTP_REFERER'];

	if ( is_wp_error( $user ) ) {
		if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
			if ( !strstr($referrer, '?login=failed' ) )
				wp_redirect( strtok($referrer, '?') . '?login=failed' );
			else
				wp_redirect( strtok($referrer, '?') );
		}
		exit;
	}
}
add_action( 'wp_login_failed', 'swa_front_end_login_fail' );

/*
 * Redirect the lost password url to a custom url
 */
function swa_lostpassword_url() {
	return site_url('/lost-password/'); // update this to your custom lost password page slug.
}
add_filter( 'lostpassword_url',  'swa_lostpassword_url', 10, 0 );