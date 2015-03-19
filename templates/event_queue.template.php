<div id="event-queue">

	<form id="event-queue-qty-frm" action="<?php echo $register_url; ?>" method="POST">
		<input type="hidden" name="event_queue" value="update">

		<h2 class=""><?php echo $event_queue_heading;?></h2>

		<div id="event-queue-wrap-dv" class="event-queue-wrap-dv">

			<table id="event-queue-tbl" class="event-queue-tbl" border="0" cellspacing="0" cellpadding="0" style="width:100%;">

				<thead>
					<tr id="event-queue-tbl-row-hdr" class="event-queue-tbl-row">
						<td colspan="2"><?php echo __('Details', 'event_espresso');?></td>
						<td class="jst-cntr"><?php echo __('Price', 'event_espresso');?></td>
						<td class="jst-cntr"><?php echo __('Quantity', 'event_espresso');?></td>
						<td class="jst-cntr"><?php echo __('Subtotal', 'event_espresso');?></td>
					</tr>
				</thead>

				<tbody>
					<?php echo $event_queue;?>
				</tbody>

			</table>
		</div>

		<div class="event-queue-grand-total">
		<?php	if ( $total_items ) { ?>
			<span class=" smaller-text">
				<a class="event-queue-empty-cart-lnk empty-cart-lnk event-queue-button button" href="<?php echo $empty_queue_url; ?>">
					<span class="dashicons dashicons-trash"></span><?php echo __( 'empty event queue',
						'event_espresso' ); ?>
				</a>
			</span>
			<span class=" smaller-text">
				<a class="event-queue-update-cart-lnk update-cart-lnk event-queue-button button" href="<?php echo $update_queue_url; ?>">
					<span class="dashicons dashicons-update"></span><?php echo __( 'update event queue', 'event_espresso' ); ?>
				</a>
			</span>
		<?php } // if ( $total_items )  ?>
		</div>



		<div id="event-queue-whats-next-buttons" class="event-queue-whats-next-buttons">

			<a class="event-queue-go-back-button event-queue-button button add-hover-fx" href="<?php echo $events_list_url;?>">
				<span class="dashicons dashicons-arrow-left-alt2"></span><?php echo __('Return to Events List', 'event_espresso'); ?>
			</a>

<?php if ( $total_items ) { ?>

			<a class="event-queue-register-button event-queue-button button add-hover-fx"  href="<?php echo $register_url;?>">
				<?php echo __('Proceed to Registration', 'event_espresso'); ?><span class="dashicons dashicons-arrow-right-alt2"></span>
			</a>

<?php } // if ( $total_items ) ?>

		</div>

	</form>

</div>
<br />
<br />