<?php

require_once __DIR__ . "/../lib/polygon.php";

$db = Polygon::getDB();

$prefix = '';
if (!empty($argv[1])) {
	$prefix = $argv[1] . '_';
}
else {
	$prefix = "odtw_";
}

$sql = "SELECT concat(cast(x as char),'_',cast(y as char)) as xy, count(distinct rid) as count_rid, rid from ".$prefix."polygon_over_grids group by xy order by count_rid desc;";

$res = $db->query($sql);

while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
	$frags = explode("_", $row['xy']);
	$x = $frags[0];
	$y = $frags[1];
	if ($row['count_rid'] > 1) {
		$sql = "select distinct rid from ".$prefix."polygon_over_grids where x='$x' and y='$y';";
		$rid_res = $db->query($sql);
		$rids = array();
		while ($rid_row = $rid_res->fetch(PDO::FETCH_ASSOC)) {
			$rids[] = $rid_row['rid'];
		}
		$rid_string = implode("|", $rids);
	}
	else {
		$rid_string = $row['rid'];
	}

	$sql = "replace into ".$prefix."mergedIndex set x='$x', y='$y', rid_string='".$rid_string."';";
	echo $sql . "\n";
	$db->query($sql);
}

Polygon::close();

?>
