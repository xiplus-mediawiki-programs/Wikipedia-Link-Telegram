<?php

function WriteLog($log="", $type="")
{
	global $C, $G;
	$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}log` (`type`, `log`) VALUES (:type, :log)");
	$sth->bindValue(":type", $type);
	$sth->bindValue(":log", $log);
	$sth->execute();
}
function DeleteLog($timelimit=2592000)
{
	global $C, $G;
	$timelimit = date("Y-m-d H:i:s", time()-$timelimit);
	$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}log` WHERE `time` < :timelimit");
	$sth->bindValue(":timelimit", $timelimit);
	$sth->execute();
}
