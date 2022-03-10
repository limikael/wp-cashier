<table class="table table-hover table-striped mt-3 cashier-bs-table">
	<tr class="table-dark">
		<th>Currency</th>
		<th>Balance</th>
	</tr>
	<?php foreach ($currencies as $currency) { ?>
		<tr class="cashier-link-row">
			<td>
				<!--<img src="<?php echo esc_attr($currency["logo"]); ?>"/>-->
				<a class="text-reset text-decoration-none"
						href="<?php echo esc_attr($currency["url"]); ?>">
					<?php echo esc_html($currency["title"]); ?>
				</a>
			</td>
			<td>
				<?php echo esc_html($currency["balance"]); ?>
			</td>
		</tr>
	<?php } ?>
</table>