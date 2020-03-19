<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
 /**
 *
 * Class EE_Event_Cart_Line_Item_Display_Strategy
 *
 * Description
 *
 * @package         Event Espresso
 * @subpackage    core
 * @author				Brent Christensen
 * 
 *
 */

class EE_Event_Cart_Line_Item_Display_Strategy implements EEI_Line_Item_Display {

	/**
	 * whether to display the taxes row or not
	 * @type bool $_show_taxes
	 */
	private $_show_taxes = false;

	/**
	 * whether any required tickets have been selected
	 * @type bool $_show_taxes
	 */
	private $_has_required = false;

	/**
	 * html for any tax rows
	 * @type string $_show_taxes
	 */
	private $_taxes_html = '';

	/**
	 * array of  events
	 * @type EE_Event[] $_events
	 */
	private $_events = array();



    /**
     * @param EE_Line_Item $line_item
     * @param array        $options
     * @return mixed
     * @throws \EE_Error
     */
	public function display_line_item( EE_Line_Item $line_item, $options = array() ) {

		EE_Registry::instance()->load_helper( 'HTML' );

		$html = '';
		// set some default options and merge with incoming
		$default_options = array(
			'show_desc' => TRUE,  // 	TRUE 		FALSE
			'event_count' => 0,
		);
		$options = array_merge( $default_options, (array)$options );
		$options = (array) apply_filters(
			'FHEE__EE_Event_Cart_Line_Item_Display_Strategy__display_line_item__options',
			$options
		);

		switch( $line_item->type() ) {

			case EEM_Line_Item::type_line_item:
				// item row
				if ( $line_item->OBJ_type() === 'Ticket' ) {
					$html .= $this->_ticket_row( $line_item, $options );
				} else {
					$html .= $this->_item_row( $line_item, $options );
				}
				// got any kids?
				foreach( $line_item->children() as $child_line_item ) {
					$this->display_line_item( $child_line_item, $options );
				}
				break;

			case EEM_Line_Item::type_sub_line_item:
				$html .= $this->_sub_item_row( $line_item, $options );
				break;

			case EEM_Line_Item::type_sub_total:

				if ( $line_item->OBJ_type() === 'Event' ) {
					if ( ! isset( $this->_events[ $line_item->OBJ_ID() ] ) ) {
						$html .= $this->_event_row( $line_item );
					}
				}
				// loop thru children
				$child_line_items = $line_item->children();
				$count = 0;
				static $total_count = 0;
                /** @var EE_Line_Item $child_line_item */
                foreach ($child_line_items as $child_line_item ) {
                    // recursively feed children back into this method
					$html .= $this->display_line_item( $child_line_item, $options );
					$count += $child_line_item->OBJ_type() === 'Ticket' ? $child_line_item->quantity() : 0;
				}
				$total_count += $line_item->code() !== 'pre-tax-subtotal' ? $count : 0;
				//echo "<br>line_item->code: "  . $line_item->code();
				//echo "<br>count: "  . $count;
				//echo "<br>total_count: "  . $total_count;
				// only display subtotal if there are multiple child line items
				if (
				    ( $line_item->total() > 0 && $count > 1 )
                    || ( $line_item->code() === 'pre-tax-subtotal' &&  count( $child_line_items ) )
                ) {
					$count = $line_item->code() === 'pre-tax-subtotal' ? $total_count : $count;
					$text = esc_html__( 'Subtotal', 'event_espresso' );
					$text = $line_item->code() === 'pre-tax-subtotal'
						? EED_Multi_Event_Registration::$event_cart_name . ' ' . $text
						: $text;
					$text = apply_filters(
						'FHEE__EE_Event_Cart_Line_Item_Display_Strategy__display_line_item__pretax_subtotal_text',
						$text,
						$line_item
					);
					$html .= $this->_sub_total_row( $line_item, $text, $count );
				}
				break;

			case EEM_Line_Item::type_tax:
				if ( $this->_show_taxes ) {
					$this->_taxes_html .= $this->_tax_row( $line_item, $options );
				}
				break;

			case EEM_Line_Item::type_tax_sub_total:
				if ( $this->_show_taxes ) {
					$child_line_items = $line_item->children();
					// loop thru children
					foreach( $child_line_items as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item, $options );
					}
					if ( count( $child_line_items ) > 1 ) {
						$this->_taxes_html .= $this->_total_tax_row( $line_item, esc_html__('Tax Total', 'event_espresso') );
					}
				}
				break;

			case EEM_Line_Item::type_total:
				// determine whether to display taxes or not
				$this->_show_taxes = $line_item->get_total_tax() > 0 ? true : false;
				if ( count( $line_item->get_items() ) ) {
					$options['event_count'] = count( $this->_events );
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item, $options );
					}
				} else {
					$html .= $this->_empty_msg_row();
				}
				$html .= $this->_taxes_html;
				$html .= $this->_total_row(
					$line_item,
					apply_filters(
						'FHEE__EE_Event_Cart_Line_Item_Display_Strategy__display_line_item__grand_total_text',
						EED_Multi_Event_Registration::$event_cart_name . ' ' . esc_html__( 'Total', 'event_espresso' )
					),
					EE_Registry::instance()->CART->all_ticket_quantity_count()
				);
                if ($this->_has_required) {
                    add_action(
                        'AHEE__event_cart_template__after_event_cart_table',
                        array( $this, 'after_event_cart_table')
                    );
                }
				break;

		}

		return $html;
	}


	/**
	 * 	_ticket_row
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _event_row( EE_Line_Item $line_item ) {
		$this->_events[ $line_item->OBJ_ID() ] = $line_item;
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-event-row-' . $line_item->ID(), 'event-cart-event-row' );
		// event name td
		$html .= EEH_HTML::td( 
			EEH_HTML::strong( $line_item->name() ),
			'',
			'event-header',
			'',
			apply_filters(
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___event_row__other_attributes',
				' colspan="4"',
				$line_item
			)
		);
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}


	/**
	 * 	_ticket_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _ticket_row( EE_Line_Item $line_item, $options = array() ) {
		$ticket = EEM_Ticket::instance()->get_one_by_ID( $line_item->OBJ_ID() );
		if ( $ticket instanceof EE_Ticket ) {
            $required = '';
            $required_class = '';
            if ( $ticket->required() ) {
                $this->_has_required = true;
                $required = ' ** ';
                $required_class = ' required';
            }
			// start of row
			$html = EEH_HTML::tr(
			    '',
                'event-cart-ticket-row-' . $line_item->ID(),
                "event-cart-ticket-row item{$required_class}"
            );
			// name && desc
			$name_and_desc = $line_item->name();
			$name_and_desc .= $options['show_desc'] ? '<span class="line-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>'  : '';
			$name_and_desc = $line_item->is_taxable() ? $name_and_desc . ' * ' : $name_and_desc;
            $name_and_desc .= $required;
            $name_and_desc = apply_filters(
            	'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___ticket_row__name_and_desc',
            	$name_and_desc,
            	$line_item,
            	$required
            );
            // name td
			$html .= EEH_HTML::td( $name_and_desc, '', 'ticket info' );
			// filter hook to allow for adding another td
			$html = apply_filters( 
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___ticket_row__html',
				$html,
            	$line_item				
			);
			// price td
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
			// quantity td
			$html .= EEH_HTML::td( $this->_ticket_qty_input( $line_item, $ticket, $required_class ), '', 'jst-rght' );
			// total td
			$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
			// end of row
			$html .= EEH_HTML::trx();
			return $html;
		}
		return null;
	}



    /**
     *    _ticket_qty_input
     *
     * @param EE_Line_Item $line_item
     * @param \EE_Ticket   $ticket
     * @param string       $required
     * @return mixed
     */
	private function _ticket_qty_input( EE_Line_Item $line_item, EE_Ticket $ticket, $required = '' ) {
        $input_disabled_class = '';
        $input_disabled = '';
        $line_item_quantity = $line_item->quantity();

		if ( !($ticket->remaining() - $line_item_quantity) || ($line_item_quantity >= $ticket->max()) ) {
            $input_disabled = ' disabled';
            $add_disabled_class = $input_disabled_class = ' disabled-event-cart-btn';
            $add_disabled_title = esc_html__( 'there are no more items available', 'event_espresso' );
            $add_query_args = array( 'event_cart' => 'view' );
		} else {
            $add_disabled_class = '';
            $add_disabled_title = esc_html__( 'add one item', 'event_espresso' );
            $add_query_args = array( 'event_cart' => 'add_ticket', 'ticket' => $ticket->ID(), 'line_item' => $line_item->code() );
		}

        if ( ($line_item_quantity <= $ticket->min()) ) {
            $input_disabled = ' disabled';
            $remove_disabled_class = $input_disabled_class = ' disabled-event-cart-btn';
            $remove_disabled_title = esc_html__( 'You cannot remove more items', 'event_espresso' );
            $remove_query_args = array( 'event_cart' => 'view' );
        } else {
            $remove_disabled_class = '';
            $remove_disabled_title = esc_html__( 'remove one item', 'event_espresso' );
            $remove_query_args = array( 'event_cart' => 'remove_ticket', 'ticket' => $ticket->ID(), 'line_item' => $line_item->code());
        }

		return '
	<div class="event-cart-ticket-qty-dv">
		<input type="text"
					id="event-cart-update-txt-qty-' . $line_item->code() . '"
					class="event-cart-update-txt-qty ' . $input_disabled_class . $required . '"
					name="event_cart_update_txt_qty[' . $ticket->ID() . '][' . $line_item->code() . ']"
					rel="' . $line_item->code() . '"
					value="' . $line_item_quantity . '"
					' . $input_disabled . '
					size="3"
		/>
		<span class="event-cart-update-buttons" >
			<a	title="' . $add_disabled_title . '"
				class="event-cart-add-ticket-button event-cart-button event-cart-icon-button button' . $add_disabled_class . '"
				rel="' . $line_item->code() . '"
				href="' . add_query_arg( $add_query_args, EE_EVENT_QUEUE_BASE_URL ) . '"
			>
				<span class="dashicons dashicons-plus" ></span >
			</a >
			<a	title = "' . $remove_disabled_title . '"
					class="event-cart-remove-ticket-button event-cart-button event-cart-icon-button button' . $required . $remove_disabled_class . '"
					rel = "' . $line_item->code() . '"
				    data-target="event-cart-update-txt-qty-' . $line_item->code() . '"
					href = "' . add_query_arg( $remove_query_args, EE_EVENT_QUEUE_BASE_URL ) . '"
			>
				<span class="dashicons dashicons-minus" ></span >
			</a >
			<a	title="' . esc_html__(/** @lang text */'delete item from event cart', 'event_espresso' ) . '"
					class="event-cart-delete-ticket-button event-cart-button event-cart-icon-button button' . $required . '"
					rel="' . $line_item->code() . '"
				    data-target="event-cart-update-txt-qty-' . $line_item->code() . '"
					href="' . add_query_arg( array(
						'event_cart' => 'delete_ticket',
						'ticket'      => $ticket->ID(),
						'line_item'   => $line_item->code()
					), EE_EVENT_QUEUE_BASE_URL ) . '"
			>
				<span class="dashicons dashicons-trash"></span>
			</a>
		</span >
	</div>
