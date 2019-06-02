<!DOCTYPE html>
<html>
<head>
	<title>wikipedia_link</title>
	<style type="text/css">
		table{
			border-collapse: collapse;
		}
		th, td{
			vertical-align: top; border: 1px solid black;
		}
	</style>
</head>
<body>
<table>
	<tr>
		<th>chatid</th>
		<th>chattitle</th>
		<th>mode</th>
		<th>404</th>
		<th>cmdadminonly</th>
		<th>articlepath</th>
		<th>lastuse</th>
		<th>stoptime</th>
		<th>pagepreview</th>
		<th>leave</th>
		<th>noautoleave</th>
	</tr>
<?php
require_once __DIR__ . '/config/config.default.php';

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}setting` WHERE `chatid` < 0 ORDER BY `lastuse` DESC");
$sth->execute();
$list = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($list as $data) {
	echo "<tr>";
	echo "<td>" . $data['chatid'] . "</td>";
	echo "<td>" . htmlentities($data['chattitle']) . "</td>";
	echo "<td>" . $data['mode'];
	if (in_array($data['mode'], ["optin", "optout"])) {
		echo " (" . htmlentities($data['regex']) . ")";
	}
	echo "</td>";
	echo "<td>" . $data['404'] . "</td>";
	echo "<td>" . $data['cmdadminonly'] . "</td>";
	echo "<td>" . htmlentities($data['articlepath']) . "</td>";
	echo "<td style='white-space: nowrap;'>" . $data['lastuse'] . "</td>";
	echo "<td style='white-space: nowrap;'>" . $data['stoptime'] . "</td>";
	echo "<td>" . $data['pagepreview'] . "</td>";
	echo "<td>" . $data['leave'] . "</td>";
	echo "<td>" . $data['noautoleave'] . "</td>";
	echo "</tr>";
}
?>
</table>
</body>
</html>
