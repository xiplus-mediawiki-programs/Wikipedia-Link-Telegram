<?php
require(__DIR__."/../config/config.default.php");
$commend = 'curl https://api.telegram.org/bot'.$C["token"].'/setWebhook -d "url='.urlencode($C["webhook"]).'"';
echo $commend;
system($commend);
