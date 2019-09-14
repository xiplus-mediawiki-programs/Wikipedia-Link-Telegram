<?php
set_time_limit(60);
$starttime = microtime(true);
require_once __DIR__ . '/config/config.default.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/function/curl.php';
require_once $C['module']['mediawikiurlencode'];

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	if (!isset($input['message'])) {
		exit();
	}
	if ($input['message']['date'] < time() - 600) {
		exit();
	}
	$chat_id = $input['message']['chat']['id'];
	$nowtimestr = date('Y-m-d H:i:s');

	$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}setting` WHERE `chatid` = :chatid");
	$sth->bindValue(":chatid", $chat_id);
	$sth->execute();
	$data = $sth->fetch(PDO::FETCH_ASSOC);
	if ($data === false) {
		$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}setting` (`chatid`) VALUES (:chatid)");
		$sth->bindValue(":chatid", $chat_id);
		$sth->execute();

		$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}setting` WHERE `chatid` = :chatid");
		$sth->bindValue(":chatid", $chat_id);
		$sth->execute();
		$data = $sth->fetch(PDO::FETCH_ASSOC);
		if ($data === false) {
			WriteLog($chat_id . "\n" . $datastr, "config_parse_fail");
			exit;
		}
	}

	if (isset($input['message']['text']) || isset($input['message']['caption'])) {
		$sourcetext = $input["message"]["from"]["first_name"] . "(" . $input["message"]["from"]["id"] . ")";
		if (isset($input["message"]["chat"]["title"])) {
			$sourcetext .= " @ " . $input["message"]["chat"]["title"] . "(" . $input["message"]["chat"]["id"] . ")";
			$data['chattitle'] = $input["message"]["chat"]["title"];
		} else {
			$data['chattitle'] = $input["message"]["from"]["first_name"];
			if (isset($input["message"]["from"]["last_name"])) {
				$data['chattitle'] .= ' ' . $input["message"]["from"]["last_name"];
			}
		}

		if (isset($input['message']['text'])) {
			$text = $input['message']['text'];
		} else if (isset($input['message']['caption'])) {
			$text = $input['message']['caption'];
		} else {
			$text = "";
		}
		if (strpos($text, "/") === 0) {
			$user_id = $input['message']['from']['id'];
			$res = file_get_contents('https://api.telegram.org/bot' . $C['token'] . '/getChatMember?chat_id=' . $chat_id . '&user_id=' . $user_id);
			$res = json_decode($res, true);
			$isadmin = in_array($res["result"]["status"], ["creator", "administrator"]);
			$arg = str_replace("\n", " ", $text);
			$arg = preg_replace("/^([^ ]+) +/", "$1 ", $arg);
			$arg = explode(" ", $arg);
			$cmd = $arg[0];
			unset($arg[0]);
			$arg = implode(" ", $arg);
			$response = "";
			if ($chat_id < 0 && $cmd === "/cmdadminonly@WikipediaLinkBot") {
				if (!$isadmin) {
					$response = "只有群組管理員可以變更此設定";
					WriteLog($sourcetext . "\n" . $text, "cmdadminonly_denied");
				} else {
					$data["cmdadminonly"] = !$data["cmdadminonly"];
					if ($data["cmdadminonly"]) {
						$response = "現在起只有群組管理員可以變更回覆設定";
						WriteLog($sourcetext . "\n" . $text, "cmdadminonly_on");
					} else {
						$response = "現在起所有人都可以變更回覆設定";
						WriteLog($sourcetext . "\n" . $text, "cmdadminonly_pff");
					}
				}
			} else if (($chat_id > 0 && $cmd === "/start") || $cmd === "/start@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "start_denied");
				} else {
					$data["mode"] = "start";
					$response = "已啟用連結回覆";
					WriteLog($sourcetext . "\n" . $text, "start");
				}
			} else if (($chat_id > 0 && $cmd === "/stop") || $cmd === "/stop@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "stop_denied");
				} else if ($chat_id < 0) {
					if ($data["mode"] !== "stop") {
						$data["stoptime"] = $nowtimestr;
					}
					$data["mode"] = "stop";
					$response = "已停用連結回覆";
					if (!$data['noautoleave']) {
						$response .= "\n機器人將會在" . ($C['stoplimit'] - (time() - $data["stoptime"])) . "秒後自動退出";
					}
					WriteLog($sourcetext . "\n" . $text, "stop");
				} else {
					$data["mode"] = "stop";
					$response = "已停用連結回覆";
					WriteLog($sourcetext . "\n" . $text, "stop");
				}
			} else if (($chat_id > 0 && $cmd === "/optin") || $cmd === "/optin@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "optin_denied");
				} else {
					if ($arg === "") {
						$response = "此指令需包含一個參數為正規表達式(php)，當訊息符合這個正規表達式才會回覆連結\n" .
							"範例：/optin /pattern/";
						WriteLog($sourcetext . "\n" . $text, "optin_no_arg");
					} else {
						if ($arg[0] === "/" && substr($arg, -1) === "/") {
							$arg = substr($arg, 1, -1);
						}
						$arg = "/" . $arg . "/";
						if (preg_match($arg, null) === false) {
							$response = "設定 /optin 的正規表達式包含錯誤，設定沒有改變";
							WriteLog($sourcetext . "\n" . $text, "optin_wrong_arg");
						} else {
							$data["mode"] = "optin";
							$data["regex"] = $arg;
							$response = "已啟用部分連結回覆：" . $arg;
							WriteLog($sourcetext . "\n" . $text, "optin");
						}
					}
				}
			} else if (($chat_id > 0 && $cmd === "/optout") || $cmd === "/optout@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "optout_denied");
				} else {
					if ($arg === "") {
						$response = "此指令需包含一個參數為正規表達式(php)，當訊息符合這個正規表達式不會回覆連結\n" .
							"範例：/optout /pattern/";
						WriteLog($sourcetext . "\n" . $text, "optout_no_arg");
					} else {
						if ($arg[0] === "/" && substr($arg, -1) === "/") {
							$arg = substr($arg, 1, -1);
						}
						$arg = "/" . $arg . "/";
						if (preg_match($arg, null) === false) {
							$response = "設定 /optout 的正規表達式包含錯誤，設定沒有改變";
							WriteLog($sourcetext . "\n" . $text, "optout_wrong_arg");
						} else {
							$data["mode"] = "optout";
							$data["regex"] = $arg;
							$response = "已停用部分連結回覆：" . $arg;
							WriteLog($sourcetext . "\n" . $text, "optout");
						}
					}
				}
			} else if (($chat_id > 0 && $cmd === "/settings") || $cmd === "/settings@WikipediaLinkBot") {
				$response = "chat id為" . $chat_id . ($data['noautoleave'] ? "（不退出白名單）" : "");
				$response .= "\n連結回覆設定為" . $data["mode"];
				if (in_array($data["mode"], ["optin", "optout"])) {
					$response .= "\n正規表達式：" . $data["regex"] . "";
				}
				$response .= "\n頁面存在檢測為" . ($data["404"] ? "開啟" : "關閉");
				$response .= "\n連結預覽為" . ($data["pagepreview"] ? "開啟" : "關閉");
				$response .= "\n文章路徑為 " . $data["articlepath"];
				if ($chat_id < 0) {
					$response .= "\n" . ($data["cmdadminonly"] ? "只有管理員可以變更回覆設定" : "所有人都可以變更回覆設定");
				}
				$response .= "\n使用 /help 查看更改設定的指令";
				WriteLog($sourcetext . "\n" . $text, "settings");
			} else if (($chat_id > 0 && $cmd === "/404") || $cmd === "/404@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "404_denied");
				} else {
					$data["404"] = !$data["404"];
					if ($data["404"]) {
						$response = "已開啟頁面存在檢測（提醒：回應會稍慢）";
						WriteLog($sourcetext . "\n" . $text, "404_on");
					} else {
						$response = "已關閉頁面存在檢測";
						WriteLog($sourcetext . "\n" . $text, "404_off");
					}
				}
			} else if (($chat_id > 0 && $cmd === "/pagepreview") || $cmd === "/pagepreview@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更回覆設定\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "pagepreview_denied");
				} else {
					$data["pagepreview"] = !$data["pagepreview"];
					if ($data["pagepreview"]) {
						$response = "已開啟連結預覽（提醒：僅有一個連結時會預覽）";
						WriteLog($sourcetext . "\n" . $text, "pagepreview_on");
					} else {
						$response = "已關閉連結預覽";
						WriteLog($sourcetext . "\n" . $text, "pagepreview_off");
					}
				}
			} else if (($chat_id > 0 && $cmd === "/articlepath") || $cmd === "/articlepath@WikipediaLinkBot") {
				if ($chat_id < 0 && $data["cmdadminonly"] && !$isadmin) {
					$response = "只有群組管理員可以變更文章路徑\n群組管理員可使用指令 /cmdadminonly@WikipediaLinkBot 取消此限制";
					WriteLog($sourcetext . "\n" . $text, "articlepath_denied");
				} else {
					if ($arg === "") {
						$response = "此指令需包含一個參數為文章路徑\n" .
							"範例：/articlepath https://zh.wikipedia.org/wiki/";
						WriteLog($sourcetext . "\n" . $text, "articlepath_no_arg");
					} else {
						$data["articlepath"] = $arg;
						$response = "文章路徑已設定為 " . $arg;
						$res = file_get_contents($arg);
						if ($res === false) {
							$response .= "\n提醒：檢測到網頁可能不存在";
						}
						WriteLog($sourcetext . "\n" . $text, "articlepath");
					}
				}
			} else if ($chat_id < 0 && $cmd === "/noautoleave@WikipediaLinkBot") {
				if (!in_array($user_id, $C['operator'])) {
					$response = "只有機器人操作者可以變更此設定";
					WriteLog($sourcetext . "\n" . $text, "noautoleave_denied");
				} else {
					$data["noautoleave"] = !$data["noautoleave"];
					if ($data["noautoleave"]) {
						$response = "已將此群加入到不退出白名單";
						WriteLog($sourcetext . "\n" . $text, "noautoleave_on");
					} else {
						$response = "已將此群從不退出白名單移除";
						WriteLog($sourcetext . "\n" . $text, "noautoleave_off");
					}
				}
			} else if (($chat_id > 0 && $cmd === "/help") || ($cmd === "/help@WikipediaLinkBot" && !in_array($chat_id, $C['notreplyhelplist']))) {
				$response = "/settings 檢視連結回覆設定\n" .
					"/start 啟用所有連結回覆\n" .
					"/stop 停用所有連結回覆\n" .
					"/optin 啟用部分連結回覆(參數設定，使用正規表達式)\n" .
					"/optout 停用部分連結回覆(參數設定，使用正規表達式)\n" .
					"/404 檢測頁面存在(開啟時回應會較慢)\n" .
					"/pagepreview 連結預覽(僅有一個連結時會預覽)\n" .
					"/articlepath 變更文章路徑\n";
				if ($chat_id < 0) {
					$response .= "/cmdadminonly 調整是否只有管理員才可變更設定\n";
				}
				WriteLog($sourcetext . "\n" . $text, "help");
			} else if (($chat_id > 0 && $cmd === "/editcount") || $cmd === "/editcount@WikipediaLinkBot") {
				WriteLog($sourcetext . "\n" . $text, "editcount");

				$arg = trim($arg);
				$arg = explode("@", $arg);
				if (count($arg) !== 2 || trim($arg[0]) === "" || trim($arg[1]) === "") {
					$response = "格式錯誤，必須為 Username@Wiki";
				} else {
					$arg[0] = ucfirst($arg[0]);
					$url = "https://xtools.wmflabs.org/ec/" . $arg[1] . "/" . urlencode($arg[0]) . "?uselang=en";
					$res = file_get_contents($url);
					if ($res === false) {
						$response = "連線發生錯誤";
					} else {
						$res = str_replace("\n", "", $res);
						$res = html_entity_decode($res);
						$response = '<a href="' . mediawikiurlencode("https://meta.wikimedia.org/wiki/", "Special:CentralAuth/" . $arg[0]) . '">' . $arg[0] . '</a>' .
							"@" . $arg[1] . '（<a href="' . $url . '">檢查</a>）';
						$get = false;
						file_put_contents(__DIR__ . "/data/" . $arg[0] . ".html", $res);
						if (preg_match("/User groups.*?<\/td>\s*<td>\s*(.*?)\s*<\/td>/", $res, $m)) {
							$response .= "\n權限：" . preg_replace("/\s{2,}/", " ", trim($m[1]));
							$get = true;
						}
						if (preg_match("/Global user groups<\/td>\s*<td>\s*(.*?)\s*<\/td>/", $res, $m)) {
							$response .= "\n全域權限：" . preg_replace("/\s{2,}/", " ", trim($m[1]));
							$get = true;
						}
						if (preg_match('/(?:<strong>Total edits.*?<\/td>|Total<\/td>)\s*<td.*?>(.*?)<\/td>/', $res, $m)) {
							$response .= "\n總計：" . trim(strip_tags($m[1]));
							$get = true;
						}
						if (preg_match('/Live edits<\/td>\s*<td.*?>(.*?)<\/td>/', $res, $m)) {
							$response .= "\n可見編輯：" . preg_replace("/\s{2,}/", " ", trim(strip_tags($m[1])));
							$get = true;
						}
						if (preg_match("/<td>Deleted edits<\/td>\s*<td>(.*?)<\/td>/", $res, $m)) {
							$response .= "\n已刪編輯：" . preg_replace("/\s{2,}/", " ", trim(strip_tags($m[1])));
							$get = true;
						}
						if (preg_match("/Edits in the past 24 hours<\/td><td>(.+?)<\/td>/", $res, $m)) {
							$response .= "\n24小時內編輯：" . trim($m[1]);
							$get = true;
						}
						if (preg_match("/Edits in the past 7 days<\/td><td>(.+?)<\/td>/", $res, $m)) {
							$response .= "\n7天內編輯：" . trim($m[1]);
							$get = true;
						}
						if (!$get) {
							$response = '用戶名或Wiki不存在（<a href="' . mediawikiurlencode("https://meta.wikimedia.org/wiki/", "Special:CentralAuth/" . $arg[0]) . '">檢查</a>）';
						}
					}
				}
			} else if (($chat_id > 0 && $cmd === "/whatis") || $cmd === "/whatis@WikipediaLinkBot") {
				$articlepath = $data["articlepath"];
				$arg = trim($arg);
				if ($arg === "") {
					$response = "請提供要搜尋的詞";
				} else {
					$nslist = ["Special", "", "User", "Project", "File", "Mediawiki", "Template", "Help", "Category", "Portal", "Draft", "Module"];
					$titles = [];
					foreach ($nslist as $ns) {
						$titles[] = ($ns . ":" . $arg);
					}
					$api = "https://zh.wikipedia.org/w/api.php?action=query&format=json&prop=info&titles=" . urlencode(implode("|", $titles));
					$res = file_get_contents($api);
					if ($res === false) {
						$response = "抓取發生錯誤，請稍後再試";
					} else {
						$response = [];
						$res = json_decode($res, true);
						foreach ($res["query"]["pages"] as $page) {
							if (!isset($page["missing"])) {
								$response[] = mediawikiurlencode("https://zh.wikipedia.org/wiki/", $page["title"]);
							}
						}
						if (count($response) > 0) {
							$response = implode("\n", $response);
						} else {
							$response = "沒有名為「" . $arg . "」的頁面";
						}
					}
				}
				WriteLog($sourcetext . "\n" . $text, "whatis");
			}
			if ($response !== "") {
				$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/sendMessage -d "chat_id=' . $chat_id . '&reply_to_message_id=' . $input['message']['message_id'] . '&disable_web_page_preview=1&parse_mode=HTML&text=' . urlencode($response) . '"';
				system($commend);

				$spendtime = (microtime(true) - $starttime);
				WriteLog($sourcetext . "\n" . $response . "\n" . $spendtime, "response");
			}
		} else if ($data["mode"] == "stop") {
			if (time() - strtotime($data["stoptime"]) > $C['stoplimit'] && !$data['noautoleave']) {
				$data["leave"] = true;
				$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/sendMessage -d "chat_id=' . $chat_id . '&text=' . urlencode("因為停用回覆過久，機器人將自動退出以節省伺服器資源，欲再使用請重新加入機器人") . '"';
				system($commend);
				$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/leaveChat -d "chat_id=' . $chat_id . '"';
				system($commend);

				WriteLog($sourcetext . "\n" . $text, "leave");
			}
		} else if ($data["mode"] == "optin" && !preg_match($data["regex"], $text)) {

		} else if ($data["mode"] == "optout" && preg_match($data["regex"], $text)) {

		} else if (preg_match_all("/(\[\[([^\[\]])+?]]|{{([^{}]+?)}})/", $text, $m)) {
			WriteLog($sourcetext . "\n" . $text, "request");

			$data["lastuse"] = $nowtimestr;
			$urls = [];
			$urlsinfo = [];
			foreach ($m[1] as $temp) {
				$articlepath = $data["articlepath"];
				if (preg_match("/^\[\[([^|#]+)(?:#([^|]+))?.*?]]$/", $temp, $m2)) {
					$prefix = "";
					$page = trim($m2[1]);
					if (isset($m2[2])) {
						$section = $m2[2];
					} else {
						$section = "";
					}

					$C['specialrule']($chat_id, $articlepath, $page);

					$page = preg_replace("/:$/i", "%3A", $page);
					$page = preg_replace("/!$/i", "%21", $page);
					$page = preg_replace("/\?$/i", "%3F", $page);
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
				$url = mediawikiurlencode($articlepath, $prefix . $page, $section);
				$urls[] = $url;
				$urlsinfo[$url] = ['page' => $page, 'articlepath' => $articlepath];
			}
			$responsetext = implode("\n", $urls);
			if ($data["404"]) {
				$responsetext .= "\n正在檢查404...";
			}
			$post = [
				'chat_id' => $chat_id,
				'reply_to_message_id' => $input['message']['message_id'],
				'parse_mode' => 'HTML',
				'text' => $responsetext,
			];
			if (count($urls) > 1 || !$data["pagepreview"]) {
				$post['disable_web_page_preview'] = '1';
			}
			$res = cURL('https://api.telegram.org/bot' . $C['token'] . '/sendMessage', $post);

			$spendtime = (microtime(true) - $starttime);
			WriteLog($sourcetext . "\n" . $responsetext . "\n" . $spendtime . "s", "response");

			$res = json_decode($res, true);
			if ($res["ok"] && $data["404"]) {
				$message_id = $res["result"]["message_id"];
				$response = [];
				foreach ($urls as $cnt => $url) {
					$text = $url;
					if ($cnt < $C['404limit']) {
						$res = @file_get_contents($url);
						if ($res === false) {
							$text .= " （404，<a href='" . $urlsinfo[$url]['articlepath'] . "Special:Search?search=" . urlencode($urlsinfo[$url]['page']) . "&fulltext=1'>搜尋</a>）";
						}
					}
					$response[] = $text;
				}
				$responsetext = implode("\n", $response);
				if (count($urls) > $C['404limit']) {
					$responsetext .= "\n只檢查前" . $C['404limit'] . "個頁面是否存在";
				}
				$post = [
					'chat_id' => $chat_id,
					'message_id' => $message_id,
					'parse_mode' => 'HTML',
					'text' => $responsetext,
				];
				if (count($urls) > 1 || !$data["pagepreview"]) {
					$post['disable_web_page_preview'] = '1';
				}
				$res = cURL('https://api.telegram.org/bot' . $C['token'] . '/editMessageText', $post);

				$spendtime = (microtime(true) - $starttime);
				WriteLog($sourcetext . "\n" . $responsetext . "\n" . $spendtime . "s", "response_update");
			}
		} else {
			if ($chat_id < 0 && time() - strtotime($data["lastuse"]) > $C['unusedlimit'] && !$data['noautoleave']) {
				$data["leave"] = true;
				$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/sendMessage -d "chat_id=' . $chat_id . '&text=' . urlencode("機器人發現已經" . $C['unusedlimit'] . "秒沒有被使用了，因此將自動退出以節省伺服器資源，欲再使用請重新加入機器人") . '"';
				system($commend);
				$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/leaveChat -d "chat_id=' . $chat_id . '"';
				system($commend);

				WriteLog($sourcetext . "\n" . $text, "leave");
			}
		}
	} else if (isset($input['message']['new_chat_member'])) {
		if (isset($input['message']['new_chat_member']['username'])
			&& $input['message']['new_chat_member']['username'] == $C['bot_username']) {
			$data["lastuse"] = $nowtimestr;
			$data["stoptime"] = null;
			$data["leave"] = false;
			$commend = 'curl https://api.telegram.org/bot' . $C['token'] . '/sendMessage -d "chat_id=' . $chat_id . '&text=' . urlencode("感謝您使用本機器人，當您輸入[[頁面名]]或{{模板名}}時，機器人將會自動回覆連結") . '"';
			system($commend);

			WriteLog($sourcetext, "add");
		}
	}

	if ($data["mode"] !== "stop") {
		$data["stoptime"] = null;
	}

	$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}setting` SET
		`chattitle` = :chattitle,
		`mode` = :mode,
		`regex` = :regex,
		`404` = :404,
		`cmdadminonly` = :cmdadminonly,
		`articlepath` = :articlepath,
		`lastuse` = :lastuse,
		`stoptime` = :stoptime,
		`pagepreview` = :pagepreview,
		`lastuse` = :lastuse,
		`leave` = :leave,
		`noautoleave` = :noautoleave
		WHERE `chatid` = :chatid");
	$sth->bindValue(":chattitle", $data['chattitle']);
	$sth->bindValue(":mode", $data['mode']);
	$sth->bindValue(":regex", $data['regex']);
	$sth->bindValue(":404", $data['404']);
	$sth->bindValue(":cmdadminonly", $data['cmdadminonly']);
	$sth->bindValue(":articlepath", $data['articlepath']);
	$sth->bindValue(":lastuse", $data['lastuse']);
	$sth->bindValue(":stoptime", $data['stoptime']);
	$sth->bindValue(":pagepreview", $data['pagepreview']);
	$sth->bindValue(":leave", $data['leave']);
	$sth->bindValue(":noautoleave", $data['noautoleave']);
	$sth->bindValue(":chatid", $chat_id);
	$sth->execute();
}
