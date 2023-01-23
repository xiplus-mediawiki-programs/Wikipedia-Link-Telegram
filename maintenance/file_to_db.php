<?php

require_once __DIR__ . '/../config/config.default.php';

$list = [];
if ($dir = opendir(__DIR__ . '/../data')) {
	while (($filename = readdir($dir)) !== false) {
		if (preg_match("/^(.+)_setting\.json$/", $filename, $m)) {
			$chatid = $m[1];
			$datastr = file_get_contents(__DIR__ . '/../data/' . $filename);
			if ($data !== false) {
				$data = json_decode($datastr, true);
				if ($data !== false) {
					$list[$chatid] = $data;
				} else {
					echo $chatid . ' parse json fail.<br>';
				}
			} else {
				echo $chatid . ' reading fail.<br>';
			}
		}
	}
	closedir($dir);
}
foreach ($list as $chatid => $data) {
	echo "$chatid\t";
	echo $data['mode'];
	if (in_array($data['mode'], ['optin', 'optout'])) {
		echo ' (' . $data['regex'] . ')';
	}
	echo "\t";
	echo $data['404'] . "\t";
	echo $data['cmdadminonly'] . "\t";
	echo $data['articlepath'] . "\t";
	echo(isset($data['lastuse']) ? date('Y-m-d H:i:s', $data['lastuse']) : 0) . "\t";
	echo(isset($data['stoptime']) ? date('Y-m-d H:i:s', $data['stoptime']) : 0) . "\t";
	echo $data['pagepreview'] ?? 0 . "\t";
	echo $data['leave'] ?? 0 . "\t";
	echo "\n";

	$sth = $G['db']->prepare("INSERT INTO `{$C['DBTBprefix']}setting` (`chatid`, `mode`, `regex`, `404`, `cmdadminonly`, `articlepath`, `lastuse`, `stoptime`, `pagepreview`, `leave`) VALUES (:chatid, :mode, :regex, :404, :cmdadminonly, :articlepath, :lastuse, :stoptime, :pagepreview, :leave)");
	$sth->bindValue(':chatid', $chatid);
	$sth->bindValue(':mode', $data['mode']);
	$sth->bindValue(':regex', (isset($data['regex']) ? $data['regex'] : null));
	$sth->bindValue(':404', $data['404']);
	$sth->bindValue(':cmdadminonly', $data['cmdadminonly']);
	$sth->bindValue(':articlepath', $data['articlepath']);
	$sth->bindValue(':lastuse', (isset($data['lastuse']) ? date('Y-m-d H:i:s', $data['lastuse']) : null));
	$sth->bindValue(':stoptime', (isset($data['stoptime']) ? date('Y-m-d H:i:s', $data['stoptime']) : null));
	$sth->bindValue(':pagepreview', $data['pagepreview'] ?? 0);
	$sth->bindValue(':leave', $data['leave'] ?? 0);
	$res = $sth->execute();
	if ($res === false) {
		var_dump($sth->errorInfo());
	}
}
