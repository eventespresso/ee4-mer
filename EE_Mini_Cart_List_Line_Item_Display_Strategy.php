<?php

/**
 * Class EE_Mini_Cart_List_Line_Item_Display_Strategy
 * Description
 *
 * @package             Event Espresso
 * @subpackage          core
 * @author              Brent Christensen
 */

class EE_Mini_Cart_List_Line_Item_Display_Strategy implements EEI_Line_Item_Display
{
    private int $_tax_count  = 0;

    private bool $_show_taxes = false;

    private array $_events     = [];


    /**
     * @param EE_Line_Item $line_item
     * @param array        $options
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function display_line_item(EE_Line_Item $line_item, $options = [])
    {
        EE_Registry::instance()->load_helper('HTML');

        $html = '';

        switch ($line_item->type()) {
            case EEM_Line_Item::type_line_item:
                // item row
                if ($line_item->OBJ_type() == 'Ticket') {
                    $html .= $this->_ticket_row($line_item);
                } else {
                    $html .= $this->_item_row($line_item);
                }
                // got any kids?
                foreach ($line_item->children() as $child_line_item) {
                    $this->display_line_item($child_line_item);
                }
                break;

            case EEM_Line_Item::type_sub_line_item:
                $html .= $this->_sub_item_row($line_item);
                break;

            case EEM_Line_Item::type_sub_total:
                if (
                    count($this->_events) > 1
                    && $line_item->OBJ_type() === 'Event'
                    && ! isset($this->_events[ $line_item->OBJ_ID() ])
                ) {
                    $html .= $this->_event_row($line_item);
                }
                $count = 0;
                // loop thru children
                foreach ($line_item->children() as $child_line_item) {
                    // recursively feed children back into this method
                    $html .= $this->display_line_item($child_line_item);
                    $count++;
                }
                // only display subtotal if there are multiple child line items
                $html .= $count > 1 ? $this->_sub_total_row($line_item, esc_html__('Subtotal', 'event_espresso')) : '';
                break;

            case EEM_Line_Item::type_tax:
                if ($this->_show_taxes) {
                    $html .= $this->_tax_row($line_item);
                }
                break;

            case EEM_Line_Item::type_tax_sub_total:
                if ($this->_show_taxes) {
                    // loop thru children
                    foreach ($line_item->children() as $child_line_item) {
                        // recursively feed children back into this method
                        $html .= $this->display_line_item($child_line_item);
                    }
                    $html .= $this->_total_row($line_item, esc_html__('Tax Total', 'event_espresso'), true);
                }
                break;

            case EEM_Line_Item::type_total:
                if (count($line_item->get_items())) {
                    // loop thru children
                    foreach ($line_item->children() as $child_line_item) {
                        // recursively feed children back into this method
                        $html .= $this->display_line_item($child_line_item);
                    }
                } else {
                    $html .= $this->_empty_msg_row();
                }
                $html .= $this->_total_row($line_item, esc_html__('Total', 'event_espresso'));
                break;
        }

        return $html;
    }


    /**
     *  _ticket_row
     *
     * @param EE_Line_Item $line_item
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _event_row(EE_Line_Item $line_item): string
    {
        $this->_events[ $line_item->OBJ_ID() ] = $line_item;
        // event name
        return EEH_HTML::li(
            EEH_HTML::strong($line_item->name()),
            '',
            'event-header event-cart-total'
        );
    }


    /**
     *  _ticket_row
     *
     * @param EE_Line_Item $line_item
     * @return string|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _ticket_row(EE_Line_Item $line_item): ?string
    {
        $ticket = EEM_Ticket::instance()->get_one_by_ID($line_item->OBJ_ID());
        if ($ticket instanceof EE_Ticket) {
            // start of row
            $content = $line_item->name();
            $content .= '<div style="margin:0 0 0 2em; text-align: right;">';
            $content .= ' ' . $line_item->quantity();
            $content .= _x(' x ', 'short form for times, for example: 2 x 4 = 8.', 'event_espresso');
            $content .= $line_item->unit_price_no_code() . ' = ';
            $content .= $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
            $content .= '</div>';
            // track taxes
            $this->_show_taxes = $line_item->is_taxable() ? true : $this->_show_taxes;
            return EEH_HTML::li($content, 'event-cart-ticket-list-' . $line_item->code());
        }
        return null;
    }


    /**
     *    _item_row
     *
     * @param EE_Line_Item $line_item
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _item_row(EE_Line_Item $line_item): string
    {
        // start of row
        $content = $line_item->name();
        if ($line_item->is_percent()) {
            $content .= ' ' . apply_filters('FHEE__format_percentage_value', $line_item->percent());
        } else {
            $content .= ' ' . $line_item->unit_price_no_code();
        }
        $content .= ' ' . $line_item->quantity() . ' = ';
        $content .= $line_item->is_taxable() ? $line_item->total_no_code() . '*' : $line_item->total_no_code();
        // track taxes
        $this->_show_taxes = $line_item->is_taxable() ? true : $this->_show_taxes;
        return EEH_HTML::li($content, 'event-cart-item-list-' . $line_item->code());
    }


    /**
     *  _empty_msg_row
     *
     * @return string
     */
    private function _empty_msg_row(): string
    {
        // empty td
        return EEH_HTML::li(
            apply_filters(
                'FHEE__EE_Event_Cart_Line_Item_Display_Strategy___empty_msg_row',
                esc_html__('The Event Cart is empty', 'event_espresso')
            ),
            '',
            'event-cart-list-empty-msg item'
        );
    }


