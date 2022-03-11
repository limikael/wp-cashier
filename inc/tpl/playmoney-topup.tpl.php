<form method="POST" action="" class="cashier-form">
	<p>
		You can top up your playmoney account to 
		<?php echo esc_html($replenishText); ?>!
	</p>

	<input type="submit" 
			class="btn btn-primary" 
			name="do_ply_topup"
			value="Top up to <?php echo esc_attr($replenishText); ?>"/>
</form>