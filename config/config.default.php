<?php

$C['token'] = '';
$C['webhook'] = 'https://example.com/WikipediaLinkBot/server.php';
$C["max_connections"] = 40;
$C['bot_username'] = 'WikipediaLinkBot';
$C['defaultdata'] = [
	'mode' => 'start',
	'404' => false,
	'pagepreview' => true,
	'cmdadminonly' => false,
	'articlepath' => 'https://zh.wikipedia.org/wiki/',
	'lastuse' => time()
];
$C['404limit'] = 5;
$C['stoplimit'] = 86400 * 7;
$C['unusedlimit'] = 86400 * 7;
$C['parseconfigretry'] = 3;
$C['noautoleavelist'] = [];
$C['notreplyhelplist'] = [];
$C['specialrule'] = function($chat_id, &$articlepath, &$page) {};

$C['module']['mediawikiurlencode'] = __DIR__.'/function/Mediawiki-urlencode/mediawikiurlencode.php';

$C['DBhost'] = 'localhost';
$C['DBname'] = 'WikipediaLinkBot';
$C['DBuser'] = 'root';
$C['DBpass'] = '';
$C['DBTBprefix'] = '';

@include(__DIR__.'/config.php');

$G['db'] = new PDO ('mysql:host='.$C['DBhost'].';dbname='.$C['DBname'].';charset=utf8mb4', $C['DBuser'], $C['DBpass']);
