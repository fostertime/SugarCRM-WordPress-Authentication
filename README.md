# SugarCRM WordPress Authentication
SugarCRM User Authentication Plugin for WordPress.

## The plugin does the following:
* Overrides WordPress user authentication and uses SugarCRM Portal user information for authentication.
* Reset/Forgot Password
* Register New Portal User

## How it works
When the plugin is activated, it catches all login attempts via WordPress and uses the SugarCRM Portal user information for authentication.
If the user does not exist within WordPress it will create a new WordPress user with the SugarCRM credentials. 

## Files
* sugar-auth.php = the main plugin file.
* /include/extras.php = extra functions/hooks you can use/modify for redirecting users, or tapping into logouts/forgot password requests.
* /include/sugar-action.php = WordPress Action hooks for various plugin operation.
* /include/sugar-class.php = main class file used for connection to SugarCRM API. This class file is for use with SugarCRM v7.x

## Work In Progress
* This plugin is a work in progress, and should not be used in a production environment. Clone, or Fork and modify for your needs.