<form method="post" autocomplete="off">
	<div class="form-group">
		<select class="form-select" id="transaction-method">
			<?php cashier\HtmlUtil::displaySelectOptions($methodOptions); ?>
		</select>
	</div>

	<div data-condition='{"#transaction-method":"lightning"}'>
		<div class="form-group">
			<label class="form-label mt-4">
				Deposit amount in <b><?php echo esc_html($currencySymbol); ?></b>
			</label>

			<div style="display: flex; flex-direction: row">
				<div style="flex-grow: 1">
					<input name="amount" class="form-control" type="text"
						value="<?php echo esc_attr($amount); ?>"/>
				</div>
				<div style="flex-grow: 0; margin-left: 1em">
					<input type="submit" name="cashier-generate-lightning-invoice"
							value="Generate Invoice" class="btn btn-primary"/>
				</div>
			</div>
		</div>

		<?php if ($lightningQr) { ?>
			<?php echo $lightningQr; ?>
		<?php } ?>
	</div>

	<div style="display: none"
			data-condition='{"#transaction-method":"onchain"}'>
		<?php echo $onchainQr; ?>
	</div>
</form>

<?php if (isset($lightningInvoice)) { ?>
	<input type="hidden" class="event-source-param" name="lightningInvoice" value="<?php echo esc_attr($lightningInvoice); ?>"/>
<?php } ?>