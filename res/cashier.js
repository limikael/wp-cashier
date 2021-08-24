(function($) {
	function updateConditionalVisibility() {
		$("[data-condition]").each(function() {
			let condition=JSON.parse($(this).attr("data-condition"));
			let visible=true;

			for (let i in condition) {
				console.log($(i).val());
				if ($(i).val()!=condition[i])
					visible=false;
			}

			if (visible)
				$(this).show();

			else
				$(this).hide();
		});
	}

	updateConditionalVisibility();

	let exprs=[];
	$("[data-condition]").each(function() {
		let condition=JSON.parse($(this).attr("data-condition"));
		for (let i in condition)
			if (exprs.indexOf(i)<0)
				exprs.push(i);
	});

	for (let expr of exprs)
		$(expr).change(updateConditionalVisibility);
})(jQuery);