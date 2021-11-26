<?php
function cURL($url, $post = null, $header = array()) {
	global $C;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if (is_array($post)) {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}

	if (is_array($header)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	}

	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$res = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpcode === 404) {
		return false;
	}
	return $res;
}
