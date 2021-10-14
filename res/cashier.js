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

	function installEventSource() {
		window.setEventSourceParams=function(param, value) {
			if (!window.eventSourceParams)
				window.eventSourceParams={};

			window.eventSourceParams[param]=value;
		}

		$(document).ready(function() {
			$(".event-source-param").each(function() {
				window.setEventSourceParams($(this).attr("name"),$(this).val());
			});

			if (!window.eventSourceParams ||
					!Object.keys(window.eventSourceParams).length)
				return;

			var buildUrl = function(base, key, value) {
				var sep = (base.indexOf('?') > -1) ? '&' : '?';
				return base + sep + key + '=' + encodeURIComponent(value);
			};

			let url=window.ajaxurl;
			url=buildUrl(url,"action","events");

			for (let paramName in window.eventSourceParams)
				url=buildUrl(url,paramName,window.eventSourceParams[paramName]);

			let es=new EventSource(url);
			es.onmessage=function(messageEvent) {
				console.log("sse event...");

				let data=JSON.parse(messageEvent.data);
				window.postMessage(data);
			}
		});
	}

	function processJqueryReplacements(res) {
		if (res.text)
			for (let selector in res.text)
				$(selector).text(res.text[selector]);

		if (res.replaceWith)
			for (let selector in res.replaceWith)
				$(selector).replaceWith(res.replaceWith[selector]);
	}

	function installEventSourceListener() {
		window.addEventListener("message",function(ev) {
			let openId=$(".cashier-tx-open-row:visible").attr("data-tx-id");
			processJqueryReplacements(ev.data);
			installTxUi(openId);
		});
	}

	installConditionalVisibility();
	installQrCode();
	installCopyOnClick();
	installTxUi();
	installEventSource();
	installEventSourceListener();
})(jQuery);