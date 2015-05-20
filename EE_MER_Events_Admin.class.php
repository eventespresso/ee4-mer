<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * Class EE_MER_Events_Admin
 *
 * Adds MER related functionality to the EE Events Admin page
 *
 * @package 			Event Espresso
 * @subpackage 	core
 * @author 				Darren Ethier
 * @since                4.7
 *
 */

class EE_MER_Events_Admin {

	function __construct() {
		add_filter(
			'FHEE__Events_Admin_Page__get_events__where',
			array( $this, 'event_list_query_params' ),
			10, 2
		);
		add_filter(
			'FHEE__EE_Admin_Page___display_admin_list_table_page__before_list_table__template_arg',
			array( $this, 'before_events_list_table_content' ),
			10, 4
		);
	}



	/**
	 * Callback for FHEE__Events_Admin_Page__get_events__where filter on Event List table to maybe filter by Event when
	 * incoming request is for the events in a specific transaction.
	 *
	 * @param array $where existing where params
	 * @param array $req_data incoming request data
	 * @return array
	 */
	public static function event_list_query_params( $where, $req_data ) {
		if ( ! empty( $req_data[ 'EVT_IDs' ] ) && ! empty( $req_data[ 'TXN_ID' ] ) ) {
			$where[ 'EVT_ID' ] = array( 'IN', explode( ',', $req_data[ 'EVT_IDs' ] ) );
		}
		return $where;
	}



	/**
	 * Callback for FHEE__EE_Admin_Page___display_admin_list_table_page__before_list_table__template_arg for adding
	 * helpful title.
	 *
	 * @param string $content Current content
	 * @param string $page_slug Page slug for page
	 * @param array $req_data Incoming request data
	 * @param string $req_action 'action' value for page
	 *
	 * @return string   If correct page and conditions are met, the new string. Otherwise existing string.
	 */
	public static function before_events_list_table_content( $content, $page_slug, $req_data, $req_action ) {
		if ( $page_slug !== 'espresso_events' || $req_action !== 'default' || empty( $req_data[ 'TXN_ID' ] ) ) {
			return $content;
		}
		$transaction = EEM_Transaction::instance()->get_one_by_ID( $req_data[ 'TXN_ID' ] );
		if ( $transaction instanceof EE_Transaction ) {
			$query_args = array(
				'page'   => 'espresso_transactions',
				'action' => 'view_transaction',
				'TXN_ID' => $req_data[ 'TXN_ID' ]
			);
			EE_Registry::instance()->load_helper( 'URL' );
			$url = EEH_URL::add_query_args_and_nonce( $query_args, admin_url( 'admin.php' ) );
			$link_text = '<a href="' . $url . '">' . $transaction->ID() . '</a>';
			$content .= '<h2>' . sprintf( __( 'Events Registered for in Transaction # %s', 'event_espresso' ), $link_text ) . '</h2>';
		}
		return $content;
	}



}
// End of file EE_MER_Events_Admin.class.php
// Location: /EE_MER_Events_Admin.class.php