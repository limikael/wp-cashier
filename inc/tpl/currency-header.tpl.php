<div class="row">
	<div class="col-3 col-md-2"><b>Balance:</b></div>
	<div class="col-4">
		<span class="cashier-account-balance"></span>
	</div>
</div>
<div class="row mb-3">
	<div class="col-3 col-md-2"><b>Reserved:</b></div>
	<div class="col-2">
		<span class="cashier-account-reserved"></span>
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

<input type="hidden" class="event-source-param" name="currency" value="<?php echo esc_attr($currencyId); ?>"/>