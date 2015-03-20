
<div class="ui-widget-content  ui-corner-all">
	<ul id="mini-cart-<?php echo $cart_type;?>-ul" class="mini-cart-ul">
	<?php if ( $cart['has_items'] ) { ?>
	<?php foreach ( $cart['items'] as $item ) { ?>
		<li>
			<h6><?php echo $item['name'];?></h6>
			<ul class="mini-cart-line-item-ul">
				<li><?php echo __('Price', 'event_espresso');?> : <?php echo $currency_symbol . $item['price'];?></li>
				<li><?php echo __('Qty', 'event_espresso');?> : <?php echo $item['qty'];?></li>
				<li><?php echo __('Total', 'event_espresso');?> : <?php echo $currency_symbol . $item['line_total'];?></li>
			</ul>
		</li>
	<?php } ?>
		<?php if ( $nmbr_of_carts > 1 ) : ?>
			<li>
				<h5><?php echo __('Total', 'event_espresso') . ' ' . $cart['title'];?></h5>
				<?php
				printf(  _n( '%s item,  ', '%s items, ', $cart['total_items'], 'event_espresso' ), $cart['total_items'] );
				echo $currency_symbol . $cart['sub_total'];
				?>
			</li>
		<?php endif; ?>
	<?php } else { ?>
		<li><?php echo __( $cart['empty_msg'], 'event_espresso');?></li>
	<?php } ?>
	</ul>
</div>

<?php
/** @type string $before_widget */
/** @type string $after_widget */
/** @type string $before_title */
/** @type string $after_title */
/** @type string $title */
/** @type string $register_url */
/** @type string $event_queue */
/** @type string $view_event_queue_url */
/** @type string $events_list_url */
/** @type int $total_items */

echo $before_widget;
echo $before_title . $title . $after_title;
?>

<div id="mini-cart-widget-dv" class="small-text">

	<form id="mini-cart-qty-frm" action="<?php echo $register_url; ?>" method="POST">
		<input type="hidden" name="event_queue" value="update">

		<div id="mini-cart-wrap-dv" class="mini-cart-wrap-dv">

			<ul id="mini-cart-ul" class="mini-cart-ul" style="width:100%;">

				<tr id="mini-cart-tbl-row-hdr" class="mini-cart-tbl-row">
				<ul class="mini-cart-line-item-ul">
					<th class="mini-cart-tbl-price-th jst-rght" colspan="2"><?php echo __( 'Price', 'event_espresso' ); ?></th>
					<th class="mini-cart-tbl-qty-th jst-rght"><?php echo __( 'Qty', 'event_espresso' ); ?></th>
					<th class="mini-cart-tbl-total-th jst-rght"><?php echo __( 'Total', 'event_espresso' ); ?></th>
				</tr>
				</thead>

				<tbody>
				<?php echo $event_queue; ?>
				</tbody>

			</ul>
		</div>

		<div id="mini-cart-whats-next-buttons" class="mini-cart-whats-next-buttons">

			<?php if ( $total_items ) { ?>

				<span class="tiny-text">
				<a class="mini-cart-view-cart-lnk view-cart-lnk mini-cart-button button" href="<?php echo $view_event_queue_url; ?>">
					<span class="dashicons dashicons-cart"></span><?php echo __( 'view event queue', 'event_espresso' ); ?>
				</a>
			</span>
				<br/>
				<span class="tiny-text">
				<a class="mini-cart-register-button mini-cart-button button" href="<?php echo $register_url; ?>">
					<?php echo __( 'proceed to registration', 'event_espresso' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			</span>

			<?php } // if ( $total_items ) ?>

		</div>

	</form>

</div>

<?php echo $after_widget; ?>

