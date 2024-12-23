<?php

use EventEspresso\core\domain\entities\RegCode;
use EventEspresso\core\domain\entities\RegUrlLink;
use EventEspresso\core\domain\services\registration\CreateRegistrationService;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventEspresso\core\services\request\CurrentPage;
use EventEspresso\core\services\request\DataType;
use EventEspresso\core\services\request\Response;

/**
 * Multi_Event_Registration class
 *
 * @package     Multi Event Registration
 * @subpackage  espresso-multi-registration
 * @author      Brent Christensen
 * @method EED_Multi_Event_Registration EED_Module::get_instance($module_name = '')
 * @method EE_Multi_Event_Registration_Config config()
 */
class EED_Multi_Event_Registration extends EED_Module
{
    /**
     * Cart? Event Cart? Ticket Basket?
     *
     * @var string $event_cart_name
     */
    public static string $event_cart_name = '';

    /**
     * @var bool $is_ajax
     */
    private bool $is_ajax = false;


    /**
     * @var array holds the array of instantiated Admin route object indexed by route.
     */
    public static array $admin_route_objects = [];


    /**
     * @return EED_Multi_Event_Registration
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function instance(): EED_Multi_Event_Registration
    {
        return parent::get_instance(__CLASS__);
    }


    /**
     * @return string
     */
    public static function event_cart_name(): string
    {
        return self::$event_cart_name;
    }


    /**
     * set_hooks - for hooking into EE Core, other modules, etc
     *
     * @return    void
     */
    public static function set_hooks()
    {
        EED_Multi_Event_Registration::set_definitions();

        EED_Module::registerRoute('view', 'Multi_Event_Registration', 'view_event_cart', 'event_cart');
        EED_Module::registerRoute('update', 'Multi_Event_Registration', 'update_event_cart', 'event_cart');
        EED_Module::registerRoute('add_ticket', 'Multi_Event_Registration', 'add_ticket', 'event_cart');
        EED_Module::registerRoute('remove_ticket', 'Multi_Event_Registration', 'remove_ticket', 'event_cart');
        EED_Module::registerRoute('delete_ticket', 'Multi_Event_Registration', 'delete_ticket', 'event_cart');
        EED_Module::registerRoute('empty', 'Multi_Event_Registration', 'empty_event_cart', 'event_cart');

        add_action('wp_enqueue_scripts', ['EED_Multi_Event_Registration', 'enqueue_styles_and_scripts']);
        // don't empty cart
        add_filter('FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false');
        // show a View Cart button even if there are no tickets
        add_filter(
            'FHEE__EE_Ticket_Selector__display_ticket_selector_submit__no_tickets_but_display_register_now_button',
            ['EED_Multi_Event_Registration', 'no_tickets_but_display_register_now_button'],
            10,
            2
        );
        // process registration links
        add_filter(
            'FHEE__EE_Ticket_Selector__ticket_selector_form_open__html',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_form_html'],
            10,
            2
        );
        add_filter(
            'FHEE__EE_Ticket_Selector__display_ticket_selector_submit__btn_text',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_submit_button'],
            10,
            2
        );
        add_filter(
            'FHEE__EED_Ticket_Selector__proceed_to_registration_btn_txt',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_button_txt'],
            10,
            2
        );
        add_filter(
            'FHEE__EE_Ticket_Selector__display_view_details_btn__btn_text',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_button_txt'],
            10,
            2
        );
        add_filter(
            'FHEE__EED_Ticket_Selector__ticket_selector_iframe__css',
            ['EED_Multi_Event_Registration', 'style_sheet_URLs']
        );
        add_filter(
            'FHEE__EventEspresso_modules_events_archive_EventsArchiveIframe__display__js',
            ['EED_Multi_Event_Registration', 'javascript_URLs']
        );
        add_filter(
            'FHEE__EE_Ticket_Selector__process_ticket_selections__success_redirect_url',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_redirect_url'],
            10,
            2
        );
        add_filter(
            'FHEE__EED_Ticket_Selector__proceed_to_registration_btn_url',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_button_url'],
            10,
            2
        );
        add_filter(
            'FHEE__EE_Ticket_Selector__display_view_details_btn__btn_url',
            ['EED_Multi_Event_Registration', 'filter_ticket_selector_button_url'],
            10,
            2
        );
        // verify that SPCO registrations correspond to tickets in cart
        add_filter(
            'FHEE__EED_Single_Page_Checkout___initialize__checkout',
            ['EED_Multi_Event_Registration', 'verify_tickets_in_cart']
        );
        // redirect to event_cart
        add_action(
            'EED_Ticket_Selector__process_ticket_selections__before',
            ['EED_Multi_Event_Registration', 'redirect_to_event_cart']
        );
        add_filter(
            'FHEE__EE_SPCO_Reg_Step__reg_step_submit_button__sbmt_btn_html',
            ['EED_Multi_Event_Registration', 'return_to_event_cart_button'],
            10,
            2
        );
        // toggling reg status
        add_filter(
            'FHEE__EE_Registration_Processor__toggle_registration_status_if_no_monies_owing',
            ['EED_Multi_Event_Registration', 'toggle_registration_status_if_no_monies_owing'],
            10,
            2
        );
        // display errors
        add_action('wp_footer', ['EED_Multi_Event_Registration', 'cart_results_modal_div'], 1);
        // update cart in session
        add_action('shutdown', ['EED_Multi_Event_Registration', 'save_cart']);
    }


