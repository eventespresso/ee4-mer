<div id="event-cart" class="small-text">

	<form id="event-cart-qty-frm" action="<?php echo $register_url; ?>" method="POST">
		<input type="hidden" name="event_cart" value="update">

		<h2 class=""><?php echo $event_cart_heading;?></h2>

		<div id="event-cart-wrap-dv" class="event-cart-wrap-dv">

			<table id="event-cart-tbl" class="event-cart-tbl" border="0" cellspacing="0" cellpadding="0" style="width:100%;">

				<thead>
					<tr id="event-cart-tbl-row-hdr" class="event-cart-tbl-row">
						<td class="jst-left"><?php echo __('Details', 'event_espresso');?></td>
						<td class="jst-cntr"><?php echo __( 'Price', 'event_espresso' ); ?></td>
						<td class="jst-cntr"><?php echo __( 'Quantity', 'event_espresso' ); ?></td>
						<td class="jst-cntr"><?php echo __('Total', 'event_espresso');?></td>
					</tr>
				</thead>

				<tbody>
					<?php echo $event_cart;?>
				</tbody>

			</table>
            <?php do_action('AHEE__event_cart_template__after_event_cart_table'); ?>
        </div>

		<div class="event-cart-grand-total">
		<?php	if ( $total_items ) { ?>
			<span class=" smaller-text">
				<a class="event-cart-empty-cart-lnk empty-cart-lnk event-cart-button button <?php echo $btn_class; ?>" href="<?php echo $empty_cart_url; ?>">
					<span class="dashicons dashicons-trash"></span><?php echo apply_filters( 'FHEE__EED_Multi_Event_Registration__empty_event_cart_btn_txt', sprintf( __( 'Empty %s', 'event_espresso' ), $event_cart_name ) ); ?>
				</a>
			</span>
			<span class=" smaller-text">
				<a class="event-cart-update-cart-lnk update-cart-lnk event-cart-button button <?php echo $btn_class; ?>" href="<?php echo $update_cart_url; ?>">
					<span class="dashicons dashicons-update"></span><?php echo apply_filters( 'FHEE__EED_Multi_Event_Registration__update_event_cart_btn_txt', sprintf( __( 'Update %s', 'event_espresso' ), $event_cart_name ) ); ?>
				</a>
			</span>
		<?php } // if ( $total_items )  ?>
		</div>



		<div id="event-cart-whats-next-buttons" class="event-cart-whats-next-buttons">

			<a class="event-cart-go-back-button event-cart-button button <?php echo $btn_class; ?>" href="<?php echo $events_list_url;?>">
				<span class="dashicons dashicons-arrow-left-alt2"></span><?php echo apply_filters( 	'FHEE__EED_Multi_Event_Registration__return_to_events_list_btn_txt',  __( 'Return to Events List', 'event_espresso' ) ); ?>
			</a>

<?php if ( $total_items ) { ?>

			<a class="event-cart-register-button event-cart-button button <?php echo $btn_class; ?>"  href="<?php echo $register_url;?>">
				<?php echo apply_filters( 'FHEE__EED_Multi_Event_Registration__proceed_to_registration_btn_txt', __( 'Proceed to Registration', 'event_espresso' ) ); ?><span class="dashicons dashicons-arrow-right-alt2"></span>
			</a>

<?php } // if ( $total_items ) ?>

		</div>

	</form>

</div>
<br />
<br />