    /**
     *  _sub_item_row
     *
     * @param EE_Line_Item $line_item
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _sub_item_row(EE_Line_Item $line_item): string
    {
        // start of row
        $html = EEH_HTML::li();
        $html .= EEH_HTML::ul('', '', 'event-cart-sub-item-list sub-item-list');
        // name td
        $content = $line_item->name();
        // discount/surcharge td
        if ($line_item->is_percent()) {
            $content .= ' ' . apply_filters('FHEE__format_percentage_value', $line_item->percent());
        } else {
            $content .= ' ' . $line_item->unit_price_no_code();
        }
        $content .= ' ' . $line_item->total_no_code();
        $html    .= EEH_HTML::li($content);
        // end of row
        $html .= EEH_HTML::ulx();
        $html .= EEH_HTML::lix();
        return $html;
    }


    /**
     *  _tax_row
     *
     * @param EE_Line_Item $line_item
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _tax_row(EE_Line_Item $line_item): string
    {
        $this->_tax_count++;
        // name && desc
        $content = $line_item->name();
        $content .= '<span class="tiny-text" style="margin:0 0 0 2em;">'
                    . esc_html__(' * taxable items', 'event_espresso')
                    . '</span>';
        // percent td
        $content .= ' ' . apply_filters('FHEE__format_percentage_value', $line_item->percent());
        // total td
        $content .= ' ' . $line_item->total_no_code();
        return EEH_HTML::li($content, '', 'event-cart-tax-list tax-list', 'text-align:right; width:100%;');
    }


    /**
     *  _total_row
     *
     * @param EE_Line_Item $line_item
     * @param string       $text
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _sub_total_row(EE_Line_Item $line_item, string $text = ''): string
    {
        if ($line_item->total() && count($this->_events) > 1) {
            return $this->_total_row($line_item, $text);
        }
        return '';
    }


    /**
     *    _total_row
     *
     * @param EE_Line_Item $line_item
     * @param string       $text
     * @param bool         $tax_total
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function _total_row(EE_Line_Item $line_item, string $text = '', bool $tax_total = false): string
    {
        if ($tax_total && $this->_tax_count < 2) {
            return '';
        }
        if ($line_item->OBJ_type() === 'Event') {
            return '';
        }
        // total td
        $content = EEH_HTML::strong($text);
        // total td
        $content .= ' ' . EEH_HTML::strong($line_item->total_no_code());
        return EEH_HTML::li($content, '', 'event-cart-total-list total-list', 'text-align:right; width:100%;');
    }
}
// End of file EE_Mini_Cart_List_Line_Item_Display_Strategy.php
// Location: /EE_Mini_Cart_List_Line_Item_Display_Strategy.php
