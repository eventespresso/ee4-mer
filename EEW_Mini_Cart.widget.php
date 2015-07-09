<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license				http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link						http://www.eventespresso.com
 * @ version		 	3.2.P
 *
 * ------------------------------------------------------------------------
 *
 * EEW_Mini_Cart class
 *
 * @package			Event Espresso
 * @subpackage		includes/classes
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EEW_Mini_Cart extends WP_Widget {

	/**
	 * @access protected
	 * @var \EE_Cart
	 */
	protected $_cart = NULL;


	/**
	 * @see WP_Widget for construct details
	 */
	public function __construct() {
		$widget_options = array(
			'classname' => 'espresso-mini-cart',
			'description' => __('A widget for displaying the Event Espresso Mini Cart.', 'event_espresso')
		);
		parent::__construct( 'espresso_minicart', __('Event Espresso Mini Cart Widget', 'event_espresso' ), $widget_options );
	}



	/**
	 *        build the widget settings form
	 *
	 * @access public
	 * @param array $instance
	 * @return string|void
	 */
	function form( $instance ) {

		$defaults = array( 'title' => __( 'Your Registrations', 'event_espresso' ), 'template' => 'widget_minicart' );

		$instance = wp_parse_args( (array)$instance, $defaults );

		echo '
	<p>' . __('Mini Cart Title:', 'event_espresso') . '
		<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'"  type="text" value="'.esc_attr( $instance['title'] ).'" />
	</p>';
		$minicart_templates = glob( EE_MER_PATH.'templates' . DS . 'widget_minicart*.template.php' );
		$minicart_templates = apply_filters( 'FHEE__EEW_Mini_Cart__form__minicart_templates', $minicart_templates, $instance );
		rsort( $minicart_templates, SORT_STRING );
		$find = array( '.template.php', '-', '_' );
		$replace = array( '', ' ', ' ' );
		echo '
	<p>' . __('Mini Cart Template:', 'event_espresso') . '<br />
		<select name="'.$this->get_field_name( 'template' ).'">';
		foreach ( $minicart_templates as $minicart_template ) {
			$selected = $minicart_template == $instance[ 'template' ] ? ' selected="selected"' : 'false';
			$template = str_replace ( $find, $replace, basename( $minicart_template ));
			echo "\n\t\t\t".'<option value="'.$minicart_template.'" ' . $selected . '>'. $template .'&nbsp;&nbsp;&nbsp;</option>';
		}
		echo '
		</select>
	</p>
';
	}



	/**
	 *        save the widget settings
	 *
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['template'] = ! empty( $new_instance[ 'template' ] ) ? strip_tags( $new_instance['template'] ) : EE_MER_PATH . 'templates' . DS . 'widget_minicart_table.template.php';
		return $instance;
	}



	/**
	 *        display the widget
	 *
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @throws \EE_Error
	 */
	function widget( $args, $instance ) {

		$instance[ 'title' ] = ! empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : __( 'Your Registrations', 'event_espresso' );
		$instance[ 'template' ] = ! empty( $instance[ 'template' ] ) ? $instance[ 'template' ] : EE_MER_PATH . 'templates' . DS . 'widget_minicart_table.template.php';
		// autoload Line_Item_Display classes
		EE_Registry::instance()->load_core( 'Cart' );
		EE_Registry::instance()->load_helper( 'Line_Item' );
		EE_Registry::instance()->load_core( 'Request_Handler' );
		extract($args);
		/** @type string $before_widget */
		/** @type string $after_widget */
		/** @type string $before_title */
		/** @type string $after_title */

		$checkout_page = false;
		if (
			EE_Registry::instance()->REQ->get_post_name_from_request() == basename( EE_Registry::instance()->CFG->core->reg_page_url() )
			&& EE_Registry::instance()->REQ->get( 'event_cart', '' ) !== 'view'
		) {
			$checkout_page = true;
		}
		$template_args = array();
		$template_args['before_widget'] = $before_widget;
		$template_args['after_widget'] = $after_widget;
		$template_args['before_title'] = $before_title;
		$template_args['after_title'] = $after_title;
		$template_args['title'] = apply_filters( 'widget_title', $instance['title'] );
		$template_args['event_cart_name'] = EED_Multi_Event_Registration::event_cart_name();
		// hide "Proceed to Checkout" button on checkout page
		$template_args[ 'checkout_page' ] = $checkout_page;
		$template_args[ 'events_list_url' ] = EE_EVENTS_LIST_URL;
		$template_args[ 'register_url' ] = EE_EVENT_QUEUE_BASE_URL;
		$template_args[ 'view_event_cart_url' ] = add_query_arg( array( 'event_cart' => 'view' ), EE_EVENT_QUEUE_BASE_URL );
		$template_args[ 'btn_class' ] = apply_filters( 'FHEE__EEW_Mini_Cart__event_cart_template__btn_class', '' );
		$template_args[ 'mini_cart_display' ] = EE_Registry::instance()->CART->all_ticket_quantity_count() > 0 ? '' 	: ' 	style="display:none;"';
		$template_args[ 'event_cart' ] = $this->get_mini_cart( $instance[ 'template' ] );
		// ugh... inline css... well... better than loading another stylesheet on every page
		// and at least it's filterable...
		echo apply_filters(
			'FHEE__EEW_Mini_Cart__widget__minicart_css',
'
<style>
.mini-cart-tbl-qty-th,
.mini-cart-tbl-qty-td {
	padding: 0 .25em;
}
#mini-cart-whats-next-buttons {
	text-align: right;
}
.mini-cart-button {
	box-sizing: border-box;
	display: inline-block;
	padding: 8px;
	margin: 4px 0 4px 4px;
	vertical-align: middle;
	line-height: 8px;
	font-size: 12px;
	font-weight: normal;
	-webkit-user-select: none;
	border-radius: 2px !important;
	text-align: center !important;
	cursor: pointer !important;
	white-space: nowrap !important;
}
</style>
'
		);
		echo EEH_Template::display_template( $instance[ 'template' ], $template_args, true );

	}



	/**
	 *    get_event_cart
	 *
	 * @access public
	 * @param string $template
	 * @return string
	 * @throws \EE_Error
	 */
	public function get_mini_cart( $template = '' ) {

		switch ( $template ) {
			case EE_MER_PATH . 'templates' . DS . 'widget_minicart_list.template.php' :
				$minicart_line_item_display_strategy = 'EE_Mini_Cart_List_Line_Item_Display_Strategy';
				break;
			case EE_MER_PATH . 'templates' . DS . 'widget_minicart_table.template.php' :
			default :
				$minicart_line_item_display_strategy = 'EE_Mini_Cart_Table_Line_Item_Display_Strategy';
				break;

		}
		EEH_Autoloader::register_autoloader(
			array(
				$minicart_line_item_display_strategy => EE_MER_PATH . $minicart_line_item_display_strategy . '.php'
			)
		);
		// autoload Line_Item_Display classes
		EEH_Autoloader::register_line_item_display_autoloaders();
		$Line_Item_Display = new EE_Line_Item_Display(
			'event_cart',
			apply_filters(
				'FHEE__EEW_Mini_Cart__widget__minicart_line_item_display_strategy',
				$minicart_line_item_display_strategy
			)
		);
		if ( ! $Line_Item_Display instanceof EE_Line_Item_Display && WP_DEBUG ) {
			throw new EE_Error( __( 'A valid instance of EE_Event_Cart_Line_Item_Display_Strategy could not be obtained.', 'event_espresso' ) );
		}
		return $Line_Item_Display->display_line_item( EE_Registry::instance()->CART->get_grand_total() );
	}

}

/* End of file EE_Mini_Cart_widget.class.php */
/* Location: /includes/classes/EE_Mini_Cart_widget.class.php */