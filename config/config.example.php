<?php

$cfg['token'] = 'token';
$cfg['webhook'] = 'webhook_url';
$cfg['bot_username'] = 'WikipediaLinkBot';
$cfg['log'] = false;
$cfg['defaultdata'] = [
	"mode" => "start",
	"404" => false,
	"pagepreview" => true,
	"cmdadminonly" => false,
	"articlepath" => "https://zh.wikipedia.org/wiki/",
	"lastuse" => time()
];
$cfg['stoplimit'] = 86400 * 7;
$cfg['unusedlimit'] = 86400 * 7;
$cfg['noautoleavelist'] = [
	'chat_id'
];
