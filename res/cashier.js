(function($) {
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

	function openTxRow(openId) {
		if (openId) {
			$(".cashier-tx-open-row[data-tx-id='"+openId+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+openId+"']").hide();
		}
	}

	function buildUrl(base, key, value) {
		let sep = (base.indexOf('?') > -1) ? '&' : '?';
		return base + sep + key + '=' + encodeURIComponent(value);
	}

	function installEventSource() {
		if (window.cashierEventSource) {
			window.cashierEventSource.close();
			window.cashierEventSource=null;
		}

		if (!$(".event-source-param").length)
			return;

		url=buildUrl(window.ajaxurl,"action","events");
		$(".event-source-param").each(function() {
			url=buildUrl(url,$(this).attr("name"),$(this).val());
		});

		//console.log("event source: "+url);

		window.cashierEventSource=new EventSource(url);
		window.cashierEventSource.onmessage=function(messageEvent) {
			//console.log("sse event...");

			let openId=$(".cashier-tx-open-row:visible").attr("data-tx-id");
			processJqueryReplacements(JSON.parse(messageEvent.data));
			openTxRow(openId);
		}
	}

	function processJqueryReplacements(replacements) {
		for (let selector in replacements)
			$(selector).html(replacements[selector]);
	}

	function installCashierComponents() {
		installConditionalVisibility();
		installQrCode();
		installCopyOnClick();
		installEventSource();
	}

	function installTableUi() {
		$(document).on("click",".cashier-link-row",function(e) {
			if ($(e.target).prop("tagName")!="A") {
				$(this).find("a")[0].click();
			}
		});

		$(document).on("click",".cashier-tx-closed-row",function() {
			let id=$(this).attr("data-tx-id");
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			$(".cashier-tx-open-row[data-tx-id='"+id+"']").show();
			$(".cashier-tx-closed-row[data-tx-id='"+id+"']").hide();
			return false;
		});

		$(document).on("click",".cashier-tx-open-row",function() {
			$(".cashier-tx-open-row").hide();
			$(".cashier-tx-closed-row").show();
			return false;
		});
	}

	window.addEventListener("reload",installCashierComponents);
	installCashierComponents();
	installTableUi();
})(jQuery);