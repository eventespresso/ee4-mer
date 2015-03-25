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
 * @since		 	   $VID:$
 *
 */

class EE_Event_Cart_Line_Item_Display_Strategy implements EEI_Line_Item_Display {

	private $_show_taxes = FALSE;
	private $_events = array();

	/**
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	public function display_line_item( EE_Line_Item $line_item, $options = array() ) {

		EE_Registry::instance()->load_helper( 'HTML' );

		$html = '';
		// set some default options and merge with incoming
		$default_options = array(
			'show_desc' => TRUE,  // 	TRUE 		FALSE
			'odd' => FALSE,
			'event_count' => 0,
		);
		$options = array_merge( $default_options, (array)$options );

		switch( $line_item->type() ) {

			case EEM_Line_Item::type_line_item:
				// item row
				if ( $line_item->OBJ_type() == 'Ticket' ) {
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
				if ( $line_item->OBJ_type() == 'Event' ) {
					if ( ! isset( $this->_events[ $line_item->OBJ_ID() ] ) ) {
						$html .= $this->_event_row( $line_item );
					}
				}
				$count = 0;
				// loop thru children
				foreach ( $line_item->children() as $child_line_item ) {
					// recursively feed children back into this method
					$html .= $this->display_line_item( $child_line_item, $options );
					$count++;
				}
				// only display subtotal if there are multiple child line items
				$html .= $count > 1 ? $this->_sub_total_row( $line_item, __( 'Subtotal', 'event_espresso' ), $options ) : '';
				break;

			case EEM_Line_Item::type_tax:
				if ( $this->_show_taxes ) {
					$html .= $this->_tax_row( $line_item, $options );
				}
				break;

			case EEM_Line_Item::type_tax_sub_total:
				if ( $this->_show_taxes ) {
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item, $options );
					}
					$html .= $this->_total_row( $line_item, __('Tax Total', 'event_espresso'), $options );
				}
				break;

			case EEM_Line_Item::type_total:

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
				$html .= $this->_total_row( $line_item, __('Total', 'event_espresso'), $options );
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
		$html = EEH_HTML::tr( '', 'event-queue-total-row', 'total_tr odd' );
		// event name td
		$html .= EEH_HTML::td( EEH_HTML::strong( $line_item->desc() ), '', 'event-header', '', ' colspan="5"' );
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
			// start of row
			$html = '';
			$row_class = $options['odd'] ? 'item odd' : 'item';
			$html .= EEH_HTML::tr( '', 'event-queue-item-row-' . $line_item->code(), $row_class );
			// name && desc
			$name_and_desc = $line_item->name();
			$name_and_desc .= $options['show_desc'] ? '<span class="line-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>'  : '';
			// name td
			$html .= EEH_HTML::td( $name_and_desc, '', 'ticket info', '', ' colspan="2"' );
			// price td
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
			// quantity td
			$html .= EEH_HTML::td( $this->_ticket_qty_input( $line_item, $ticket ), '',  'jst-rght' );
			// total td
			$total = $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
			$this->_show_taxes = $line_item->is_taxable() ? TRUE : $this->_show_taxes;
			$html .= EEH_HTML::td( $total, '',  'jst-rght' );
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
	 * @return mixed
	 */
	private function _ticket_qty_input( EE_Line_Item $line_item, EE_Ticket $ticket ) {
		if ( $ticket->remaining() - $line_item->quantity() ) {
			$disabled = '';
			$disabled_class = '';
			$disabled_style = '';
			$disabled_title = __( 'add one ticket', 'event_espresso' );
			$query_args = array( 'event_queue' => 'add_ticket', 'ticket' => $ticket->ID(), 'line_item' => $line_item->code() );
		} else {
			$disabled = ' disabled="disabled"';
			$disabled_class = ' disabled';
			$disabled_style = ' style="background-color:#e8e8e8;"';
			$disabled_title = __( 'there are no more tickets available', 'event_espresso' );
			$query_args = array( 'event_queue' => 'view' );
		}
		return '
	<div class="event-queue-ticket-qty-dv">
		<input type="text"
					id="event-queue-update-txt-qty-' . $line_item->code() . '"
					class="event-queue-update-txt-qty ' . $disabled_class . '"
					name="event_queue_update_txt_qty[' . $ticket->ID() . '][' . $line_item->code() . ']"
					rel="' . $line_item->code() . '"
					value="' . $line_item->quantity() . '"
					' . $disabled . '
					size="3"
		/>
		<span class="event-queue-update-buttons" >
			<a	title = "' . $disabled_title . '"
				class="event-queue-add-ticket-button event-queue-button event-queue-icon-button button' . $disabled_class . '"
				rel = "' . $line_item->code() . '"
				href = "' . add_query_arg( $query_args, EE_EVENT_QUEUE_BASE_URL ) . '"
				' . $disabled_style . '
				>
				<span class="dashicons dashicons-plus" ></span >
			</a >
			<a	title = "' . __( 'remove one ticket', 'event_espresso' ) . '"
					class="event-queue-remove-ticket-button event-queue-button event-queue-icon-button button"
					rel = "' . $line_item->code() . '"
					href = "' . add_query_arg( array(
			'event_queue' => 'remove_ticket',
			'ticket'      => $ticket->ID(),
			'line_item'   => $line_item->code()
		), EE_EVENT_QUEUE_BASE_URL ) . '"
						>
				<span class="dashicons dashicons-minus" ></span >
			</a >
			<a	title="' . __( 'delete ticket from event queue', 'event_espresso' ) . '"
					class="event-queue-delete-ticket-button event-queue-button event-queue-icon-button button"
					rel="' . $line_item->code() . '"
					href="' . add_query_arg( array(
			'event_queue' => 'delete_ticket',
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
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$row_class = $options['odd'] ? 'item odd' : 'item';
		$html = EEH_HTML::tr( '', 'event-queue-item-row-' . $line_item->code(), $row_class );
		// empty td
		$html .= EEH_HTML::td( EEH_HTML::nbsp() );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? '<span class="line-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>'  : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc );
		if ( $line_item->percent() ) {
			// percent td
			$html .= EEH_HTML::td( $line_item->percent() . ' %', '',  'jst-rght' );
		} else {
			// price td
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
		}
		// quantity td
		$html .= EEH_HTML::td( $line_item->quantity(), '',  'jst-rght' );
		// total td
		$total = $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
		$this->_show_taxes = $line_item->is_taxable() ? TRUE : $this->_show_taxes;
		$html .= EEH_HTML::td( $total, '',  'jst-rght' );
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
		$html = EEH_HTML::tr( '', '', 'event-queue-tbl-row-empty-msg item' );
		// empty td
		$html .= EEH_HTML::td(
			apply_filters(
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___empty_msg_row',
				__('The Event Cart is empty', 'event_espresso' )
			),
			'',  '', '', ' colspan="5"'
		);
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}




	/**
	 * 	_sub_item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param array        $options
	 * @return mixed
	 */
	private function _sub_item_row( EE_Line_Item $line_item, $options = array() ) {
		// start of row
		$html = EEH_HTML::tr( '', '', 'event-queue-sub-item-row item sub-item-row' );
		// empty td
		$html .= EEH_HTML::td( EEH_HTML::nbsp() );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= $options['show_desc'] ? '<span class="line-sub-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>' : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item' );
		// discount/surcharge td
		if ( $line_item->is_percent() ) {
			$html .= EEH_HTML::td( $line_item->percent() . '%' );
		} else {
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
		}
		// empty td
		$html .= EEH_HTML::td( EEH_HTML::nbsp() );
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
		$html = EEH_HTML::tr( '', '', 'event-queue-tax-row item sub-item tax-total' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= '<span class="tiny-text" style="margin:0 0 0 2em;">' . __( ' * taxable items', 'event_espresso' ) . '</span>';
		$name_and_desc .= $options['show_desc'] ? '<br/>' . $line_item->desc() : '';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item', '', ' colspan="2"' );
		// percent td
		$html .= EEH_HTML::td( $line_item->percent() . '%', '', 'jst-rght' );
		// empty td
		$html .= EEH_HTML::td( EEH_HTML::nbsp() );
		// total td
		$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @param array        $options
	 * @return mixed
	 */
	private function _sub_total_row( EE_Line_Item $line_item, $text = '', $options = array() ) {
		if ( $line_item->total() && $options['event_count'] > 1 ) {
			return $this->_total_row( $line_item, $text, $options);
		}
		return '';
	}



	/**
	 * 	_total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string       $text
	 * @return mixed
	 */
	private function _total_row( EE_Line_Item $line_item, $text = '' ) {
		//EE_Registry::instance()->load_helper('Money');
		//if ( )
		// start of row
		$html = EEH_HTML::tr( '', 'event-queue-total-row', 'total_tr odd' );
		// total td
		$html .= EEH_HTML::td( EEH_HTML::strong( $line_item->desc() . ' ' . $text ), '',  'total_currency total jst-rght',
			'', ' colspan="3"' );
		// total qty
		$total_items = EE_Registry::instance()->CART->all_ticket_quantity_count();
		//$qty = sprintf(  _n( '%s ticket  ', '%s tickets ', $total_items, 'event_espresso' ), '<span class="total">' . $total_items. '</span>' );
		$html .= EEH_HTML::td( EEH_HTML::strong( '<span class="total">' . $total_items . '</span>' ), '',  'total
		jst-cntr' );
		// total td
		$html .= EEH_HTML::td( EEH_HTML::strong( $line_item->total_no_code() ), '',  'total jst-rght' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
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