<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
// define the plugin directory path and URL
define( 'EE_MER_BASENAME', plugin_basename( EE_MER_PLUGIN_FILE ));
define( 'EE_MER_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_MER_URL', plugin_dir_url( __FILE__ ));
define( 'EE_MER_ADMIN', EE_MER_PATH . 'admin' . DS . 'multi_event_registration' . DS );
define( 'EE_MER_CORE', EE_MER_PATH . 'core' . DS);

/**
 * Class EE_Multi_Event_Registration
 *
 * @package 			Event Espresso
 * @subpackage 	core
 * @author 				Brent Christensen
 * 
 *
 */

class EE_Multi_Event_Registration extends EE_Addon {



	/**
	 * register_addon
	 */
	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Multi_Event_Registration',
			array(
				'version' 					=> EE_MER_VERSION,
				'min_core_version' => EE_MER_CORE_VERSION_REQUIRED,
				'main_file_path' 		=> EE_MER_PLUGIN_FILE,
				'plugin_slug' 			=> 'espresso_multi_event_registration',
				'config_class' 			=> 'EE_Multi_Event_Registration_Config',
				'config_name'			=> 'multi_event_registration',
				'module_paths' 		=> array(
					EE_MER_PATH . 'EED_Multi_Event_Registration.module.php',
				),
				'widget_paths' 		=> array( EE_MER_PATH . 'EEW_Mini_Cart.widget.php' ),
				// register autoloaders
				'autoloader_paths' => array(
					'EE_Multi_Event_Registration_Config' 						=> EE_MER_PATH . 'EE_Multi_Event_Registration_Config.php',
					'EE_Event_Cart_Line_Item_Display_Strategy' 			=> EE_MER_PATH . 'EE_Event_Cart_Line_Item_Display_Strategy.php',
					'EE_Mini_Cart_Table_Line_Item_Display_Strategy' 	=> EE_MER_PATH . 'EE_Mini_Cart_Table_Line_Item_Display_Strategy.php',
					'EE_MER_Transactions_Admin' 									=> EE_MER_PATH . 'EE_MER_Transactions_Admin.class.php',
					'EE_MER_Events_Admin' 											=> EE_MER_PATH . 'EE_MER_Events_Admin.class.php',
				),
				'pue_options'			=> array(
					'pue_plugin_slug' 		=> 'eea-multi-event-registration',
					'checkPeriod' 				=> '24',
					'use_wp_update' 		=> FALSE
				),
			)
		);
	}



}



// End of file EE_Multi_Event_Registration.php
// Location: /EE_Multi_Event_Registration.php