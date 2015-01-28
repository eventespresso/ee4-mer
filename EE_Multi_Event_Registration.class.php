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
 * @since 				$VID:$
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
//				'admin_path' 			=> EE_MER_ADMIN,
//				'admin_callback'		=> 'additional_admin_hooks',
				'plugin_slug' 			=> 'espresso_multi_event_registration',
				'config_class' 			=> 'EE_Multi_Event_Registration_Config',
				'config_name'			=> 'multi_event_registration',
//				'dms_paths' 			=> array( EE_MER_CORE . 'data_migration_scripts' . DS ),
				'module_paths' 		=> array( EE_MER_PATH . 'EED_Multi_Event_Registration.module.php' ),
//				'shortcode_paths' 	=> array( EE_MER_PATH . 'EES_Espresso_MER.shortcode.php' ),
//				'widget_paths' 		=> array( EE_MER_PATH . 'EEW_Minicart.widget.php' ),
				// register autoloaders
				'autoloader_paths' => array(
					'EE_Multi_Event_Registration_Config' 					=> EE_MER_PATH . 'EE_Multi_Event_Registration_Config.php',
					'EE_Event_Queue_Line_Item_Display_Strategy' 	=> EE_MER_PATH . 'EE_Event_Queue_Line_Item_Display_Strategy.php',
//					'MER_Admin_Page_Init' 	=> EE_MER_ADMIN . 'MER_Admin_Page_Init.core.php',
//					'MER_Admin_Page' 			=> EE_MER_ADMIN . 'MER_Admin_Page.core.php',
//					'MER_Admin_List_Table' 	=> EE_MER_ADMIN . 'MER_Admin_List_Table.class.php',
//					'EE_Promotion_Scope' 					=> EE_MER_PATH . 'lib' . DS . 'scopes' . DS . 'EE_Promotion_Scope.lib.php'
				),
//				'autoloader_folders' => array(
//					'MER_Plugin_API' 	=> EE_MER_PATH . 'lib' . DS . 'plugin_api',
//					'Promotion_Scopes' 			=> EE_MER_PATH . 'lib' . DS . 'scopes'
//				),
				'pue_options'			=> array(
					'pue_plugin_slug' 		=> 'eea-multi-event-registration',
					'checkPeriod' 				=> '24',
					'use_wp_update' 		=> FALSE
				),
				// EE_Register_Model
//				'model_paths'	=> array( EE_MER_CORE . 'db_models' ),
//				'class_paths'	=> array( EE_MER_CORE . 'db_classes' ),
				// EE_Register_Model_Extensions
//				'model_extension_paths'	=> array( EE_MER_CORE . 'db_model_extensions' . DS ),
//				'class_extension_paths'		=> array( EE_MER_CORE . 'db_class_extensions'  . DS )
			)
		);
	}



}



// End of file EE_Multi_Event_Registration.php
// Location: /EE_Multi_Event_Registration.php