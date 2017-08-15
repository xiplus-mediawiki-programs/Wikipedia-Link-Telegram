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
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:NUMBEROFPAGES|NUMBEROFARTICLES|NUMBEROFFILES|NUMBEROFEDITS|NUMBEROFVIEWS|NUMBEROFUSERS|NUMBEROFADMINS|NUMBEROFACTIVEUSERS)}}$/", $temp, $m2)) {
					$prefix = "";
					$page = "Help:魔术字";
					$section = "统计";
				} else if (preg_match("/^{{ *(?:subst:|safesubst:)?(?:PAGESINCATEGORY|PAGESINCAT|PAGESINCATEGORY|PAGESINCATEGORY|PAGESINCATEGORY|PAGESINCATEGORY|NUMBERINGROUP|NUMBERINGROUP|PAGESINNS|PAGESINNAMESPACE)(:.+?)?}}$/", $temp, $m2)) {
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
				$response[]= "https://zh.wikipedia.org/wiki/".$prefix.str_replace(" ", "_", $page).section($section);
			}
			$response = implode("\n", $response);
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$user_id.'&text='.urlencode($response).'"';
			system($commend);
		}
	}
}
