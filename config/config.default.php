<?php

$C['token'] = '';
$C['webhook'] = 'https://example.com/WikipediaLinkBot/server.php';
$C['max_connections'] = 40;
$C['bot_username'] = 'WikipediaLinkBot';
$C['404limit'] = 5;
$C['stoplimit'] = 86400 * 7;
$C['unusedlimit'] = 86400 * 7;
$C['parseconfigretry'] = 3;
$C['notreplyhelplist'] = [];
$C['operator'] = [];
$C['specialrule'] = function($chat_id, &$articlepath, &$page, &$no404) {};

$C['logkeep'] = 86400 * 30;

$C['module']['mediawikiurlencode'] = __DIR__.'/function/Mediawiki-urlencode/mediawikiurlencode.php';

$C['DBhost'] = 'localhost';
$C['DBname'] = 'WikipediaLinkBot';
$C['DBuser'] = 'root';
$C['DBpass'] = '';
$C['DBTBprefix'] = '';

@include(__DIR__.'/config.php');

$G['db'] = new PDO ('mysql:host='.$C['DBhost'].';dbname='.$C['DBname'].';charset=utf8mb4', $C['DBuser'], $C['DBpass']);
