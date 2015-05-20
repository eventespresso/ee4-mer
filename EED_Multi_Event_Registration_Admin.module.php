<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) { exit( 'No direct script access allowed' ); }
/**
 * Multi_Event_Registration_Admin class
 *
 * This contains all the code integration Multi Event Registration with the EE Admin.
 *
 * @package				Multi Event Registration
 * @subpackage			espresso-multi-registration
 * @author				Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class EED_Multi_Event_Registration_Admin extends EED_Module {

	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}


	public function run( $WP ) {}


	public static function set_hooks() {}


	/**
	 * All hooks for the admin
	 */
	public static function set_hooks_admin() {
		//transaction list table event links and the corresponding filter of queries on event page.
		$transactions_list_table_screen_id = sanitize_title( __('Event Espresso', 'event_espresso' ) ) . '_page_espresso_transactions';
		add_filter( 'FHEE_manage_' . $transactions_list_table_screen_id . '_columns', array( 'EED_Multi_Event_Registration_Admin', 'transaction_list_table_events_column' ), 10, 2 );
		add_action( 'AHEE__EE_Admin_List_Table__column_events__' . $transactions_list_table_screen_id, array( 'EED_Multi_Event_Registration_Admin', 'transaction_list_table_events_column_content'), 10, 2 );
		add_filter( 'FHEE__Events_Admin_Page__get_events__where', array( 'EED_Multi_Event_Registration_Admin', 'event_list_query_params' ), 10, 2 );
		add_filter( 'FHEE__EE_Admin_Page___display_admin_list_table_page__before_list_table__template_arg', array( 'EED_Multi_Event_Registration_Admin', 'before_events_list_table_content' ), 10, 4 );
	}


	/**
	 * Callback for FHEE__Events_Admin_Page__get_events__where filter on Event List table to maybe filter by Event when
	 * incoming request is for the events in a specific transaction.
	 *
	 * @param array $where     existing where params
	 * @param array $req_data  incoming request data
	 * @return array
	 */
	public static function event_list_query_params( $where, $req_data ) {
		if ( ! empty( $req_data['EVT_IDs'] ) && ! empty( $req_data['TXN_ID'] ) ) {
			$where['EVT_ID'] = array( 'IN', explode( ',', $req_data['EVT_IDs'] ) );
		}
		return $where;
	}


	/**
	 * Callback for FHEE__EE_Admin_Page___display_admin_list_table_page__before_list_table__template_arg for adding
	 * helpful title.
	 *
	 * @param string $content  Current content
	 * @param string $page_slug Page slug for page
	 * @param array $req_data  Incoming request data
	 * @param string $req_action 'action' value for page
	 *
	 * @return string   If correct page and conditions are met, the new string. Otherwise existing string.
	 */
	public static function before_events_list_table_content( $content, $page_slug, $req_data, $req_action ) {
		if ( $page_slug !== 'espresso_events' || $req_action !== 'default' || empty( $req_data['TXN_ID']) )
			return $content;

		$transaction = EEM_Transaction::instance()->get_one_by_ID( $req_data['TXN_ID'] );

		if ( $transaction instanceof EE_Transaction ) {
			$query_args = array(
				'page' => 'espresso_transactions',
				'action' => 'view_transaction',
				'TXN_ID' => $req_data['TXN_ID']
			);
			EE_Registry::instance()->load_helper( 'URL' );
			$url = EEH_URL::add_query_args_and_nonce( $query_args, admin_url( 'admin.php' ) );
			$link_text = '<a href="' . $url . '">' . $transaction->ID() . '</a>';
			$content .= '<h3>' . sprintf( __( 'Viewing Events that belong to Transaction: %s', 'event_espresso' ), $link_text ) . '</h3>';
		}

		return $content;
	}


	/**
	 * Callback for transaction list table column action for new events column.
	 *
	 * @param EE_Transaction $transaction
	 * @param WP_Screen $screen
	 * @return string
	 */
	public static function transaction_list_table_events_column_content( $transaction, $screen ) {
		if ( ! $transaction instanceof EE_Transaction ) {
			return;
		}

		//get event ids
		$registrations = $transaction->registrations();
		$event_IDs = array();
		foreach ( $registrations as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				if ( $registration->event_ID() && ! in_array( $registration->event_ID(), $event_IDs ) ) {
					$event_IDs[] = $registration->event_ID();
				}
			}
		}

		if ( ! empty( $event_IDs ) ) {
			$count = count( $event_IDs );
			$event_IDs = implode( ',', $event_IDs );
			$url = add_query_arg( array(
				'EVT_IDs' => $event_IDs,
				'TXN_ID' => $transaction->ID(),
				'page' => 'espresso_events',
				'action' => 'default'
			), admin_url( 'admin.php') );
			echo '<a href="' . $url . '">' . sprintf( _n( '1 Event', '%d Events', $count, 'event_espresso' ), $count ) . '</a>';
		}
	}


	/**
	 * Callback for transaction list table manage columns action to remove existing event name column
	 * Registration and add new events column registration in place of.
	 *
	 * @param array $columns This is an array of columns setup for the list table.
	 * @param WP_Screen $screen
	 * @return array
	 */
	public static function transaction_list_table_events_column( $columns, $screen ) {
		//looping through columns and setting up new array to preserve location of new events column.
		$new_columns = array();
		foreach( $columns as $column_name => $label ) {
			if ( $column_name == 'event_name' ) {
				$new_columns['events'] = __( 'Events', 'event_espresso' );
			} else {
				$new_columns[$column_name] = $label;
			}
		}
		return $new_columns;
	}



} //end EED_Multi_Event_Registration_Admin