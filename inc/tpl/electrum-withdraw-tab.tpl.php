<form method="post" autocomplete="off">
	<div class="form-group">
		<select class="form-select" id="transaction-method">
			<?php cashier\HtmlUtil::displaySelectOptions($methodOptions); ?>
		</select>
	</div>

	<div data-condition='{"#transaction-method":"lightning"}'>
		<div class="form-group">
			<label class="form-label mt-4">
				First generate an invoice. Then paste the invoice here.
			</label>
			<textarea name="cashier-request" class="form-control" rows="6"></textarea>
		</div>

		<input type="submit" value="Withdraw Using Lightning Network"
			class="btn btn-primary withdraw-submit mt-4"
			name="cashier-withdraw-lightning"/>
	</div>

	<div style="display: none"
			data-condition='{"#transaction-method":"onchain"}'>
		<div class="form-group">
			<label class="form-label mt-4">
				Bitcoin address to withdraw to
			</label>
			<input name="cashier-address" class="form-control" type="text"/>
		</div>

		<div class="form-group">
			<label class="form-label mt-4">
				Amount to withdraw in <b><?php echo esc_html($currencySymbol); ?></b>
			</label>
			<input name="cashier-amount" class="form-control" type="text"/>
		</div>

		<div class="form-group">
			<label class="form-label mt-4">
				Minig fee to use
			</label>
			<select class="form-select" name="cashier-fee">
				<?php cashier\HtmlUtil::displaySelectOptionsKeyLabel($feeOptions,$feeOptions[1]["key"]); ?>
			</select>
		</div>

		<input type="submit" value="Withdraw Using Bitcoin Transaction" 
			class="btn btn-primary withdraw-submit mt-4"
			name="cashier-withdraw-onchain"/>
	</div>
</form>
