(function($) {

	$.ajaxSetup ({ cache: false });

	// clear firefox and safari cache
	$(window).unload( function() {}); 
	
	//close btn for notifications
	$('.close-event-queue-msg').on( 'click', function(){
		$(this).parent().hide();
	});	
	
	function wheres_the_top() {
		// window width
		wnd_width = parseInt( $(window).width() );
		// window height
		wnd_height = parseInt( $(window).height() );
		// how far down the page the use has scrolled
		var st = $('html').scrollTop();
		var top_adjust = wnd_height / 4.6;
		// where message boxes will appear
		if ( st > top_adjust ) {
			var msg_top = st - (wnd_height/4.6);
		} else {
			var msg_top = st;
		}
		
		
		return msg_top;
	}


	
	/**
	*		do_before_event_queue_ajax
	*/	
	function do_before_event_queue_ajax( line_item_id ) {
		// stop any message alerts that are in progress	
		$('.event-queue-msg').stop();
		// if no line item id
		if ( line_item_id != 0 ) {
			// prevent multiple button clicks with BUTTON BLOKKA!! You been BLOKKED SUKA !!!
			$('#button-blokka-'+line_item_id).show();	
		}
		// spinny things pacify the masses
//		var st = $('html').scrollTop();
//		var po = $('#mer-ajax-loading').parent().offset();
//		
//		var wh = $(window).height();
//		var mal_top = ( st+(wh/2)-po.top ) - 15;
//
//		var ww = $('#mer-ajax-loading').parent().width();
//		var mal_left = ( ww/2 ) -15;
		
//		$('#mer-ajax-loading').css({ 'top' : mal_top, 'left' : mal_left }).show();
		$('#mer-ajax-loading').show();
		
	}		
	
	
	
	
	/**
	*		show event queue ajax success msg
	*/	
	function show_event_queue_ajax_success_msg( line_item_id, success_msg ) {
		
		if ( success_msg != undefined && success_msg != '' )  {
		
			if ( success_msg.success != undefined ) {
				success_msg = success_msg.success;
			}		
			//alert( 'success_msg'+success_msg);
//			msg_top = wheres_the_top();
//			$('#mer-success-msg').css({ 'top' : msg_top });	
			$('#mer-success-msg > .msg').html( success_msg );
			$('#mer-ajax-loading').fadeOut('fast');
			$('#mer-success-msg').removeClass('hidden').show().delay(4000).fadeOut();			
		} else {
			$('#mer-ajax-loading').fadeOut('fast');
		}	
		
		// if no line item id
//		if ( line_item_id != 0 ) {t
//			$('#button-blokka-'+line_item_id).hide();		
//		}		
		$('.button-blokka').hide();		
	}	
	
	
	
	
	/**
	*		show event queue ajax error msg
	*/	
	function show_event_queue_ajax_error_msg( line_item_id, error_msg ) {
		if ( error_msg != undefined && error_msg != '' ) {
		
			if ( error_msg.error != undefined ) {
				error_msg = error_msg.error;
			} 
			//alert( 'error_msg'+ error_msg);
//			msg_top = wheres_the_top();
//			$('#mer-error-msg').stop().css({ 'top' : msg_top });				
			$('#mer-error-msg > .msg').html( error_msg );
			$('#mer-ajax-loading').fadeOut('fast');
			$('#mer-error-msg').removeClass('hidden').show().delay(8000).fadeOut();

		} else {
			$('#mer-ajax-loading').fadeOut('fast');
		}
	
		// if no line item id
//		if ( line_item_id != 0 ) {
//			$('#button-blokka-'+line_item_id).hide();		
//		}	
		$('.button-blokka').hide();	
	}





	/**
	*		process event queue ajax response
	*/	
	function process_event_queue_ajax_response( line_item_id, response ) {
	
		if ( response.success ) {
		
			// if line_item_id exists then process changes to line items
			if ( line_item_id ) {			
				// line item qty
				$('#event-queue-update-qty-txt-'+line_item_id).val( response.new_qty );
				// line item total $
				var line_total = response.new_line_total;
				$('#event-queue-line-total-'+line_item_id).html( line_total.toFixed(2) );				
			}

			// then process individual cart totals
			// cart total items
			$('#event-queue-tbl-row-totals-qty-'+response.which_cart+' > .cart_total_items').html( response.new_total_items );
			// cart subtotal $$
			var sub_total = response.new_sub_total;
			$('#event-queue-tbl-row-totals-subtotal-'+response.which_cart+' > .sub_total').html( sub_total.toFixed(2) );	

			// then process grand totals for entire event queue
			// event queue grand total items
			$('#event-queue-grand-total-items-spn > .total').html( response.new_grand_total_qty );
			// event queue grand total $$$
			var grand_total_amount = response.new_grand_total_amount;
			$('#event-queue-grand-total-price-spn > .grand_total').html( grand_total_amount.toFixed(2) );
			
			// show success msg
			show_event_queue_ajax_success_msg( line_item_id, response.success )
			
		} else if ( response.error ) {		
			// show error msg
			show_event_queue_ajax_error_msg( line_item_id, response.error )
		}		
		
		//$('#cart_contents').html( DumpObjectIndented( response.cart_contents ) );		
	}

	
	
	
	
	/**
	*		add an attendee to event in the event queue via AJAX
	*/	
	$('.event-queue-add-attendee-btn').click(function() {
		// grab line item id
		var line_item_id = $(this).attr('rel');		
		// grab current attendee qty
		var qty = $('#event-queue-update-qty-txt-'+line_item_id).val();
	
		$.ajax({
					type: "POST",
			       	url:  mer.ajax_url,
					data: {
						"action" : "espresso_add_attendee_to_event_queue",
						"line_item" : line_item_id,
						"qty" : qty,
						"event_queue_ajax" : 1
					},
					dataType: "json",
					beforeSend: function() {
						do_before_event_queue_ajax( line_item_id );
					}, 
					success: function(response){   
						process_event_queue_ajax_response( line_item_id, response );
					},
					error: function(response) {
						var error_msg = 'An error occured! An additional attendee could not be added for this event!!!! Please refresh the page and try again.';
						show_event_queue_ajax_error_msg( line_item_id, error_msg );
					}			
			});	
			
		return false;
			
	});		
	
	
	
	
	
	/**
	*		remove an attendee to event in the event queue via AJAX
	*/	
	$('.event-queue-remove-attendee-btn').click(function() {
	
		// grab line item id
		var line_item_id = $(this).attr('rel');		
		// grab current attendee qty
		var qty = $('#event-queue-update-qty-txt-'+line_item_id).val();
	
		$.ajax({
					type: "POST",
			       url:  mer.ajax_url,
					data: {
						"action" : "espresso_remove_attendee_from_event_queue",
						"line_item" : line_item_id,
						"qty" : qty,
						"event_queue_ajax" : 1
					},
					dataType: "json",
					beforeSend: function() {					
						do_before_event_queue_ajax( line_item_id );
					}, 
					success: function(response){	
						process_event_queue_ajax_response( line_item_id, response );
					},
					error: function(response) {
						var error_msg = 'An error occured! An additional attendee could not be removed for this event. Please refresh the page and try again.';
						show_event_queue_ajax_error_msg( line_item_id, error_msg );
					}			
			});	
		
		return false;
			
	});		
	
	
	
	
	
	/**
	*		remove an attendee to event in the event queue via AJAX
	*/	
	$('.event-queue-remove-event-btn').click(function() {
	
		// grab line item id
		var line_item_id = $(this).attr('rel');		
	
		$.ajax({
					type: "POST",
			       url:  mer.ajax_url,
					data: {
						"action" : "espresso_remove_event_from_event_queue",
						"line_item" : line_item_id,
						"event_queue_ajax" : 1
					},
					dataType: "json",
					beforeSend: function() {
						do_before_event_queue_ajax( line_item_id );
					}, 
					success: function(response){
							
						$('#event-queue-tbl-row-3-'+line_item_id).fadeOut(500).delay(1000).html('');
						$('#event-queue-tbl-row-2-'+line_item_id).delay(500).fadeOut(500).delay(1000).html('');
						$('#event-queue-tbl-row-1-'+line_item_id).delay(1000).fadeOut(500).delay(1000).html('');
						
						if ( response.new_grand_total_qty == 0 ) {
							$('#event-queue-tbl-row-empty-msg').show();							
							$('.event-queue-update-btn').hide();
							$('.event-queue-register-btn').hide();
							$('.empty-cart-lnk').hide();
						}
						
						process_event_queue_ajax_response( false, response );	

					},
					error: function(response) {
						var error_msg = 'An error occured! An additional attendee could not be removed for this event. Please refresh the page and try again.';
						show_event_queue_ajax_error_msg( line_item_id, error_msg );
					}			
			});	
		
		return false;
		
	});	
	
	
	
	




	/**
	*		remove all events from the event queue
	*/	
	$('.empty-cart-lnk').click(function() {
	
		$.ajax({
					type: "POST",
			       url:  mer.ajax_url,
					data: {
						"action" : "espresso_empty_event_queue",
						"event_queue_ajax" : 1
					},
					dataType: "json",
					beforeSend: function() {
						do_before_event_queue_ajax( 0 );
					}, 
					success: function(response){   
					
						// check if event queue exists
						if ( $('.event-queue-tbl').length > 0 ){

							$('.event-queue-tbl-row-1').fadeOut('fast', function() {
						     	$(this).html('');
								$('.event-queue-tbl-row-2').fadeOut('fast', function() {
							     	$(this).html('');
									$('.event-queue-tbl-row-3').fadeOut('fast', function() {
								     	$(this).html('');
										$('.event-queue-tbl-row-totals').fadeOut('fast', function() {
									     	$(this).html('');
									    });
								    });
							    });
						    });

							// then process grand totals for entire event queue
							$('.event-queue-grand-total').fadeOut('fast', function() {
								// event queue grand total items
								$('#event-queue-grand-total-items-spn > .total').html( '0' );
								$('#event-queue-grand-total-price-spn > .grand_total').html( '0.00' );
								$('#event-queue-tbl-row-empty-msg').show( function() {
									$('.event-queue-grand-total').fadeIn('slow');
								 });
							 });
																										
							$('.event-queue-update-btn').fadeOut('fast');
							$('.event-queue-register-btn').fadeOut('fast');
							$('.empty-cart-lnk').fadeOut('fast');
								
						}
						
						// check if mini cart exists
						if ( $('.mini-cart-widget-tbl').length > 0 ){
							
							$('.mini-cart-widget-tbl-row-1').fadeOut('fast', function() {
						     	$(this).html('');
								$('.mini-cart-widget-tbl-row-2').fadeOut('fast', function() {
							     	$(this).html('');
									$('.mini-cart-widget-tbl-row-3').fadeOut('fast', function() {
								     	$(this).html('');
										$('.mini-cart-widget-tbl-row-4').fadeOut('fast', function() {
									     	$(this).html('');
											$('.mini-cart-widget-tbl-row-totals').fadeOut('fast', function() {
										     	$(this).html('');
										    });
									    });
								    });
							    });
						    });
							
							// then process grand totals for entire event queue
							$('.event-queue-grand-total').fadeOut('fast', function() {
								// event queue grand total items
								$('.event-queue-grand-total-items-spn').html( '<b>0 attendees </b>' );
								$('.event-queue-grand-total-price-spn').html( '<b>$0.00</b>' );
								$('#mini-cart-widget-tbl-row-empty-msg').show( function() {
									$('.event-queue-grand-total').fadeIn('slow');
								 });
							 });
						}						
												
						show_event_queue_ajax_success_msg( 0, response.success );
					},
					error: function(response) {
						
						if ( response.error ){ 
							var error_msg = response.error;						
						} else {					
							var error_msg = 'An error occured! The Event Queue could not be emptied!!! Please refresh the page and it try again and again.';
						}						
						show_event_queue_ajax_error_msg( 0, error_msg );
					}			
			});	
		
		return false;
		
	});	





	/**
	*		retrieve available spaces updates from the server on a timed interval
	*/
	function poll_available_spaces( event_id, httpTimeout ) {
	
		//alert( 'event_id : ' + event_id);	
		
		if ( httpTimeout == undefined ){
			httpTimeout = 30000;
		}

		if ( event_id ) {				
	    	$.ajax({
						type: "POST",
				       url:  mer.ajax_url,
						data: {
							"action" : "espresso_get_available_spaces",
							"event_id" : event_id,
							"event_queue_ajax" : 1
						},
						dataType: "json",
						success: function(response){   
							
								var availability = response.spaces + ' <span class="available-spaces-last-update-spn">( last update: ' + response.time + ' )</span>';
								$('#available-spaces-spn-' + response.id).fadeOut(500, function() { 
									$('#available-spaces-spn-' + response.id).html(availability).fadeIn(500);	
								});						
													
						}, 
						error: function(response) {
							
								if ( response.error == undefined || response.error == '' ){ 		
									response = new Object();
									response.error = 'Available space polling failed. Please refresh the page and it try again and again.';
								}						
								show_event_queue_ajax_error_msg( 0, response );
							
						},
//						complete: function(response) { 

//							setTimeout(function() {	
//								poll_available_spaces( event_id );
//							}, httpTimeout ); 
							
//						},
						timeout: httpTimeout 
				});										
		}	
	}

				



	/**
	*		loop thru events in event list and begin polling server re: available spaces
	*/	
	if ( $('#event-queue-poll-server').val() == 1 ) {
		
		setTimeout( function begin_polling_available_spaces(){	
			var httpTimeout = 30000 * $( '.available-spaces-spn' ).size();			
			
			$( '.available-spaces-spn' ).each(function(index) {				
				var event_id = $(this).attr('id');	
				event_id = event_id.replace('available-spaces-spn-', '');					
				setTimeout( function() {			
					poll_available_spaces( event_id, httpTimeout );
			    }, 30000 * index );			
			});
			
			setTimeout(function() {	
				begin_polling_available_spaces();
			}, httpTimeout ); 
			
		}, 30000 );	
	
	}







	
	

	function  showCartContents( cart_contents, line_item ) {

		var item = cart_contents['items'][line_item].line_total;		
		$('#cart_contents').html( DumpObjectIndented( cart_contents ) );
							
	}

	
	function DumpObjectIndented(obj, indent)
	{
	  var linebreak = '<br \/>'; // '\n'
	  var result = "";
	  if (indent == null) indent = "  ";
	
	  for (var property in obj)
	  {
	    var value = obj[property];
	    if (typeof value == 'string')
	      value = "'" + value + "'";
	    else if (typeof value == 'object')
	    {
	      if (value instanceof Array)
	      {
	        // Just let JS convert the Array to a string!
	        value = "[ " + value + " ]";
	      }
	      else
	      {
	        // Recursive dump
	        // (replace "  " by "\t" or something else if you prefer)
	        var od = DumpObjectIndented(value, indent + "  ");
	        // If you like { on the same line as the key
	        //value = "{\n" + od + '<br />' + indent + "}";
	        // If you prefer { and } to be aligned
	        value = linebreak + indent + "{" + linebreak + od + linebreak + indent + "}";
	      }
	    }
	    result += indent + "'" + property + "' : " + value + "," + linebreak;
	  }
	  return result.replace(/,\n$/, "");
	}



})(jQuery);

