<form method="post" autocomplete="off">
	<div class="form-group">
		<select class="form-select" id="transaction-method">
			<?php cashier\HtmlUtil::displaySelectOptions($methodOptions); ?>
		</select>
	</div>

	<div style="display: none"
			data-condition='{"#transaction-method":"lightning"}'>
		<div class="form-group">
			<label class="form-label mt-4">
				Deposit amount in <b><?php echo esc_html($currencySymbol); ?></b>
			</label>

			<div style="display: flex; flex-direction: row">
				<div style="flex-grow: 1">
					<input name="cashier-amount" class="form-control" type="text"
						value="<?php echo esc_attr($amount); ?>"/>
				</div>
				<div style="flex-grow: 0; margin-left: 1em">
					<input type="submit" name="cashier-generate-lightning-invoice"
							value="Generate Invoice" class="btn btn-primary"/>
				</div>
			</div>
		</div>

		<?php if ($invoice) { ?>
			<script>
				window.lightningInvoice="<?php echo esc_js($invoice); ?>";
			</script>
			<label class="mt-4">Pay to invoice:</label>
			<a href="<?php echo esc_attr($invoiceUrl); ?>">
				<div class="cashier-qrcode-container">
					<img class="tphbtc-qrcode"
						data-value="<?php echo esc_attr($invoice); ?>"/>
					<div class="tphbtc-qrcode-cover"></div>
				</div>
			</a>
			<div class="cashier-code">
				<?php echo esc_html($invoice); ?>
			</div>
		<?php } ?>
	</div>

	<div style="display: none"
			data-condition='{"#transaction-method":"onchain"}'>
		<label class="mt-4">Address:</label>
		<a href="<?php echo esc_attr($addressUrl); ?>">
			<div class="card border-secondary">
				<p class="text-center mb-0">
					<img class="cashier-qrcode"
						data-value="<?php echo esc_attr($address); ?>"/>
				</p>
			</div>
		</a>
		<div class="card border-secondary">
			<div class="card-body">
				<div class="card-text" style="font-family: monospace;">
					<?php echo esc_html($address); ?>
				</div>
			</div>
		</div>
	</div>
</form>