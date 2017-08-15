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
		if (preg_match_all("/(\[\[([^\]])+?]]|{{([^}]+?)}})/", $text, $m)) {
			$response = [];
			foreach ($m[1] as $temp) {
				if (preg_match("/^\[\[([^|#]+)(?:#([^|]+))?.*?]]$/", $temp, $m2)) {
					$prefix = "";
					$page = trim($m2[1]);
					if (isset($m2[2])) {
						$section = "#".str_replace("%", ".", urlencode($m2[2]));
					} else {
						$section = "";
					}
				} else if (preg_match("/^{{ *#(exer|if|ifeq|ifexist|ifexpr|switch|time|language|babel|invoke) *:/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:解析器函数";
					$section = "#".$m2[1];
				} else if (preg_match("/^{{([^|]+)(?:|.+)?}}$/", $temp, $m2)) {
					$prefix = "Template:";
					$page = trim($m2[1]);
					$section = "";
				} else {
					continue;
				}
				$response[]= "https://zh.wikipedia.org/wiki/".$prefix.str_replace(" ", "_", $page).$section;
			}
			$response = implode("\n", $response);
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$user_id.'&text='.urlencode($response).'"';
			system($commend);
		}
	}
}
