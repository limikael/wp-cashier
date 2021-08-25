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

	let refreshRate=10000;

	function installTxUi(openId) {
		$(".cashier-tx-closed-row a").click(function(el) {
			let id=el.target.dataset.txId;
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			$(".cashier-tx-open-row[data-tx-id='"+id+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+id+"']").hide();
			return false;
		});

		$(".cashier-tx-open-row a").click(function(el) {
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			return false;
		});

		if (openId) {
			$(".cashier-tx-open-row[data-tx-id='"+openId+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+openId+"']").hide();
		}
	}

	/*function refreshBalances() {
		let data={
			action: "tonopah-frontend",
			call: "getCurrencyTexts",
			currency: tonopahCurrency
		};

		if ($("#tonopah-transaction-list").length)
			data.renderTransactionList=true;

		$(window).trigger("tonopah-pre-refresh-account",data);

		$.ajax({
			type: "GET",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function(res) {
				let openId=$(".tonopah-tx-open-row:visible").attr("data-tx-id");
				console.log("got balance update, open: "+openId);

				for (let selector in res.text)
					$(selector).text(res.text[selector]);

				for (let selector in res.replaceWith)
					$(selector).replaceWith(res.replaceWith[selector]);

				installTxUi(openId);
				setTimeout(refreshBalances,refreshRate);
			},
			error: function(e) {
				console.log(e);
				setTimeout(refreshBalances,refreshRate);
			}
		});
	}

	if ($("#tonopah-account-balance").length)
		setTimeout(refreshBalances,refreshRate);*/

	installTxUi();
})(jQuery);