';
	}



	/**
	 *    _item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-item-row-' . $line_item->ID(), 'event-cart-item-row item' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? '<span class="line-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>'  : '';
		$name_and_desc = $line_item->is_taxable() ? $name_and_desc . ' * ' : $name_and_desc;
		$name_and_desc = apply_filters(
			'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___item_row__name_and_desc',
			$name_and_desc,
			$line_item,
			$options
		);

		// name td
		$html .= EEH_HTML::td( $name_and_desc );
		// amount
		if ( $line_item->percent() ) {
			// percent td
			$html .= EEH_HTML::td( $line_item->percent() . ' %', '',  'jst-rght' );
		} else {
			// price td
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
		}
		// quantity td
		$html .= EEH_HTML::td( $line_item->quantity(), '', 'jst-cntr' );
		// total td
		$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}




	/**
	 * 	_empty_msg_row
	 *
	 * @return string
	 */
	private function _empty_msg_row() {
		// start of row
		$html = EEH_HTML::tr( '', '', 'event-cart-tbl-row-empty-msg item' );
		// empty td
		$html .= EEH_HTML::td(
			apply_filters(
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___empty_msg_row',
                esc_html__('The Event Cart is empty', 'event_espresso' )
			),
			'',  '', '', ' colspan="4"'
		);
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



    /**
     *    _sub_item_row
     *
     * @param EE_Line_Item $line_item
     * @param array        $options
     * @return mixed
     * @throws \EE_Error
     */
	private function _sub_item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-sub-item-row-' . $line_item->ID(), 'event-cart-sub-item-row item sub-item-row' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? '<span class="line-sub-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>' : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item', '', ' colspan="2"' );
		// discount/surcharge td
		if ( $line_item->is_percent() ) {
			$html .= EEH_HTML::td( $line_item->percent() . '%' );
		} else {
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
		}
		// total td
		$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_tax_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _tax_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-tax-row-' . $line_item->ID(), 'event-cart-tax-row item sub-item tax-total' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= '<span class="smaller-text" style="margin:0 0 0 2em;">' . esc_html__( ' * taxable items', 'event_espresso' ) . '</span>';
		$name_and_desc .= $options['show_desc'] ? '<br/>' . $line_item->desc() : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item' );
		// percent td
		$html .= EEH_HTML::td( $line_item->percent() . '%', '', 'jst-rght' );
		// empty td (price)
		$html .= EEH_HTML::td( EEH_HTML::nbsp() );
		// total td
		$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 *    _sub_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @param int          $qty
	 * @return mixed
	 */
	private function _sub_total_row( EE_Line_Item $line_item, $text = '', $qty =0 ) {
		return $this->_total_row( $line_item, $text, $qty);
	}



	/**
	 *    _total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string $text
	 * @return mixed
	 */
	private function _total_tax_row( EE_Line_Item $line_item, $text = '' ) {
		$html = '';
		if ( $line_item->total() ) {
			// start of row
			$html = EEH_HTML::tr( '', 'event-cart-total-tax-row-' . $line_item->ID(), 'event-cart-total-tax-row total_tr' );
			// total td
			$html .= EEH_HTML::td( $text, '', 'total_currency total jst-rght' );
			$html .= EEH_HTML::td( '', '', 'total jst-cntr' );
			// empty td (price)
			$html .= EEH_HTML::td( EEH_HTML::nbsp() );
			// total td
			$html .= EEH_HTML::td( $line_item->total_no_code(), '', 'total jst-rght' );
			// end of row
			$html .= EEH_HTML::trx();
		}
		return $html;
	}



	/**
     *    _total_row
     *
     * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @param int|string   $total_items
	 * @return mixed
	 */
	private function _total_row( EE_Line_Item $line_item, $text = '', $total_items = 0 ) {
		//EE_Registry::instance()->load_helper('Money');
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-total-row-' . $line_item->ID(), 'event-cart-total-row-' . $line_item->type() . ' event-cart-total-row total_tr' );
		// total td
		$html .= EEH_HTML::td( 
			EEH_HTML::strong( $text ),
			'',
			'total_currency total jst-rght',
			'',
			apply_filters(
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___total_row__other_attributes',
				' colspan="2"',
				$line_item
			)
		);
		// total qty
		$total_items = $total_items ? $total_items : '';
		$html .= EEH_HTML::td( EEH_HTML::strong( '<span class="total">' . $total_items . '</span>' ), '', 'total jst-cntr' );
		// total td
		$html .= EEH_HTML::td( EEH_HTML::strong( $line_item->total_no_code() ), '',  'total jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



    public function after_event_cart_table()
    {
        echo EEH_HTML::p(
            esc_html__(' ** indicates an item that is required and must be purchased.', 'event_espresso'),
            '', 'event-cart-required-items-notice important-notice'
        );
	}



	/**
	 * 	_separator_row
	 *
	 * @param array        $options
	 * @return mixed
	 */
//	private function _separator_row( $options = array() ) {
//		// start of row
//		$html = EEH_HTML::tr( EEH_HTML::td( '<hr>', '',  '',  '',  ' colspan="4"' ));
//		return $html;
//	}


}
// End of file EE_Event_Cart_Line_Item_Display_Strategy.php
// Location: /EE_Event_Cart_Line_Item_Display_Strategy.php