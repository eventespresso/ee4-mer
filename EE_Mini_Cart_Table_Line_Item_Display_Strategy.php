<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
 /**
 *
 * Class EE_Mini_Cart_Table_Line_Item_Display_Strategy
 *
 * Description
 *
 * @package         Event Espresso
 * @subpackage    core
 * @author				Brent Christensen
 * 
 *
 */

class EE_Mini_Cart_Table_Line_Item_Display_Strategy implements EEI_Line_Item_Display {

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

		switch( $line_item->type() ) {

			case EEM_Line_Item::type_line_item:
				// item row
				if ( $line_item->OBJ_type() == 'Ticket' ) {
					$html .= $this->_ticket_row( $line_item );
				} else {
					$html .= $this->_item_row( $line_item );
				}
				// got any kids?
				foreach( $line_item->children() as $child_line_item ) {
					$this->display_line_item( $child_line_item );
				}
				break;

			case EEM_Line_Item::type_sub_line_item:
				$html .= $this->_sub_item_row( $line_item );
				break;

			case EEM_Line_Item::type_sub_total:
				$text = __( 'Sub-Total', 'event_espresso' );
				if ( $line_item->OBJ_type() == 'Event' ) {
					if ( ! isset( $this->_events[ $line_item->OBJ_ID() ] ) ) {
						$html .= $this->_event_row( $line_item );
						$text = __( 'Event Sub-Total', 'event_espresso' );
					}
				}
				$count = 0;
				// loop thru children
				foreach ( $line_item->children() as $child_line_item ) {
					// recursively feed children back into this method
					$html .= $this->display_line_item( $child_line_item );
					$count++;
				}
				// only display subtotal if there are multiple child line items
				$html .= $count > 1 ? $this->_sub_total_row( $line_item, $text ) : '';
				break;

			case EEM_Line_Item::type_tax:
				if ( $this->_show_taxes ) {
					$html .= $this->_tax_row( $line_item );
				}
				break;

			case EEM_Line_Item::type_tax_sub_total:
				if ( $this->_show_taxes ) {
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item );
					}
					$html .= $this->_total_row( $line_item, __('Tax Total', 'event_espresso'), true );
				}
				break;

			case EEM_Line_Item::type_total:

				if ( count( $line_item->get_items() ) ) {
					// loop thru children
					foreach( $line_item->children() as $child_line_item ) {
						// recursively feed children back into this method
						$html .= $this->display_line_item( $child_line_item );
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
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-item-row-' . $line_item->ID(), 'event_tr odd' );
		// event name td
		$html .= EEH_HTML::td( EEH_HTML::strong( $line_item->name() ), '', 'event-header', '', ' colspan="4"' );
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}



	/**
	 * 	_ticket_row
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _ticket_row( EE_Line_Item $line_item ) {
		$ticket = EEM_Ticket::instance()->get_one_by_ID( $line_item->OBJ_ID() );
		if ( $ticket instanceof EE_Ticket ) {
			// start of row
			$html = '';
			$html .= EEH_HTML::tr( '', 'event-cart-item-row-' . $line_item->code() );
			$this->_show_taxes = $line_item->is_taxable() ? true : $this->_show_taxes;
			$line_item_name = $line_item->is_taxable() ? $line_item->name() . ' * ' : $line_item->name();
			$line_item_name = apply_filters(
				'FHEE__EE_Mini_Cart_Table_Line_Item_Display_Strategy___ticket_row__line_item_name',
				$line_item_name,
				$line_item
			);

			// name td
			$html .= EEH_HTML::td( $line_item_name, '', 'ticket info' );
			// price td
			$html .= EEH_HTML::td( $line_item->unit_price_no_code(), '',  'jst-rght' );
			// quantity td
			$html .= EEH_HTML::td( $line_item->quantity(), '', 'mini-cart-tbl-qty-td jst-cntr' );
			// total td
			$html .= EEH_HTML::td( $line_item->total_no_code(), '',  'jst-rght' );
			// end of row
			$html .= EEH_HTML::trx();
			return $html;
		}
		return null;
	}



	/**
	 *    _item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _item_row( EE_Line_Item $line_item ) {
		// start of row
		$html = EEH_HTML::tr( '', 'event-cart-item-row-' . $line_item->code() );
		$name_and_desc = $line_item->name();
		$name_and_desc .= $line_item->desc() != '' ? '<span class="line-item-desc-spn smaller-text"> : ' . $line_item->desc() . '</span>' : '';
		$name_and_desc = $line_item->is_taxable() ? $name_and_desc . ' * ' : $name_and_desc;
		$name_and_desc = apply_filters(
			'FHEE__EE_Mini_Cart_Table_Line_Item_Display_Strategy___item_row__name_and_desc',
			$name_and_desc,
			$line_item
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
		$html .= EEH_HTML::td( $line_item->quantity(), '', 'mini-cart-tbl-qty-td jst-cntr' );
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
		$html = EEH_HTML::tr( '', '', 'event-cart-tbl-row-empty-msg item' );
		// empty td
		$html .= EEH_HTML::td(
			apply_filters(
				'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___empty_msg_row',
				__('The Event Cart is empty', 'event_espresso' )
			),
			'',  '', '', ' colspan="4"'
		);
		// end of row
		$html .= EEH_HTML::trx();
		return $html;
	}




	/**
	 * 	_sub_item_row
	 *
	 * @param EE_Line_Item $line_item
	 * @return mixed
	 */
	private function _sub_item_row( EE_Line_Item $line_item ) {
		// start of row
		$html = EEH_HTML::tr( '', '', 'event-cart-sub-item-row item sub-item-row' );
		// name td
		$html .= EEH_HTML::td( $line_item->name(), '',  'sub-item', '', ' colspan="2"' );
		// discount/surcharge td
		if ( $line_item->is_percent() ) {
			$html .= EEH_HTML::td( $line_item->percent() . '%', '', 'jst-rght' );
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
	 * @return mixed
	 */
	private function _tax_row( EE_Line_Item $line_item ) {
		$this->_tax_count++;
		// start of row
		$html = EEH_HTML::tr( '', '', 'event-cart-tax-row item sub-item tax-total' );
		// name && desc
		$name_and_desc = $line_item->name();
		$name_and_desc .= '<span class="tiny-text" style="margin:0 0 0 2em;">' . __( ' * taxable items', 'event_espresso' ) . '</span>';
		// name td
		$html .= EEH_HTML::td( $name_and_desc, '',  'sub-item' );
		// percent td
		$html .= EEH_HTML::td( $line_item->percent() . '%', '', 'mini-cart-tbl-qty-td jst-rght' );
		// empty td (price)
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
	 * @return mixed
	 */
	private function _sub_total_row( EE_Line_Item $line_item, $text = '' ) {
		$html = '';
		if ( $line_item->total() ) {
			// start of row
			$html = EEH_HTML::tr( '', '', 'total_tr odd' );
			$text = $line_item->code() == 'pre-tax-subtotal' ? EED_Multi_Event_Registration::$event_cart_name . ' ' . $text : $text;
				// total td
			$html .= EEH_HTML::td( $text, '', 'total_currency total jst-rght', '', ' colspan="3"' );
			// total td
			$total = $line_item->total();
			$html .= EEH_HTML::td( EEH_Template::format_currency( $total, false, false ), '', 'total jst-rght' );
			// end of row
			$html .= EEH_HTML::trx();
		}
		return $html;
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
		$html = EEH_HTML::tr( '', 'event-cart-total-row', 'total_tr odd' );
		// total td
		$html .= EEH_HTML::td(
			EEH_HTML::strong( EED_Multi_Event_Registration::$event_cart_name . ' ' . $line_item->desc() . ' ' . $text ),
			'',  'total_currency total jst-rght', '', ' colspan="2"'
		);
		// total qty
		$total_qty = $tax_total ? '' : EE_Registry::instance()->CART->all_ticket_quantity_count();
		$html .= EEH_HTML::td( EEH_HTML::strong( $total_qty ), '', 'mini-cart-tbl-qty-td total jst-cntr' );
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
// End of file EE_Mini_Cart_Table_Line_Item_Display_Strategy.php
// Location: /EE_Mini_Cart_Table_Line_Item_Display_Strategy.php