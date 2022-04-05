<?php

namespace cashier;

class UmController extends Singleton {
	protected function __construct() {
		add_filter('um_account_page_default_tabs_hook',array($this,'account_hook'), 100 );
		add_action('um_account_tab__balance',array($this,'um_account_tab__balance'));
		add_filter('um_account_content_hook_balance',array($this,'um_account_content_hook_balance'));
	}

	public function account_hook( $tabs ) {
		$tabs[50]['balance']['icon'] = 'um-faicon-money';
		$tabs[50]['balance']['title'] = 'Balance';
		$tabs[50]['balance']['custom'] = true;
		return $tabs;
	}

	public function um_account_tab__balance( $info ) {
		global $ultimatemember;
		extract( $info );

		$output = $ultimatemember->account->get_tab_output('balance');
		if ( $output ) { echo $output; }
	}

	function um_account_content_hook_balance( $output ){
		$user=wp_get_current_user();
		$currencies=Currency::findMany();
		$currencyViews=array();

		foreach ($currencies as $currency) {
			$account=Account::getUserAccount($user->ID,$currency->ID);
			$formatter=$currency->getFormatterForUser($user->ID);
			$currencyViews[]=array(
				"title"=>$currency->post_title,
				"logo"=>null,
				"url"=>get_post_permalink($currency->ID),
				"balance"=>$formatter->format($account->getBalance()),
			);
		}

		$t=new Template(__DIR__."/../tpl/um-account-list.tpl.php");
		return $t->render(array(
			"currencies"=>$currencyViews
		));
	}
}