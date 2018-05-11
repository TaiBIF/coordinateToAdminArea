<?php

error_reporting(0);

header('Access-Control-Allow-Origin: *');
require_once __DIR__ . "/../lib/polygon.php";

function v ($val, $default) {
	if (!empty($val)) {
		return $val;
	}
	return $default;
}
// 24.429580, 121.255428
//

$gcoords = v(@$argv[1], "0,0");

$latlng = v(trim(@$_REQUEST['latlng']), '0,0');
$frags = explode(",", $latlng);
$llx = trim($frags[1]);
$lly = trim($frags[0]);

$prefix = 'odtw_';
if (!empty($argv[2])) {
	$prefix = $argv[2] . '_';
}

if (!empty($_REQUEST['prefix'])) {
	$prefix = $_REQUEST['prefix']. '_';
}

$frags = explode(",", $gcoords);
$gx = trim($frags[1]);
$gy = trim($frags[0]);

$x = Polygon::floorDec(v(trim(@$_REQUEST['x']), $gx));
$y = Polygon::floorDec(v(trim(@$_REQUEST['y']), $gy));

$x = Polygon::floorDec(v($llx, $x));
$y = Polygon::floorDec(v($lly, $y));

function getGeoJSON ($rids) {
	$db = Polygon::getDB();
	$base['features'] = array();
	$base['type'] = 'FeatureCollection';
	$idx = 0;
	foreach ($rids as $rid => $name) {
		$sql = "select distinct * from odtw_region_coordinates where rid='$rid' order by polygon_id desc, weight asc";
//		echo $sql;
		$res = $db->query($sql);
		$base['features'][$idx]['type'] = 'Feature';
		$base['features'][$idx]['properties']['rid'] = $rid;
		$base['features'][$idx]['properties']['name'] = $name;
		$first = true;
		$multi = false;
		$n = 0;
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
			if ($first) {
				if ($row['polygon_id'] > 0) {
					$multi = true;
					$base['features'][$idx]['geometry']['type'] = 'MultiPolygon';
					$n = $row['polygon_id'];
				}
				else {
					$base['features'][$idx]['geometry']['type'] = 'Polygon';
				}
			}
			if ($multi) {
				$base['features'][$idx]['geometry']['coordinates'][$n-$row['polygon_id']][0][$row['weight']] = array ($row['longitude'], $row['latitude']);
			}
			else {
				$base['features'][$idx]['geometry']['coordinates'][0][$row['weight']] = array ($row['longitude'], $row['latitude']);
			}
			$first = false;
		}
		$idx++;
	}
	return json_encode($base);
}



// 九宮格
$xsets[] = $x;
$xsets[] = $x + 0.005;
$xsets[] = $x - 0.005;

$ysets[] = $y;
$ysets[] = $y + 0.005;
$ysets[] = $y - 0.005;

$xs = "('" . implode("','", $xsets) . "')";
$ys = "('" . implode("','", $ysets) . "')";
 
$db = Polygon::getDB();

if ($prefix == 'odtw_') {
	$sql = "select distinct rn.rid, name, adm1, adm2, adm3, lang from ".$prefix."polygon_over_grids pog join ".$prefix."region_names rn on pog.rid=rn.rid where x in $xs and y in $ys;";
}
else {
	$sql = "select distinct rn.rid, name, lang from ".$prefix."polygon_over_grids pog join ".$prefix."region_names rn on pog.rid=rn.rid where x in $xs and y in $ys;";
}

$res = $db->query($sql);
//var_dump($res->errorinfo());

$rids = array();
$ret = array();
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
	$ret[$row['rid']]['full'][$row['lang']] = $row['name'];
	$ret[$row['rid']]['adm1'][$row['lang']] = $row['adm1'];
	$ret[$row['rid']]['adm2'][$row['lang']] = $row['adm2'];
	$ret[$row['rid']]['adm3'][$row['lang']] = $row['adm3'];
	$rids[$row['rid']] = $row['name'];
}
#echo getGeoJSON($rids);


if (@$_REQUEST['debug'] == 'on') {
//	echo $sql . "\n";
	var_dump($ret);
}
else {
	$json = getGeoJSON($rids);
	$size = strlen($json);
	header('Content-type: application/json');
	header("Content-length: $size");
	echo $json;
}

file_put_contents(__DIR__ . "/log/rq_getgeojson.log", var_export($_REQUEST, true));
Polygon::close();

?>
