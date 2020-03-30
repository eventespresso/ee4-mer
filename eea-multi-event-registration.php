<?php
/*
Plugin Name: Event Espresso - MER - Multi Event Registration (EE 4.7.0+)
Plugin URI: http://www.eventespresso.com
Description: Multi Events Registration addon for Event Espresso. - Lots of events to register for? Now you can add events to a registration Event Cart ( shopping cart ), then register for them all at once.
Version: 2.0.18.p
Author: Event Espresso
Author URI: http://www.eventespresso.com
Copyright 2015 Event Espresso (email : support@eventespresso.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA

* ------------------------------------------------------------------------
*
* Event Espresso
*
* Event Registration and Management Plugin for WordPress
*
* @ package		Event Espresso
* @ author			Event Espresso
* @ copyright	(c) 2008-2015 Event Espresso  All Rights Reserved.
* @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
* @ link				http://www.eventespresso.com
* @ version	 	EE4
*
* ------------------------------------------------------------------------
*/

define( 'EE_MER_CORE_VERSION_REQUIRED', '4.9.23.rc.001' );
define( 'EE_MER_VERSION', '2.0.18.p' );
define( 'EE_MER_PLUGIN_FILE', __FILE__ );



/**
 * 	load_espresso_multi_event_registration
 */
function load_espresso_multi_event_registration() {
	if ( class_exists( 'EE_Addon' ) ) {
		// multi_event_registration_version
		require_once( plugin_dir_path( __FILE__ ) . 'EE_Multi_Event_Registration.class.php' );
		EE_Multi_Event_Registration::register_addon();
	} else {
		add_action( 'admin_notices', 'espresso_multi_event_registration_activation_error' );
	}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_multi_event_registration', 5 );



/**
 * 	espresso_multi_event_registration_activation_check
 */
function espresso_multi_event_registration_activation_check() {
	if ( ! did_action( 'AHEE__EE_System__load_espresso_addons' ) ) {
		add_action( 'admin_notices', 'espresso_multi_event_registration_activation_error' );
	}
}
add_action( 'init', 'espresso_multi_event_registration_activation_check', 1 );



/**
 * 	espresso_multi_event_registration_activation_error
 */
function espresso_multi_event_registration_activation_error() {
	unset( $_GET[ 'activate' ] );
	unset( $_REQUEST[ 'activate' ] );
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	deactivate_plugins( plugin_basename( EE_MER_PLUGIN_FILE ) );
	?>
	<div class="error">
		<p><?php printf( __( 'Event Espresso Multi Event Registration could not be activated. Please ensure that Event Espresso version %s or higher is running', 'event_espresso' ), EE_MER_CORE_VERSION_REQUIRED ); ?></p>
	</div>
<?php
}
// End of file eea-multi-event-registration.php
// Location: /eea-multi-event-registration.php
