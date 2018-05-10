<?php

require_once "../lib/polygon.php";

$db = Polygon::getDB();

$prefix = '';
if (!empty($argv[1])) {
	$prefix = $argv[1] . '_';
}
else {
	$prefix = "odtw_";
}

$sql = "SELECT concat(cast(x as char),'_',cast(y as char)) as xy, count(distinct rid) as count_rid, rid from ".$prefix."polygon_over_grids where rid >= 7698
	group by xy order by count_rid desc;";

$res = mysql_query($sql);

while ($row = mysql_fetch_assoc($res)) {
	$frags = explode("_", $row['xy']);
	$x = $frags[0];
	$y = $frags[1];
	if ($row['count_rid'] > 1) {
		$sql = "select distinct rid from ".$prefix."polygon_over_grids where x='$x' and y='$y';";
		$rid_res = mysql_query($sql);
		$rids = array();
		while ($rid_row = mysql_fetch_assoc($rid_res)) {
			$rids[] = $rid_row['rid'];
		}
		$rid_string = implode("|", $rids);
	}
	else {
		$rid_string = $row['rid'];
	}

	$sql = "replace into ".$prefix."mergedIndex set x='$x', y='$y', rid_string='".$rid_string."';";
	echo $sql . "\n";
	mysql_query($sql);
}

Polygon::close();

?>
