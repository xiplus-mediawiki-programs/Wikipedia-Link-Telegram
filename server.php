<?php
require_once(__DIR__.'/config/config.php');
function section($section) {
	if ($section === "") {
		return "";
	} else {
		return "#".str_replace("%", ".", urlencode($section));
	}
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	$chat_id = $input['message']['chat']['id'];
	if ($cfg['log']) {
		file_put_contents(__DIR__."/data/".$chat_id.".log", $inputJSON);
	}
	$datafile = __DIR__."/data/".$chat_id."_setting.json";
	$data = @file_get_contents($datafile);
	if ($data === false) {
		$data = $cfg['defaultdata'];
	} else if (($data = json_decode($data, true)) === null) {
		$data = $cfg['defaultdata'];
	}
	$data += $cfg['defaultdata'];
	if (isset($input['message']['text'])) {
		$text = $input['message']['text'];
		if (strpos($text, "/") === 0) {
			$user_id = $input['message']['from']['id'];
			$res = file_get_contents('https://api.telegram.org/bot'.$cfg['token'].'/getChatMember?chat_id='.$chat_id.'&user_id='.$user_id);
			$res = json_decode($res, true);
			$isadmin = in_array($res["result"]["status"], ["creator", "administrator"]);
			$temp = explode(" ", $text);
			$cmd = $temp[0];
			unset($temp[0]);
			$text = implode(" ", $temp);
			$response = "";
			if ($chat_id < 0 && $cmd === "/cmdadminonly@WikipediaLinkBot") {
				if (!$isadmin) {
					$response = "只有群組管理員可以變更此設定";
				} else {
					$data["cmdadminonly"] = !$data["cmdadminonly"];
					if ($data["cmdadminonly"]) {
						$response = "現在起只有群組管理員可以變更回覆設定";
					} else {
						$response = "現在起所有人都可以變更回覆設定";
					}
				}
			} else if (($chat_id > 0 && $cmd === "/start") || $cmd === "/start@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					$data["mode"] = "start";
					$response = "已啟用連結回覆";
				}
			} else if (($chat_id > 0 && $cmd === "/stop") || $cmd === "/stop@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else if ($chat_id < 0) {
					if ($data["mode"] !== "stop") {
						$data["stoptime"] = time();
					}
					$data["mode"] = "stop";
					$response = "已停用連結回覆\n機器人將會在".($cfg['stoplimit']-(time()-$data["stoptime"]))."秒後自動退出";
				} else {
					$data["mode"] = "stop";
					$response = "已停用連結回覆";
				}
			} else if (($chat_id > 0 && $cmd === "/optin") || $cmd === "/optin@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					if ($text === "") {
						$response = "此指令需包含一個參數為正規表達式(php)，當訊息符合這個正規表達式才會回覆連結\n".
						"範例：/optin /pattern/";
					} else {
						if ($text[0] === "/" && substr($text, -1) === "/") {
							$text = substr($text, 1, -1);
						}
						$text = "/".$text."/";
						if (preg_match($text, null) === false) {
							$response = "設定 /optin 的正規表達式包含錯誤，設定沒有改變";
						} else {
							$data["mode"] = "optin";
							$data["regex"] = $text;
							$response = "已啟用部分連結回覆：".$text;
						}
					}
				}
			} else if (($chat_id > 0 && $cmd === "/optout") || $cmd === "/optout@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					if ($text === "") {
						$response = "此指令需包含一個參數為正規表達式(php)，當訊息符合這個正規表達式不會回覆連結\n".
							"範例：/optout /pattern/";
					} else {
						if ($text[0] === "/" && $text[-1] === "/") {
							$text = substr($text, 0, -1);
						}
						$text = "/".$text."/";
						if (preg_match($text, null) === false) {
							$response = "設定 /optout 的正規表達式包含錯誤，設定沒有改變";
						} else {
							$data["mode"] = "optout";
							$data["regex"] = $text;
							$response = "已停用部分連結回覆：".$text;
						}
					}
				}
			} else if (($chat_id > 0 && $cmd === "/status") || $cmd === "/status@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					$response = "現在連結回覆設定為".$data["mode"];
					if (in_array($data["mode"], ["optin", "optout"])) {
						$response .= "\n正規表達式：".$data["regex"]."";
					}
				}
			} else if (($chat_id > 0 && $cmd === "/404") || $cmd === "/404@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					$data["404"] = !$data["404"];
					if ($data["404"]) {
						$response = "已開啟頁面存在檢測（提醒：回應會稍慢）";
					} else {
						$response = "已關閉頁面存在檢測";
					}
				}
			} else if (($chat_id > 0 && $cmd === "/articlepath") || $cmd === "/articlepath@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更文章路徑\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
				} else {
					if ($text === "") {
						$response = "此指令需包含一個參數為文章路徑\n".
						"範例：/articlepath https://zh.wikipedia.org/wiki/";
					} else {
						$data["articlepath"] = $text;
						$response = "文章路徑已設定為 ".$text;
						$res = file_get_contents($text);
						if ($res === false) {
							$response .= "\n提醒：檢測到網頁可能不存在";
						}
					}
				}
			}
			if ($response !== "") {
				$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$chat_id.'&text='.urlencode($response).'"';
				system($commend);
			}
		} else if ($data["mode"] == "stop") {
			if (!isset($data["stoptime"])) {
				$data["stoptime"] = time();
			}
			if (time() - $data["stoptime"] > $cfg['stoplimit']) {
				$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$chat_id.'&text='.urlencode("因為停用回覆過久，機器人將自動退出以節省伺服器資源，欲再使用請重新加入機器人").'"';
				system($commend);
				$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/leaveChat -d "chat_id='.$chat_id.'"';
				system($commend);
			}
		} else if ($data["mode"] == "optin" && !preg_match($data["regex"], $text)) {
			
		} else if ($data["mode"] == "optout" && preg_match($data["regex"], $text)) {

		} else if (preg_match_all("/(\[\[([^\[\]])+?]]|{{([^{}]+?)}})/", $text, $m)) {
			$data["lastuse"] = time();
			$response = [];
			foreach ($m[1] as $temp) {
				$articlepath = $data["articlepath"];
				if (preg_match("/^\[\[([^|#]+)(?:#([^|]+))?.*?]]$/", $temp, $m2)) {
					$prefix = "";
					$page = trim($m2[1]);
					if (preg_match("/^:?moe:(.*)$/i", $page, $m3)) {
						$articlepath = "https://zh.moegirl.org/";
						$page = $m3[1];
					} else if (preg_match("/^:?kom?:(.*)$/i", $page, $m3)) {
						$articlepath = "https://wiki.komica.org/";
						$page = $m3[1];
					} else if (preg_match("/^:?unct?:(.*)$/i", $page, $m3)) {
						$articlepath = "http://uncyclopedia.tw/wiki/";
						$page = $m3[1];
					} else if (preg_match("/^:?uncc:(.*)$/i", $page, $m3)) {
						$articlepath = "http://cn.uncyclopedia.wikia.com/wiki/";
						$page = $m3[1];
					}
					$page = preg_replace("/^Special:AF/i", "Special:AbuseFilter", $page);
					$page = preg_replace("/:$/i", "%3A", $page);
					$page = preg_replace("/\?$/i", "%3F", $page);
					if (isset($m2[2])) {
						$section = $m2[2];
					} else {
						$section = "";
					}
				} else if (preg_match("/^{{ *#(exer|if|ifeq|ifexist|ifexpr|switch|time|language|babel|invoke) *:/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:解析器函数";
					$section = $m2[1];
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:CURRENTYEAR|CURRENTMONTH|CURRENTMONTHNAME|CURRENTMONTHNAMEGEN|CURRENTMONTHABBREV|CURRENTDAY|CURRENTDAY2|CURRENTDOW|CURRENTDAYNAME|CURRENTTIME|CURRENTHOUR|CURRENTWEEK|CURRENTTIMESTAMP|LOCALYEAR|LOCALMONTH|LOCALMONTHNAME|LOCALMONTHNAMEGEN|LOCALMONTHABBREV|LOCALDAY|LOCALDAY2|LOCALDOW|LOCALDAYNAME|LOCALTIME|LOCALHOUR|LOCALWEEK|LOCALTIMESTAMP) .*}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "日期与时间";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:SITENAME|SERVER|SERVERNAME|DIRMARK|DIRECTIONMARK|SCRIPTPATH|CURRENTVERSION|CONTENTLANGUAGE|CONTENTLANG) .*}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "技术元数据";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:REVISIONID|REVISIONDAY|REVISIONDAY2|REVISIONMONTH|REVISIONYEAR|REVISIONTIMESTAMP|REVISIONUSER|PAGESIZE|PROTECTIONLEVEL|DISPLAYTITLE|DEFAULTSORT|DEFAULTSORTKEY|DEFAULTCATEGORYSORT)(:.+?)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "技术元数据";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:NUMBEROFPAGES|NUMBEROFARTICLES|NUMBEROFFILES|NUMBEROFEDITS|NUMBEROFVIEWS|NUMBEROFUSERS|NUMBEROFADMINS|NUMBEROFACTIVEUSERS|PAGESINCATEGORY|PAGESINCAT|PAGESINCATEGORY|PAGESINCATEGORY|PAGESINCATEGORY|PAGESINCATEGORY|NUMBERINGROUP|NUMBERINGROUP|PAGESINNS|PAGESINNAMESPACE)([:|].+?)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "统计";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:FULLPAGENAME|PAGENAME|BASEPAGENAME|SUBPAGENAME|SUBJECTPAGENAME|TALKPAGENAME|FULLPAGENAMEE|PAGENAMEE|BASEPAGENAMEE|SUBPAGENAMEE|SUBJECTPAGENAMEE|TALKPAGENAMEE)(:.+?)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "页面标题";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:NAMESPACE|SUBJECTSPACE|ARTICLESPACE|TALKSPACE|NAMESPACEE|SUBJECTSPACEE|TALKSPACEE)(:.+?)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "命名空间";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:NAMESPACE|SUBJECTSPACE|ARTICLESPACE|TALKSPACE|NAMESPACEE|SUBJECTSPACEE|TALKSPACEE)(:.+?)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "命名空间";
				} else if (preg_match("/^{{ *! *}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "其他";
				} else if (preg_match("/^{{ *(localurl|fullurl|filepath|urlencode|anchorencode):.+}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "URL数据";
				} else if (preg_match("/^{{ *(localurl|fullurl|filepath|urlencode|anchorencode):.+}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "命名空间_2";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(lc|lcfirst|uc|ucfirst|formatnum|#dateformat|#formatdate|padleft|padright|plural):.+}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "格式";
				} else if (preg_match("/^{{ *(int|#special|#tag|gender|PAGEID|noexternallanglinks)(:.+)?}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "杂项";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?([^|]+)(?:|.+)?}}$/", $temp, $m2)) {
					$prefix = "Template:";
					$page = trim($m2[1]);
					$section = "";
				} else {
					continue;
				}
				$url = $articlepath.$prefix.str_replace(" ", "_", $page).section($section);
				$text = $url;
				if ($data["404"]) {
					$res = @file_get_contents($url);
					if ($res === false) {
						$text .= " (404)";
					}
				}
				$response[]= $text;
			}
			$response = implode("\n", $response);
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$chat_id.'&text='.urlencode($response).'"';
			system($commend);
		} else {
			if (time() - $data["lastuse"] > $cfg['unusedlimit']) {
				$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$chat_id.'&text='.urlencode("機器人發現已經".$cfg['unusedlimit']."秒沒有被使用了，因此將自動退出以節省伺服器資源，欲再使用請重新加入機器人").'"';
				system($commend);
				$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/leaveChat -d "chat_id='.$chat_id.'"';
				system($commend);
			}
		}
		file_put_contents($datafile, json_encode($data));
	} else if (isset($input['message']['new_chat_member'])) {
		if ($input['message']['new_chat_member']['username'] == $cfg['bot_username']) {
			$data["lastuse"] = time();
			$data["stoptime"] = time();
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$chat_id.'&text='.urlencode("感謝您使用本機器人，當您輸入[[頁面名]]或{{模板名}}時，機器人將會自動回覆連結").'"';
			system($commend);
			file_put_contents($datafile, json_encode($data));
		}
	}
}
