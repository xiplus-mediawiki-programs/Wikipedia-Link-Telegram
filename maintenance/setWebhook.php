<?php

require __DIR__ . "/../config/config.default.php";

$action = $argv[1] ?? "get";

switch ($action) {
	case 'set':
		$commend = 'curl https://api.telegram.org/bot' . $C["token"] . '/setWebhook -d "url=' . urlencode($C["webhook"]) . '&max_connections=' . $C["max_connections"] . '"';
		break;

	case 'delete':
		$commend = 'curl https://api.telegram.org/bot' . $C["token"] . '/deleteWebhook';
		break;

	case 'get':
		$commend = 'curl https://api.telegram.org/bot' . $C["token"] . '/getWebhookinfo';
		break;

	default:
		exit("Unknown action.");
}

echo $commend;
system($commend);
