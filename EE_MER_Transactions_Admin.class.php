<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * Class EE_MER_Transactions_Admin
 *
 * Adds MER related functionality to the EE Transactions Admin page
 *
 * @package 			Event Espresso
 * @subpackage 	core
 * @author 				Darren Ethier
 * @since 				4.7
 *
 */

class EE_MER_Transactions_Admin {

	function __construct() {
		//transaction list table event links and the corresponding filter of queries on event page.
		$transactions_list_table_screen_id = sanitize_title( __( 'Event Espresso', 'event_espresso' ) ) . '_page_espresso_transactions';
		add_filter(
			'FHEE_manage_' . $transactions_list_table_screen_id . '_columns',
			array( $this, 'transaction_list_table_events_column' ),
			10, 1
		);
		add_action(
			'AHEE__EE_Admin_List_Table__column_events__' . $transactions_list_table_screen_id,
			array( $this, 'transaction_list_table_events_column_content' ),
			10, 1
		);
	}



	/**
	 * Callback for transaction list table column action for new events column.
	 *
	 * @param EE_Transaction $transaction
	 * @return string
	 */
	public function transaction_list_table_events_column_content( $transaction ) {
		if ( ! $transaction instanceof EE_Transaction ) {
			return;
		}
		//get event ids
		$registrations = $transaction->registrations();
		$event_IDs = array();
		foreach ( $registrations as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				if ( $registration->event_ID() && ! in_array( $registration->event_ID(), $event_IDs ) ) {
					$event_IDs[ ] = $registration->event_ID();
				}
			}
		}
		if ( ! empty( $event_IDs ) ) {
			$count = count( $event_IDs );
			$event_IDs = implode( ',', $event_IDs );
			$url = add_query_arg( array(
				'EVT_IDs' => $event_IDs,
				'TXN_ID'  => $transaction->ID(),
				'page'    => 'espresso_events',
				'action'  => 'default'
			), admin_url( 'admin.php' ) );
			echo '<a href="' . $url . '">' . sprintf( _n( '1 Event', '%d Events', $count, 'event_espresso' ), $count ) . '</a>';
		}
	}



	/**
	 * Callback for transaction list table manage columns action to remove existing event name column
	 * Registration and add new events column registration in place of.
	 *
	 * @param array $columns This is an array of columns setup for the list table.
	 * @return array
	 */
	public function transaction_list_table_events_column( $columns ) {
		//looping through columns and setting up new array to preserve location of new events column.
		$new_columns = array();
		foreach ( $columns as $column_name => $label ) {
			if ( $column_name == 'event_name' ) {
				$new_columns[ 'events' ] = __( 'Events', 'event_espresso' );
			} else {
				$new_columns[ $column_name ] = $label;
			}
		}
		return $new_columns;
	}



}



// End of file EE_MER_Transactions_Admin.class.php
// Location: /EE_MER_Transactions_Admin.class.php