    /**
     * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @return    void
     */
    public static function set_hooks_admin()
    {
        EED_Multi_Event_Registration::set_definitions();
        // loads additional classes for modifying admin pages
        add_action('admin_init', ['EED_Multi_Event_Registration', 'route_admin_page_requests']);
        add_action(
            'EED_Ticket_Selector__process_ticket_selections__before',
            ['EED_Multi_Event_Registration', 'redirect_to_event_cart']
        );
        // process ticket selections
        add_action(
            'wp_ajax_espresso_process_ticket_selections',
            ['EED_Multi_Event_Registration', 'process_ticket_selections']
        );
        add_action(
            'wp_ajax_nopriv_espresso_process_ticket_selections',
            ['EED_Multi_Event_Registration', 'process_ticket_selections']
        );
        // don't empty cart
        add_filter('FHEE__EE_Ticket_Selector__process_ticket_selections__clear_session', '__return_false');
        // ajax add attendees
        add_action('wp_ajax_espresso_add_ticket_to_event_cart', ['EED_Multi_Event_Registration', 'ajax_add_ticket']);
        add_action(
            'wp_ajax_nopriv_espresso_add_ticket_to_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_add_ticket']
        );
        // ajax remove attendees
        add_action(
            'wp_ajax_espresso_remove_ticket_from_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_remove_ticket']
        );
        add_action(
            'wp_ajax_nopriv_espresso_remove_ticket_from_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_remove_ticket']
        );
        // ajax remove event
        add_action(
            'wp_ajax_espresso_delete_ticket_from_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_delete_ticket']
        );
        add_action(
            'wp_ajax_nopriv_espresso_delete_ticket_from_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_delete_ticket']
        );
        // ajax remove event
        add_action('wp_ajax_espresso_empty_event_cart', ['EED_Multi_Event_Registration', 'ajax_empty_event_cart']);
        add_action(
            'wp_ajax_nopriv_espresso_empty_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_empty_event_cart']
        );
        // ajax update event
        add_action('wp_ajax_espresso_view_event_cart', ['EED_Multi_Event_Registration', 'ajax_view_event_cart']);
        add_action('wp_ajax_nopriv_espresso_view_event_cart', ['EED_Multi_Event_Registration', 'ajax_view_event_cart']);
        // ajax update event
        add_action('wp_ajax_espresso_update_event_cart', ['EED_Multi_Event_Registration', 'ajax_update_event_cart']);
        add_action(
            'wp_ajax_nopriv_espresso_update_event_cart',
            ['EED_Multi_Event_Registration', 'ajax_update_event_cart']
        );
        // ajax available_spaces
        add_action(
            'wp_ajax_espresso_get_available_spaces',
            ['EED_Multi_Event_Registration', 'ajax_get_available_spaces']
        );
        add_action(
            'wp_ajax_nopriv_espresso_get_available_spaces',
            ['EED_Multi_Event_Registration', 'ajax_get_available_spaces']
        );
        // verify that SPCO registrations correspond to tickets in cart
        add_filter(
            'FHEE__EED_Single_Page_Checkout___initialize__checkout',
            ['EED_Multi_Event_Registration', 'verify_tickets_in_cart']
        );
        // toggling reg status
        add_filter(
            'FHEE__EE_Registration_Processor__toggle_registration_status_if_no_monies_owing',
            ['EED_Multi_Event_Registration', 'toggle_registration_status_if_no_monies_owing'],
            10,
            2
        );
        // prevent overloading cart
        add_filter(
            'FHEE__EE_Ticket_Selector___add_ticket_to_cart__allow_add_to_cart',
            ['EED_Multi_Event_Registration', 'allow_ticket_selector_add_to_cart'],
            10,
            3
        );
        add_filter(
            'FHEE__EE_Ticket_Selector___add_ticket_to_cart__allow_display_availability_error',
            ['EED_Multi_Event_Registration', 'display_availability_error']
        );
        add_filter(
            'FHEE__EE_SPCO_Reg_Step__reg_step_submit_button__sbmt_btn_html',
            ['EED_Multi_Event_Registration', 'return_to_event_cart_button'],
            10,
            2
        );
        // update cart in session
        add_action('shutdown', ['EED_Multi_Event_Registration', 'save_cart']);
    }


    /**
     * @return void
     */
    public static function set_definitions()
    {
        // base url for the site's registration page - additional url params will be added to this
        define('EE_EVENT_QUEUE_BASE_URL', EE_Registry::instance()->CFG->core->reg_page_url());
        define(
            'EE_EVENTS_LIST_URL',
            apply_filters(
                'FHEE__EED_Multi_Event_Registration__set_definitions__events_list_url',
                get_post_type_archive_link('espresso_events')
            )
        );
        EED_Multi_Event_Registration::$event_cart_name        = apply_filters(
            'FHEE__EED_Multi_Event_Registration__set_definitions__event_cart_name',
            esc_html__('Event Cart', 'event_espresso')
        );
        EE_Registry::$i18n_js_strings['iframe_tickets_added'] = sprintf(
            esc_html__('Success! Please click "View %s" to proceed with your registration.', 'event_espresso'),
            EED_Multi_Event_Registration::$event_cart_name
        );
    }


    /**
     * loads additional classes for modifying admin pages
     *
     * @return void
     */
    public static function route_admin_page_requests()
    {
        $route = self::getRequest()->getRequestParam('page');
        if (! empty($route)) {
            $sanitized_route = sanitize_title($route);
            // convert page=espresso_transactions into "Transactions"
            $page = ucwords(str_replace('espresso_', '', $sanitized_route));
            // then into "EE_MER_Transactions_Admin"
            $class_name = 'EE_MER_' . $page . '_Admin';
            // and then load that class if it exists
            if (class_exists($class_name)) {
                EED_Multi_Event_Registration::$admin_route_objects[ $sanitized_route ] = new $class_name();
            }
        }
    }


    /**
     * this configures this module to use the same config as the EE_Promotions class
     *
     * @return EE_Multi_Event_Registration_Config
     */
    public function set_config(): EE_Multi_Event_Registration_Config
    {
        $this->set_config_section('addons');
        $this->set_config_class('EE_Multi_Event_Registration_Config');
        $this->set_config_name('multi_event_registration');
        return $this->config();
    }


    /**
     * @return void
     */
    protected function init()
    {
        // stop SPCO from executing
        add_filter('FHEE__EED_Single_Page_Checkout__run', '__return_false');
        if (! defined('MER_ACTIVE')) {
            define('MER_ACTIVE', true);
        }
        // set MER active to TRUE
        add_filter('filter_hook_espresso_MER_active', '__return_true');
        $this->is_ajax = self::getRequest()->isFrontAjax();
        $this->translate_js_strings();
    }


    /**
     * load resources required to run MER
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function load_classes()
    {
        static $loaded = false;
        if (! $loaded) {
            EE_Registry::instance()->load_core('Cart');
            $loaded = true;
        }
    }


    /**
     * @return void
     */
    public function translate_js_strings()
    {
        EE_Registry::$i18n_js_strings['server_error']         =
            esc_html__(
                'An unknown error occurred on the server while attempting to process your request. Please refresh the page and try again or contact support.',
                'event_espresso'
            );
        EE_Registry::$i18n_js_strings['confirm_delete_state'] =
            esc_html__(
                "This item is required. Removing it will also remove any related items from the event cart!\nClick OK to continue or Cancel to keep this item.",
                'event_espresso'
            );
    }


    /**
     * mer_style_sheets
     *
     * @param array $style_sheet_URLs
     * @return array
     */
    public static function style_sheet_URLs(array $style_sheet_URLs = []): array
    {
        $style_sheet_URLs[] = EE_MER_URL . 'css' . DS . 'multi_event_registration.css';
        return $style_sheet_URLs;
    }


    /**
     * @param array $javascript_URLs
     * @return array
     */
    public static function javascript_URLs(array $javascript_URLs = []): array
    {
        $javascript_URLs[] = EE_MER_URL . 'scripts' . DS . 'multi_event_registration.js';
        return $javascript_URLs;
    }


    /**
     * Load the scripts and css
     *
     * @return    void
     */
    public static function enqueue_styles_and_scripts()
    {
        /** @var CurrentPage $current_page */
        $current_page = LoaderFactory::getLoader()->getShared(CurrentPage::class);
        // only load on our pages plz
        if ($current_page->isEspressoPage()) {
            // styles
            wp_register_style(
                'espresso_multi_event_registration',
                apply_filters(
                    'FHEE__EED_Multi_Event_Registration__enqueue_scripts__event_cart_css',
                    EE_MER_URL . 'css' . DS . 'multi_event_registration.css'
                ),
                ['espresso_default'],
                EE_MER_VERSION
            );
            wp_enqueue_style('espresso_multi_event_registration');
            // scripts
            wp_register_script(
                'espresso_multi_event_registration',
                EE_MER_URL . 'scripts' . DS . 'multi_event_registration.js',
                ['espresso_core'],
                EE_MER_VERSION,
                true
            );
            wp_enqueue_script('espresso_multi_event_registration');
        }
    }


    /**
     * run - initial module setup
     *
     * @param WP $WP
     * @return    void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function run($WP)
    {
        EED_Multi_Event_Registration::instance()->set_config();
    }




    // *******************************************************************************************************
    // *******************************************   EVENT LISTING   *******************************************
    // *******************************************************************************************************


    /**
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function no_tickets_but_display_register_now_button($display = false, $event = null): bool
    {
        // verify event
        if (! $event instanceof EE_Event) {
            if (WP_DEBUG) {
                EE_Error::add_error(
                    esc_html__('An invalid event object was received.', 'event_espresso'),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
            return false;
        }
        return EED_Multi_Event_Registration::has_tickets_in_cart($event);
    }


    /**
     * changes the default "Register Now" text based on event's inclusion in the cart
     *
     * @param string        $btn_text
     * @param EE_Event|null $event
     * @param bool          $tickets_in_cart
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function filter_ticket_selector_submit_button(
        string $btn_text = '',
        EE_Event $event = null,
        bool $tickets_in_cart = false
    ): string {
        // verify event
        if (! $event instanceof EE_Event && ! $tickets_in_cart) {
            if (WP_DEBUG) {
                EE_Error::add_error(
                    esc_html__('An invalid event object was received.', 'event_espresso'),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
            return $btn_text;
        }
        if (
            $event instanceof EE_Event
            && $event->external_url()
            && $event->external_url() !== get_permalink()
        ) {
            return $btn_text;
        } elseif ($tickets_in_cart || EED_Multi_Event_Registration::has_tickets_in_cart($event)) {
            $btn_text = sprintf(
                esc_html__('View %s', 'event_espresso'),
                EED_Multi_Event_Registration::$event_cart_name
            );
        } else {
            $btn_text = sprintf(
                esc_html__('Add to %s', 'event_espresso'),
                EED_Multi_Event_Registration::$event_cart_name
            );
        }
        return $btn_text;
    }


    /**
     * @param EE_Event $event
     * @return array
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function get_all_event_tickets(EE_Event $event): array
    {
        $tickets = [];
        // get active events
        foreach ($event->datetimes_ordered(false) as $datetime) {
            $datetime_tickets = $datetime->ticket_types_available_for_purchase();
            foreach ($datetime_tickets as $datetime_ticket) {
                if ($datetime_ticket instanceof EE_Ticket) {
                    $tickets[ $datetime_ticket->ID() ] = $datetime_ticket;
                }
            }
        }
        return $tickets;
    }


    /**
     * @param EE_Event|null $event
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function has_tickets_in_cart(EE_Event $event = null): bool
    {
        if (! $event instanceof EE_Event) {
            return false;
        }
        EED_Multi_Event_Registration::load_classes();
        $event_tickets   = EED_Multi_Event_Registration::get_all_event_tickets($event);
        $tickets_in_cart = EE_Registry::instance()->CART->get_tickets();
        foreach ($tickets_in_cart as $ticket_in_cart) {
            if (
                $ticket_in_cart instanceof EE_Line_Item
                && $ticket_in_cart->OBJ_type() === 'Ticket'
                && isset($event_tickets[ $ticket_in_cart->OBJ_ID() ])
            ) {
                return true;
            }
        }
        return false;
    }


    /**
     * adds a hidden input to the Ticket Selector form
     *
     * @param string        $html
     * @param EE_Event|null $event
     * @param bool          $tickets_in_cart
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function filter_ticket_selector_form_html(
        string $html = '',
        EE_Event $event = null,
        bool $tickets_in_cart = false
    ): string {
        if ($tickets_in_cart || EED_Multi_Event_Registration::has_tickets_in_cart($event)) {
            $html .= '<input type="hidden" value="view" name="event_cart">';
        }
        return $html;
    }


    /**
     * @param string   $button_url
     * @param EE_Event $event
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function filter_ticket_selector_button_url(string $button_url, EE_Event $event): string
    {
        return EED_Multi_Event_Registration::_filter_ticket_selector_button($event)
            ? add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL)
            : $button_url;
    }


    /**
     * If the Ticket Selector should be displayed
     * and the event is NOT sold out
     * (unless it's sold out because we just added the last tickets to the cart)
     * then change the submit button text to "View Event Cart"
     *
     * @param EE_Event $event
     * @return boolean
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function _filter_ticket_selector_button(EE_Event $event): bool
    {
        return $event->display_ticket_selector()
            && EE_Config::instance()->template_settings->EED_Events_Archive instanceof EE_Events_Archive_Config
            && EE_Config::instance()->template_settings->EED_Events_Archive->display_ticket_selector
            && ! (
                $event->is_sold_out()
                && ! EED_Multi_Event_Registration::has_tickets_in_cart($event)
            );
    }


    /**
     * @param string   $button_txt
     * @param EE_Event $event
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function filter_ticket_selector_button_txt(string $button_txt, EE_Event $event): string
    {
        return EED_Multi_Event_Registration::_filter_ticket_selector_button($event)
            ? sprintf(esc_html__('View %s', 'event_espresso'), EED_Multi_Event_Registration::$event_cart_name)
            : $button_txt;
    }


    /**
     * changes event list button URL based on tickets in cart
     *
     * @return    string
     */
    public static function filter_ticket_selector_redirect_url(): string
    {
        if (
            apply_filters(
                'FHEE__EED_Multi_Event_Registration__filter_ticket_selector_redirect_url__redirect_to_cart',
                false
            )
        ) {
            return add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL);
        }
        $request     = self::getRequest();
        $referer_uri = $request->getServerParam('HTTP_REFERER', '');
        if ($referer_uri === EE_EVENTS_LIST_URL) {
            return EE_EVENTS_LIST_URL;
        }
        $request_uri = $request->getServerParam('REQUEST_URI', '');
        if (basename($request_uri) != basename(EE_EVENTS_LIST_URL)) {
            return EE_EVENTS_LIST_URL . basename($request_uri);
        }
        return EE_EVENTS_LIST_URL;
    }


    /**
     * creates button for going back to the Event Cart
     *
     * @param string           $html
     * @param EE_SPCO_Reg_Step $reg_step
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function return_to_event_cart_button(string $html, EE_SPCO_Reg_Step $reg_step): string
    {
        // returning to SPCO ?
        if ($reg_step->checkout->revisit) {
            // no return to cart button for you!
            return $html;
        }
        // and if a payment has already been made and this isn't a revisit
        if (
            $reg_step->checkout->transaction instanceof EE_Transaction
            && self::getRequest()->getRequestParam('e_reg_url_link', '') === ''
        ) {
            $last_payment = $reg_step->checkout->transaction->last_payment();
            if (
                $last_payment instanceof EE_Payment
                && $last_payment->status() !== EEM_Payment::status_id_failed
                && $reg_step->checkout->transaction->paid() > 0
            ) {
                return $html;
            }
        }
        return '<a class="return-to-event-cart-mini-cart-lnk mini-cart-view-cart-lnk view-cart-lnk mini-cart-button hide-me-after-successful-payment-js button" href = "'
            . add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL)
            . '" ><span class="dashicons dashicons-cart" ></span >'
            . apply_filters(
                'FHEE__EED_Multi_Event_Registration__view_event_cart_btn_txt',
                sprintf(
                    esc_html__(
                        'return to %s',
                        'event_espresso'
                    ),
                    EED_Multi_Event_Registration::$event_cart_name
                )
            )
            . '</a >'
            . $html;
    }


    /**
     * @return    void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function redirect_to_event_cart()
    {
        // grab event ID from Ticket Selector
        $EVT_ID = self::getRequest()->getRequestParam('tkt-slctr-event-id', 0, DataType::INT);
        if ($EVT_ID) {
            // grab ticket quantity array
            $ticket_quantities = self::getRequest()->getRequestParam(
                'tkt-slctr-qty-' . $EVT_ID,
                [],
                DataType::INT,
                true
            );
            $ticket_quantities = is_array($ticket_quantities) ? $ticket_quantities : [$ticket_quantities];
            foreach ($ticket_quantities as $ticket_quantity) {
                // if ANY qty was set, then don't redirect
                $ticket_quantity = absint($ticket_quantity);
                if ($ticket_quantity == 1) {
                    $event = EEM_Event::instance()->get_one_by_ID($EVT_ID);
                    if ($event instanceof EE_Event) {
                        if (
                            $event->additional_limit() == 1
                            && EED_Multi_Event_Registration::instance()
                                                           ->has_tickets_in_cart($event)
                        ) {
                            continue;
                        } else {
                            return;
                        }
                    }
                } elseif ($ticket_quantity > 1) {
                    return;
                }
            }
        }
        $redirect_url = add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL);
        if (self::getRequest()->getRequestParam('event_cart') === 'view') {
            if (self::getRequest()->isFrontAjax()) {
                // just send the ajax
                echo json_encode(
                    array_merge(
                        EE_Error::get_notices(false),
                        ['redirect_url' => $redirect_url]
                    )
                );
            } else {
                wp_safe_redirect($redirect_url);
            }
            exit();
        }
    }


    /**
     * @return    void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function process_ticket_selections()
    {
        // echo "\n\n " . __LINE__ . ") " . __METHOD__ . "() <br />";
        $response = ['tickets_added' => false];
        if (EED_Ticket_Selector::instance()->process_ticket_selections()) {
            $EVT_ID = self::getRequest()->getRequestParam('tkt-slctr-event-id', 0, DataType::INT);
            // radio buttons send ticket info as a string like: "TKT_ID-QTY"
            $ticket_string = self::getRequest()->getRequestParam('tkt-slctr-qty-' . $EVT_ID);
            if ($ticket_string) {
                $tickets = explode('-', $ticket_string);
                array_shift($tickets);
            } else {
                // ticket qty info is an array
                $tickets = self::getRequest()->getRequestParam(
                    'tkt-slctr-qty-' . $EVT_ID,
                    [],
                    DataType::INT,
                    true
                );
            }
            $ticket_count = 0;
            foreach ($tickets as $quantity) {
                $ticket_count += $quantity;
            }
            $response = [
                'tickets_added' => true,
                'ticket_count'  => $ticket_count,
                'btn_id'        => "#ticket-selector-submit-$EVT_ID-btn",
                'btn_txt'       => EED_Multi_Event_Registration::filter_ticket_selector_submit_button('', null, true),
                'form_html'     => EED_Multi_Event_Registration::filter_ticket_selector_form_html('', null, true),
                'mini_cart'     => EED_Multi_Event_Registration::get_mini_cart(),
                'cart_results'  => EED_Multi_Event_Registration::get_cart_results($ticket_count),
            ];
        }
        // $notices = EE_Error::get_notices( false );
        // echo "\n notices: ";
        // var_dump( $notices );
        // just send the ajax
        echo json_encode(
            array_merge(
                EE_Error::get_notices(false),
                $response
            )
        );
        // to be... or...
        die();
    }


    /**
     * @param int $ticket_count
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function get_cart_results(int $ticket_count = 0): string
    {
        // total tickets in cart
        $total_tickets = EE_Registry::instance()->CART->all_ticket_quantity_count();
        // what page the user is currently on
        $referer_uri = isset($_SERVER['HTTP_REFERER']) ? basename($_SERVER['HTTP_REFERER']) : '';
        $term_exists = term_exists($referer_uri, 'espresso_event_categories');
        if (isset($term_exists['term_id'])) {
            $term_id     = intval($term_exists['term_id']);
            $return_url  = get_term_link($term_id, 'espresso_event_categories');
            $close_modal = ' close-modal-js';
            if ($return_url != wp_get_referer() || is_wp_error($return_url)) {
                $return_url  = EE_EVENTS_LIST_URL;
                $close_modal = '';
            }
        } elseif ($referer_uri == basename(EE_EVENTS_LIST_URL)) {
            $return_url  = EE_EVENTS_LIST_URL;
            $close_modal = ' close-modal-js';
        } else {
            $return_url  = EE_EVENTS_LIST_URL;
            $close_modal = '';
        }
        $template_args = [
            'results'             => apply_filters(
                'FHEE__EED_Multi_Event_Registration__get_cart_results_results_message',
                sprintf(
                    esc_html(
                        _n(
                            '1 item was successfully added for this event.',
                            '%1$s items were successfully added for this event.',
                            $ticket_count,
                            'event_espresso'
                        )
                    ),
                    $ticket_count
                ),
                $ticket_count
            ),
            'current_cart'        => apply_filters(
                'FHEE__EED_Multi_Event_Registration__get_cart_results_current_cart_message',
                sprintf(
                    esc_html(
                        _n(
                            'There is currently 1 item in the %2$s.',
                            'There are currently %1$d items in the %2$s.',
                            $total_tickets,
                            'event_espresso'
                        )
                    ),
                    $total_tickets,
                    EED_Multi_Event_Registration::event_cart_name()
                ),
                $total_tickets
            ),
            'event_cart_name'     => EED_Multi_Event_Registration::event_cart_name(),
            'return_url'          => $return_url,
            'register_url'        => EE_EVENT_QUEUE_BASE_URL,
            'view_event_cart_url' => add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL),
            'close_modal'         => $close_modal,
            'btn_class'           => apply_filters(
                'FHEE__EED_Multi_Event_Registration__event_cart_template__btn_class',
                ''
            ),
            'additional_info'     => apply_filters(
                'FHEE__EED_Multi_Event_Registration__event_cart_template__additional_info',
                ''
            ),
        ];
        return EEH_Template::display_template(
            EE_MER_PATH . 'templates' . DS . 'cart_results_modal_dialog.template.php',
            $template_args,
            true
        );
    }


    /**
     * @return    void
     */
    public static function cart_results_modal_div()
    {
        echo '<div id="cart-results-modal-wrap-dv" style="display: none;"></div>';
    }


    /**
     * @return    string
     * @throws EE_Error
     */
    public static function get_mini_cart(): string
    {
        global $wp_widget_factory;
        $mini_cart = $wp_widget_factory->widgets['EEW_Mini_Cart'];
        if ($mini_cart instanceof EEW_Mini_Cart) {
            $options = get_option($mini_cart->option_name);
            if (isset($options[ $mini_cart->number ], $options[ $mini_cart->number ]['template'])) {
                $template = $options[ $mini_cart->number ]['template'];
            } else {
                $template = '';
            }
            return $mini_cart->get_mini_cart($template);
        }
        return '';
    }



    // *******************************************************************************************************
    // ********************************************   EVENT QUEUE   ********************************************
    // *******************************************************************************************************


    /**
     * basically retrieves the URL for the event cart, and instructs browser to redirect
     *
     * @return void
     */
    public static function ajax_view_event_cart()
    {
        // just send the ajax
        echo json_encode(
            array_merge(
                EE_Error::get_notices(false),
                ['redirect_url' => add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL)]
            )
        );
        exit();
    }


    /**
     * load and display Event Cart contents prior to completing registration
     *
     * @return        void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function view_event_cart()
    {
        $this->init();
        // load classes
        EED_Multi_Event_Registration::load_classes();
        EE_Registry::instance()->load_helper('Template');
        // EE_Registry::instance()->CART->recalculate_all_cart_totals();
        $grand_total = EE_Registry::instance()->CART->get_grand_total();
        $grand_total->recalculate_total_including_taxes();

        // Set the SPCO_active filter to `false` to prevent assets that rely on single_page_checkout from loading.
        add_filter('EED_Single_Page_Checkout__SPCO_active', '__return_false', 20);

        // autoload Line_Item_Display classes
        $template_args['event_cart_heading'] = apply_filters(
            'FHEE__EED_Multi_Event_Registration__view_event_cart__event_cart_heading',
            EED_Multi_Event_Registration::$event_cart_name
        );
        $template_args['event_cart_name']    = EED_Multi_Event_Registration::$event_cart_name;
        $template_args['total_items']        = EE_Registry::instance()->CART->all_ticket_quantity_count();
        $template_args['event_cart']         = $this->_get_event_cart($grand_total);
        $template_args['reg_page_url']       = EE_EVENT_QUEUE_BASE_URL;
        $template_args['events_list_url']    = EE_EVENTS_LIST_URL;
        $template_args['add_ticket_url']     = add_query_arg(['event_cart' => 'add_ticket'], EE_EVENT_QUEUE_BASE_URL);
        $template_args['remove_ticket_url']  = add_query_arg(
            ['event_cart' => 'remove_ticket'],
            EE_EVENT_QUEUE_BASE_URL
        );
        $template_args['register_url']       = EE_EVENT_QUEUE_BASE_URL;
        $template_args['update_cart_url']    = add_query_arg(
            ['event_cart' => 'update_cart_url'],
            EE_EVENT_QUEUE_BASE_URL
        );
        $template_args['empty_cart_url']     = add_query_arg(['event_cart' => 'empty'], EE_EVENT_QUEUE_BASE_URL);
        $template_args['btn_class']          = apply_filters(
            'FHEE__EED_Multi_Event_Registration__event_cart_template__btn_class',
            ''
        );
        LoaderFactory::getLoader()->getShared(Response::class)->addOutput(
            EEH_Template::display_template(
                EE_MER_PATH . 'templates' . DS . 'event_cart.template.php',
                $template_args,
                true
            )
        );
    }


    /**
     * @param EE_Line_Item|null $line_item
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _get_event_cart(EE_Line_Item $line_item = null): string
    {
        $line_item = $line_item instanceof EE_Line_Item ? $line_item : EE_Registry::instance()->CART->get_grand_total();
        // autoload Line_Item_Display classes
        EEH_Autoloader::register_line_item_display_autoloaders();
        $Line_Item_Display = new EE_Line_Item_Display(
            'event_cart',
            'EE_Event_Cart_Line_Item_Display_Strategy'
        );
        return $Line_Item_Display->display_line_item($line_item);
    }


    /**
     * get the max number of additional tickets that can be purchased per registration for an event
     *
     * @param int $event_id
     * @return        int
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _get_additional_limit(int $event_id): int
    {
        do_action('AHEE_log', __FILE__, __FUNCTION__, '');
        $event = EEM_Event::instance()->get_one_by_ID($event_id);
        return $event instanceof EE_Event ? $event->additional_limit() : 0;
    }


    /**
     * increment or decrement a ticket's quantity in the event cart
     *
     * @return EE_Ticket|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _validate_request(): ?EE_Ticket
    {
        $this->init();
        EED_Multi_Event_Registration::load_classes();
        // check the request
        $request   = self::getRequest();
        $ticket_id = $request->getRequestParam('ticket', 0, DataType::INT);
        if (! $ticket_id) {
            $line_item_ticket_id = $request->getRequestParam('line_item');
            if (strpos($line_item_ticket_id, 'ticket-') !== false) {
                $ticket_id = (int) str_replace('ticket-', '', $line_item_ticket_id);
            }
        }
        if ($ticket_id) {
            return $this->get_ticket($ticket_id);
        }
        // no ticket or line item !?!?!
        EE_Error::add_error(
            sprintf(
                esc_html__(
                    'Either the ticket or %1$s line item was not specified or invalid, therefore the %1$s could not be updated. Please refresh the page and try again.',
                    'event_espresso'
                ),
                EED_Multi_Event_Registration::$event_cart_name
            ),
            __FILE__,
            __FUNCTION__,
            __LINE__
        );
        return null;
    }


    /**
     * retrieves a valid EE_Ticket from the db
     *
     * @param int $TKT_ID
     * @return EE_Ticket|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function get_ticket(int $TKT_ID = 0): ?EE_Ticket
    {
        $ticket = EEM_Ticket::instance()->get_one_by_ID(absint($TKT_ID));
        if ($ticket instanceof EE_Ticket) {
            return $ticket;
        } else {
            // no ticket found
            EE_Error::add_error(
                esc_html__(
                    'The Ticket information could not be retrieved from the database. Please refresh the page and try again.',
                    'event_espresso'
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
            return null;
        }
    }


    /**
     * call update_event_cart() via AJAX
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_update_event_cart()
    {
        EED_Multi_Event_Registration::instance()->update_event_cart();
    }


    /**
     * increment or decrement all ticket quantities in the event cart
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function update_event_cart()
    {
        $this->init();
        EED_Multi_Event_Registration::load_classes();
        $request           = self::getRequest();
        $ticket_quantities = $request->getRequestParam('event_cart_update_txt_qty', [], DataType::INT, true);
        foreach ($ticket_quantities as $TKT_ID => $ticket_quantity) {
            $ticket = $this->get_ticket($TKT_ID);
            if ($ticket instanceof EE_Ticket) {
                foreach ($ticket_quantity as $line_item_id => $quantity) {
                    $line_item = $this->get_line_item($line_item_id);
                    if ($this->can_purchase_ticket_quantity($ticket, $quantity, $line_item, 'update')) {
                        $line_item = $this->adjust_line_item_quantity($line_item, $quantity, 'update');
                        if ($line_item instanceof EE_Line_Item) {
                            $this->_adjust_ticket_reserves($ticket, $line_item->quantity() - $quantity);
                        }
                    } else {
                        break 2;
                    }
                }
            }
        }
        $this->send_ajax_response();
    }


    /**
     * call add_ticket() via AJAX
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_add_ticket()
    {
        EED_Multi_Event_Registration::instance()->add_ticket();
    }


    /**
     * increment or decrement a ticket's quantity in the event cart
     *
     * @param int $quantity
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function add_ticket(int $quantity = 1)
    {
        // check the request
        $ticket    = $this->_validate_request();
        $line_item = $this->get_line_item(self::getRequest()->getRequestParam('line_item'));

        if ($this->can_purchase_ticket_quantity($ticket, $quantity, $line_item)) {
            // you can DO IT !!!
            $line_item = $this->adjust_line_item_quantity($line_item, $quantity);
            if ($line_item instanceof EE_Line_Item) {
                $this->_adjust_ticket_reserves($ticket, $quantity);
            }
        }
        $this->send_ajax_response();
    }


    /**
     * increment or decrement a ticket's quantity in the event cart
     *
     * @param EE_Ticket $ticket
     * @param int       $quantity
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _adjust_ticket_reserves(EE_Ticket $ticket, int $quantity = 1)
    {
        if ($quantity > 0) {
            $ticket->increaseReserved($quantity);
        } else {
            $ticket->decreaseReserved($quantity);
        }
        $ticket->save();
    }


    /**
     * @param bool      $allow
     * @param EE_Ticket $ticket
     * @param int       $quantity
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function allow_ticket_selector_add_to_cart(bool $allow, EE_Ticket $ticket, int $quantity = 1): bool
    {
        // if already toggled to false by something else then don't bother processing
        if (filter_var($allow, FILTER_VALIDATE_BOOLEAN)) {
            $allow = EED_Multi_Event_Registration::instance()->can_purchase_ticket_quantity($ticket, $quantity);
        }
        return $allow;
    }


    /**
     * @param bool $allow
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function display_availability_error(bool $allow = true): bool
    {
        // if already toggled to false by something else then don't bother processing
        if (EE_Registry::instance()->CART->all_ticket_quantity_count()) {
            $allow = false;
            add_filter(
                'FHEE__EED_Multi_Event_Registration__event_cart_template__additional_info',
                function ($additional_info) {
                    return $additional_info . apply_filters(
                            'FHEE__EED_Multi_Event_Registration__display_availability_error__additional_info',
                            sprintf(
                                esc_html__(
                                    '%sYour request could not be completely fulfilled due to lack of availability.%s',
                                    'event_espresso'
                                ),
                                '<div class="important-notice small-text">',
                                '</div><br />'
                            )
                        );
                }
            );
        }
        return $allow;
    }


    /**
     * @param EE_Ticket|null    $ticket
     * @param int               $quantity
     * @param EE_Line_Item|null $line_item
     * @param string            $action
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function can_purchase_ticket_quantity(
        ?EE_Ticket $ticket = null,
        int $quantity = 1,
        ?EE_Line_Item $line_item = null,
        string $action = 'add'
    ): bool {
        // any tickets left at all?
        $tickets_remaining = $ticket instanceof EE_Ticket ? $ticket->remaining() : 0;
        if (! $tickets_remaining && $action !== 'remove') {
            // event is full
            EE_Error::add_error(
                esc_html__(
                    'We\'re sorry, but there are no available spaces left for this event. No additional attendees can be added.',
                    'event_espresso'
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
            return false;
        }
        $quantity = absint($quantity);
        // is there enough tickets left to satisfy request?
        if ($tickets_remaining < $quantity && $action !== 'remove') {
            // translate and possibly pluralize the error
            $limit_error_1 = esc_html(
                sprintf(
                    _n(
                        'You have attempted to purchase %d ticket.',
                        'You have attempted to purchase %d tickets.',
                        $quantity,
                        'event_espresso'
                    ),
                    $quantity
                )
            );
            $limit_error_2 = esc_html(
                sprintf(
                    _n(
                        'There is only %1$d ticket remaining for this event, therefore the total number of tickets you may purchase is %1$d.',
                        'There are only %1$d tickets remaining for this event, therefore the total number of tickets you may purchase is %1$d.',
                        $tickets_remaining,
                        'event_espresso'
                    ),
                    $tickets_remaining
                )
            );
            EE_Error::add_error($limit_error_1 . '<br/>' . $limit_error_2, __FILE__, __FUNCTION__, __LINE__);
            return false;
        }

        // is the quantity allowed based on ticket min & max?
        if ($line_item instanceof EE_Line_Item && $ticket instanceof EE_Ticket) {
            $new_quantity = $quantity;
            if ($action === 'add') {
                $new_quantity += $line_item->quantity();
            } elseif ($action === 'remove') {
                $new_quantity -= $line_item->quantity();
            }

            // can purchase more tickets based on ticket max?
            $ticket_maximum = $ticket->max();
            if ($new_quantity > $ticket_maximum) {
                // translate and possibly pluralize the error
                $max_error = esc_html(
                    sprintf(
                        _n(
                            'The registration limit for this ticket is %1$d ticket per transaction, therefore the total number of tickets you may purchase at this time can not exceed %1$d.',
                            'The registration limit for this ticket is %1$d tickets per transaction, therefore the total number of tickets you may purchase at this time can not exceed %1$d.',
                            $ticket_maximum,
                            'event_espresso'
                        ),
                        $ticket_maximum
                    )
                );
                EE_Error::add_error($max_error, __FILE__, __FUNCTION__, __LINE__);
                return false;
            }

            // can purchase less tickets based on ticket min?
            $ticket_minimum = $ticket->min();
            if ($ticket_minimum > 0 && ($new_quantity < $ticket_minimum)) {
                // translate and possibly pluralize the error
                $min_error = esc_html(
                    sprintf(
                        _n(
                            'The registration minimum for this ticket is %1$d ticket per transaction, therefore the total number of tickets you may purchase at this time can not be less than %1$d.',
                            'The registration minimum for this ticket is %1$d tickets per transaction, therefore the total number of tickets you may purchase at this time can not be less than %1$d.',
                            $ticket_minimum,
                            'event_espresso'
                        ),
                        $ticket_minimum
                    )
                );
                EE_Error::add_error($min_error, __FILE__, __FUNCTION__, __LINE__);
                return false;
            }
        }

        // check event
        $total_ticket_quantity_within_event_additional_limit =
            $this->_total_ticket_quantity_within_event_additional_limit(
                $ticket,
                $quantity,
                $action === 'update'
            );
        if (! $total_ticket_quantity_within_event_additional_limit && $action !== 'remove') {
            // New Quantity
            if ($action === 'update') {
                $quantity = $quantity - $line_item->quantity();
            }
            // get some details from the ticket
            $additional_limit = $ticket->first_datetime()->event()->additional_limit();
            // can't register anymore attendees
            $limit_error_1 = esc_html(
                sprintf(
                    _n(
                        'You have attempted to purchase %1$d ticket but that would result in too many tickets in the %2$s for this event.',
                        'You have attempted to purchase %1$d tickets but that would result in too many tickets in the %2$s for this event.',
                        $quantity,
                        'event_espresso'
                    ),
                    $quantity,
                    EED_Multi_Event_Registration::$event_cart_name
                )
            );
            // translate and possibly pluralize the error
            $limit_error_2 = esc_html(
                sprintf(
                    _n(
                        'The registration limit for this event is %1$d ticket per transaction, therefore the total number of tickets you may purchase at any time can not exceed %1$d.',
                        'The registration limit for this event is %1$d tickets per transaction, therefore the total number of tickets you may purchase at any time can not exceed %1$d.',
                        $additional_limit,
                        'event_espresso'
                    ),
                    $additional_limit
                )
            );

            EE_Error::add_error($limit_error_1 . '<br/>' . $limit_error_2, __FILE__, __FUNCTION__, __LINE__);
            return false;
        }
        // YEAH !!!
        return true;
    }


    /**
     * returns true if the requested ticket quantity
     * does not exceed the event's additional limit
     * when combined with the tickets for the same event
     * that have already been added to the cart
     *
     * @param EE_Ticket $ticket
     * @param int       $quantity
     * @param bool      $cart_update
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _total_ticket_quantity_within_event_additional_limit(
        EE_Ticket $ticket,
        int $quantity = 1,
        bool $cart_update = false
    ): bool {
        $event = $ticket->first_datetime()->event();
        // we want to exclude this ticket from the count if this is a cart update,
        // because we are not simply incrementing the cart count
        // but replacing the quantity in the cart with a totally new value
        $TKT_ID        = $cart_update ? $ticket->ID() : 0;
        $event_tickets = $this->_event_tickets($TKT_ID);
        if (isset($event_tickets[ $event->ID() ])) {
            // add tickets that are already in cart
            $quantity += count($event_tickets[ $event->ID() ]);
        }
        return $quantity <= $event->additional_limit();
    }


    /**
     * generates a multidimensional array of tickets grouped by event
     * where the event ids are the keys for the outer array
     *
     * @param int $TKT_ID
     * @return array
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _event_tickets(int $TKT_ID = 0): array
    {
        $event_tickets     = [];
        $ticket_line_items = EE_Registry::instance()->CART->get_tickets();
        foreach ($ticket_line_items as $ticket_line_item) {
            if ($ticket_line_item instanceof EE_Line_Item && $ticket_line_item->OBJ_type() == 'Ticket') {
                $ticket = EEM_Ticket::instance()->get_one_by_ID($ticket_line_item->OBJ_ID());
                if ($ticket instanceof EE_Ticket && $ticket->ID() != $TKT_ID) {
                    $event = $ticket->first_datetime()->event();
                    for ($x = 1; $x <= $ticket_line_item->quantity(); $x++) {
                        $event_tickets[ $event->ID() ][] = $ticket->ID();
                    }
                }
            }
        }
        return $event_tickets;
    }


    /**
     * @param EE_Line_Item|null $line_item
     * @param int               $quantity
     * @param string            $action
     * @return EE_Line_Item|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function adjust_line_item_quantity(
        EE_Line_Item $line_item = null,
        int $quantity = 1,
        string $action = 'add'
    ): ?EE_Line_Item {
        if (! $line_item instanceof EE_Line_Item) {
            return null;
        }
        if ($quantity === 0 && $action === 'update') {
            self::getRequest()->setRequestParam('ticket', $line_item->OBJ_ID());
            self::getRequest()->setRequestParam('line_item', $line_item->code());
            $this->delete_ticket(false);
            $line_item->set_quantity(0);
            return $line_item;
        }
        if ($quantity > 0) {
            $additional       = 'An additional';
            $added_or_removed = 'added';
        } else {
            $additional       = 'A ';
            $added_or_removed = 'removed';
        }
        $new_quantity = $action === 'update' ? $quantity : $line_item->quantity() + $quantity;
        // update quantity
        $line_item->set_quantity($new_quantity);
        // it's "proper" to update the subline items quantities too, but core can actually fix it if we don't anyways
        if (method_exists('EEH_Line_Item', 'update_quantity')) {
            EEH_Line_Item::update_quantity($line_item, $new_quantity);
        } else {
            $line_item->set_quantity($new_quantity);
        }
        $saved = $line_item->ID()
            ? $line_item->save()
            : $line_item->quantity() === $new_quantity;
        if ($saved) {
            do_action(
                'FHEE__EED_Multi_Event_Registration__adjust_line_item_quantity__line_item_quantity_updated',
                $line_item,
                $quantity
            );
            if ($action !== 'update') {
                $msg = sprintf(
                    esc_html__('%1$s item was successfully %2$s for this event.', 'event_espresso'),
                    $additional,
                    $added_or_removed
                );
            } else {
                $msg = esc_html__('The quantities were successfully updated for this event.', 'event_espresso');
            }
            // something got added
            if (apply_filters('FHEE__EED_Multi_Event_Registration__display_success_messages', false)) {
                EE_Error::add_success($msg, __FILE__, __FUNCTION__, __LINE__);
            }
        } elseif ($line_item->quantity() !== $new_quantity) {
            // nothing added
            EE_Error::add_error(
                sprintf(
                    esc_html__(
                        '%1$s item was not %2$s for this event. Please refresh the page and try it again.',
                        'event_espresso'
                    ),
                    $additional,
                    $added_or_removed
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
            return null;
        }

        return $line_item;
    }


    /**
     * @param int|string $line_item_id
     * @return EE_Line_Item|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function get_line_item($line_item_id): ?EE_Line_Item
    {
        if (is_int($line_item_id)) {
            // get line item from db
            $line_item = EEM_Line_Item::instance()->get_one_by_ID(absint($line_item_id));
            if ($line_item instanceof EE_Line_Item) {
                return $line_item;
            }
        }
        $line_item_id = sanitize_text_field($line_item_id);
        // or... search thru cart
        $tickets_in_cart = EE_Registry::instance()->CART->get_tickets();
        foreach ($tickets_in_cart as $ticket_in_cart) {
            if (
                $ticket_in_cart instanceof EE_Line_Item && $ticket_in_cart->code() == $line_item_id
            ) {
                return $ticket_in_cart;
            }
        }
        // couldn't find the line item !?!?!
        EE_Error::add_error(
            esc_html__(
                'The specified item could not be found in the cart, therefore the quantity could not be adjusted. Please refresh the page and try again.',
                'event_espresso'
            ),
            __FILE__,
            __FUNCTION__,
            __LINE__
        );
        return null;
    }


    /**
     * call remove_ticket() via AJAX
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_remove_ticket()
    {
        EED_Multi_Event_Registration::instance()->remove_ticket();
    }


    /**
     * remove an attendee from event in the event cart
     *
     * @param int $quantity
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function remove_ticket(int $quantity = 1)
    {
        // check the request
        $ticket = $this->_validate_request();
        if ($ticket instanceof EE_Ticket) {
            $quantity = absint($quantity);
            $line_item = $this->get_line_item(self::getRequest()->getRequestParam('line_item'));
            if ($line_item instanceof EE_Line_Item && $quantity) {
                // if there will still be tickets in cart after this request
                // then just remove the requested quantity, else update the entire event cart
                if (($line_item->quantity() - $quantity > 0)) {
                    // If allowed to remove
                    if ($this->can_purchase_ticket_quantity($ticket, $quantity, $line_item, 'remove')) {
                        $line_item = $this->adjust_line_item_quantity($line_item, $quantity * -1, 'remove');
                        if ($line_item instanceof EE_Line_Item) {
                            $this->_adjust_ticket_reserves($ticket, $quantity * -1);
                        }
                    }
                } else {
                    // just empty the cart if removing a required ticket
                    if ($ticket->required()) {
                        $this->remove_all_tickets_for_event($ticket->get_related_event());
                    }
                    $line_item = $this->adjust_line_item_quantity($line_item, 0, 'update');
                    if ($line_item instanceof EE_Line_Item) {
                        $this->_adjust_ticket_reserves($ticket, abs($line_item->quantity()) * -1);
                    }
                }
            } else {
                // no ticket or line item !?!?!
                EE_Error::add_error(
                    esc_html__(
                        'The cart line item was not specified, therefore a ticket could not be removed. Please refresh the page and try again.',
                        'event_espresso'
                    ),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
        }
        $this->send_ajax_response();
    }


    /**
     * call delete_ticket() via AJAX
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_delete_ticket()
    {
        EED_Multi_Event_Registration::instance()->delete_ticket();
    }


    /**
     * removes ticket completely
     *
     * @param bool $send_ajax_response
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function delete_ticket(bool $send_ajax_response = true)
    {
        // check the request
        $ticket = $this->_validate_request();
        if ($ticket instanceof EE_Ticket) {
            // just empty the cart if removing a required ticket
            if ($ticket->required()) {
                $this->remove_all_tickets_for_event($ticket->get_related_event());
            }
            $line_item = $this->get_line_item(
                self::getRequest()->getRequestParam('line_item')
            );
            if ($line_item instanceof EE_Line_Item) {
                // get the parent line item now, because we will need it later
                $parent_line_item = $line_item->parent();
                $quantity_deleted = $line_item->quantity();
                $line_item->delete_children_line_items();
                $deleted = $this->_delete_line_item($line_item);
                if ($deleted && $parent_line_item instanceof EE_Line_Item) {
                    do_action(
                        'FHEE__EED_Multi_Event_Registration__delete_ticket__ticket_removed_from_cart',
                        $ticket,
                        $quantity_deleted,
                        $parent_line_item
                    );
                }
                // then something got deleted
                if ($deleted && apply_filters('FHEE__EED_Multi_Event_Registration__display_success_messages', false)) {
                    if ($quantity_deleted === 1) {
                        $msg = sprintf(
                            esc_html__('%1$s item was successfully removed from the %2$s', 'event_espresso'),
                            $quantity_deleted,
                            EED_Multi_Event_Registration::$event_cart_name
                        );
                    } else {
                        $msg = sprintf(
                            esc_html__('%1$s items were successfully removed from the %2$s', 'event_espresso'),
                            $quantity_deleted,
                            EED_Multi_Event_Registration::$event_cart_name
                        );
                    }
                    EE_Error::add_success($msg, __FILE__, __FUNCTION__, __LINE__);
                } elseif (! $deleted) {
                    // nothing removed
                    EE_Error::add_error(
                        sprintf(
                            esc_html__('The item was not removed from the %1$s', 'event_espresso'),
                            EED_Multi_Event_Registration::$event_cart_name
                        ),
                        __FILE__,
                        __FUNCTION__,
                        __LINE__
                    );
                }
            }
        }
        if ($send_ajax_response) {
            $this->send_ajax_response();
        }
    }


    /**
     * @param EE_Line_Item $line_item
     * @return int
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function _delete_line_item(EE_Line_Item $line_item): int
    {
        return $line_item->ID()
            ? $line_item->delete()
            : EE_Registry::instance()->CART->delete_items($line_item->code());
    }


    /**
     * call empty_event_cart() via AJAX
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_empty_event_cart()
    {
        EED_Multi_Event_Registration::instance()->empty_event_cart();
    }


    /**
     * remove all events from the event cart
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function empty_event_cart()
    {
        $this->init();
        EED_Multi_Event_Registration::load_classes();
        do_action(
            'AHEE__EED_Multi_Event_Registration__empty_event_cart__before_delete_cart',
            EE_Registry::instance()->SSN
        );
        // remove all unwanted records from the db
        if (EE_Registry::instance()->CART->delete_cart()) {
            // and clear the session too
            EE_Registry::instance()->SSN->clear_session(__CLASS__, __FUNCTION__);
            // reset cached cart
            EE_Registry::instance()->CART = EE_Cart::instance();
            if (apply_filters('FHEE__EED_Multi_Event_Registration__display_success_messages', false)) {
                EE_Error::add_success(
                    sprintf(
                        esc_html__('The %1$s was successfully emptied!', 'event_espresso'),
                        EED_Multi_Event_Registration::$event_cart_name
                    ),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
        } else {
            EE_Error::add_error(
                sprintf(
                    esc_html__('The %1$s could not be emptied!', 'event_espresso'),
                    EED_Multi_Event_Registration::$event_cart_name
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
        $this->send_ajax_response(
            true,
            apply_filters('FHEE__EED_Multi_Event_Registration__empty_event_cart__redirect_url', EE_EVENTS_LIST_URL)
        );
    }


    /**
     * @param EE_Event $event
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function remove_all_tickets_for_event(EE_Event $event)
    {
        $event_line_item = EEH_Line_Item::get_event_line_item(
            EE_Registry::instance()->CART->get_grand_total(),
            $event
        );
        if ($event_line_item instanceof EE_Line_Item) {
            $deleted = $event_line_item->delete_children_line_items();
            if ($deleted) {
                $deleted = $event_line_item->delete_if_childless_subtotal();
            }
            if (! $deleted) {
                EE_Error::add_error(
                    sprintf(
                        esc_html__('Event line item (ID:%1d$) deletion failed.', 'event_espresso'),
                        $event_line_item->ID()
                    ),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
        } else {
            EE_Error::add_error(
                sprintf(
                    esc_html__('A valid line item for the event (ID:%1d$) could not be found.', 'event_espresso'),
                    $event->ID()
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
        $this->send_ajax_response();
    }


    /**
     * get number of available spaces for event via ajax
     *
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function ajax_get_available_spaces()
    {
        // has a line item been sent?
        $event_id = self::getRequest()->getRequestParam('event_id', 0, DataType::INT);
        if ($event_id) {
            $event = EEM_Event::instance()->get_one_by_ID($event_id);
            if ($event instanceof EE_Event) {
                $available_spaces = $event->first_datetime()->tickets_remaining();
                // just send the ajax
                echo json_encode(
                    [
                        'id'     => $event->ID(),
                        'spaces' => $available_spaces,
                        'time'   => current_time('g:i:s a T'),
                    ]
                );
                // to be... or...
                exit();
            } else {
                EE_Error::add_error(
                    esc_html__(
                        'Available space polling via ajax failed. Event not found.',
                        'event_espresso'
                    ),
                    __FILE__,
                    __FUNCTION__,
                    __LINE__
                );
            }
        } else {
            EE_Error::add_error(
                esc_html__('Available space polling via ajax failed. No event id.', 'event_espresso'),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
        // just send the ajax
        echo json_encode(EE_Error::get_notices());
        // to be... or...
        die();
    }


    /**
     * handle ajax message responses
     *
     * @param bool   $empty_cart
     * @param string $redirect_url
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function send_ajax_response(bool $empty_cart = false, string $redirect_url = '')
    {
        $grand_total = EE_Registry::instance()->CART->get_grand_total();
        $grand_total->recalculate_total_including_taxes();
        $total_ticket_count = EE_Registry::instance()->CART->all_ticket_quantity_count();
        $empty_cart = $empty_cart || $total_ticket_count < 1;
        // if this is an ajax request AND a callback function exists
        if ($this->is_ajax === true) {
            // grab updated html for the event cart
            $new_html = ['tbody' => '<tbody>' . $this->_get_event_cart($grand_total) . '</tbody>'];
            if ($empty_cart) {
                $new_html['.event-cart-grand-total']     = '';
                $new_html['.event-cart-register-button'] = '';
            }
            // just send the ajax
            wp_send_json(
                array_merge(
                 EE_Error::get_notices(false),
                 [
                     'new_html'  => $new_html,
                     'mini_cart' => EED_Multi_Event_Registration::get_mini_cart(),
                 ]
             )
            );
        }
        EE_Error::get_notices(false, true);
        $redirect_url = ! empty($redirect_url)
            ? $redirect_url
            : add_query_arg(['event_cart' => 'view'], EE_EVENT_QUEUE_BASE_URL);
        wp_safe_redirect($redirect_url);
        exit;
    }


    /**
     * compares the current tickets in the cart with the current registrations for the checkout's transaction.
     * if any difference between the type of ticket or their associated quantities exist,
     * then registrations will be added or removed accordingly.
     *
     * @param EE_Checkout $checkout
     * @return EE_Checkout
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function verify_tickets_in_cart(EE_Checkout $checkout): EE_Checkout
    {
        // verify transaction
        if ($checkout->transaction instanceof EE_Transaction) {
            $changes = false;
            EED_Multi_Event_Registration::load_classes();
            // first we need to get an accurate list of tickets in the cart
            $cart_tickets = EED_Multi_Event_Registration::get_tickets_in_cart($checkout);
            // then we need to get an accurate list of registration tickets
            $registrations = $checkout->transaction->registrations();
            $reg_tickets   = EED_Multi_Event_Registration::get_registration_tickets($registrations);
            // now delete registrations for any tickets that were completely removed
            foreach ($reg_tickets as $reg_ticket_id => $reg_ticket_registrations) {
                if (! isset($cart_tickets[ $reg_ticket_id ])) {
                    foreach ($reg_ticket_registrations as $reg_ticket_registration) {
                        $changes = EED_Multi_Event_Registration::remove_registration(
                            $checkout->transaction,
                            $reg_ticket_registration
                        )
                            ? true
                            : $changes;
                    }
                }
            }
            // then add new tickets and/or adjust quantities for others
            foreach ($cart_tickets as $TKT_ID => $ticket_line_items) {
                $changes = EED_Multi_Event_Registration::adjust_registration_quantities(
                    $checkout->transaction,
                    $TKT_ID,
                    $ticket_line_items,
                    $reg_tickets
                )
                    ? true
                    : $changes;
            }
            if ($changes) {
                $new_ticket_count = count($checkout->transaction->registrations());
                EED_Multi_Event_Registration::reset_registration_details(
                    $checkout->transaction,
                    $new_ticket_count
                );
                EED_Multi_Event_Registration::update_cart($checkout);
                EED_Multi_Event_Registration::update_checkout_and_transaction(
                    $checkout,
                    $new_ticket_count
                );
                add_filter('FHEE__Single_Page_Checkout__load_reg_steps__reload_reg_steps', '__return_true');
            }
        }
        return $checkout;
    }


    /**
     * returns a multi array of EE_Ticket objects
     * indexed by:    [ ticket ID ][ auto-numerical ]
     * is an accurate representation of total tickets in cart
     *
     * @param EE_Checkout $checkout
     * @return array
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function get_tickets_in_cart(EE_Checkout $checkout): array
    {
        // arrays for tracking ticket counts
        $cart_tickets = [];
        // first we need to get an accurate count of tickets in the cart
        $tickets_in_cart = $checkout->cart->get_tickets();
        foreach ($tickets_in_cart as $ticket_line_item) {
            if ($ticket_line_item instanceof EE_Line_Item && $ticket_line_item->OBJ_type() == 'Ticket') {
                for ($x = 1; $x <= $ticket_line_item->quantity(); $x++) {
                    $cart_tickets[ $ticket_line_item->OBJ_ID() ][] = $ticket_line_item;
                }
            }
        }
        return $cart_tickets;
    }


    /**
     * returns a multi array of EE_Ticket objects
     * indexed by:    [ ticket ID ][ registration ID ]
     * is an accurate representation of total tickets in checkout
     *
     * @param array $registrations
     * @return array
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function get_registration_tickets(array $registrations = []): array
    {
        $reg_tickets = [];
        // now we need to get an accurate count of registration tickets
        foreach ($registrations as $registration) {
            if ($registration instanceof EE_Registration) {
                $reg_ticket = $registration->ticket();
                if ($reg_ticket instanceof EE_Ticket) {
                    $reg_tickets[ $reg_ticket->ID() ][ $registration->ID() ] = $registration;
                }
            }
        }
        return $reg_tickets;
    }


    /**
     * will either add registrations for NEW tickets
     * or adjust quantities for existing tickets
     *
     * @param EE_Transaction $transaction
     * @param int            $TKT_ID
     * @param array          $ticket_line_items
     * @param array          $reg_tickets
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function adjust_registration_quantities(
        EE_Transaction $transaction,
        int $TKT_ID = 0,
        array $ticket_line_items = [],
        array $reg_tickets = []
    ): bool {
        $changes = false;
        // are there any corresponding registrations for this ticket?
        if (isset($reg_tickets[ $TKT_ID ])) {
            // corresponding registrations exist, so let's compare counts...
            $tkt_count = count($ticket_line_items);
            $reg_count = count($reg_tickets[ $TKT_ID ]);
            if ($tkt_count > $reg_count) {
                // ticket(s) added = create registrations
                for ($x = 0; $x < ($tkt_count - $reg_count); $x++) {
                    $changes = EED_Multi_Event_Registration::add_registration(
                        $ticket_line_items[ $x ],
                        $transaction
                    )
                        ? true
                        : $changes;
                }
            } elseif ($tkt_count < $reg_count) {
                // ticket(s) removed = remove registrations
                for ($x = $reg_count; $x > $tkt_count; $x--) {
                    // grab last registration that corresponds to this ticket type
                    $registration = array_pop($reg_tickets[ $TKT_ID ]);
                    $changes      = EED_Multi_Event_Registration::remove_registration(
                        $transaction,
                        $registration
                    )
                        ? true
                        : $changes;
                }
            } else {
                // tickets match registrations = no change
                return false;
            }
        } else {
            // no corresponding registrations????
            // we need to create registrations for these tickets
            foreach ($ticket_line_items as $ticket_line_item) {
                $changes = EED_Multi_Event_Registration::add_registration(
                    $ticket_line_item,
                    $transaction
                ) ? true : $changes;
            }
        }
        return $changes;
    }


    /**
     * @param EE_Line_Item|null   $ticket_line_item
     * @param EE_Transaction|null $transaction
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function add_registration(
        EE_Line_Item $ticket_line_item = null,
        EE_Transaction $transaction = null
    ): bool {
        $registration = null;
        if ($ticket_line_item instanceof EE_Line_Item) {
            $attendee_number      = count($transaction->registrations()) + 1;
            $ticket               = $ticket_line_item->ticket();
            $registration_service = new CreateRegistrationService();
            // then generate a new registration from that
            $registration = $registration_service->create(
                $ticket->get_related_event(),
                $transaction,
                $ticket,
                $ticket_line_item,
                $attendee_number,
                $attendee_number
            );
        }
        return $registration instanceof EE_Registration;
    }


    /**
     * @param EE_Transaction       $transaction
     * @param EE_Registration|null $registration
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function remove_registration(
        EE_Transaction $transaction,
        EE_Registration $registration = null
    ): bool {
        if ($registration instanceof EE_Registration) {
            add_filter(
                'FHEE__EE_Transaction_Processor__update_transaction_after_canceled_or_declined_registration__cancel_ticket_line_item',
                function (
                    $cancel_ticket_line_item,
                    EE_Registration $canceled_registration
                ) use ($registration) {
                    if ($canceled_registration->ID() === $registration->ID()) {
                        $cancel_ticket_line_item = false;
                    }
                    return $cancel_ticket_line_item;
                },
                10,
                2
            );
            $registration->delete();
            $transaction->_remove_relation_to($registration, 'Registration');
            return true;
        }
        return false;
    }


    /**
     * @param EE_Transaction $transaction
     * @param int            $total_ticket_count
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function reset_registration_details(EE_Transaction $transaction, int $total_ticket_count = 0)
    {
        $attendee_number = 0;
        $registrations   = $transaction->registrations();
        uasort($registrations, ['EED_Multi_Event_Registration', 'sort_registrations_by_reg_count_callback']);
        foreach ($registrations as $registration) {
            if ($registration instanceof EE_Registration) {
                $attendee_number++;
                $registration->set_count($attendee_number);
                $registration->set_group_size($total_ticket_count);
                $reg_url_bits = explode('-', $registration->reg_url_link());
                $reg_url_link = $attendee_number . '-' . end($reg_url_bits);
                $registration->set_reg_url_link($reg_url_link);
                $reg_code = new RegCode(
                    RegUrlLink::fromRegistration($registration),
                    $registration->transaction(),
                    $registration->ticket()
                );
                $registration->set('REG_code', $reg_code);
                $registration->save();
                $transaction->_add_relation_to($registration, 'Registration');
            }
        }
    }


    /**
     * @param EE_Registration $registration_A
     * @param EE_Registration $registration_B
     * @return int
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function sort_registrations_by_reg_count_callback(
        EE_Registration $registration_A,
        EE_Registration $registration_B
    ): int {
        // send any registrations that don't already have the count set to the end of the array
        if (! $registration_A->count()) {
            return 1;
        }
        if ($registration_A->count() == $registration_B->count()) {
            return 0;
        }
        return ($registration_A->count() > $registration_B->count()) ? 1 : -1;
    }


    /**
     * @param EE_Checkout $checkout
     * @param int         $new_ticket_count
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected static function update_checkout_and_transaction(EE_Checkout $checkout, int $new_ticket_count = 0)
    {
        $checkout->total_ticket_count = $new_ticket_count;
        $checkout->generate_reg_form  = true;
        $current_step                 = true;
        foreach ($checkout->reg_steps as $reg_step) {
            if ($reg_step instanceof EE_SPCO_Reg_Step) {
                // set first reg step as the current
                $reg_step->set_is_current_step($current_step);
                $current_step = false;
                $reg_step->set_not_completed();
            }
        }
        // reset all reg step completion statuses to false
        $reg_steps = $checkout->transaction->reg_steps();
        foreach ($reg_steps as $reg_step => $completed) {
            $reg_steps[ $reg_step ] = false;
        }
        $checkout->transaction->set_reg_steps($reg_steps);
        if ($checkout->transaction->ID()) {
            $checkout->transaction->save();
        }
    }


    /**
     * @param EE_Checkout $checkout
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function update_cart(EE_Checkout $checkout)
    {
        $checkout->cart->get_grand_total()->recalculate_total_including_taxes();
        $checkout->transaction->set_total($checkout->cart->get_grand_total()->total());
    }


    /**
     * determine whether to toggle free tickets to "Approved" based on payment status (kinda sorta) of other tickets for
     * the same event. So if more than one ticket for the same event is in the cart, and one or more tickets are NOT
     * free, then free tickets will NOT be automatically toggled to "Approved"
     *
     * @param bool                 $toggle_registration_status
     * @param EE_Registration|null $registration
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function toggle_registration_status_if_no_monies_owing(
        bool $toggle_registration_status = false,
        EE_Registration $registration = null
    ): bool {
        $reg_tickets = [];
        if ($registration instanceof EE_Registration && $registration->transaction() instanceof EE_Transaction) {
            // now we need to get an accurate count of registration tickets
            foreach ($registration->transaction()->registrations() as $reg) {
                if ($reg instanceof EE_Registration) {
                    if ($reg->event() instanceof EE_Event && $reg->ticket() instanceof EE_Ticket) {
                        $reg_tickets[ $reg->event()->ID() ][ $reg->ticket()->ID() ] = $reg->ticket()->is_free();
                    }
                }
            }
        }
        if ($registration->ticket() instanceof EE_Ticket && $registration->ticket()->is_free()) {
            $toggle_registration_status = true;
            if ($registration->event() instanceof EE_Event && isset($reg_tickets[ $registration->event()->ID() ])) {
                foreach ($reg_tickets[ $registration->event()->ID() ] as $free_ticket) {
                    $toggle_registration_status = $free_ticket ? $toggle_registration_status : false;
                }
            }
        }
        return $toggle_registration_status;
    }


    /**
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function save_cart()
    {
        if (EE_Registry::instance()->CART instanceof EE_Cart) {
            EE_Registry::instance()->CART->save_cart();
        }
    }


    /************************************** DEPRECATED **************************************/


    /**
     * checks if an event line item still has any tickets associated with it,
     * and if not, then deletes the event plus any other non-ticket items,
     * which may be things like promotion codes
     *
     * @param EE_Line_Item $parent_line_item
     * @return void
     * @throws EE_Error
     * @throws ReflectionException
     * @deprecated 2.0.21.p
     */
    public function maybe_delete_event_line_item(EE_Line_Item $parent_line_item)
    {
        // are there any tickets left for this event ?
        $ticket_line_items = EEH_Line_Item::get_ticket_line_items($parent_line_item);
        if (empty($ticket_line_items) || $parent_line_item->quantity() === 0) {
            // find and delete ALL children which may include non-ticket items like promotions
            $parent_line_item->delete_children_line_items();
            $this->_delete_line_item($parent_line_item);
        }
    }
}
/* End of file EE_Multi_Event_Registration.class.php */
/* Location: espresso-multi-registration/EE_Multi_Event_Registration.class.php */
