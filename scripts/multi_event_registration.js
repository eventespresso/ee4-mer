var MER;
jQuery( document ).ready( function( $ ) {

	/**
	 * @namespace MER
	 * @type {{
		 *     container: object,
		 *     form_input: object,
		 *     form_data: object,
		 *     display_debug: number,
	 * }}
	 * @namespace eei18n
	 * @type {{
		 *     ajax_url: string
		 * }}
	 * @namespace response
	 * @type {{
		 *     errors: string,
		 *     attention: string,
		 *     success: string,
		 *     new_html: object
		 * }}
	 */


	MER = {
		// main event cart container
		container : {},
		// event cart text input field
		form_input : {},
		// array of form data
		form_data : {},
		// display debugging info in console?
		display_debug : eei18n.wp_debug,
		/********** INITIAL SETUP **********/



		/**
		 * @function initialize
		 */
		initialize : function() {
			var container = $( '#event-cart' );
			if ( container.length ) {
				MER.container = container;
				MER.set_listener_for_add_ticket_button();
				MER.set_listener_for_remove_ticket_button();
				MER.set_listener_for_delete_ticket_button();
				MER.set_listener_for_update_event_cart_button();
				MER.set_listener_for_empty_event_cart_link();
			}
		},



		/**
		 *  @function set_listener_for_add_ticket_button
		 */
		set_listener_for_add_ticket_button : function() {
			MER.container.on( 'click', '.event-cart-add-ticket-button', function( event ) {
				event.preventDefault();
				event.stopPropagation();
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_add_ticket_to_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
				}
			});
		},



		/**
		 *  @function set_listener_for_remove_ticket_button
		 */
		set_listener_for_remove_ticket_button : function() {
			MER.container.on( 'click', '.event-cart-remove-ticket-button', function( event ) {
				event.preventDefault();
				event.stopPropagation();
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_remove_ticket_from_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
				}
			});
		},



		/**
		 *  @function set_listener_for_delete_ticket_button
		 */
		set_listener_for_delete_ticket_button : function() {
			MER.container.on( 'click', '.event-cart-delete-ticket-button', function( event ) {
				event.preventDefault();
				event.stopPropagation();
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_delete_ticket_from_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
					event.stopPropagation();
				}
			});
		},



		/**
		 *  @function set_listener_for_update_event_cart_link
		 */
		set_listener_for_update_event_cart_button : function() {
			MER.container.on( 'click', '.event-cart-update-cart-lnk', function( event ) {
				event.preventDefault();
				event.stopPropagation();
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var serialized_form_data = $( MER.container ).find( 'form' ).serializeArray();
					MER.form_data = MER.convert_to_JSON( serialized_form_data );
					//console.log( MER.form_data );
					MER.form_data.action = 'espresso_update_event_cart';
					MER.submit_ajax_request();
					event.stopPropagation();
				}
			});
		},



		/**
		 *  @function set_listener_for_empty_event_cart_link
		 */
		set_listener_for_empty_event_cart_link : function() {
			MER.container.on( 'click', '.event-cart-empty-cart-lnk', function( event ) {
				event.preventDefault();
				event.stopPropagation();
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					MER.form_data = {};
					MER.form_data.action = 'espresso_empty_event_cart';
					MER.submit_ajax_request();
					event.stopPropagation();
				}
			} );
		},



		/**
		 *  @function submit_promo_code
		 */
		submit_ajax_request : function() {
			// no form_data ?
			if ( typeof MER.form_data.action === 'undefined' || MER.form_data.action === '' ) {
				return;
			}
			MER.form_data.noheader = 1;
			MER.form_data.ee_front_ajax = 1;
			// send AJAX
			$.ajax( {

				type : "POST",
				url : eei18n.ajax_url,
				data : MER.form_data,
				dataType : "json",

				beforeSend : function() {
					MER.do_before_sending_ajax();
				},

				success : function( response ) {
					MER.process_response( response );
					MER.do_after_ajax( response );
				},

				error : function() {
					var response = {};
					response.error = eei18n.server_error;
					MER.do_after_ajax( response );
					//return SPCO.ajax_request_server_error();
				}

			} );

		},



		/**
		 * @function do_before_sending_ajax
		 */
		do_before_sending_ajax : function() {
			MER.disable_buttons();
			$( '#espresso-ajax-long-loading' ).remove();
			$( '#espresso-ajax-loading' ).show();
		},



		/**
		 * @function do_after_ajax
		 * @param  {object} response
		 */
		do_after_ajax : function( response ) {
			MER.enable_buttons();
			$( '#espresso-ajax-loading' ).fadeOut( 'fast' );
			MER.display_messages( response );
		},



		/**
		 * @function process_response
		 * @param  {object} response
		 */
		process_response : function( response ) {
			//console.log( response );
			if ( typeof response === 'object' && typeof response.new_html !== 'undefined' ) {
				// loop thru tracked errors
				$.each( response.new_html, function( index, html ) {
					//console.log( JSON.stringify( 'index: ' + index, null, 4 ) );
					if ( typeof index !== 'undefined' && typeof html !== 'undefined' ) {
						var event_cart_element = $( MER.container ).find( index );
						if ( event_cart_element.length ) {
							event_cart_element.replaceWith( html )
							//console.log( JSON.stringify( 'html: ' + html, null, 4 ) );
						}
					}
				} );
			}
		},



		/**
		 *  @function disable_buttons
		 */
		disable_buttons : function() {
			$( '.event-cart-button' ).each( function() {
				$( this ).addClass( 'disabled disabled-event-cart-btn' );
			} );
		},



		/**
		 *  @function enable_buttons
		 */
		enable_buttons : function() {
			$( '.event-cart-button' ).each( function() {
				$( this ).removeClass( 'disabled disabled-event-cart-btn' );
			} );
		},



		/**
		 * @function display messages
		 * @param  {object} msg
		 */
		display_messages : function( msg ) {
			if ( typeof msg.attention !== 'undefined' && msg.attention ) {
				MER.show_event_cart_ajax_msg( 'attention', msg.attention, 10000 );
			} else if ( typeof msg.errors !== 'undefined' && msg.errors ) {
				MER.show_event_cart_ajax_msg( 'error', msg.errors, 10000 );
			} else if ( typeof msg.success !== 'undefined' && msg.success ) {
				MER.show_event_cart_ajax_msg( 'success', msg.success, 6000 );
			}
		},



		/**
		 * @function show event cart ajax msg
		 * @param  {string} type
		 * @param  {string} msg
		 * @param  {number} fadeOut
		 */
		show_event_cart_ajax_msg : function( type, msg, fadeOut ) {
			// does an actual message exist ?
			if ( typeof msg !== 'undefined' && msg !== '' ) {
				// ensure message type is set
				var msg_type = typeof type !== 'undefined' && type !== '' ? type : 'error';
				// make sure fade out time is not too short
				fadeOut = typeof fadeOut === 'undefined' || fadeOut < 4000 ? 4000 : fadeOut;
				// center notices on screen
				$( '#espresso-ajax-notices' ).eeCenter( 'fixed' );
				// target parent container
				var espresso_ajax_msg = $( '#espresso-ajax-notices-' + msg_type );
				//  actual message container
				espresso_ajax_msg.children( '.espresso-notices-msg' ).html( msg );
				// display message
				espresso_ajax_msg.removeClass( 'hidden' ).show().delay( fadeOut ).fadeOut();
			}
		},



		/**
		 *  @function convert_to_JSON
		 * @param  {string} serialized_array
		 */
		convert_to_JSON : function( serialized_array ) {
			var json_object = {};
			$.each( serialized_array, function() {
				if ( json_object[ this.name ] ) {
					if ( !json_object[ this.name ].push ) {
						json_object[ this.name ] = [ json_object[ this.name ] ];
					}
					json_object[ this.name ].push( this.value || '' );
				} else {
					json_object[ this.name ] = this.value || '';
				}
			} );
			return json_object;
		},



		/**
		*        retrieve available spaces updates from the server on a timed interval
		*/
		poll_available_spaces :function( event_id, httpTimeout ) {

			//alert( 'event_id : ' + event_id);

			if ( httpTimeout == undefined ) {
				httpTimeout = 30000;
			}

			if ( event_id ) {
				$.ajax( {
					type : "POST",
					url : mer.ajax_url,
					data : {
						"action" : "espresso_get_available_spaces",
						"event_id" : event_id,
						"event_cart_ajax" : 1
					},
					dataType : "json",
					success : function( response ) {

						var availability = response.spaces + ' <span class="available-spaces-last-update-spn">( last update: ' + response.time + ' )</span>';
						$( '#available-spaces-spn-' + response.id ).fadeOut( 500, function() {
							$( '#available-spaces-spn-' + response.id ).html( availability ).fadeIn( 500 );
						} );

					},
					error : function( response ) {

						if ( response.error == undefined || response.error == '' ) {
							response = new Object();
							response.error = 'Available space polling failed. Please refresh the page and it try again and again.';
						}
						show_event_cart_ajax_error_msg( 0, response );

					},
					//						complete: function(response) {

					//							setTimeout(function() {
					//								poll_available_spaces( event_id );
					//							}, httpTimeout );

					//						},
					timeout : httpTimeout
				} );
			}
		},



		/**
		 *        loop thru events in event list and begin polling server re: available spaces
		 */
		begin_polling_available_spaces : function() {
			var httpTimeout = 30000 * $( '.available-spaces-spn' ).size();
			$( '.available-spaces-spn' ).each( function( index ) {
				var event_id = $( this ).attr( 'id' );
				event_id = event_id.replace( 'available-spaces-spn-', '' );
				setTimeout( function() {
					poll_available_spaces( event_id, httpTimeout );
				}, 30000 * index );
			} );
			setTimeout( function() {
				begin_polling_available_spaces();
			}, httpTimeout );

		},



		/**
		 *        loop thru events in event list and begin polling server re: available spaces
		 */
		event_list_polling : function( serialized_array ) {
			if ( $( '#event-cart-poll-server' ).val() == 1 ) {
				setTimeout( MER.begin_polling_available_spaces(), 30000 );
			}
		}



};

	MER.initialize();

} );

