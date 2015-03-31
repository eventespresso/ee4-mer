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

	/**
	 * Cart? Event Cart? Ticket Basket?
	 *
	 * @type string $_event_cart_name
	 */
	protected static $_event_cart_name = '';

	/**
	 * @type bool $_ajax
	 */
	private $_ajax = false;



	/**
	 * @return EED_Multi_Event_Registration
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	/**
	 * @return string
	 */
	public static function event_cart_name() {
		return self::$_event_cart_name;
	}



	/**
	 *    set_hooks - for hooking into EE Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks() {
		EED_Multi_Event_Registration::set_definitions();
		add_action( 'wp_enqueue_scripts', array( 'EED_Multi_Event_Registration', 'enqueue_styles_and_scripts' ), 10 );

		EE_Config::register_route( 'view', 'Multi_Event_Registration', 'view_event_cart', 'event_cart' );
		EE_Config::register_route( 'update', 'Multi_Event_Registration', 'update_event_cart', 'event_cart' );
		EE_Config::register_route( 'add_ticket', 'Multi_Event_Registration', 'add_ticket', 'event_cart' );
		EE_Config::register_route( 'remove_ticket', 'Multi_Event_Registration', 'remove_ticket', 'event_cart' );
		EE_Config::register_route( 'delete_ticket', 'Multi_Event_Registration', 'delete_ticket', 'event_cart' );
		EE_Config::register_route( 'empty', 'Multi_Event_Registration', 'empty_event_cart', 'event_cart' );
		// don't empty cart
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false' );
		// process registration links
		add_filter( 'FHEE__EE_Ticket_Selector__ticket_selector_form_open__html', array( 'EED_Multi_Event_Registration', 'filter_ticket_selector_form_html' ), 10, 2 );
		add_filter( 'FHEE__EE_Ticket_Selector__display_ticket_selector_submit__btn_text', array( 'EED_Multi_Event_Registration', 'filter_ticket_selector_submit_button' ), 10, 2 );
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__success_redirect_url', array( 'EED_Multi_Event_Registration', 'filter_ticket_selector_redirect_url' ), 10, 2 );
		// verify that SPCO registrations correspond to tickets in cart
		add_filter( 'FHEE__EED_Single_Page_Checkout___final_verifications__checkout', array('EED_Multi_Event_Registration', 'verify_tickets_in_cart' ), 10, 1 );
		// redirect to event_cart
		add_action( 'EED_Ticket_Selector__process_ticket_selections__before', array( 'EED_Multi_Event_Registration', 'redirect_to_event_cart' ), 10 );
		add_filter( 'FHEE__EE_SPCO_Reg_Step__reg_step_submit_button__sbmt_btn_html', array( 'EED_Multi_Event_Registration', 'return_to_event_cart_button'	), 10 );
		// update cart in session
		add_action( 'shutdown', array( 'EED_Multi_Event_Registration', 'save_cart' ), 10 );
	}



	/**
	 *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks_admin() {
		EED_Multi_Event_Registration::set_definitions();
		add_action(
			'wp_ajax_espresso_process_ticket_selections',
			array( 'EED_Multi_Event_Registration', 'process_ticket_selections' )
		);
		add_action(
			'wp_ajax_nopriv_espresso_process_ticket_selections',
			array( 'EED_Multi_Event_Registration', 'process_ticket_selections' )
		);
		// don't empty cart
		add_filter( 'FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false' );
		// ajax add attendees
		add_action( 'wp_ajax_espresso_add_ticket_to_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_add_ticket' ) );
		add_action( 'wp_ajax_nopriv_espresso_add_ticket_to_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_add_ticket' ) );
		// ajax remove attendees
		add_action( 'wp_ajax_espresso_remove_ticket_from_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_remove_ticket' ) );
		add_action( 'wp_ajax_nopriv_espresso_remove_ticket_from_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_remove_ticket' ) );
		// ajax remove event
		add_action( 'wp_ajax_espresso_delete_ticket_from_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_delete_ticket' ) );
		add_action( 'wp_ajax_nopriv_espresso_delete_ticket_from_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_delete_ticket' ) );
		// ajax remove event
		add_action( 'wp_ajax_espresso_empty_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_empty_event_cart' ) );
		add_action( 'wp_ajax_nopriv_espresso_empty_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_empty_event_cart' ) );
		// ajax update event
		add_action( 'wp_ajax_espresso_update_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_update_event_cart' ) );
		add_action( 'wp_ajax_nopriv_espresso_update_event_cart', array( 'EED_Multi_Event_Registration', 'ajax_update_event_cart' ) );
		// ajax available_spaces
		add_action( 'wp_ajax_espresso_get_available_spaces', array( 'EED_Multi_Event_Registration', 'ajax_get_available_spaces' ) );
		add_action( 'wp_ajax_nopriv_espresso_get_available_spaces', array( 'EED_Multi_Event_Registration', 'ajax_get_available_spaces' ) );
		// verify that SPCO registrations correspond to tickets in cart
		add_filter( 'FHEE__EED_Single_Page_Checkout___final_verifications__checkout', array(
			'EED_Multi_Event_Registration',
			'verify_tickets_in_cart'
		), 10, 1 );
		// update cart in session
		add_action( 'shutdown', array( 'EED_Multi_Event_Registration', 'save_cart' ), 10 );
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
		EED_Multi_Event_Registration::$_event_cart_name = apply_filters(
			'FHEE__EED_Multi_Event_Registration__set_definitions__event_cart_name',
			__( 'Event Cart', 'event_espresso' )
		);
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
		if ( ! defined( 'MER_ACTIVE' )) {
			define( 'MER_ACTIVE', true );
		}
		// set MER active to TRUE
		add_filter( 'filter_hook_espresso_MER_active', '__return_true' );
		$this->_ajax = isset( $_REQUEST[ 'ee_front_ajax'] ) ? true : false;
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
	 *        translate_js_strings
	 *
	 * @access        public
	 * @return        void
	 */
	public static function translate_js_strings() {
		EE_Registry::$i18n_js_strings[ 'server_error' ] = __( 'An unknown error occurred on the server while attempting to process your request. Please refresh the page and try again or contact support.', 'event_espresso' );
	}



	/**
	 *    enqueue_styles_and_scripts - Load the scripts and css
	 *
	 * @access    public
	 * @return    void
	 */
	public static function enqueue_styles_and_scripts() {

		// multi_event_registration script
		wp_register_script( 'espresso_core', EE_GLOBAL_ASSETS_URL . 'scripts' . DS . 'espresso_core.js', array( 'jquery' ), EVENT_ESPRESSO_VERSION, true );

		$page = EE_Registry::instance()->REQ->get_post_name_from_request();
		$page = ! empty( $page ) && $page != '1' ? $page : '';
		$page = empty( $page ) && isset( $_SERVER['REQUEST_URI'] ) ? basename( $_SERVER[ 'REQUEST_URI' ] ) : $page;
		switch ( $page ) {

			// event list
			case basename( EE_EVENTS_LIST_URL ) :
		// event cart / registration checkout
			case basename( EE_EVENT_QUEUE_BASE_URL ) :
			// styles
				wp_register_style(
					'espresso_multi_event_registration',
					apply_filters(
						'FHEE__EED_Multi_Event_Registration__enqueue_scripts__event_cart_css',
						EE_MER_URL . 'css' . DS . 'multi_event_registration.css'
					)
				);
				wp_enqueue_style( 'espresso_multi_event_registration' );
				// scripts
				wp_register_script( 'espresso_multi_event_registration', EE_MER_URL . 'scripts' . DS . 'multi_event_registration.js', array( 'espresso_core' ), EE_MER_VERSION, true );
				wp_enqueue_script( 'espresso_multi_event_registration' );

		}
		// load JS
		wp_localize_script( 'espresso_multi_event_registration', 'eei18n', EE_Registry::$i18n_js_strings );
	}



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
	 * @access    public
	 * @param    string $btn_text
	 * @param    EE_Event $event
	 * @param bool $tickets_in_cart
	 * @return string
	 */
	public static function filter_ticket_selector_submit_button( $btn_text = '', $event = null, $tickets_in_cart = false ) {
		// verify event
		if ( ! $event instanceof EE_Event && ! $tickets_in_cart ) {
			if ( WP_DEBUG ) {
				EE_Error::add_error( __( 'An invalid event object was received.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
			return $btn_text;
		}
		if ( $tickets_in_cart || EED_Multi_Event_Registration::has_tickets_in_cart( $event ) ) {
			$btn_text = sprintf( __( 'View %s', 'event_espresso' ), EED_Multi_Event_Registration::$_event_cart_name );
		} else {
			$btn_text = sprintf( __( 'Add to %s', 'event_espresso' ), EED_Multi_Event_Registration::$_event_cart_name );
		}
		return $btn_text;
	}



	/**
	 *    get_all_event_tickets
	 *
	 * @access    protected
	 * @param \EE_Event $event
	 * @return array
	 */
	protected static function get_all_event_tickets( EE_Event $event ) {
		$tickets = array();
		// get active events
		foreach ( $event->datetimes_ordered( false ) as $datetime ) {
			$datetime_tickets = $datetime->ticket_types_available_for_purchase();
			foreach ( $datetime_tickets as $datetime_ticket ) {
				if ( $datetime_ticket instanceof EE_Ticket ) {
					$tickets[ $datetime_ticket->ID() ] = $datetime_ticket;
				}
			}
		}
		return $tickets;
	}



	/**
	 *    get_all_event_tickets
	 *
	 * @access    protected
	 * @param \EE_Event $event
	 * @return bool
	 */
	protected static function has_tickets_in_cart( EE_Event $event ) {
		EED_Multi_Event_Registration::load_classes();
		$event_tickets = EED_Multi_Event_Registration::get_all_event_tickets( $event );
		$tickets_in_cart = EE_Registry::instance()->CART->get_tickets();
		foreach ( $tickets_in_cart as $ticket_in_cart ) {
			if (
				$ticket_in_cart instanceof EE_Line_Item &&
				$ticket_in_cart->OBJ_type() == 'Ticket' &&
				isset( $event_tickets[ $ticket_in_cart->OBJ_ID() ] )
			) {
				return true;
			}
		}
		return false;
	}



	/**
	 *    adds a hidden input to the Ticket Selector form
	 *
	 * @access    public
	 * @param string $html
	 * @param    EE_Event $event
	 * @param bool $tickets_in_cart
	 * @return string
	 */
	public static function filter_ticket_selector_form_html( $html = '', $event = null, $tickets_in_cart = false ) {
		if ( $tickets_in_cart || EED_Multi_Event_Registration::has_tickets_in_cart( $event ) ) {
			$html .= '<input type="hidden" value="view" name="event_cart">';
		}
		return $html;
	}



	/**
	 *    changes event list button URL based on tickets in cart
	 *
	 * @access 	public
	 * @return 	string
	 */
	public static function filter_ticket_selector_redirect_url() {
		if ( apply_filters( 'FHEE__EED_Multi_Event_Registration__filter_ticket_selector_redirect_url__redirect_to_cart', false ) ) {
			return add_query_arg( array( 'event_cart' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
		} else {
			return EE_EVENTS_LIST_URL;
		}
	}



	/**
	 *    creates button for going back to the Event Cart
	 *
	 * @access    public
	 * @param string $html
	 * @return string
	 */
	public static function return_to_event_cart_button( $html = '' ) {
		$html = '<a class="mini-cart-view-cart-lnk view-cart-lnk mini-cart-button button" href = "' . add_query_arg(
				array( 'event_cart' => 'view' ), EE_EVENT_QUEUE_BASE_URL ) . '" ><span class="dashicons
				dashicons-cart" ></span >' . apply_filters(
				'FHEE__EED_Multi_Event_Registration__view_event_cart_btn_txt', sprintf( __( 'return to %s',
				'event_espresso' ), EED_Multi_Event_Registration::$_event_cart_name ) )  . '</a ><br />' . $html;
		return $html;
	}



	/**
	 *    redirect_to_event_cart
	 *
	 * @access 	public
	 * @return 	void
	 */
	public static function redirect_to_event_cart() {
		// grab event ID from Ticket Selector
		$EVT_ID = absint( EE_Registry::instance()->REQ->get( 'tkt-slctr-event-id', 0 ));
		if ( $EVT_ID ) {
			// grab ticket quantity array
			$ticket_quantities = EE_Registry::instance()->REQ->get( 'tkt-slctr-qty-' . $EVT_ID, array() );
			$ticket_quantities = is_array( $ticket_quantities ) ? $ticket_quantities : array( $ticket_quantities );
			foreach ( $ticket_quantities as $ticket_quantity ) {
				// if ANY qty was set, then don't redirect
				if ( absint( $ticket_quantity )) {
					return;
				}
			}
		}
		if ( EE_Registry::instance()->REQ->get( 'event_cart', '' ) == 'view' ) {
			wp_safe_redirect( add_query_arg( array( 'event_cart' => 'view' ), EE_EVENT_QUEUE_BASE_URL ) );
			exit();
		}
		exit();
	}



	/**
	 *    process_ticket_selections
	 *
	 * @access 	public
	 * @return 	void
	 */
	public static function process_ticket_selections() {
		$response = array( 'tickets_added' => false );
		if ( EED_Ticket_Selector::instance()->process_ticket_selections() ) {
			$EVT_ID = absint( EE_Registry::instance()->REQ->get( 'tkt-slctr-event-id', 0 ) );
			$tickets = EE_Registry::instance()->REQ->get( 'tkt-slctr-qty-' . $EVT_ID, array() );
			$ticket_count = 0;
			foreach ( $tickets as $quantity ) {
				$ticket_count += $quantity;
			}
			$response = array(
				'tickets_added' => true,
				'btn_id' => "#ticket-selector-submit-$EVT_ID-btn",
				'btn_txt' => EED_Multi_Event_Registration::filter_ticket_selector_submit_button( '', null, true ),
				'form_html' => EED_Multi_Event_Registration::filter_ticket_selector_form_html( '', null, true ),
				'ee_mini_cart_details' => EED_Multi_Event_Registration::get_mini_cart(),
			);
			EE_Error::add_success(
				_n(
					__( '1 ticket was successfully added for this event.' ),
					sprintf( __( '%1$s tickets were successfully added for this event.' ), $ticket_count ),
					$ticket_count
				),
				__FILE__, __FUNCTION__, __LINE__
			);
		}
		// just send the ajax
		echo json_encode(
			array_merge(
				EE_Error::get_notices( false ),
				$response
			)
		);
		// to be... or...
		die();
	}



	// *******************************************************************************************************
	// ********************************************   EVENT QUEUE   ********************************************
	// *******************************************************************************************************



	/**
	 *        load and display Event Cart contents prior to completing registration
	 *
	 * @access        public
	 * @return        void
	 */
	public function view_event_cart() {
		$this->init();
		// load classes
		EED_Multi_Event_Registration::load_classes();
		EE_Registry::instance()->load_helper( 'Template' );
		//EE_Registry::instance()->CART->recalculate_all_cart_totals();
		$grand_total = EE_Registry::instance()->CART->get_grand_total();
		$grand_total->recalculate_total_including_taxes();

		// autoload Line_Item_Display classes
		$template_args[ 'event_cart_heading' ] = apply_filters(
			'FHEE__EED_Multi_Event_Registration__view_event_cart__event_cart_heading',
			EED_Multi_Event_Registration::$_event_cart_name
		);
		$template_args[ 'event_cart_name' ] = EED_Multi_Event_Registration::$_event_cart_name;
		$template_args[ 'total_items' ] = EE_Registry::instance()->CART->all_ticket_quantity_count();
		$template_args[ 'event_cart' ] = $this->_get_event_cart( $grand_total );
		$template_args[ 'reg_page_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'events_list_url' ] = EE_EVENTS_LIST_URL;
		$template_args[ 'add_ticket_url' ] = add_query_arg( array( 'event_cart' => 'add_ticket' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'remove_ticket_url' ] = add_query_arg( array( 'event_cart' => 'remove_ticket' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'register_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'update_cart_url' ] = add_query_arg( array( 'event_cart' => 'update_cart_url' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'empty_cart_url' ] = add_query_arg( array( 'event_cart' => 'empty' ), EE_EVENT_QUEUE_BASE_URL );
		EE_Registry::instance()->REQ->add_output( EEH_Template::display_template( EE_MER_PATH . 'templates' . DS . 'event_cart.template.php', $template_args, true ) );
	}



	/**
	 *    _get_event_cart
	 *
	 * @access        protected
	 * @param \EE_Line_Item $line_item
	 * @return string
	 * @throws \EE_Error
	 */
	protected function _get_event_cart( $line_item = null ) {
		$line_item = $line_item instanceof EE_Line_Item ? $line_item : EE_Registry::instance()->CART->get_grand_total();
		// autoload Line_Item_Display classes
		EEH_Autoloader::register_line_item_display_autoloaders();
		$Line_Item_Display = new EE_Line_Item_Display( 'event_cart', 'EE_Event_Cart_Line_Item_Display_Strategy' );
		if ( ! $Line_Item_Display instanceof EE_Line_Item_Display && WP_DEBUG ) {
			throw new EE_Error( __( 'A valid instance of EE_Event_Cart_Line_Item_Display_Strategy could not be obtained.','event_espresso' ));
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
	 * 	increment or decrement a ticket's quantity in the event cart
	 *
	 * @access 	protected
	 * @return EE_Ticket|null
	 */
	protected function _validate_request() {
		$this->init();
		EED_Multi_Event_Registration::load_classes();
		// check the request
		if ( isset( $_REQUEST[ 'ticket' ], $_REQUEST[ 'line_item' ] ) ) {
			return $this->get_ticket( $_REQUEST[ 'ticket' ] );
		} else {
			// no ticket or line item !?!?!
			EE_Error::add_error(
				sprintf(
					__( 'Either the ticket or %1$s line item was not specified or invalid, therefore the
				%1$s could not be updated. Please refresh the page and try again.', 'event_espresso' ),
					EED_Multi_Event_Registration::$_event_cart_name
				),
				__FILE__, __FUNCTION__, __LINE__
			);
		}
		return null;
	}



	/**
	 *    retrieves a valid EE_Ticket from the db
	 *
	 * @access    protected
	 * @param int $TKT_ID
	 * @return EE_Ticket|null
	 */
	protected function get_ticket( $TKT_ID = 0 ) {
		$ticket = EEM_Ticket::instance()->get_one_by_ID( absint( $TKT_ID ) );
		if ( $ticket instanceof EE_Ticket ) {
			return $ticket;
		} else {
			// no ticket found
			EE_Error::add_error( __( 'The Ticket information could not be retrieved from the database. Please refresh the page and try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			return null;
		}
	}



	/**
	 * ajax_update_event_cart
	 *    call update_event_cart() via AJAX
	 *
	 * @access public
	 * @return array
	 */
	public static function ajax_update_event_cart() {
		EED_Multi_Event_Registration::instance()->update_event_cart();
	}



	/**
	 * 	increment or decrement all ticket quantities in the event cart
	 *
	 * @access 	public
	 * @return void
	 * @throws \EE_Error
	 */
	public function update_event_cart() {
		$this->init();
		EED_Multi_Event_Registration::load_classes();
		$ticket_quantities = EE_Registry::instance()->REQ->get( 'event_cart_update_txt_qty', array() );
		foreach ( $ticket_quantities as $TKT_ID => $ticket_quantity ) {
			$ticket = $this->get_ticket( $TKT_ID );
			if ( $ticket instanceof EE_Ticket ) {
				foreach ( $ticket_quantity as $line_item_id => $quantity ) {
					if ( $this->can_purchase_ticket_quantity( $ticket, $quantity ) ) {
						$line_item = $this->get_line_item( $line_item_id );
						$this->adjust_line_item_quantity( $line_item, $quantity, false );
					}
				}
			}
		}
		$this->send_ajax_response();
	}



	/**
	 * ajax_add_ticket
	 * 	call add_ticket() via AJAX
	 *
	 * @access public
	 * @return array
	 */
	public static function ajax_add_ticket() {
		EED_Multi_Event_Registration::instance()->add_ticket( 1, true );
	}



	/**
	 *    increment or decrement a ticket's quantity in the event cart
	 *
	 * @access    public
	 * @param int $quantity
	 * @return array
	 */
	public function add_ticket( $quantity = 1 ) {
		$line_item = null;
		// check the request
		$ticket = $this->_validate_request();
		if ( $this->can_purchase_ticket_quantity( $ticket, $quantity ) ) {
			// you can DO IT !!!
			$line_item = $this->get_line_item( $_REQUEST[ 'line_item' ] );
			$this->adjust_line_item_quantity( $line_item, $quantity );
		}
		$this->send_ajax_response();
	}



	/**
	 *    adjust_ticket_quantity
	 *
	 * @access    protected
	 * @param EE_Ticket $ticket
	 * @param int $quantity
	 * @return bool
	 */
	protected function can_purchase_ticket_quantity( EE_Ticket $ticket, $quantity = 1 ) {
		if ( $ticket instanceof EE_Ticket ) {
			$event = $ticket->first_datetime()->event();
			$additional_limit = $event->additional_limit();
			$tickets_remaining = $ticket->remaining();
			if ( $tickets_remaining ) {
				$quantity = absint( $quantity );
				if ( $tickets_remaining >= $quantity ) {
					// YEAH !!!
					return true;
				} else {
					// can't register anymore attendees
					$singular = 'You have attempted to purchase %s ticket.';
					$plural = 'You have attempted to purchase %s tickets.';
					// translate and possibly pluralize the error
					$limit_error_1 = sprintf( _n( $singular, $plural, $quantity, 'event_espresso' ), $quantity, $quantity );
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
		return false;
	}



	/**
	 *    _adjust_line_item_quantity
	 *
	 * @access    protected
	 * @param EE_Line_Item $line_item
	 * @param int $quantity
	 * @param bool $add
	 * @return EE_Line_Item|null
	 */
	protected function adjust_line_item_quantity( $line_item, $quantity = 1, $add = true ) {
		if ( $line_item instanceof EE_Line_Item ) {
			//EEH_Debug_Tools::printr( $line_item->code(), '$line_item->code()', __FILE__, __LINE__ );
			//EEH_Debug_Tools::printr( $line_item->type(), '$line_item->type()', __FILE__, __LINE__ );
			//EEH_Debug_Tools::printr( $line_item->OBJ_type(), '$line_item->OBJ_type()', __FILE__, __LINE__ );
			//EEH_Debug_Tools::printr( $quantity, '$quantity', __FILE__, __LINE__ );
			if ( $quantity > 0 ) {
				$additional = 'An additional';
				$added_or_removed = 'added';
			} else if ( $quantity < 0 ) {
				if ( $line_item->quantity() < 2 ) {
					EE_Error::add_attention(
						sprintf(
							__( 'Ticket quantity must be at least one for each event.%2$sIf you wish to remove this
							ticket from the %1$s, click on the trash can icon.', 'event_espresso' ),
							EED_Multi_Event_Registration::$_event_cart_name,
							'<br />'
						),
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
			$quantity = $add ? $line_item->quantity() + $quantity : $quantity;
			// update quantity
			$line_item->set_quantity( $quantity );
			//EEH_Debug_Tools::printr( $line_item, '$line_item', __FILE__, __LINE__ );
			$saved = $line_item->ID() ? $line_item->save() : $line_item->quantity() == $quantity;
			if ( $saved ) {
				if ( $add ) {
					$msg = sprintf(
						__( '%1$s ticket was successfully %2$s for this event.', 'event_espresso' ),
						$additional, $added_or_removed
					);
				} else {
					$msg = __( 'Ticket quantities were successfully updated for this event.', 'event_espresso' );
				}
				// something got added
				if ( apply_filters( 'FHEE__EED_Multi_Event_Registration__display_success_messages', false ) ) {
					EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
				}
			} else if ( $line_item->quantity() != $quantity ) {
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
	 *        remove an attendee from event in the event cart
	 *
	 * @access    protected
	 * @param int $line_item_id
	 * @return \EE_Line_Item|null
	 */
	protected function get_line_item( $line_item_id = 0 ) {
		$line_item = null;
		//EEH_Debug_Tools::printr( $line_item_id, '$line_item_id', __FILE__, __LINE__ );
		if ( is_int( $line_item_id )) {
			//EEH_Debug_Tools::printr( absint( $line_item_id ), 'absint( $line_item_id )', __FILE__, __LINE__ );
			$line_item = EEM_Line_Item::instance()->get_one_by_ID( absint( $line_item_id ) );
		}
		// get line item from db
		//EEH_Debug_Tools::printr( $line_item, '$line_item', __FILE__, __LINE__ );
		if ( $line_item instanceof EE_Line_Item ) {
			return $line_item;
		}
		$line_item_id = sanitize_text_field( $line_item_id );
		//EEH_Debug_Tools::printr( $line_item_id, '$line_item_id', __FILE__, __LINE__ );
		// or... search thru cart
		$tickets_in_cart = EE_Registry::instance()->CART->get_tickets();
		foreach ( $tickets_in_cart as $ticket_in_cart ) {
			if (
				$ticket_in_cart instanceof EE_Line_Item &&
				$ticket_in_cart->code() == $line_item_id
			) {
				$line_item = $ticket_in_cart;
				if ( $line_item instanceof EE_Line_Item ) {
					return $line_item;
				}
			}
		}
		// couldn't find the line item !?!?!
		EE_Error::add_error(
			__( 'The specified item could not be found in the cart, therefore the ticket quantity could not be adjusted. Please refresh the page and try again.', 'event_espresso' ),
			__FILE__, __FUNCTION__, __LINE__
		);
		return null;
	}



	/**
	 * ajax_remove_ticket
	 *    call remove_ticket() via AJAX
	 *
	 * @access public
	 * @return array
	 */
	public static function ajax_remove_ticket() {
		EED_Multi_Event_Registration::instance()->remove_ticket( 1, true );
	}




	/**
	 *        remove an attendee from event in the event cart
	 *
	 * @access 	public
	 * @param int $quantity
	 * @return TRUE on success and FALSE on fail
	 */
	public function remove_ticket( $quantity = 1 ) {
		$line_item = null;
		// check the request
		$ticket = $this->_validate_request();
		if ( $ticket instanceof EE_Ticket ) {
			$quantity = absint( $quantity );
			$line_item = $this->get_line_item( $_REQUEST[ 'line_item' ] );
			if ( $line_item instanceof EE_Line_Item  && $quantity ) {
				// you can DO IT !!!
				$this->adjust_line_item_quantity( $line_item, $quantity * -1 );
			} else {
				// no ticket or line item !?!?!
				EE_Error::add_error( __( 'The cart line item was not specified, therefore a ticket could not be removed. Please refresh the page and try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
			}
		}
		$this->send_ajax_response();
	}



	/**
	 * ajax_delete_ticket
	 *    call delete_ticket() via AJAX
	 *
	 * @access public
	 * @return array
	 */
	public static function ajax_delete_ticket() {
		EED_Multi_Event_Registration::instance()->delete_ticket();
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
			$line_item = $this->get_line_item( $_REQUEST[ 'line_item' ] );
			if ( $line_item instanceof EE_Line_Item ) {
				//$deleted = false;
				$removals = $line_item->quantity();
				$line_item->delete_children_line_items();
				//EEH_Debug_Tools::printr( $line_item, '$line_item', __FILE__, __LINE__ );
				if ( $line_item->ID() ) {
					//if ( $line_item->delete() ) {
					$deleted = $line_item->delete();
					//EEH_Debug_Tools::printr( $deleted, '$deleted', __FILE__, __LINE__ );
					//$deleted = true;
					//}
				} else {
					$deleted = EE_Registry::instance()->CART->delete_items( $line_item->code() );
					//EEH_Debug_Tools::printr( $deleted, '$deleted', __FILE__, __LINE__ );
					//$deleted = true;
				}
				//EEH_Debug_Tools::printr( $deleted, '$deleted', __FILE__, __LINE__ );
				// then something got deleted
				if ( $deleted && apply_filters( 'FHEE__EED_Multi_Event_Registration__display_success_messages', false )
				) {
					if ( $removals === 1 ) {
						$msg = sprintf(
							__( '%1$s ticket was successfully removed from the %2$s', 'event_espresso' ),
							$removals,
							EED_Multi_Event_Registration::$_event_cart_name
						);
					} else {
						$msg = sprintf(
							__( '%1$s tickets were successfully removed from the %2$s', 'event_espresso' ),
							$removals,
							EED_Multi_Event_Registration::$_event_cart_name
						);
					}
					EE_Error::add_success( $msg, __FILE__, __FUNCTION__, __LINE__ );
				} elseif ( ! $deleted ) {
					// nothing removed
					EE_Error::add_error(
						sprintf(
							__( 'The ticket was not removed from the %1$s', 'event_espresso' ),
							EED_Multi_Event_Registration::$_event_cart_name
						),
						__FILE__, __FUNCTION__, __LINE__
					);
				}
			}
		}
		$this->send_ajax_response();
	}



	/**
	 * ajax_empty_event_cart
	 *    call empty_event_cart() via AJAX
	 *
	 * @access public
	 * @return array
	 */
	public static function ajax_empty_event_cart() {
		EED_Multi_Event_Registration::instance()->empty_event_cart();
	}



	/**
	 *        remove all events from the event cart
	 *
	 * @access        public
	 * @return        void
	 */
	public function empty_event_cart() {
		$this->init();
		EED_Multi_Event_Registration::load_classes();
		// remove all unwanted records from the db
		if ( EE_Registry::instance()->CART->delete_cart() ) {
			// and clear the session too
			EE_Registry::instance()->SSN->clear_session( __CLASS__, __FUNCTION__ );
			EED_Multi_Event_Registration::save_cart();
			if ( apply_filters( 'FHEE__EED_Multi_Event_Registration__display_success_messages', false )) {
				EE_Error::add_success(
					sprintf(
						__( 'The %1$s was successfully emptied!', 'event_espresso' ),
						EED_Multi_Event_Registration::$_event_cart_name
					),
					__FILE__, __FUNCTION__, __LINE__
				);
			}
		} else {
			EE_Error::add_error(
				sprintf(
					__( 'The %1$s could not be emptied!', 'event_espresso' ),
					EED_Multi_Event_Registration::$_event_cart_name
				),
				__FILE__, __FUNCTION__, __LINE__
			);		}
		$this->send_ajax_response(
			true,
			apply_filters( 'FHEE__EED_Multi_Event_Registration__empty_event_cart__redirect_url', EE_EVENTS_LIST_URL )
		);
	}



	/**
	 * 	ajax_get_available_spaces
	 * 	get number of available spaces for event via ajax
	 *
	 * @access        public
	 * @return        int
	 */
	public static function ajax_get_available_spaces() {
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
	 * @param bool $empty_cart
	 * @param string  $redirect_url
	 * @return void
	 */
	protected function send_ajax_response( $empty_cart = false, $redirect_url = '' ) {
		$grand_total = EE_Registry::instance()->CART->get_grand_total();
		$grand_total->recalculate_total_including_taxes();
		$empty_cart = $empty_cart || EE_Registry::instance()->CART->all_ticket_quantity_count() == 0 ? true : false;
		// if this is an ajax request AND a callback function exists
		if ( $this->_ajax === true ) {
			// grab updated html for the event cart
			$new_html = array( 'tbody' => '<tbody>' . $this->_get_event_cart() . '</tbody>' );
			if ( $empty_cart ) {
				$new_html['.event-cart-grand-total'] = '';
				$new_html['.event-cart-register-button'] = '';
			}
			// just send the ajax
			echo json_encode(
				array_merge(
					EE_Error::get_notices( false ),
					array(
						'new_html' => $new_html,
						'ee_mini_cart_details' => EED_Multi_Event_Registration::get_mini_cart(),
					)
				)
			);
			// to be... or...
			die();
		}
		EE_Error::get_notices( false, true );
		$redirect_url = ! empty( $redirect_url ) ? $redirect_url : add_query_arg( array( 'event_cart' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
		wp_safe_redirect( $redirect_url );
		exit;
	}



	/**
	 *   verify_tickets_in_cart
	 * compares the current tickets in the cart with the current registrations for the checkout's transaction.
	 * if any difference between the type of ticket or their associated quantities exist,
	 * then registrations will be added or removed accordingly.
	 *
	 * @access public
	 * @param \EE_Checkout $checkout
	 * @return \EE_Checkout
	 */
	public static function verify_tickets_in_cart( EE_Checkout $checkout ) {
		// verify transaction
		if ( $checkout->transaction instanceof EE_Transaction ) {
			$changes = false;
			EED_Multi_Event_Registration::load_classes();
			// first we need to get an accurate list of tickets in the cart
			$cart_tickets = EED_Multi_Event_Registration::get_tickets_in_cart();
			// then we need to get an accurate list of registration tickets
			$reg_tickets = EED_Multi_Event_Registration::get_registration_tickets( $checkout->transaction );
			// now delete registrations for any tickets that were completely removed
			foreach ( $checkout->transaction->registrations() as $registration ) {
				$reg_ticket = $registration->ticket();
				if ( $reg_ticket instanceof EE_Ticket && ! isset( $cart_tickets[ $reg_ticket->ID() ]) ) {
					$changes = EED_Multi_Event_Registration::remove_registration(
						$checkout->transaction, $registration ) ? true : $changes;
				}
			}
			// then add new tickets and/or adjust quantities for others
			foreach ( $cart_tickets as $TKT_ID => $ticket_line_items ) {
				$changes = EED_Multi_Event_Registration::adjust_registration_quantities(
					$checkout->transaction,
					$TKT_ID,
					$ticket_line_items,
					$reg_tickets
				) ? true : $changes;
			}
			if ( $changes ) {
				$checkout->total_ticket_count = count( $checkout->transaction->registrations() );
				EED_Multi_Event_Registration::reset_registration_details(
					$checkout->transaction,
					$checkout->total_ticket_count
				);
				$checkout->generate_reg_form = true;
			}
		}
		return $checkout;
	}



	/**
	 *   get_tickets_in_cart
	 * returns a multi array of EE_Ticket objects
	 * indexed by:	[ ticket ID ][ auto-numerical ]
	 * is an accurate representation of total tickets in cart
	 *
	 * @access protected
	 * @return array
	 */
	protected static function get_tickets_in_cart() {
		// arrays for tracking ticket counts
		$cart_tickets = array();
		// first we need to get an accurate count of tickets in the cart
		$tickets_in_cart = EE_Registry::instance()->CART->get_tickets();
		foreach ( $tickets_in_cart as $ticket_line_item ) {
			if ( $ticket_line_item instanceof EE_Line_Item && $ticket_line_item->OBJ_type() == 'Ticket' ) {
				for ( $x = 1; $x <= $ticket_line_item->quantity(); $x++ ) {
					$cart_tickets[ $ticket_line_item->OBJ_ID() ][] = $ticket_line_item;
				}
			}
		}
		return $cart_tickets;
	}



	/**
	 *   get_registration_tickets
	 * returns a multi array of EE_Ticket objects
	 * indexed by:    [ ticket ID ][ registration ID ]
	 * is an accurate representation of total tickets in checkout
	 *
	 * @access protected
	 * @param \EE_Transaction $transaction
	 * @return array
	 */
	protected static function get_registration_tickets( EE_Transaction $transaction ) {
		$reg_tickets = array();
		// now we need to get an accurate count of registration tickets
		foreach ( $transaction->registrations() as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				$reg_ticket = $registration->ticket();
				if ( $reg_ticket instanceof EE_Ticket ) {
					$reg_tickets[ $reg_ticket->ID() ][ $registration->ID() ] = $registration;
				}
			}
		}
		return $reg_tickets;
	}



	/**
	 *   adjust_registration_quantities
	 * will either add registrations for NEW tickets
	 * or adjust quantities for existing tickets
	 *
	 * @access protected
	 * @param \EE_Transaction $transaction
	 * @param int $TKT_ID
	 * @param array $ticket_line_items
	 * @param array $reg_tickets
	 * @return bool
	 */
	protected static function adjust_registration_quantities( EE_Transaction $transaction, $TKT_ID = 0, $ticket_line_items = array(), $reg_tickets = array() ) {
		$changes = false;
		// are there any corresponding registrations for this ticket?
		if ( isset( $reg_tickets[ $TKT_ID ] ) ) {
			// corresponding registrations exist, so let's compare counts...
			$tkt_count = count( $ticket_line_items );
			$reg_count = count( $reg_tickets[ $TKT_ID ] );
			if ( $tkt_count > $reg_count ) {
				// ticket(s) added = create registrations
				for ( $x = 0; $x < ( $tkt_count - $reg_count ); $x++ ) {
					$changes = EED_Multi_Event_Registration::add_registration(
						$ticket_line_items[ $x ], $transaction ) ? true : $changes;
				}
			} else if ( $tkt_count < $reg_count ) {
				// ticket(s) removed = remove registrations
				for ( $x = $reg_count; $x > $tkt_count; $x-- ) {
					// grab last registration that corresponds to this ticket type
					$registration = array_pop( $reg_tickets[ $TKT_ID ] );
					$changes = EED_Multi_Event_Registration::remove_registration(
						$transaction, $registration ) ? true : $changes;
				}
			} else {
				// tickets match registrations = no change
				return false;
			}
		} else {
			// no corresponding registrations????
			// we need to create registrations for these tickets
			$reg_count = 0;
			foreach ( $ticket_line_items as $ticket_line_item ) {
				$reg_count++;
				$changes = EED_Multi_Event_Registration::add_registration(
					$ticket_line_item, $transaction ) ? true : $changes;
			}
		}
		return $changes;
	}



	/**
	 *   add_registration
	 *
	 * @access protected
	 * @param \EE_Line_Item $ticket_line_item
	 * @param \EE_Transaction $transaction
	 * @return bool
	 */
	protected static function add_registration( EE_Line_Item $ticket_line_item, EE_Transaction $transaction ) {
		$registration = null;
		if ( $ticket_line_item instanceof EE_Line_Item ) {
			/** @type EE_Registration_Processor $registration_processor */
			$registration_processor = EE_Registry::instance()->load_class( 'Registration_Processor' );
			$registration = $registration_processor->generate_ONE_registration_from_line_item(
				$ticket_line_item,
				$transaction
			);
		}
		return $registration instanceof EE_Registration ? true: false;
	}



	/**
	 *   remove_registration
	 *
	 * @access protected
	 * @param EE_Transaction $transaction
	 * @param EE_Registration $registration
	 * @return bool
	 */
	protected static function remove_registration( EE_Transaction $transaction, EE_Registration $registration ) {
		if ( $registration instanceof EE_Registration ) {
			$transaction->_remove_relation_to( $registration, 'Registration' );
			$registration->delete();
			return true;
		}
		return false;
	}



	/**
	 *   reset_registration_reg_counts
	 *
	 * @access protected
	 * @param EE_Transaction $transaction
	 * @param int $total_ticket_count
	 * @return void
	 */
	protected static function reset_registration_details( EE_Transaction $transaction, $total_ticket_count = 0 ) {
		$att_nmbr = 0;
		/** @type EE_Registration_Processor $registration_processor */
		$registration_processor = EE_Registry::instance()->load_class( 'Registration_Processor' );
		foreach ( $transaction->registrations() as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				$att_nmbr++;
				$registration->set_count( $att_nmbr );
				$registration->set_group_size( $total_ticket_count );
				$reg_url_bits = explode( '-', $registration->reg_url_link() );
				$reg_url_link = $att_nmbr . '-' . end( $reg_url_bits );
				$registration->set_reg_url_link( $reg_url_link );
				$registration->set( 'REG_code', $registration_processor->generate_reg_code( $registration ) );
				$registration->save();
				$transaction->_add_relation_to( $registration, 'Registration' );
			}
		}
	}



	/**
	 *    get_mini_cart
	 *
	 * @access    public
	 * @return    string
	 */
	public static function get_mini_cart() {
		global $wp_widget_factory;
		$mini_cart = $wp_widget_factory->widgets[ 'EEW_Mini_Cart' ];
		if ( $mini_cart instanceof EEW_Mini_Cart ) {
			$options = get_option( $mini_cart->option_name );
			if ( isset( $options[ $mini_cart->number ], $options[ $mini_cart->number ][ 'template' ] ) ) {
				$template = $options[ $mini_cart->number ][ 'template' ];
			} else {
				$template = '';
			}
			return $mini_cart->get_mini_cart( $template );
		}
		return '';
	}



	/**
	 *   shutdown
	 *
	 * @access public
	 * @return void
	 */
	public static function save_cart() {
		if ( EE_Registry::instance()->CART instanceof EE_Cart ) {
			EE_Registry::instance()->CART->save_cart();
		}
	}



}
/* End of file EE_Multi_Event_Registration.class.php */
/* Location: espresso-multi-registration/EE_Multi_Event_Registration.class.php */