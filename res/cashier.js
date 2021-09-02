(function($) {
	let refreshRate=10000;
	//let refreshRate=0;

	function installCopyOnClick() {
		$("[data-copy-on-click]").click(function(e) {
			let data=$(this).attr("data-copy-on-click");
			navigator.clipboard.writeText(data);
			e.preventDefault();
		});
	}

	function installQrCode() {
		$(".cashier-qrcode").each(function(i, el) {
			let qr=new QRious({
				element: el,
				value: el.dataset.value,
				size: 250,
				level: "M"
			});
		});
	}

	function updateConditionalVisibility() {
		$("[data-condition]").each(function() {
			let condition=JSON.parse($(this).attr("data-condition"));
			let visible=true;

			for (let i in condition) {
				//console.log($(i).val());
				if ($(i).val()!=condition[i])
					visible=false;
			}

			if (visible)
				$(this).show();

			else
				$(this).hide();
		});
	}

	function installConditionalVisibility() {
		let exprs=[];
		$("[data-condition]").each(function() {
			let condition=JSON.parse($(this).attr("data-condition"));
			for (let i in condition)
				if (exprs.indexOf(i)<0)
					exprs.push(i);
		});

		for (let expr of exprs)
			$(expr).change(updateConditionalVisibility);

		updateConditionalVisibility();
	}

	function installTxUi(openId) {
		$(".cashier-tx-closed-row").click(function() {
			let id=$(this).attr("data-tx-id");
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			$(".cashier-tx-open-row[data-tx-id='"+id+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+id+"']").hide();
			return false;
		});

		$(".cashier-tx-open-row").click(function() {
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			return false;
		});

		if (openId) {
			$(".cashier-tx-open-row[data-tx-id='"+openId+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+openId+"']").hide();
		}
	}

	function sendBalanceUpdate(balance) {
		window.top.postMessage({
			type: "balanceUpdate",
			balance: parseInt(balance)
		});
	}

	function refreshBalances() {
		let data={
			action: "cashier-frontend",
			call: "getCurrencyTexts",
			currency: $("#cashier-account-balance").attr("data-currency")
		};

		if ($("#cashier-transaction-list").length)
			data.renderTransactionList=true;

		let lightningInvoice=$(".cashier-qrcode.lightning").attr("data-value");
		if (lightningInvoice)
			data.lightningInvoice=lightningInvoice;

		$(window).trigger("cashier-pre-refresh-account",data);

		$.ajax({
			type: "GET",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function(res) {
				console.log(res);
				let openId=$(".cashier-tx-open-row:visible").attr("data-tx-id");
				//console.log("got balance update, open: "+openId);

				for (let selector in res.text)
					$(selector).text(res.text[selector]);

				for (let selector in res.replaceWith)
					$(selector).replaceWith(res.replaceWith[selector]);

				sendBalanceUpdate(res.balance);

				installTxUi(openId);
				setTimeout(refreshBalances,refreshRate);
			},
			error: function(e) {
				console.log(e);
				setTimeout(refreshBalances,refreshRate);
			}
		});
	}

	if ($("#cashier-account-balance").length) {
		setTimeout(refreshBalances,refreshRate);
		sendBalanceUpdate($("#cashier-account-balance").attr("data-balance"));
	}

	installTxUi();
	installConditionalVisibility();
	installQrCode();
	installCopyOnClick();
})(jQuery);