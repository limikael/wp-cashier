<form method="POST">
	<div class="form-group">
		<label for="exampleSelect1" class="form-label">Show amounts in denomination</label>
		<select class="form-select" name="denomination">
			<?php cashier\HtmlUtil::displaySelectOptions($denominationOptions,$denomination); ?>
		</select>
	</div>

	<input type="submit" class="btn btn-primary mt-4"
		name="save-currency-settings"
		value="Save Currency Settings"/>
</form>