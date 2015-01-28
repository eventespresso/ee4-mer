<div id="event-list-reg-link-div-<?php echo $event_id; ?>" class="event-list-reg-link-dv">
	<h3><?php echo __('This event has already been added to the Event Queue.', 'event_espresso'); ?></h3>
	<br/>
	<div class="event-more-info-dv clear-float">	
		<a href="<?php echo $reg_href; ?>" class="event-list-view-eq-link-btn ui-button ui-button-big ui-priority-primary ui-state-default ui-corner-all add-hover-fx">
			<span class="ui-icon ui-icon-cart"></span>&nbsp;<?php _e( $sbmt_btn_text, 'event_espresso' ); ?>
		</a>
	</div>
	<br/>
</div>
						