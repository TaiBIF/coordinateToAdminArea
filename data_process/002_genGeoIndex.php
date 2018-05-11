<?php

require_once "../lib/polygon.php";

$db = Polygon::getDB();

$prefix = "";
if (!empty($argv[1])) {
	$prefix = $argv[1] . "_";
}
else {
	$prefix = "odtw_";
}

$sql = "select distinct rid, polygon_id as pid from ".$prefix."region_coordinates order by rid asc, polygon_id asc;";
$res = $db->query($sql);

while ($row = $res->fetch(PDO::FETCH_ASSOC)) {

	echo "進行到" . $row['rid'] . ", " . $row['pid'] . "\n";
	$plg = new Polygon($row['rid'], $row['pid']);
	$plg->loadRegionPolygon(null, null, $prefix);
	$plg->createBox($useDB=true, $prefix);
	$plg->smartFF();
	$grids = $plg->getGrids();
	foreach ($grids as $x => $ykeys) {
		foreach ($ykeys as $y => $in) {
			if ($in['pog']) {
				$sql = "insert into ".$prefix."polygon_over_grids set x='$x', y='$y', rid='".$row['rid']."', polygon_id='" . $row['pid'] . "';";
				$db->query($sql);
			}
		}
	}
	$plg->draw($row['rid'] . "_" . $row['pid'] . ".png");
	unset ($plg);
}



Polygon::close();



?>
