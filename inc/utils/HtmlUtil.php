<?php

namespace custodial;

class HtmlUtil {
	static function renderSelectOptions($options, $current=NULL) {
		$res="";

		foreach ( $options as $key => $label ) {
			$res.=sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				( ( strval( $current ) === strval( $key ) ) ? 'selected' : '' ),
				esc_html( $label )
			);
		}

		return $res;
	}

	static function displaySelectOptions($options, $current=NULL) {
		echo HtmlUtil::renderSelectOptions($options,$current);
	}

	static function getReqVar( $name, $default = null ) {
		if ( ! isset( $_REQUEST[ $name ] ) ) {
			if ( null !== $default ) {
				return $default;
			}

			throw new Exception( 'Expected request variable: ' . $name );
		}

		return wp_unslash( $_REQUEST[ $name ] );
	}

	static function getCurrentUrl() {
		$protocol="http";

		if (array_key_exists("HTTP_X_FORWARDED_PROTO",$_SERVER) && 
				$_SERVER["HTTP_X_FORWARDED_PROTO"]=="https")
			$protocol="https";

		if (array_key_exists("HTTPS",$_SERVER) && 
				$_SERVER["HTTPS"]=="on")
			$protocol="https";

		return $protocol."://".$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'];
	}

	static function redirectAndContinue($url, $code=302) {
		wp_redirect($url,$code);

		ob_end_clean();

		//Tell the browser that the connection's closed
		header("Connection: close");

		//Ignore the user's abort (which we caused with the redirect).
		ignore_user_abort(true);
		//Extend time limit to 30 minutes
		set_time_limit(0);
		//Extend memory limit to 10MB
		//ini_set("memory_limit","10M");
		//Start output buffering again
		ob_start();

		//Tell the browser we're serious... there's really
		//nothing else to receive from this page.
		header("Content-Length: 0");

		//Send the output buffer and turn output buffering off.
		ob_end_flush();
		flush();
		//Close the session.
		session_write_close();
	}

	public static function renderTag($tag, $attr, $content="") {
		$s="<$tag";
		foreach ($attr as $k=>$v)
			$s.=" ".$k.'="'.esc_attr($v).'"';
		$s.=">$content</$tag>";

		return $s;
	}
}
