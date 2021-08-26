<div class="card border-primary mt-4 <?php echo esc_attr($class); ?>" style="position: relative;">
	<div class="card-header"><?php echo esc_html($title); ?></div>
	<div class="card-body">
		<p class="text-center mb-0">
			<a href="<?php echo esc_attr($url) ?>">
				<img class="cashier-qrcode <?php echo esc_attr($class); ?>"
					data-value="<?php echo esc_attr($data); ?>"/>
			</a>
		</p>
		<p class="text-center mb-0">
			<a class="badge rounded-pill bg-primary text-light text-decoration-none"
					href="#"
					data-copy-on-click="<?php echo esc_attr($data) ?>">
				Copy <?php echo esc_html($title); ?>
			</a>
			<a class="badge rounded-pill bg-primary text-light text-decoration-none"
					href="<?php echo esc_attr($url) ?>">
				Open <?php echo esc_html($title); ?>
			</a>
		</p>
	</div>
	<div class="card-body bg-dark text-light">
		<p class="text-center mb-0" style="font-family: monospace;">
			<?php echo esc_html($data); ?>
		</p>
	</div>
	<div class="cashier-qr-cover <?php echo esc_attr($class); ?>"
			data-qr-data="<?php echo esc_html($data); ?>" style="display: none">
	</div>
</div>
