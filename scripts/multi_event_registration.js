var MER;
jQuery( document ).ready( function( $ ) {

	/**
	 * @namespace MER
	 * @type {{
	 *     event_cart : object,
	 *     form_input: object,
	 *     form_data: object,
	 *     response: object,
	 *     display_debug: boolean,
	 * }}
	 * @namespace eei18n
	 * @type {{
	 *     ajax_url: string
	 *     wp_debug: boolean
	 * }}
	 */


	MER = {

		/**
		 * DOM element - main event cart container
		 */
		event_cart  : {},
		/**
		 * DOM element - event cart text input field
		 */
		form_input : {},
		/**
		 * DOM element - AJAX notices container
		 */
		ajax_notices : {},
		/**
		 * array of form data
		 * @namespace form_data
		 * @type {{
		 *     action: string,
		 *     ticket: string,
		 *     line_item: string,
		 *     ee: string,
		 *     cart_results: string,
		 * }}
		 */
		form_data : {},
		/**
		 * AJAX response
		 * @namespace response
		 * @type {{
		 *     errors: string,
		 *     attention: string,
		 *     success: string,
		 *     new_html: object,
		 *     tickets_added: boolean,
		 *     btn_id: string,
		 *     btn_txt: string,
		 *     form_html: string,
		 *     mini_cart: string,
		 * }}
		 */
		response : {},
		/**
		 * display debugging info in console?
		 * @namespace display_debug
		 * @type  boolean
		 */
		display_debug : eei18n.wp_debug,

		/********** INITIAL SETUP **********/

		/**
		 * @function initialize
		 */
		initialize : function() {
			MER.ajax_notices  = $( '#espresso-ajax-notices' );
			var event_cart  = $( '#event-cart' );
			if ( event_cart .length ) {
				MER.event_cart  = event_cart ;
				MER.set_listener_for_add_ticket_button();
				MER.set_listener_for_remove_ticket_button();
				MER.set_listener_for_delete_ticket_button();
				MER.set_listener_for_update_event_cart_button();
				MER.set_listener_for_empty_event_cart_link();
			} else {
				MER.set_listener_for_ticket_selector_submit_btn();
				MER.set_listener_for_close_modal_btn();
			}
			//alert( 'initialized' );
		},



	/**
		 *  @function set_listener_for_add_ticket_button
		 */
		set_listener_for_add_ticket_button : function() {
			MER.event_cart.on( 'click', '.event-cart-add-ticket-button', function( event ) {
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_add_ticket_to_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
				}
				event.preventDefault();
				event.stopPropagation();
			});
		},



		/**
		 *  @function set_listener_for_remove_ticket_button
		 */
		set_listener_for_remove_ticket_button : function() {
			MER.event_cart.on( 'click', '.event-cart-remove-ticket-button', function( event ) {
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_remove_ticket_from_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
				}
				event.preventDefault();
				event.stopPropagation();
			});
		},



		/**
		 *  @function set_listener_for_delete_ticket_button
		 */
		set_listener_for_delete_ticket_button : function() {
			MER.event_cart.on( 'click', '.event-cart-delete-ticket-button', function( event ) {
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					var urlParams = $( this ).eeGetParams();
					MER.form_data = {};
					MER.form_data.action = 'espresso_delete_ticket_from_event_cart';
					MER.form_data.ticket = typeof( urlParams.ticket ) !== 'undefined' ? urlParams.ticket : '';
					MER.form_data.line_item = typeof( urlParams.line_item ) !== 'undefined' ? urlParams.line_item : '';
					MER.submit_ajax_request();
				}
				event.preventDefault();
				event.stopPropagation();
			});
		},



		/**
		 *  @function set_listener_for_update_event_cart_link
		 */
		set_listener_for_update_event_cart_button : function() {
			MER.event_cart.on( 'click', '.event-cart-update-cart-lnk', function( event ) {
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					//var serialized_form_data = $( MER.event_cart  ).find( 'form' ).serializeArray();
					//MER.form_data = MER.convert_to_JSON( serialized_form_data );
					MER.form_data = MER.get_form_data( MER.event_cart, true );
					console.log( MER.form_data );
					MER.form_data.action = 'espresso_update_event_cart';
					MER.submit_ajax_request();
				}
				event.preventDefault();
				event.stopPropagation();
			});
		},



		/**
		 *  @function set_listener_for_empty_event_cart_link
		 */
		set_listener_for_empty_event_cart_link : function() {
			MER.event_cart.on( 'click', '.event-cart-empty-cart-lnk', function( event ) {
				if ( ! $( this ).hasClass( 'disabled' ) ) {
					MER.form_data = {};
					MER.form_data.action = 'espresso_empty_event_cart';
					MER.submit_ajax_request();
				}
				event.preventDefault();
				event.stopPropagation();
			} );
		},



		/**
		 *  @function set_listener_for_ticket_selector_submit_btn
		 */
		set_listener_for_ticket_selector_submit_btn : function() {
			$( 'body' ).on( 'click', '.ticket-selector-submit-ajax', function( event ) {
				MER.form_data = MER.get_form_data( $( this ), false );
				var ticket_count = 0;
				if ( typeof MER.form_data[ 'tkt-slctr-event-id' ] !== 'undefined' && MER.form_data[ 'tkt-slctr-event-id' ] !== ''  ) {
					var tkt_slctr_qty = 'tkt-slctr-qty-' + MER.form_data[ 'tkt-slctr-event-id' ] + '[]';
					//console.log( tkt_slctr_qty );
					//console.log( MER.form_data[ tkt_slctr_qty ] );
					if ( typeof MER.form_data[ tkt_slctr_qty ] !== 'undefined' && MER.form_data[ tkt_slctr_qty ] !== '' ) {
						var ticket_quantities =MER.form_data[ tkt_slctr_qty ];
						//console.log( 'ticket_quantities' );
						//console.log( ticket_quantities );
						if ( typeof ticket_quantities === 'object' ) {
							$.each( ticket_quantities, function( index, ticket_quantity ) {
								//console.log( 'ticket_quantity' );
								//console.log( ticket_quantity );
								ticket_count = ticket_count + parseInt( ticket_quantity );
								//alert( 'ticket_quantity = ' + parseInt( ticket_quantity ) );
							} );
						} else {
							ticket_count = parseInt( ticket_quantities );
						}
					}
				}
				ticket_count = parseInt( ticket_count );
				//console.log( JSON.stringify( 'ticket_count: ' + ticket_count, null, 4 ) );
				//console.log( JSON.stringify( 'typeof ticket_count: ' + ( typeof ticket_count ), null, 4 ) );
				//console.log( JSON.stringify( 'MER.form_data.event_cart: ' + MER.form_data.event_cart, null, 4 ) );
				var view_cart = typeof MER.form_data.event_cart !== 'undefined' && MER.form_data.event_cart === 'view';
				//console.log( JSON.stringify( 'view_cart: ' + view_cart, null, 4 ) );
				//alert( 'MER.form_data.event_cart = ' + MER.form_data.event_cart + '\n' + 'ticket_count = ' + ticket_count );
				if   ( ticket_count !== 0 || ( ticket_count === 0 && ! view_cart ) ) {
					MER.form_data.action = 'espresso_' + MER.form_data.ee;
					MER.submit_ajax_request();
					event.preventDefault();
					event.stopPropagation();
				}
			} );
		},



		/**
		 *  @function set_listener_for_close_modal_btn
		 */
		set_listener_for_close_modal_btn : function() {
			$( 'body' ).on( 'click', '.close-modal-js', function( event ) {
				var cart_results_wrapper = $( '#cart-results-modal-wrap-dv' );
				if ( cart_results_wrapper.length ) {
					cart_results_wrapper.eeRemoveOverlay().hide();
					event.preventDefault();
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
					MER.response = response;
					MER.process_response( MER.response );
					MER.do_after_ajax( response );
				},

				error : function( response ) {
					MER.response = response;
					MER.response.error = eei18n.server_error;
					MER.do_after_ajax();
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
		 */
		do_after_ajax : function() {
			MER.enable_buttons();
			$( '#espresso-ajax-loading' ).fadeOut( 'fast' );
			MER.display_messages( MER.response );
		},



		/**
		 * @function process_response
		 */
		process_response : function() {
			//console.log( MER.response );
			if ( typeof MER.response === 'object' ) {
				if ( typeof MER.response.new_html !== 'undefined' ) {
					// loop thru tracked errors
					$.each( MER.response.new_html, function( index, html ) {
						//console.log( JSON.stringify( 'index: ' + index, null, 4 ) );
						if ( typeof index !== 'undefined' && typeof html !== 'undefined' ) {
							var event_cart_element = $( MER.event_cart  ).find( index );
							if ( event_cart_element.length ) {
								event_cart_element.replaceWith( html );
								$( MER.event_cart ).eeScrollTo( 200 );
								//console.log( JSON.stringify( 'html: ' + html, null, 4 ) );
							}
						}
					} );
				}
				if ( typeof MER.response.tickets_added !== 'undefined' && MER.response.tickets_added === true ) {
					var btn_id = typeof MER.response.btn_id !== 'undefined' ? MER.response.btn_id : '';
					var btn_txt = typeof MER.response.btn_txt !== 'undefined' ? MER.response.btn_txt : '';
					var form_html = typeof MER.response.form_html !== 'undefined' ? MER.response.form_html : '';
					var submit_button = $( btn_id );
					if ( submit_button.length && btn_txt !== '' ) {
						if ( submit_button.val() !== btn_txt ) {
							submit_button.val( btn_txt );
							var ticket_form = submit_button.parents( 'form:first' );
							if ( ticket_form.length && form_html !== '' ) {
								ticket_form.append( form_html );
								//console.log( JSON.stringify( 'form_html: ' + form_html, null, 4 ) );
							}
						}
					}
					$('.ticket-selector-tbl-qty-slct' ).each( function() {
						//console.log( JSON.stringify( 'ticket-selector-tbl-qty-slct id: ' + $( this ).attr( 'id' ), null, 4 ) );
						if ( $( this ).find( 'option[value="0"]' ).length > 0 ) {
							$( this ).val( 0 );
						}
					} );
				}
				if ( typeof MER.response.mini_cart !== 'undefined' && MER.response.mini_cart !== '' ) {
					var mini_cart = $( '#ee-mini-cart-details' );
					//console.log( mini_cart );
					if ( mini_cart.length ) {
						mini_cart.html( MER.response.mini_cart );
						$('#mini-cart-whats-next-buttons' ).fadeIn();
					}
				}
				if ( typeof MER.response.cart_results !== 'undefined' && MER.response.cart_results !== '' ) {
					var cart_results_wrapper = $( '#cart-results-modal-wrap-dv' );
					//console.log( mini_cart );
					if ( cart_results_wrapper.length ) {
						cart_results_wrapper.html( MER.response.cart_results ).eeCenter( 'fixed' ).eeAddOverlay( 0.5 ).show();
						MER.add_modal_notices();
					}
				}
			}
		},




		/**
		 *  @function add_modal_notices
		 */
		add_modal_notices : function() {
			var notices = '';
			if ( typeof MER.response.attention !== 'undefined' && MER.response.attention ) {
				notices += '<div class="espresso-ajax-modal-notices attention"><p>' + MER.response.attention + '</p></div>';
			} else if ( typeof MER.response.errors !== 'undefined' && MER.response.errors ) {
				notices += '<div class="espresso-ajax-modal-notices errors"><p>' + MER.response.errors + '</p></div>';
			} else if ( typeof MER.response.success !== 'undefined' && MER.response.success ) {
				notices += '<div class="espresso-ajax-modal-notices success"><p>' + MER.response.success + '</p></div>';
			}
			if ( notices !== '' ) {
				notices = '<div id="espresso-ajax-modal-notices-dv" style="display: block;">' + notices + '</div>';
				var cart_results = $( "#cart-results-modal-dv" );
				var notices_width = cart_results.innerWidth();
				cart_results.append( notices );
				$( '#espresso-ajax-modal-notices-dv' ).css({ 'width' : notices_width } ).show();
			}
		},




		/**
		 *  @function get_form_data
		 * @param  {object} form_container
		 * @param  {bool} form_within - whether the form should be looked for above or within the indicated DOM element
		 */
		get_form_data : function( form_container, form_within ) {
			if ( form_container.length ) {
				form_within = typeof form_within === 'boolean' ? form_within : false;
				var serialized_form_data;
				if ( form_within ) {
					serialized_form_data = form_container.find( 'form' ).serializeArray();
				} else {
					serialized_form_data = form_container.parents( 'form:first' ).serializeArray();
				}
				return MER.convert_to_JSON( serialized_form_data );
			} else {
				return {};
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
				MER.show_event_cart_ajax_msg( 'attention', msg.attention, 18000 );
			} else if ( typeof msg.errors !== 'undefined' && msg.errors ) {
				MER.show_event_cart_ajax_msg( 'error', msg.errors, 12000 );
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
				MER.ajax_notices.eeCenter( 'fixed' );
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
		}



		/**
		*        retrieve available spaces updates from the server on a timed interval
		*/
		//poll_available_spaces :function( event_id, httpTimeout ) {
		//
		//	//alert( 'event_id : ' + event_id);
		//
		//	if ( httpTimeout == undefined ) {
		//		httpTimeout = 30000;
		//	}
		//
		//	if ( event_id ) {
		//		$.ajax( {
		//			type : "POST",
		//			url : mer.ajax_url,
		//			data : {
		//				"action" : "espresso_get_available_spaces",
		//				"event_id" : event_id,
		//				"event_cart_ajax" : 1
		//			},
		//			dataType : "json",
		//
		//			success : function( response ) {
		//
		//				MER.response = response;
		//				var availability = MER.response.spaces + ' <span class="available-spaces-last-update-spn">( last update: ' + MER.response.time + ' )</span>';
		//				$( '#available-spaces-spn-' + MER.response.id ).fadeOut( 500, function() {
		//					$( '#available-spaces-spn-' + MER.response.id ).html( availability ).fadeIn( 500 );
		//				} );
		//
		//			},
		//
		//			error : function( response ) {
		//
		//				MER.response = response;
		//				if ( MER.response.error == undefined || MER.response.error == '' ) {
		//					MER.response.error = 'Available space polling failed. Please refresh the page and it try again and again.';
		//				}
		//				show_event_cart_ajax_error_msg( 0, response );
		//
		//			},
		//			//						complete: function(response) {
		//
		//			//							setTimeout(function() {
		//			//								poll_available_spaces( event_id );
		//			//							}, httpTimeout );
		//
		//			//						},
		//			timeout : httpTimeout
		//		} );
		//	}
		//},
		//
		//
		//
		///**
		// *        loop thru events in event list and begin polling server re: available spaces
		// */
		//begin_polling_available_spaces : function() {
		//	var httpTimeout = 30000 * $( '.available-spaces-spn' ).size();
		//	$( '.available-spaces-spn' ).each( function( index ) {
		//		var event_id = $( this ).attr( 'id' );
		//		event_id = event_id.replace( 'available-spaces-spn-', '' );
		//		setTimeout( function() {
		//			poll_available_spaces( event_id, httpTimeout );
		//		}, 30000 * index );
		//	} );
		//	setTimeout( function() {
		//		begin_polling_available_spaces();
		//	}, httpTimeout );
		//
		//},
		//
		//
		//
		///**
		// *        loop thru events in event list and begin polling server re: available spaces
		// */
		//event_list_polling : function( serialized_array ) {
		//	if ( $( '#event-cart-poll-server' ).val() == 1 ) {
		//		setTimeout( MER.begin_polling_available_spaces(), 30000 );
		//	}
		//}



};

	MER.initialize();

} );

