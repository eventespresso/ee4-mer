<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
 /**
 *
 * Class EE_Mini_Cart_List_Line_Item_Display_Strategy
 *
 * Description
 *
 * @package         Event Espresso
 * @subpackage    core
 * @author				Brent Christensen
 * @since		 	   $VID:$
 *
 */

class EE_Mini_Cart_List_Line_Item_Display_Strategy implements EEI_Line_Item_Display {

	private $_tax_count = 0;
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
			'odd' => FALSE
		);
		$options = array_merge( $default_options, (array)$options );
		printr( $options, '$options', __FILE__, __LINE__ );
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
				$html .= $this->_sub_item_row( $line_item );
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
					$html .= $this->_total_row( $line_item, __('Tax Total', 'event_espresso'), true );
				}
				break;

			case EEM_Line_Item::type_total:

				if ( count( $line_item->get_items() ) ) {
					$options['event_count'] = count( EEH_Line_Item::get_line_items_of_object_type( $line_item, 'Event' ) );
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item, $options );
					}
				} else {
					$html .= $this->_empty_msg_row();
				}
				$html .= $this->_total_row( $line_item, __('Total', 'event_espresso') );
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
		// event name
		return EEH_HTML::li(
			EEH_HTML::strong( $line_item->desc() ),
			'', 'event-header event-queue-total'
		);
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
			$html = EEH_HTML::li();
			$html .= EEH_HTML::ul( '', 'event-queue-ticket-list-' . $line_item->code() );
			$content = $line_item->name();
			$content .= ' ' . $line_item->quantity();
			$content .= _x( ' x ', 'short form for times, for example: 2 x 4 = 8.', 'event_espresso' );
			$content .= $line_item->unit_price_no_code();
			$content .= ' ' . $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
			$html .= EEH_HTML::li( $content );
			// track taxes
			$this->_show_taxes = $line_item->is_taxable() ? true : $this->_show_taxes;
			// end of row
			$html .= EEH_HTML::ulx();
			$html .= EEH_HTML::lix();
			return $html;
		}
		return null;
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
		$html = EEH_HTML::li();
		$html .= EEH_HTML::ul( '', 'event-queue-item-list-' . $line_item->code() );
		$content = $line_item->name();
		if ( $line_item->percent() ) {
			$content .= ' ' . $line_item->percent() . ' %';
		} else {
			$content .= ' ' . $line_item->unit_price_no_code();
		}
		$content .= ' ' . $line_item->quantity();
		$content .= ' ' . $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
		$html .= EEH_HTML::li( $content );
		// track taxes
		$this->_show_taxes = $line_item->is_taxable() ? true : $this->_show_taxes;
		// end of row
		$html .= EEH_HTML::ulx();
		$html .= EEH_HTML::lix();
		return $html;
	}




	/**
	 * 	_empty_msg_row
	 *
	 * @return string
	 */
	private function _empty_msg_row() {
		// empty td
		return EEH_HTML::li(
			apply_filters(
				'FHEE__EE_Event_Queue_Line_Item_Display_Strategy___empty_msg_row',
				__('The Event Queue is empty', 'event_espresso' )
			),
			'',  'event-queue-list-empty-msg item'
		);
	}




	/**
	 * 	_sub_item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _sub_item_row( EE_Line_Item $line_item ) {
		// start of row
		$html = EEH_HTML::li();
		$html = EEH_HTML::ul( '', '', 'event-queue-sub-item-list item sub-item-list' );
		// name td
		$html .= EEH_HTML::li( $line_item->name(), '',  'sub-item', '', ' colspan="2"' );
		// discount/surcharge td
		if ( $line_item->is_percent() ) {
			$html .= EEH_HTML::li( $line_item->percent() . '%', '', 'jst-rght' );
		} else {
			$html .= EEH_HTML::li( $line_item->unit_price_no_code(), '',  'jst-rght' );
		}
		// total td
		$html .= EEH_HTML::li( $line_item->total_no_code(), '',  'jst-rght' );
		// end of row
		$html .= EEH_HTML::ulx();
		$html .= EEH_HTML::lix();
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
		$this->_tax_count++;
		// start of row
		$html = EEH_HTML::tr( '', '', 'event-queue-tax-row item sub-item tax-total' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= '<span class="tiny-text" style="margin:0 0 0 2em;">' . __( ' * taxable items', 'event_espresso' ) . '</span>';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item', '', ' colspan="2"' );
		// percent td
		$html .= EEH_HTML::td( $line_item->percent() . '%', '', 'mini-cart-tbl-qty-td jst-rght' );
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
			return $this->_total_row( $line_item, $text );
		}
		return '';
	}



	/**
	 *    _total_row
	 *
	 * @param EE_Line_Item $line_item
	 * @param string $text
	 * @param bool $tax_total
	 * @return mixed
	 */
	private function _total_row( EE_Line_Item $line_item, $text = '', $tax_total = false ) {
		if ( $tax_total && $this->_tax_count < 2 ) {
			return '';
		}
		//EE_Registry::instance()->load_helper('Money');
		//if ( )
		// start of row
		$html = EEH_HTML::tr( '', 'event-queue-total-row', 'total_tr odd' );
		// total td
		$html .= EEH_HTML::td(
			EEH_HTML::strong( $line_item->desc() . ' ' . $text ), '',  'total_currency total jst-rght', '', ' colspan="2"'
		);
		// total qty
		$total_qty = $tax_total ? '' : EE_Registry::instance()->CART->all_ticket_quantity_count();
		$html .= EEH_HTML::td( EEH_HTML::strong( $total_qty ), '',  'mini-cart-tbl-qty-td total jst-rght' );
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
// End of file EE_Mini_Cart_List_Line_Item_Display_Strategy.php
// Location: /EE_Mini_Cart_List_Line_Item_Display_Strategy.php