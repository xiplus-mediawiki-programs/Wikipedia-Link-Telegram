<?php
require(__DIR__."/../config/config.default.php");
$commend = 'curl https://api.telegram.org/bot'.$C["token"].'/setWebhook -d "url='.urlencode($C["webhook"]).'&max_connections='.$C["max_connections"].'"';
echo $commend;
system($commend);
