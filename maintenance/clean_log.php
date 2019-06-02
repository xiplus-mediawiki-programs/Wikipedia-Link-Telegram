<?php
require_once __DIR__ . '/../config/config.default.php';

$limit = date('Y-m-d H:i:s', time() - $C['logkeep']);

$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}log` WHERE `time` < :limit");
$sth->bindValue(":limit", $limit);
$sth->execute();
