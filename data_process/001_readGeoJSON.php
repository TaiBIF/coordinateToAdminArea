<?php

require_once "../lib/polygon.php";
$db = Polygon::getDB();

if (!empty($argv[1])) {
	$m = (int) $argv[1];
	if (!empty($m)) {
		ini_set("memory_limit", $m."M");
	}
}
else {
	ini_set("memory_limit", "1000M");
}

$c = file_get_contents("../data/tw-2014-05.json");
$jo = json_decode($c);

#var_dump($jo);

$nameReg = array();


function insertRegion($rid, $name, $code, $lang='zh-TW', $adm1, $adm2, $adm3) {
	$db = Polygon::getDB();
	$sql = "replace into `odtw_region_names` value ('$rid', '$name', '$code', '$lang', '$adm1', '$adm2', '$adm3');";
	echo $sql . "\n";
	$db->query($sql);
}

function insertRegionCoordinates($rid, $pid, $points) {
	$db = Polygon::getDB();
	$rcdata = array();
	foreach ($points as $weight => $p) {
		$lat = $p[1];
		$lng = $p[0];
		$rcdata[] = "('$rid', '$pid', '$lat', '$lng', '$weight')";
		if (count($rcdata) == 100) {
			$vstring = implode(",", $rcdata);
			$sql = "replace into `odtw_region_coordinates` value $vstring;";
			echo $sql . "\n";
			$db->query($sql);
			$rcdata = array();
		}
	}
	if (!empty($rcdata)) {
		$vstring = implode(",", $rcdata);
		$sql = "replace into `odtw_region_coordinates` value $vstring;";
		echo $sql . "\n";
		$db->query($sql);
		$rcdata = array();
	}
}

/*
 * 指定 rid 編碼起點
$custom_rid = 7698;
//*/


foreach($jo->features as $rid => $f) {
	$pr = $f->properties;


	/*
	 * 例外處理用
	if (!in_array($pr->COUNTY, array("臺東縣", "澎湖縣", "金門縣", "連江縣"))) {
		continue;
	}
	elseif (in_array($pr->COUNTY, array("臺東縣"))) {
		if (!in_array($pr->TOWN, array("蘭嶼鄉", "綠島鄉"))) {
			continue;
		}
	}
	//*/

	if (!empty($custom_rid)) {
		$rid = $custom_rid;
	}


	$name = $pr->COUNTY . $pr->TOWN . $pr->VILLAGE;
	$vcode = $pr->VILLCODE;
	$lang = "zh-TW";

	$adm1 = $pr->COUNTY;
	$adm2 = $pr->TOWN;
	$adm3 = $pr->VILLAGE;

	if (@$nameReg[$name] === true) {
		var_dump($name);
	}
	else {
		$nameReg[$name] = true;
	}
	insertRegion($rid, $name, $vcode, 'zh-TW', $adm1, $adm2, $adm3);

	$g = $f->geometry;
	if ($g->type == 'Polygon') {
		$pid = 0;
		foreach ($g->coordinates as $outin => $p) {
			if ($outin == 0) { // outside
				insertRegionCoordinates($rid, $pid, $p);
			}
			else { // inside
			}
		}
	}
	else if ($g->type == 'MultiPolygon') {
		foreach ($g->coordinates as $pid => $poutin) {
			foreach ($poutin as $outin => $p) {
				if ($outin == 0) {
					insertRegionCoordinates($rid, $pid, $p);
				}
				else {
				}
			}
		}
	}
	$custom_rid++;
}

Polygon::close();

?>
