<div id="cashier-transaction-list">
	<table class="table table-hover table-striped cashier-bs-table">
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

	<nav>
		<ul class="pagination justify-content-center">
			<li class="page-item">
				<a class="page-link" 
						aria-label="Previous"
						href="<?php echo esc_attr(add_query_arg("activityPage",$activityPage-1,$pageLink)); ?>">
					<span aria-hidden="true">&laquo;</span>
				</a>
			</li>
			<?php for ($i=0; $i<$numPages; $i++) { ?>
				<li class="page-item <?php echo (($i==$activityPage)?"active":"")?>">
					<a class="page-link"
							href="<?php echo esc_attr(add_query_arg("activityPage",$i,$pageLink)); ?>">
						<?php echo ($i+1); ?>
					</a>
				</li>
			<?php } ?>
			<li class="page-item">
				<a class="page-link"
						aria-label="Next"
						href="<?php echo esc_attr(add_query_arg("activityPage",$activityPage+1,$pageLink)); ?>">
					<span aria-hidden="true">&raquo;</span>
				</a>
			</li>
		</ul>
	</nav>
</div>

<input type="hidden" class="event-source-param" name="activityPage" value="<?php echo esc_attr($activityPage); ?>"/>