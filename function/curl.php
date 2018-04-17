<?php
function cURL($url, $post=null, $header=array()){
	global $C;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if(is_array($post)){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}

	if (is_array($header)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	}

	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$res = curl_exec($ch);
	curl_close($ch);
	if ($res === false) {
		return false;
	}
	return $res;
}
