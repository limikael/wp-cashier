<?php

namespace cashier;

class JsonRpcClient {
	public function __construct($options) {
		$this->options=$options;
	}

	public function call($method, ...$params) {
		if (isset($params[0]) && is_array($params[0]))
			$params=$params[0];

		foreach ($params as $param)
			if (is_array($param))
				throw new \Exception("Can't use array as param, sorry");

		$postData=array(
			"jsonrpc"=>"2.0",
			"method"=>$method,
			"params"=>$params,
			"id"=>"dummy"
		);

		$encodedPostData=json_encode($postData);

		$curl=curl_init($this->options["url"]);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$encodedPostData);
		//curl_setopt($curl,CURLOPT_TIMEOUT,600);

		set_time_limit(0);

		if (isset($this->options["userpwd"]))
			curl_setopt($curl,CURLOPT_USERPWD,$this->options["userpwd"]);

		$response=curl_exec($curl);
		$responseCode=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);

		if ($response===FALSE)
			throw new \Exception("Unable to reach JSON-RPC endpoint: ".$responseCode);

		$decodedResponse=json_decode($response,TRUE);
		if (!$decodedResponse)
			throw new \Exception("JSON-RPC Failed: Unable to parse response: ".$response);

		if (isset($decodedResponse["error"]) && $decodedResponse["error"]) {
			error_log("json rpc fail: ".$response);
			throw new \Exception("JSON-RPC Failed: ".$decodedResponse["error"]["message"]);
		}

		if ($responseCode!=200)
			throw new \Exception("JSON-RPC Failed: ".$responseCode);

		return $decodedResponse["result"];
	}
}