<table class="table table-hover table-striped cashier-bs-table"
		id="cashier-transaction-list">
	<tr class="table-dark">
		<th scope="col">Time</th>
		<th scope="col">To/From</th>
		<th scope="col">Amount</th>
		<th scope="col">Notice</th>
	</tr>
	<?php foreach ($transactions as $transaction) { ?>
		<tr class="<?php echo esc_attr($transaction["class"]);?> cashier-tx-closed-row"
				data-tx-id="<?php echo esc_attr($transaction["id"]); ?>">
			<td>
				<i class="bi <?php echo esc_attr($transaction["iconClass"]);?>"></i>
				<?php echo esc_html($transaction["stamp"]); ?>
			</td>
			<td>
				<?php echo esc_html($transaction["entity"]); ?>
			</td>
			<td>
				<?php echo esc_html($transaction["amount"]); ?>
			</td>
			<td>
				<?php echo esc_html($transaction["notice"]); ?>
			</td>
		</tr>
		<tr style="display: none"></tr>
		<tr class="<?php echo esc_attr($transaction["class"]);?> cashier-tx-open-row" style="display: none"
				data-tx-id="<?php echo esc_attr($transaction["id"]); ?>">
			<td colspan="4">
				<?php foreach ($transaction["meta"] as $metaLabel=>$meta) { ?>
					<div class="row">
						<b class="col-3">
							<?php echo esc_html($metaLabel); ?>:
						</b>
						<div class="col-8 multiline">
							<?php echo esc_html($meta); ?>
						</div>
					</div>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
</table>
