<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	$user_id = $input['message']['chat']['id'];
	$data = file_get_contents("data/".$user_id.".json");
	if (isset($input['message']['text'])) {
		$text = $input['message']['text'];
		if (preg_match_all("/(\[\[.+?]]|{{.+?}})/", $text, $m)) {
			$response = [];
			foreach ($m[1] as $temp) {
				if ($temp[0] === "[") {
					$response[]= "https://zh.wikipedia.org/wiki/".urlencode(substr($temp, 2, -2));
				} else {
					$response[]= "https://zh.wikipedia.org/wiki/Template:".urlencode(substr($temp, 2, -2));
				}
			}
			$response = implode("\n", $response);
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$user_id.'&text='.$response.'"';
			system($commend);
		}
	}
}
