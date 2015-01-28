<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * Multi_Event_Registration class
 *
 * @package				Multi Event Registration
 * @subpackage			espresso-multi-registration
 * @author					Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Multi_Event_Registration extends EED_Module {

	// array to hold parameters for the registration button
	private $_reg_btn = array();

	private $_templates = array();

	private $_ajax = 0;



	/**
	 * @return EED_Multi_Event_Registration
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	/**
	 *    set_hooks - for hooking into EE Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks() {
		EED_Multi_Event_Registration::set_definitions();
		EE_Config::register_route( 'view', 'Multi_Event_Registration', 'view_event_queue', 'event_queue' );
		EE_Config::register_route( 'add_ticket', 'Multi_Event_Registration', 'add_ticket', 'event_queue' );
		EE_Config::register_route( 'remove_ticket', 'Multi_Event_Registration', 'remove_ticket', 'event_queue' );
		EE_Config::register_route( 'delete_ticket', 'Multi_Event_Registration', 'delete_ticket', 'event_queue' );
		EE_Config::register_route( 'empty', 'Multi_Event_Registration', 'empty_event_queue', 'event_queue' );
		// don't empty cart
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false' );
		// process registration links
		add_filter( 'FHEE__EE_Ticket_Selector__display_ticket_selector_submit__btn_text', array( 'EED_Multi_Event_Registration', 'filter_ticket_selector_submit_button' ), 10, 2 );
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__success_redirect_url', array( 'EED_Multi_Event_Registration', 'filter_ticket_selector_redirect_url' ), 10, 2 );
	}



	/**
	 *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks_admin() {
		EED_Multi_Event_Registration::set_definitions();
		if ( isset( $_REQUEST[ 'event_queue_ajax' ] ) && absint( $_REQUEST[ 'event_queue_ajax' ] ) ) {
		}
		// don't empty cart
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false' );
		// ajax add attendees
		add_action( 'wp_ajax_espresso_add_attendee_to_event_queue', array( 'EED_Multi_Event_Registration', 'eq_add_attendee' ) );
		add_action( 'wp_ajax_nopriv_espresso_add_attendee_to_event_queue', array( 'EED_Multi_Event_Registration', 'eq_add_attendee' ) );
		// ajax remove attendees
		add_action( 'wp_ajax_espresso_remove_attendee_from_event_queue', array( 'EED_Multi_Event_Registration', 'eq_remove_attendee' ) );
		add_action( 'wp_ajax_nopriv_espresso_remove_attendee_from_event_queue', array( 'EED_Multi_Event_Registration', 'eq_remove_attendee' ) );
		// ajax remove event
		add_action( 'wp_ajax_espresso_remove_event_from_event_queue', array( 'EED_Multi_Event_Registration', 'eq_remove_event' ) );
		add_action( 'wp_ajax_nopriv_espresso_remove_event_from_event_queue', array( 'EED_Multi_Event_Registration', 'eq_remove_event' ) );
		// ajax remove event
		add_action( 'wp_ajax_espresso_empty_event_queue', array( 'EED_Multi_Event_Registration', 'eq_empty_event_queue' ) );
		add_action( 'wp_ajax_nopriv_espresso_empty_event_queue', array( 'EED_Multi_Event_Registration', 'eq_empty_event_queue' ) );
		// ajax available_spaces
		add_action( 'wp_ajax_espresso_get_available_spaces', array( 'EED_Multi_Event_Registration', 'get_available_spaces_via_ajax' ) );
		add_action( 'wp_ajax_nopriv_espresso_get_available_spaces', array( 'EED_Multi_Event_Registration', 'get_available_spaces_via_ajax' ) );
	}



	/**
		 *set_definitions
	 *
	 * @return void
	 */
	public static function set_definitions() {
		// base url for the site's registration page - additional url params will be added to this
		define( 'EE_EVENT_QUEUE_BASE_URL', EE_Registry::instance()->CFG->core->reg_page_url() );
		define( 'EE_EVENTS_LIST_URL', get_post_type_archive_link( 'espresso_events' ) );
	}



	/**
	 *    set_config
	 * this configures this module to use the same config as the EE_Promotions class
	 *
	 * @return EE_Promotions_Config
	 */
	public function set_config() {
		$this->set_config_section( 'addons' );
		$this->set_config_class( 'EE_Multi_Event_Registration_Config' );
		$this->set_config_name( 'multi_event_registration' );
		return $this->config();
	}



	/**
	 *        protected constructor to prevent direct creation
	 *
	 * @access protected
	 * @return EED_Multi_Event_Registration
	 */
	protected function init() {
		// stop SPCO from executing
		add_filter( 'FHEE__EED_Single_Page_Checkout__run', '__return_false' );
		define( 'MER_ACTIVE', TRUE );
		// set MER active to TRUE
		add_filter( 'filter_hook_espresso_MER_active', '__return_true' );
		$this->set_templates();
	}



	/**
	 *        load resources required to run MER
	 *
	 * @access        public
	 * @return        void
	 */
	public static function load_classes() {
		EE_Registry::instance()->load_core( 'Cart' );
		EE_Registry::instance()->load_helper( 'Line_Item' );
	}



	/**
	 *        set templates
	 *
	 * @access        public
	 * @return        void
	 */
	public function set_templates() {
		$this->_templates = array(
			'event_queue' => plugin_dir_path( __FILE__ ) . 'templates/event_queue.template.php'
		);
	}



	/**
	 *        translate_js_strings
	 *
	 * @access        public
	 * @return        void
	 */
	public static function translate_js_strings() {
//		EE_Registry::$i18n_js_strings[ 'no_promotions_code' ] = __( 'Please enter a valid Promotion Code.', 'event_espresso' );
	}



	/**
	 *    enqueue_scripts - Load the scripts and css
	 *
	 * @access    public
	 * @return    void
	 */
	public static function enqueue_scripts() {
		//Check to see if the multi_event_registration css file exists in the '/uploads/espresso/' directory
		if ( is_readable( EVENT_ESPRESSO_UPLOAD_DIR . 'css' . DS . 'multi_event_registration.css' ) ) {
			//This is the url to the css file if available
			wp_register_style( 'espresso_multi_event_registration', EVENT_ESPRESSO_UPLOAD_URL . 'css' . DS . 'multi_event_registration.css' );
		} else {
			// EE multi_event_registration style
			wp_register_style( 'espresso_multi_event_registration', EE_MER_URL . 'css' . DS . 'multi_event_registration.css' );
		}
		// multi_event_registration script
		wp_register_script( 'espresso_multi_event_registration', EE_MER_URL . 'scripts' . DS . 'multi_event_registration.js', array( 'jquery' ), EE_MER_VERSION, TRUE );
		// load JS
		wp_enqueue_style( 'espresso_multi_event_registration' );
		wp_enqueue_script( 'espresso_multi_event_registration' );
	}


	// *******************************************************************************************************
	// ********************************************   ROUTING   *********************************************
	// *******************************************************************************************************
	/**
	 *    run - initial module setup
	 *
	 * @access    public
	 * @param  WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
		EED_Multi_Event_Registration::instance()->set_config();
	}




	// *******************************************************************************************************
	// *******************************************   EVENT LISTING   *******************************************
	// *******************************************************************************************************




	/**
	 * filter_ticket_selector_submit_button
	 * changes the default "Register Now" text based on event's inclusion in the cart
	 *
	 * @access 	public
	 * @param 	string    $btn_text
	 * @param 	EE_Event $event
	 * @return 	string
	 */
	public static function filter_ticket_selector_submit_button( $btn_text = '', EE_Event $event ) {
		// verify event
		if ( ! $event instanceof EE_Event ) {
			if ( WP_DEBUG ) {
				EE_Error::add_error( __( 'An invalid event object was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
			return $btn_text;
		}
		EED_Multi_Event_Registration::load_classes();
		$cart_grand_total = EE_Registry::instance()->CART->get_grand_total();
		$event_line_item = EEH_Line_Item::get_line_items_by_object_type_and_IDs( $cart_grand_total, 'Event', array( $event->ID() ));
		if ( $event_line_item instanceof EE_Line_Item ) {
			$btn_text = __( 'View Event Queue', 'event_espresso' );
		} else {
			$btn_text = __( 'Add to Event Queue', 'event_espresso' );
		}
		return $btn_text;
	}



	/**
	 *    creates button for going to the Event Queue
	 *
	 * @access 	public
	 * @return 	string
	 */
	public static function filter_ticket_selector_redirect_url() {
		return add_query_arg( array( 'event_queue' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
	}



	/**
	 *    creates button for going to the Event Queue
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function view_event_queue_btn() {
		$template_args = array();
		$template_args[ 'reg_href' ] = $this->_reg_btn[ 'reg_href' ];
		$template_args[ 'sbmt_btn_text' ] = $this->_reg_btn[ 'text' ];
		EEH_Template::display_template( $this->_templates[ 'view_event_queue_btn' ], $template_args );
	}



	// *******************************************************************************************************
	// ********************************************   EVENT QUEUE   ********************************************
	// *******************************************************************************************************



	/**
	 *        load and display Event Queue contents prior to completing registration
	 *
	 * @access        public
	 * @return        void
	 */
	public function view_event_queue() {
		$this->init();
		// load classes
		EED_Multi_Event_Registration::load_classes();
		$this->enqueue_scripts();
		EE_Registry::instance()->load_helper( 'Template' );
		EE_Registry::instance()->CART->recalculate_all_cart_totals();

		// autoload Line_Item_Display classes
		$template_args[ 'event_queue_heading' ] = apply_filters( 'FHEE__EED_Multi_Event_Registration__view_event_queue__event_queue_heading', __( 'Event Queue', 'event_espresso' ));
		$template_args[ 'total_items' ] = EE_Registry::instance()->CART->all_ticket_quantity_count();
		$template_args[ 'event_queue' ] = $this->_get_event_queue( EE_Registry::instance()->CART->get_grand_total() );
		$template_args[ 'reg_page_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'events_list_url' ] = EE_EVENTS_LIST_URL;
		$template_args[ 'add_ticket_url' ] = add_query_arg( array( 'event_queue' => 'add_ticket' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'remove_ticket_url' ] = add_query_arg( array( 'event_queue' => 'remove_ticket' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'register_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'empty_queue_url' ] = add_query_arg( array( 'event_queue' => 'empty' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'view_event_queue_url' ] = add_query_arg( array( 'event_queue' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
		EE_Registry::instance()->REQ->add_output( EEH_Template::display_template( EE_MER_PATH . 'templates' . DS . 'event_queue.template.php', $template_args, true ) );
	}



	/**
	 *    _get_event_queue
	 *
	 * @access        protected
	 * @param \EE_Line_Item $line_item
	 * @return string
	 * @throws \EE_Error
	 */
	protected function _get_event_queue( EE_Line_Item $line_item ) {
		// autoload Line_Item_Display classes
		EEH_Autoloader::register_line_item_display_autoloaders();
		$Line_Item_Display = new EE_Line_Item_Display( 'event_queue', 'EE_Event_Queue_Line_Item_Display_Strategy' );
		if ( ! $Line_Item_Display instanceof EE_Line_Item_Display && WP_DEBUG ) {
			throw new EE_Error( __( 'A valid instance of EE_Event_Queue_Line_Item_Display_Strategy could not be obtained.','event_espresso' ));
		}
		return $Line_Item_Display->display_line_item( $line_item );
	}



	/**
	 *        get the max number of additional tickets that can be purchased per registration for an event
	 *
	 * @access        protected
	 * @param        string $event_id
	 * @return        int
	 */
	protected function _get_additional_limit( $event_id ) {
		do_action( 'AHEE_log', __FILE__, __FUNCTION__, '' );
		$event = EEM_Event::instance()->get_one_by_ID( $event_id );
		return $event instanceof EE_Event ? $event->additional_limit() : 0;
	}



	/**
	 * 	increment or decrement a ticket's quantity in the event queue
	 *
	 * @access 	protected
	 * @return bool
	 * @throws \EE_Error
	 */
	protected function _validate_request() {
		$this->init();
		EED_Multi_Event_Registration::load_classes();
		// check the request
		if ( isset( $_REQUEST[ 'ticket' ], $_REQUEST[ 'line_item' ] ) ) {
			$ticket  = EEM_Ticket::instance()->get_one_by_ID( absint( $_REQUEST[ 'ticket' ] ));
			if ( $ticket instanceof EE_Ticket ) {
				return $ticket;
			} else {
				// no ticket found
				EE_Error::add_error( __( 'The Ticket information could not be retrieved from the database. Please refresh the page and try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
		} else {
			// no ticket or line item !?!?!
			EE_Error::add_error( __( 'Either the ticket or Event Queue line item was not specified or invalid, therefore the Event Queue could not be updated. Please refresh the page and try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		}
		return false;
	}



	/**
	 * 	increment or decrement a ticket's quantity in the event queue
	 *
	 * @access 	public
	 * @param int $qty
	 * @return void
	 * @throws \EE_Error
	 */
	public function add_ticket( $qty = 1 ) {
		$line_item = null;
		// check the request
		$ticket = $this->_validate_request();
		if ( $ticket instanceof EE_Ticket ) {
			$event = $ticket->first_datetime()->event();
			$additional_limit = $event->additional_limit();
			$tickets_remaining = $ticket->remaining();
			if ( $tickets_remaining ) {
				$qty = absint( $qty );
				if ( $tickets_remaining >= $qty ) {
					// you can DO IT !!!
					$line_item = $this->_adjust_line_item_quantity( $qty );
				} else {
					// can't register anymore attendees
					$singular = 'You have attempted to purchase %s ticket.';
					$plural = 'You have attempted to purchase %s tickets.';
					// translate and possibly pluralize the error
					$limit_error_1 = sprintf( _n( $singular, $plural, $qty, 'event_espresso' ), $qty, $qty );
					$singular = 'The registration limit for this event is %s ticket per transaction, therefore the total number of tickets you may purchase at any time can not exceed %s.';
					$plural = 'The registration limit for this event is %s tickets per transaction, therefore the total number of tickets you may purchase at any time can not exceed %$s.';
					// translate and possibly pluralize the error
					$limit_error_2 = sprintf( _n( $singular, $plural, $additional_limit, 'event_espresso' ), $additional_limit, $additional_limit );
					EE_Error::add_error( $limit_error_1 . '<br/>' . $limit_error_2, __FILE__, __FUNCTION__, __LINE__ );
				}
			} else {
				// event is full
				EE_Error::add_error( __( 'We\'re sorry, but there are no available spaces left for this event. No additional attendees can be added.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
		}
		$this->send_ajax_response( array( '#event-queue-item-row-' . $line_item->code() => $this->_get_event_queue( $line_item )));
	}



	/**
	 * 	_adjust_line_item_quantity
	 *
	 * @access 	protected
	 * @param 	int $qty
	 * @return 	EE_Line_Item | null
	 * @throws \EE_Error
	 */
	protected function _adjust_line_item_quantity( $qty = 1 ) {
		$line_item = $this->_get_line_item_from_request();
		if ( $line_item instanceof EE_Line_Item ) {
			if ( $qty > 0 ) {
				$additional = 'An additional';
				$added_or_removed = 'added';
			} else if ( $qty < 0 ) {
				if ( $line_item->quantity() < 2 ) {
					EE_Error::add_attention(
						sprintf( __( 'Ticket quantity must be at least one for each event.%1$sIf you wish to remove this ticket from the Event Queue, click on the trash can icon.', 'event_espresso' ), '<br />' ),
						__FILE__, __FUNCTION__, __LINE__
					);
					return $line_item;
				}
				$additional = 'A ';
				$added_or_removed = 'removed';
			} else {
				EE_Error::add_attention(
					sprintf( __( 'Ticket quantity was not specified, therefore it could not be adjusted.', 'event_espresso' ), '<br />' ),
					__FILE__, __FUNCTION__, __LINE__
				);
				// qty = 0 ?? just return the line item
				return $line_item;
			}
			// update quantity
			$line_item->set_quantity( $line_item->quantity() + $qty );
			if ( $line_item->save() ) {
				// something got added
				EE_Error::add_success(
					sprintf( __( '%1$s ticket was successfully %2$s for this event.', 'event_espresso' ), $additional, $added_or_removed ),
					__FILE__, __FUNCTION__, __LINE__
				);
			} else {
				// nothing added
				EE_Error::add_error(
					sprintf( __( '%1$s ticket was not %2$s for this event. Please refresh the page and try it again.', 'event_espresso' ), $additional, $added_or_removed ),
					__FILE__, __FUNCTION__, __LINE__
				);
				return null;
			}
		}
		return $line_item;
	}



	/**
	 *        remove an attendee from event in the event queue
	 *
	 * @access 	protected
	 * @return EE_Line_Item | null
	 */
	protected function _get_line_item_from_request() {
		$line_item = EEM_Line_Item::instance()->get_one_by_ID( absint( $_REQUEST[ 'line_item' ] ));
		if ( $line_item instanceof EE_Line_Item ) {
			return $line_item;
		} else {
			// couldn't find the line item !?!?!
			EE_Error::add_error(
				__( 'The specified item could not be found in the cart, therefore the ticket quantity could not be adjusted. Please refresh the page and try again.', 'event_espresso' ),
				__FILE__, __FUNCTION__, __LINE__
			);
			return null;
		}
	}



	/**
	 *        remove an attendee from event in the event queue
	 *
	 * @access 	public
	 * @param int $qty
	 * @return TRUE on success and FALSE on fail
	 */
	public function remove_ticket( $qty = 1 ) {
		$line_item = null;
		// check the request
		$ticket = $this->_validate_request();
		if ( $ticket instanceof EE_Ticket ) {
			$qty = absint( $qty );
			// check the request
			if ( $_REQUEST[ 'line_item' ] && $qty ) {
				// you can DO IT !!!
				$line_item = $this->_adjust_line_item_quantity( $qty * -1 );
			} else {
				// no ticket or line item !?!?!
				EE_Error::add_error( __( 'The cart line item was not specified, therefore a ticket could not be added. Please refresh the page and try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
		}
		$this->send_ajax_response( array( '#event-queue-item-row-' . $line_item->code() => $this->_get_event_queue( $line_item )));
	}



	/**
	 *  delete_ticket - removes ticket completely
	 *
	 * @access        public
	 * @return        void
	 */
	public function delete_ticket() {
		$line_item = null;
		// check the request
		$ticket = $this->_validate_request();
		if ( $ticket instanceof EE_Ticket ) {
			$line_item = $this->_get_line_item_from_request();
			if ( $line_item instanceof EE_Line_Item ) {
				$removals = $line_item->quantity();
				$line_item->delete_children_line_items();
				if ( $line_item->delete() ) {
					// then something got deleted
					EE_Error::add_success(
						sprintf( _n( '%s ticket was successfully removed from the Event Queue', '%s tickets were successfully removed from the Event Queue', $removals, 'event_espresso' ), $removals ),
						__FILE__, __FUNCTION__, __LINE__
					);
				} else {
					// nothing removed
					EE_Error::add_error( __( 'The ticket was not removed from the Event Queue', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
				}
			}
		}
		$this->send_ajax_response( array( '#event-queue-tbl tbody' => EE_Registry::instance()->CART->get_grand_total() ));
	}



	/**
	 *        remove all events from the event queue
	 *
	 * @access        public
	 * @return        void
	 */
	public function empty_event_queue() {
		$this->init();
		EED_Multi_Event_Registration::load_classes();
		if ( EE_Registry::instance()->CART->empty_cart() ) {
			EE_Error::add_success( __( 'The Event Queue was successfully emptied!', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		} else {
			EE_Error::add_error( __( 'The Event Queue could not be emptied!', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		}
		$this->send_ajax_response(
			array( '#event-queue-tbl tbody' => EE_Registry::instance()->CART->get_grand_total() ),
			apply_filters( 'FHEE__EED_Multi_Event_Registration__empty_event_queue__redirect_url', EE_EVENTS_LIST_URL )
		);
	}



	/**
	 *        get number of available spaces for event via ajax
	 *
	 * @access        public
	 * @return        int
	 */
	public function get_available_spaces_via_ajax() {
		// has a line item been sent?
		if ( isset( $_REQUEST[ 'event_id' ] ) ) {
			$event = EEM_Event::instance()->get_one_by_ID( absint( $_REQUEST[ 'event_id' ] ) );
			if ( $event instanceof EE_Event ) {
				$available_spaces = $event->first_datetime()->tickets_remaining();
				// just send the ajax
				echo json_encode( array( 'id' => $event->ID(), 'spaces' => $available_spaces, 'time' => current_time( 'g:i:s a T' ) ) );
				// to be... or...
				exit();
			} else {
				EE_Error::add_error( __( 'Available space polling via ajax failed. Event not found.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
		} else {
			EE_Error::add_error( __( 'Available space polling via ajax failed. No event id.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		}
		// just send the ajax
		echo json_encode( EE_Error::get_notices() );
		// to be... or...
		die();
	}



	/**
	 *   handle ajax message responses
	 *
	 * @access protected
	 * @param array $line_item_html - 	an array containing the HTML element id
	 * 															and the corresponding HTML for the line item(s) it represents
	 * 															example: array( '#event-queue-item-row-' . $line_item->code(), $line_item )
	 * @param string  $redirect_url
	 * @return void
	 */
	protected function send_ajax_response( $line_item_html = array(), $redirect_url = '' ) {
		// if this is an ajax request AND a callback function exists
		if ( $this->_ajax === 1 ) {
			// just send the ajax
			echo json_encode( array_merge( EE_Error::get_notices(), $line_item_html ));
			// to be... or...
			die();
		}
		EE_Error::get_notices( false, true );
		$redirect_url = ! empty( $redirect_url ) ? $redirect_url : add_query_arg( array( 'event_queue' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
		wp_safe_redirect( $redirect_url );
		exit;
	}




}
/* End of file EE_Multi_Event_Registration.class.php */
/* Location: espresso-multi-registration/EE_Multi_Event_Registration.class.php */