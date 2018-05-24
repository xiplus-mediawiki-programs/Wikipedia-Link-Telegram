<?php
require(__DIR__."/../config/config.default.php");
$commend = 'curl https://api.telegram.org/bot'.$cfg["token"].'/setWebhook -d "url='.urlencode($cfg["webhook"]).'"';
echo $commend;
system($commend);
