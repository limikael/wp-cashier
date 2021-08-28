<div class="row">
	<div class="col-3 col-md-2"><b>Balance:</b></div>
	<div class="col-4">
		<span id="cashier-account-balance"
				data-currency="<?php echo esc_attr($currencyId); ?>"
				data-balance="<?php echo esc_attr($balance); ?>">
			<?php echo esc_html($balanceText); ?>
		</span>
	</div>
</div>
<div class="row mb-3">
	<div class="col-3 col-md-2"><b>Reserved:</b></div>
	<div class="col-2">
		<span id="cashier-account-reserved">
			<?php echo esc_html($reservedText); ?>
		</span>
	</div>
</div>

<?php echo $notices; ?>

<ul class="nav nav-tabs mb-3">
	<?php foreach ($tabs as $tabId=>$tab) { ?>
		<?php
			$class="";
			if ($tabId==$currentTab)
				$class="active";
		?>
		<li class="nav-item">
			<a class="nav-link <?php echo esc_attr($class); ?>"
					href="<?php echo esc_attr($tab["link"]); ?>">
				<?php echo esc_html($tab["title"]); ?>
			</a>
		</li>
	<?php } ?>
</ul>
